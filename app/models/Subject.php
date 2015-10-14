<?php

use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Carbon\Carbon;
use Platypus\Helpers\PlatypusBool;


class Subject extends PlatypusBaseModel {

	// The database table used by the model.
	protected $table = 'subjects';
	
	// fields we can fill directly from user input.
	protected $fillable = array('code', 'title', 'start_date', 'end_date', 'visibility');

	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array('created_at', 'updated_at', 'start_date', 'end_date');
	}	
	
	
	public static $presenterClass = 'SubjectPresenter';
	
	
	// define the relationships to other models
	public static $relationsData = array(
			'subjectMembers'  => array(self::HAS_MANY, 'SubjectMember'),
			'users'  => array(self::BELONGS_TO_MANY, 'User', 'table' => 'subject_members'),
			'assignments'  => array(self::HAS_MANY, 'Assignment'),
	);

	// pseudo relationships
	public function activeStudents() {
		return $this->users()->wherePivot('role', SubjectMemberRoles::student)->wherePivot('suspended', false);
	}

	public function allStudents() {
		return $this->users()->wherePivot('role', SubjectMemberRoles::student);
	}
	
	
	public function activeTutors() {
		return User::whereHas('subjectMembers', function($q) {
			$q->where('suspended', false)->where('role', SubjectMemberRoles::tutor)->where('subject_id', $this->id);
		});
	}

	public function lecturers() {
		return $this->users()->wherePivot('role', SubjectMemberRoles::lecturer);
	}	
	
	
	// set the validation rules that apply to this model.
	public static $rules = array (
			'code' => 'required|alpha_num|min:3|max:10',
			'title' => 'required|min:8|max:200',
			'start_date' => 'required|date|carbon',
			'end_date' => 'required|date|carbon|afterfield:start_date',
			'visibility' => 'boolean'
	);

	
	protected function runCustomValidationRules(&$success) {
		
		// we want to check that the assignment dates are all within the subject's time period.
		$earliest = null;
		$latest = null;
		
		foreach ($this->assignments as $assignment) {
			foreach( array('answers_due', 'tutors_due', 'peers_due', 'autostart_marking_time') as $key) {
				if (isset($assignment->$key) && (!isset($earliest) || $assignment->$key < $earliest)) {
					$earliest = $assignment->$key;
				}
				if (isset($assignment->$key) && (!isset($latest) || $assignment->$key > $latest)) {
					$latest = $assignment->$key;
				}
			}
		}
		
		if ($this->attributes['start_date'] instanceof Carbon) {
			if (isset($earliest) && ($this->start_date > $earliest)) {
				$success = false;
				$this->validationErrors->add('start_date', 'The start date cannot be after a date used in an assignment ('. $earliest->format('d/m/Y') .').');
			}
		}
		
		if ($this->attributes['end_date'] instanceof Carbon) {
			if (isset($latest) && ($this->end_date < $latest)) {
				$success = false;
				$this->validationErrors->add('end_date', 'The start date cannot be before a date used in an assignment ('. $latest->format('d/m/Y') .').');
			}
		}
		
	}	
	
	// the automatic conversion for timestamps doesn't work well with Arden. We make our own routine.
	public function setStartDateAttribute($value) {
		try {
			$this->attributes ['start_date'] = Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
		} catch ( Exception $e ) {
			$this->attributes ['start_date'] = $value;
		}
	}

	// the automatic conversion for timestamps doesn't work well with Arden. We make our own routine.
	public function setEndDateAttribute($value) {
		try {
			$this->attributes ['end_date'] = Carbon::createFromFormat('d/m/Y', $value)->endOfDay();
		} catch ( Exception $e ) {
			$this->attributes ['end_date'] = $value;
		}
	}	
	
	

	
	
	
	// add a new member to this subject. to be used to enrol a student or add a tutor or a co-lecturer.
	public function addMemberSave(User $user, $role) {
		$membership = new SubjectMember();
		$membership->user_id = $user->id;
		$membership->role = $role;
		$this->subjectMembers()->save($membership);
		$this->invalidateRelations();
	}
	
	// remove a member from this subject. (student, tutor or a co-lecturer).
	public function removeMemberSave(User $user) {
		$membership = SubjectMember::where('user_id', $user->id)->where('subject_id', $this->id)->firstOrFail();
		$membership->delete();
		$this->invalidateRelations();
	}
	
	public function suspendMemberSave(User $user, $suspend = true) {
		$membership = SubjectMember::where('user_id', $user->id)->where('subject_id', $this->id)->firstOrFail();
		$membership->suspended = $suspend;
		$membership->save();
		$this->invalidateRelations();
	}
	
	// check whether the user is a member of this class. 
	public function isMember($user) {
		return !is_null($this->getMembership($user));
	}
	
	// we cache the memberships we retrieved from the database because they are used all the time and repeatedly for permission checking, etc.
	private $membershipCache = array();
	
	public function getMembership($user) {
		if (isset($this->membershipCache[$user->id])) {
			$result = $this->membershipCache[$user->id];
			if ($result === false) { 
				$result = null;
			}
		} else {
			$result = $this->subjectMembers()->where('user_id','=',$user->id)->first();
			if(is_null($result)) {
				$this->membershipCache[$user->id] = false;
			} else {
				$this->membershipCache[$user->id] = $result;
			}
		}
		return $result;
		
	}
	
	public function invalidateRelations() {
		parent::invalidateRelations();
		$this->membershipCache = array();
	}
	
	public function getMembershipRole($user) {
		$membership = $this->getMembership($user);
		if (is_null($membership)) {
			return null;
		} else {
			return $membership->role;
		}
	}
	
	// check whether the user is a lecturer of this class. 
	public function isLecturer($user) {
		return $this->isMember($user) && ($this->getMembershipRole($user) == SubjectMemberRoles::lecturer);
	}
	
	public function numberOfLecturers() {
		return $this->subjectMembers()->where('role','=',SubjectMemberRoles::lecturer)->count();
	}
	
	// check whether the user is a student of this class. 
	public function isStudent($user) {
		return $this->isMember($user) && ($this->getMembershipRole($user) == SubjectMemberRoles::student);
	}
	
	public function isQuestionObserver($user) {
		return $this->isMember($user) && ($this->getMembershipRole($user) == SubjectMemberRoles::questionobserver);
	}

	public function isFullObserver($user) {
		return $this->isMember($user) && ($this->getMembershipRole($user) == SubjectMemberRoles::fullobserver);
	}	
	
	// check whether the user is a student of this class.
	public function isSuspended($user) {
		$membership = $this->getMembership($user);
		return !is_null($membership) && $membership->suspended;
	}

	// check whether the user is a student of this class and not suspended.
	public function isActiveStudent(User $user) {
		return ($this->isStudent($user) && !$this->isSuspended($user) );
	}
		
	// check whether the user is a tutor of this class. 
	public function isTutor(User $user) {
		return $this->isMember($user) && ($this->getMembershipRole($user) == SubjectMemberRoles::tutor);
	}
	
	public function isActiveTutor(User $user) {
		return $this->isTutor($user) && ! $this->isSuspended($user);
	}
	
	public function isVisibleToStudentsAndTutors() {
		return (bool)$this->visibility;
	}
	
	public function mayView(User $user) {
		if ($user->isAdmin()) return true;
		if (!$this->isMember($user)) return false;
		if (!$this->isVisibleToStudentsAndTutors()) {
			if ($this->isStudent($user)) return false;
			if ($this->isTutor($user)) return false;
		}
		return true;
	}
	
	public function mayCreateAssignment(User $user) {
		return $this->isLecturer($user) || $user->isAdmin();
	}
	
	public function mayViewMembers(User $user) {
		if ($this->mayManageMembers($user)) return true;
		if ($this->isFullObserver($user)) return true;
	}	
	
	public function mayManageMembers(User $user) {
		return $this->isLecturer($user) || $user->isAdmin();
	}
	
	public function mayEdit(User $user) {
		return $this->isLecturer($user) || $user->isAdmin();
	}
	
	public function studentHasAnswers(User $user) {
		$answerCount = Answer::where('user_id', $user->id)->whereHas('question', function($query) {
			$query->where('subject_id', $this->id);
		})->count();
		
		return $answerCount > 0;
	}
		
	public function getAnswers(User $user) {
		return Answer::where('user_id', $user->id)->whereHas('question', function($query) {
			$query->where('subject_id', $this->id);
		})->get();
	}
	
	public function studentHasNonEmptyAnswers(User $user) {

		foreach($this->getAnswers($user) as $answer) {
			if(!$answer->isEmpty()) return true;
		}
		
		return false;
	}
	
	public function deleteEmptyAnswersSave(User $user) {
		foreach($this->getAnswers($user) as $answer) {
			if($answer->isEmpty()) {
				$answer->delete();
			}
		}
	}
	
	public function userHasReviewTasks(User $user) {
		return Review::where('user_id', $user->id)->whereHas('answer', function($q) {
				$q->whereHas('question', function($q) {
					$q->where('subject_id', $this->id);
				});
		})->exists();
	}
	
	public function userHasMarks(User $user) {
		if (AssignmentMark::where('user_id', $user->id)->whereHas('assignment', function($q) {
			$q->where('subject_id', $this->id);
		})->exists()) return true;
		
		if (QuestionMark::where('user_id', $user->id)->whereHas('question', function($q) {
			$q->where('subject_id', $this->id);
		})->exists()) return true;
		
		return false;
	}
	
	public function studentHasData(User $user) {
		if ($this->studentHasAnswers($user)) return true;
		if ($this->userHasReviewTasks($user)) return true;
		if ($this->userHasMarks($user)) return true;
		return false;
	}
		
	public function studentHasNonEmptyData(User $user) {
		if ($this->studentHasNonEmptyAnswers($user)) return true;
		if ($this->userHasReviewTasks($user)) return true;
		if ($this->userHasMarks($user)) return true;
		return false;
	}

	public function deleteEmptyStudentData(User $user) {
		$this->deleteEmptyAnswersSave($user);
	}
	
	public function tutorHasData(User $user) {
		if ($this->userHasReviewTasks($user)) return true;
		return false;
	}
	
	public function tutorHasNonEmptyData(User $user) {
		if ($this->userHasReviewTasks($user)) return true;
		return false;
	}
	
	public function deleteEmptyTutorData(User $user) {
	}
	
	
	
	// create the initial database tables
	static function createTable() {
		Schema::create('subjects', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
			
			$table->string('code', 1000)->index();
			$table->string('title', 1000)->index();
			$table->timestamp('start_date')->index();
			$table->timestamp('end_date')->index();
			$table->tinyInteger('visibility')->default(PlatypusBool::false)->index();
			$table->tinyInteger('marks_released')->default(PlatypusBool::false)->index();
		});
	}

}


