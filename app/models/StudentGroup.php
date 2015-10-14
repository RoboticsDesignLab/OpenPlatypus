<?php

use Platypus\Helpers\PlatypusBool;

class StudentGroup extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'student_groups';


	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array('created_at', 'updated_at');
	}
	
	public static $automaticGarbageCollectionRelations = array('members');
	
	// define the relationships to other models
	public static $relationsData = array(
			'assignment'  => array(self::BELONGS_TO, 'Assignment'),
			'members'  => array(self::BELONGS_TO_MANY, 'SubjectMember', 'table' => 'student_group_memberships', 'foreignKey' => 'student_group_id', 'otherKey' => 'subject_member_id', 'timestamps' => true),
	);

	// pseudo-relationships

	// get the users directly without retrieving the SubjectMember model in between.
	public function users() {
		return User::whereHas('subjectMembers', function($q) {
			$q->whereHas('studentGroups', function ($q) {
				$q->where('id', $this->id);
			});
		});
	}
	
	public function submittedAnswers() {
		return $this->assignment->submittedAnswers()->whereHas('user', function($q){
			$q->whereHas('subjectMembers', function($q) {
				$q->whereHas('studentGroups', function($q) {
					$q->where('id',$this->id);
				});
			});
		});
	}
	
	public function questionsWithSubmittedAnswers() {
		return $this->assignment->allQuestions()->whereHas('answers', function($q) {
			$q->where('submitted', PlatypusBool::true)->whereHas('user', function($q) {
				$q->whereHas('subjectMembers', function($q) {
					$q->whereHas('studentGroups', function ($q) {
						$q->where('id', $this->id);
					});
				});
			});
		});
	}	
	
	public function count() {
		return $this->members()->count();
	}
	
	public function isEmpty() {
		return $this->members()->count() == 0;
	}
	
	public function isMember(User $user) {
		return !is_null($this->members()->where('user_id', $user->id)->first());
	}
	
	public function hasMultipleSubmittedAnswersForAQuestion() {
		$questions = array();
		foreach($this->submitted_answers as $answer) {
			$questions[] = $answer->question_id;
		}
		return count($questions) !== count(array_unique($questions));
	}
	
	public function canAddUser(User $user, &$reason) {
		$subject = $this->assignment->subject;
		
		if (!$subject->isActiveStudent($user)) {
			$reason  = 'User is not an active student.';
			return false;
		}
		if (!is_null(self::findGroup($this->assignment, $user))) {
			$reason = 'User is already part of a group for this assignment.';
			return false;
		}
		
		if ($this->assignment->group_work_mode == AssignmentGroupWorkMode::groupsolutions) {
			// only one solution per group is allowed. So we have to check whether there is any overlap with already submitted answers. 
			$groubSubmissions = $this->questions_with_submitted_answers;
			$userSubmissions = $this->assignment->getUserAnswersSubmitted($user);
			if (! $groubSubmissions->intersect($userSubmissions)->isEmpty()) {
				$reason = 'Only one answer is allowed per group. Adding this user to the group would violate this.';
				return false;
			}
		}
				
		$reason = '';
		return true;
	}
	
	public function addUserSave(User $user) {
		$subject = $this->assignment->subject;
		$reason = '';
		if (!$this->canAddUser($user, $reason)) {
			App::abort(500, 'Cannot add user to group. '. $reason);
		}
		$subjectMember = $this->assignment->subject->getMembership($user);
		$this->members()->attach($subjectMember->id);
		
		// delete all suggestions for this user and this assignment
		StudentGroupSuggestion::where('assignment_id', $this->assignment->id)
			->whereHas('members', function($q) use($user) {
				$q->where('user_id', $user->id);
			})
			->delete();		
	}
	
	public function removeUserSave(User $user) {
		$member = $this->members()->where('user_id',$user->id)->first();
		if (!is_null($member)) {
			$this->members()->detach($member->id);
		}
	}

	static function findGroup(Assignment $assignment, User $user) {
		return StudentGroup::where('assignment_id', $assignment->id)
			->whereHas('members', function($query) use($user) {
				$query->where('user_id', $user->id);
			})->first();
	}
	
        static function getUserGroupList(Assignment $assignment, $user_ids, $complete=false) {
                if (!$complete) {
                        return DB::table('subject_members')->whereIn('user_id', $user_ids)->where('subject_id', $assignment->subject->id)
                                ->join('student_group_memberships', 'subject_members.id', '=', 'student_group_memberships.subject_member_id')
                                ->lists('student_group_id', 'user_id');
                } else {
                        $groups = DB::table('subject_members')->whereIn('user_id', $user_ids)->where('subject_id', $assignment->subject->id)
                                ->join('student_group_memberships', 'subject_members.id', '=', 'student_group_memberships.subject_member_id')
                                ->lists('student_group_id');
                        return DB::table('subject_members')->where('subject_id', $assignment->subject->id)
                                ->join('student_group_memberships', 'subject_members.id', '=', 'student_group_memberships.subject_member_id')
                                ->whereIn('student_group_id', $groups)
                                ->lists('student_group_id', 'user_id');
                }
        }
	
	static function makeGroup(Assignment $assignment, $users) {
		if ($users instanceof User) {
			$users = array($users);
		}
		
		$group = new StudentGroup;
		$group->assignment()->associate($assignment);
		$group->save();
		foreach($users as $user) {
			$group->addUserSave($user);
		}
		return $group;
	}
	

	static function createTable() {
		
		Schema::create('student_groups', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
			
			$table->integer('assignment_id')->unsigned();
			$table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
		});
		

		Schema::create('student_group_memberships', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->integer('student_group_id')->unsigned();
			$table->foreign('student_group_id')->references('id')->on('student_groups')->onDelete('cascade');
			
			$table->integer('subject_member_id')->unsigned();
			$table->foreign('subject_member_id')->references('id')->on('subject_members')->onDelete('cascade');
		});
		
	}
}



