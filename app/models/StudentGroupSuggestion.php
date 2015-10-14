<?php

use Platypus\Helpers\PlatypusBool;

class StudentGroupSuggestion extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'student_group_suggestions';


	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array('created_at', 'updated_at');
	}
	
	public static $automaticGarbageCollectionRelations = array('members');
	
	// define the relationships to other models
	public static $relationsData = array(
			'assignment'  => array(self::BELONGS_TO, 'Assignment'),
			'members'  => array(self::BELONGS_TO_MANY, 'SubjectMember', 'table' => 'student_group_suggestion_memberships', 'foreignKey' => 'student_group_suggestion_id', 'otherKey' => 'subject_member_id', 'pivotKeys' => array('accepted'), 'timestamps' => true),
			'creator'  => array(self::BELONGS_TO, 'User', 'foreignKey' => 'creator_id'),
	);
	
	// get the users directly without retrieving the SubjectMember model in between.
	public function users() {
		return User::whereHas('subjectMembers', function($q) {
			$q->whereHas('studentGroupSuggestions', function ($q) {
				$q->where('id', $this->id);
			});
		});
	}
	
	public function count() {
		return $this->members()->count();
	}
	
	public function isEmpty() {
		return !$this->members()->exists();
	}
	
	public function isMember(User $user) {
		return $this->members()->where('user_id', $user->id)->exists();
	}
	
	public function hasAccepted(User $user) {
		return $this->members()->wherePivot('accepted', true)->where('user_id', $user->id)->exists();
	}
	
	public function isAcceptedByAll() {
		return !$this->members()->wherePivot('accepted', false)->exists();
	}
	
	public function acceptSuggestionSave(User $user) {
		$subjectMember = $this->assignment->subject->getMembership($user);
		$this->members()->updateExistingPivot($subjectMember->id, array('accepted' => true));
	}
	
	static function findSuggestions(Assignment $assignment, User $user) {
		return self::where('assignment_id', $assignment->id)
			->whereHas('members', function($query) use($user) {
				$query->where('user_id', $user->id);
			})->get();
	}
	
	// test if the given users would end up with a valid group suggestion
	static function canMakeGroupSuggestion(Assignment $assignment, array $users, &$errors = null) {
		
		$errors=array();
		$result = true;
		
		$seenIds = array();
		
		foreach ( $users as $key => $user ) {
			
			if (array_search($user->id, $seenIds) !== false) {
				$errors[$key] = 'This user is a duplicate.';
				$result = false;
				continue;
			}
			$seenIds[] = $user->id;
			
			if (! $assignment->isActiveStudent($user)) {
				$errors[$key] = 'User is not part of this class.';
				$result = false;
				continue;
			}
		
			if (!is_null(StudentGroup::findGroup($assignment, $user))) {
				$errors[$key] = 'User is already part of a group for this assignment.';
				$result = false;
				continue;
			}
			
			if ($assignment->group_work_mode == AssignmentGroupWorkMode::groupsolutions) {
				// only one solution per group is allowed. To make it simple, we don't allow group suggestions if the user has already submitted something.
				if (!$userSubmissions = $assignment->getUserAnswersSubmitted($user)->isEmpty()) {
					$errors[$key] = 'You cannot form a group with someone who has submitted answers already.';
					$result = false;
					continue;
				}
			}	

		}	
		
		return $result;		
	}
	
	// make the given group suggestion
	static function makeGroupSuggestionSave(Assignment $assignment, array $users, User $creator) {
		
		$reason = '';
		if (!self::canMakeGroupSuggestion($assignment, $users)) {
			App::abort(500);
		}
		
		$group = new StudentGroupSuggestion;
		$group->assignment()->associate($assignment);
		$group->creator()->associate($creator);
		$group->save();
		foreach($users as $user) {
			$subjectMember = $assignment->subject->getMembership($user);
			$group->members()->attach($subjectMember->id, array('accepted' => ($user->id == $creator->id) ));
		}
		return $group;
	}
	

	static function createTable() {
		
		Schema::create('student_group_suggestions', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
			
			$table->integer('assignment_id')->unsigned();
			$table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
			
			$table->integer('creator_id')->unsigned();
			$table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
		});
		

		Schema::create('student_group_suggestion_memberships', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->integer('student_group_suggestion_id')->unsigned();
			// we need to specify a name for the foreign key constraint because the automatically generated one is too long for mysql...
			$table->foreign('student_group_suggestion_id', 'student_group_suggestion_id_foreign_key')->references('id')->on('student_group_suggestions')->onDelete('cascade');
			
			$table->integer('subject_member_id')->unsigned();
			$table->foreign('subject_member_id')->references('id')->on('subject_members')->onDelete('cascade');
			
			$table->tinyInteger('accepted')->default(PlatypusBool::false);
			
		});
		
	}
}