// A presenter that formats the dates when showing them in a view.
class SubjectPresenter extends BasePresenter {

	private static $codeTranslationTables = array();
	
	public function start_date() {
		if (is_null($this->resource->start_date)) return '';
		return $this->resource->start_date->format('d/m/Y');
	}

	public function end_date() {
		if (is_null($this->resource->end_date)) return '';
		return $this->resource->end_date->format('d/m/Y');
	}
	
	public function code() {
		self::ensureCodeIsTranslated(Auth::user(), $this->resource);
		return self::$codeTranslationTables[Auth::user()->id][$this->resource->id];
	}
	
	public function code_for_admin() {
		self::ensureCodeIsTranslated(NULL, $this->resource);
		return self::$codeTranslationTables['admin'][$this->resource->id];
	}
	
	public function code_real() {
		return $this->resource->code;
	}
	
	private static function compressYearRange($start, $end) {
		if ($start->year==$end->year) return (string) $start->year;
		return (string) $start->year. '-'. $end->year;
	}
	
	private static function compressDateRange(Carbon $start, Carbon $end) {
		if ($start->year != $end->year) return $start->format('d/m/Y'). ' - ' .$end->format('d/m/Y');
		if ($start->month != $end->month) return $start->format('d/m'). ' - ' .$end->format('d/m/Y');
		if ($start->day != $end->day) return $start->format('d'). ' - ' .$end->format('d/m/Y');
		return $start->format('d/m/Y');
	}
	
	
	private function ensureCodeIsTranslated(User $user, Subject $subject) {
		
		$userid = is_null($user) ? 'admin' : $user->id;

		// Make sure we have a data field for the user we're working with.
		if (!isset(self::$codeTranslationTables[$userid])) {
			self::$codeTranslationTables[$userid] = array();
		}
		
		// if the field in question is filled, nothing to do.
		if (isset(self::$codeTranslationTables[$userid][$subject->id])) return;
		
		// select all relevant subjects with the same code.
		if (is_null($user)) {
			$similarSubjects = Subjects::where('code','=',$subject->code)->get();
		} else {
			$similarSubjects = $user->visibleSubjects()->where('code','=',$subject->code)->get();
		}
		
		// if there are no collisions, we don't have anything to do.
		if (count($similarSubjects) < 2) {
			self::$codeTranslationTables[$userid][$subject->id] = $subject->code;
			return;
		}
		
		// try to append the year.
		$candidates = array();
		foreach($similarSubjects as $key => $value) {
			$candidates[$key] = $value->code . ' ' . static::compressYearRange($value->start_date, $value->end_date);
		}
		
		// If this is not unique enough, append the full date.
		if (count(array_unique($candidates)) != count($candidates)) {
			foreach($similarSubjects as $key => $value) {
				$candidates[$key] = $value->code . ' (' . static::compressDateRange($value->start_date, $value->end_date) . ')';
			}
		}
		
		// If this is still not enough, append a running number.
		if (count(array_unique($candidates)) != count($candidates)) {
			$ids = array();
			foreach($similarSubjects as $key => $value) {
				$ids[$key] = $value->id;
			}
			asort($ids);
			$count = 0;
			foreach($ids as $key => $id) {
				$count++;
				$candidates[$key] .= ' #'.$count; 
			}
		}
		
		foreach($similarSubjects as $key => $value) {
			self::$codeTranslationTables[$userid][$value->id] = $candidates[$key];
		}		
	}
	
	
}



