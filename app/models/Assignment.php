<?php
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Carbon\Carbon;
use Platypus\Helpers\PlatypusBool;



class AssignmentVisibility extends PlatypusEnum {
	const hidden = 0;
	const posted = 1;
	const withsolutions = 2;
	const withmarkingscheme = 3;
}


class AssignmentGroupWorkMode extends PlatypusEnum {
	const no = 0;
	const individualsolutions = 1;
	const groupsolutions = 2;
}



class AssignmentGroupSelectionMode extends PlatypusEnum {
	const lecturer = 0;
	const selfservice = 1;
}



class AssignmentGuessMarks extends PlatypusEnum {
	const no = 0;
	const optional = 1;
	const yes = 2;
}

class AssignmentMarkByTutors extends PlatypusEnum {
	const all = 0;
	const none = 1;
	const allNoRateReviews = 2;
	const allNoViewReviews = 3;
}



class AssignmentShuffleMode extends PlatypusEnum {
	const shufflequestions = 0;
	const wholeassignments = 1;
}



class AssignmentLatePolicy extends PlatypusEnum {
	const donotaccept = 0;
	const acceptbeforemarking = 1;
	const markbytutor = 2;
	const markbylecturer = 3;
}


class AssignmentMarksReleased extends PlatypusEnum {
	const none = 0;
	const rapidrelease = 1;
	const rapidreleasequestionmarks = 5;
	const rapidreleasefinal = 2;
	const reviews = 3;
	const questionmarks = 6;
	const finalmarks = 4;
}

class AssignmentGroupMarkMode extends PlatypusEnum {
	const group = 0;
	const individual = 1;
}


class Assignment extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'assignments';
	
	// define the types that are taken as enum. These will automatically be validated against the proper range.
	public static $enumColumns = array (
			'group_work_mode' => 'AssignmentGroupWorkMode',
			'group_selection_mode' => 'AssignmentGroupSelectionMode',
			'group_mark_mode' => 'AssignmentGroupMarkMode',
			'guess_marks' => 'AssignmentGuessMarks',
			'mark_by_tutors' => 'AssignmentMarkByTutors',
			'shuffle_mode' => 'AssignmentShuffleMode',
			'late_policy' => 'AssignmentLatePolicy',
			'marks_released' => 'AssignmentMarksReleased',
			'offline' => 'PlatypusBool',
			'visibility' => 'AssignmentVisibility',
			'marking_started' => 'PlatypusBool',
	);
	

	// fields we can fill directly from user input.
	protected $fillable = array (
				'title',
				'offline',
				'visibility',
				'answers_due',
				'group_work_mode',
				'group_selection_mode',
				'group_mark_mode',
				'group_size_min',
				'group_size_max',
			    'guess_marks',
				'mark_by_tutors',
				'tutors_due',
				'number_of_peers',
				'peers_due',
				'shuffle_mode',
				'autostart_marking_time',
				'late_policy',
				'marks_released',
		);
	
	// fields we set with default values
	public static $defaultValues = array(
			'visibility' => AssignmentVisibility::hidden,
			'group_work_mode' => AssignmentGroupWorkMode::no,
			'group_mark_mode' => AssignmentGroupMarkMode::group,
			'mark_by_tutors' => AssignmentMarkByTutors::all,
			'number_of_peers' => 3,
			'shuffle_mode' => AssignmentShuffleMode::shufflequestions,
			'marking_started' => PlatypusBool::false,
			'offline' => PlatypusBool::false,
			'autostart_marking_time' => NULL,
			'late_policy' => AssignmentLatePolicy::donotaccept,
			'marks_released' => AssignmentMarksReleased::none,
	);
	
	
	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array (
				'created_at',
				'updated_at',
				'answers_due',
				'tutors_due',
				'peers_due',
				'autostart_marking_time' 
		);
	}
	

	// define the relationships to other models
	public static $relationsData = array (
			'subject' => array (self::BELONGS_TO,'Subject', 'foreignKey' => 'subject_id'),
			'introduction' => array (self::BELONGS_TO,'TextBlock', 'foreignKey' => 'introduction_id'),
			'questions'  => array(self::HAS_MANY, 'Question', 'foreignKey' => 'assignment_id'),
			'assignmentTutors'  => array(self::HAS_MANY, 'AssignmentTutor', 'foreignKey' => 'assignment_id'),
			'studentGroups'  => array(self::HAS_MANY, 'StudentGroup', 'foreignKey' => 'assignment_id'),
			'events'  => array(self::HAS_MANY, 'AssignmentEvent', 'foreignKey' => 'assignment_id'),
			'assignmentMarks' => array(self::HAS_MANY, 'AssignmentMark', 'foreignKey' => 'assignment_id'),
	);	
	
	// pseudo relationhips go here
	public function activeStudents() {
		return $this->subject->activeStudents();
	}
	
	public function allStudents() {
		return $this->subject->allStudents();
	}
	
	public function allStudentsOrdered() {
		return $this->allStudents()->orderBy('last_name')->orderBy('first_name');
	}
	
	public function groupedStudents() {
		return User::whereHas('subjectMembers', function($q) {
			$q->whereHas('studentGroups', function ($q) {
				$q->where('assignment_id', $this->id);
			});
		});
	}
	
	public function getStudentGroup(User $user) {
		return StudentGroup::findGroup($this, $user);
	}
	
	public function allQuestions() {

		return Question::where(function($q) {
			$q->where(function($q) {
				$q->where('assignment_id', $this->id);
			})
			->orWhere(function($q) {
				$q->where('type',QuestionType::subquestion)
				->whereRaw('master_question_id IN (SELECT master.id FROM questions as master WHERE master.assignment_id = ?)', array($this->id));
			});
		});
		
		// Unfortunately there is a shortcoming in Laravel's SQL generator when it comes to self-referencing relationships. It doesn't alias the second instance of the table.
		// Thus, we do the join stuff manually. 
		//return Question::leftJoin('questions as master_questions', 'questions.master_question_id', '=', 'master_questions.id')->select('questions.*')->where(function($q) {
		//		$q->where('questions.assignment_id', $this->id)
		//			->orWhere('master_questions.assignment_id', $this->id);
		//});
	}
	
	// This returns the questions correctly ordered as they should be displayed.
	public function questionsOrdered() {
		return $this->questions()->orderBy('position')->orderBy('id');
	}
	
	public function submittedAnswers() {
		return $this->allAnswers()->where('submitted', PlatypusBool::true);
	}
	
	public function allAnswers() {
		return Answer::whereHas('question', function($q){
			$q->leftJoin('questions as master_questions', 'questions.master_question_id', '=', 'master_questions.id')->where(function($q) {
				$q->where('questions.assignment_id', $this->id)
					->orWhere('master_questions.assignment_id', $this->id);
			});
		});
	}
	
	public function allReviews($role = null) {
		$result = Review::whereHas('answer', function($q) {
			$q->whereHas('question', function($q){
				$q->leftJoin('questions as master_questions', 'questions.master_question_id', '=', 'master_questions.id')->where(function($q) {
					$q->where('questions.assignment_id', $this->id)
					->orWhere('master_questions.assignment_id', $this->id);
				});
			});
		});
		
		if (is_null($role)) {
			return $result;
		} else {
			return $result->where('reviewer_role', $role);
		};
	}
	
	public function allReviewTasks($role = null) {
		$this->allReviews($role)->where('status', ReviewStatus::task);
	}
	
	public function allCompletedReviews($role = null) {
		return $this->allReviews($role)->where('status', ReviewStatus::completed);
	}	
	
	public function flaggedReviews() {
		return $this->allCompletedReviews()->where('flag' ,'<>', ReviewFlag::none);
	}

	public function flaggedReviewsOrdered() {
		return $this->flaggedReviews()->orderBy('time_submitted', 'desc');	
	}
	
	public function ratedReviewsOrdered() {
		return $this->allCompletedReviews()->where('review_rated', PlatypusBool::true)->orderBy('time_submitted', 'desc');
	}
	
	public function allQuestionMarks() {
		return QuestionMark::whereHas('question', function($q){
			$q->leftJoin('questions as master_questions', 'questions.master_question_id', '=', 'master_questions.id')->where(function($q) {
				$q->where('questions.assignment_id', $this->id)
				->orWhere('master_questions.assignment_id', $this->id);
			});
		});
	}	
	
	public function allTutors() {
		return $this->subject->activeTutors();
	}
	
	public function eventsOrdered() {
		return $this->events()->where('level', '<', AssignmentEventLevel::endOfNormalRange)->orderBy('created_at', 'desc')->orderBy('id', 'desc');
	}
	
	public function submissionEventsOrdered() {
		return $this->events()->where('level', AssignmentEventLevel::submission)->orderBy('created_at', 'desc')->orderBy('id', 'desc');
	}
	
	public function questionsWithSubquestionsWithoutMasters() {
		return Question::where(function($q) {
			$q->where(function($q) {
					$q->where('type',QuestionType::simple)
					->where('assignment_id', $this->id);
				})
				->orWhere(function($q) {
					$q->where('type',QuestionType::subquestion)
					->whereRaw('master_question_id IN (SELECT master.id FROM questions as master WHERE master.assignment_id = ?)', array($this->id));
				});
			});
	}
	
	public function questionsWithSubquestionsWithoutMastersOrdered() {
		return $this->questionsWithSubquestionsWithoutMasters()
			->orderBy(DB::raw('COALESCE( (SELECT master.position FROM questions as master WHERE master.id = questions.master_question_id), questions.position)'))
			->orderBy('position');
	}
	
	// set the validation rules that apply to this model.
	// the rules are set below. This is a workaround for a stupid php limitation.
	public static $rules = array ();
	
	// receives a copy of the current rules for self-validation
	// has to return the new rules.
	// can be overridden in derived models.
	protected function mangleRulesForValidation($rules) {
	
		$newRules = parent::mangleRulesForValidation($rules);
	
		if ($this->usesTutorMarking()) {
			array_unshift($newRules['tutors_due'], 'required');
		};
	
		return $newRules;
	
	}	
	
	// point out that this model uses a presenter to tweak fields whe n showing them in views.
	public static $presenterClass='AssignmentPresenter';
	
	
	protected function runCustomValidationRules(&$success) {
		$subject = $this->subject;
		
		foreach( array('answers_due', 'tutors_due', 'peers_due', 'autostart_marking_time') as $key) {
			if ($this->attributes[$key] instanceof Carbon) {
				if ($this->$key < $subject->start_date) {
					$success = false;
					$this->validationErrors->add($key, 'The date cannot be before the start date of the class.');
				}
				
				if ($this->attributes[$key] > $subject->end_date) {
					$success = false;
					$this->validationErrors->add($key, 'The date cannot be after the end date of the class.');
				}
			}
		}

		if($this->isDirty('group_work_mode') && ($this->group_work_mode == AssignmentGroupWorkMode::groupsolutions)) {
			foreach($this->studentGroups as $group) {
				if ($group->hasMultipleSubmittedAnswersForAQuestion()) {
					$success = false;
					$this->validationErrors->add('group_work_mode', 'Students have already submitted multiple andwers for a question within a student group.');
					break;
				}
			}			
		}
	}
	

	
	// This returns the questions correctly ordered as they should be displayed and also includes sub-questions in the ordering.
	public function getQuestionsOrderedWithSubquestions() {
		$result = array();
		foreach($this->questions_ordered as $question) {
			$result[] = $question;
			if ($question->isMaster()) {
				foreach($question->subquestions_ordered as $subquestion) {
					$result[] = $subquestion;
				}
			}
		}
		return $result;
	}	

	public function getQuestionsOrderedWithSubquestionsWithoutMasters() {
		$result = array();
		foreach($this->questions_ordered as $question) {
			
			if ($question->isMaster()) {
				foreach($question->subquestions_ordered as $subquestion) {
					$result[] = $subquestion;
				}
			} else {
				$result[] = $question;
			}
		}
		return $result;
	}

	
	public function getNextUnusedQuestionPosition() {
		$maxUsed = $this->questions()->max('position');
		if (is_null($maxUsed)) {
			return 1;
		} else {
			return $maxUsed + 1;
		}
	}
	
	
	
	// the automatic conversion for timestamps doesn't work well with Arden. We make our own routines
	public function setAnswersDueAttribute($value) {
		$this->attributes ['answers_due'] = parseDeadline($value);
	}

	public function setTutorsDueAttribute($value) {
		$this->attributes ['tutors_due'] = parseDeadline($value);
	}

	public function setPeersDueAttribute($value) {
		$this->attributes ['peers_due'] = parseDeadline($value);
	}

	public function setAutostartMarkingTimeAttribute($value) {
		$this->attributes ['autostart_marking_time'] = parseStarttime($value);
	}
	
	public function isMember($user) {
		return $this->subject->isMember($user);
	}	
	
	public function isLecturer(User $user) {
		return $this->subject->isLecturer($user);
	}
	
	public function isStudent(User $user) {
		return $this->subject->isStudent($user);
	}
	
	public function isActiveStudent(User $user) {
		return $this->subject->isActiveStudent($user);
	}
	
	public function isQuestionObserver($user) {
		return $this->subject->isQuestionObserver($user);
	}
	
	public function isFullObserver($user) {
		return $this->subject->isFullObserver($user);
	}	
	
	public function getTutorsForQuestionQuery(Question $question) {
		if ($question->isSubquestion()) {
			$question = $question->master_question;
		}
		
		$result = User::whereHas('subjectMembers', function($q) use($question) {
			$q->whereHas('assignmentTutors', function($q) use ($question) {
				$q->where('assignment_id', $this->id)->where(function($q) use ($question) {
					$q->where('question_id', $question->id)->orWhereNull('question_id');
				});
			});
		});
		
		$clonedResult = clone $result;
		if($clonedResult->exists()) {
			return $result;
		} else {
			return $this->allTutors();
		}		
	}
		
 	public function isTutor(User $user) {
 		return $this->allTutors()->where('id', $user->id)->exists();
 	}
 	
 	public function isSolutionEditor(User $user) {
 		if (!$this->subject->isTutor($user)) return false;
 		$membership = $this->subject->getMembership($user);
 		
 		return $this->allQuestions()->where('solution_editor_id', $membership->id)->exists(); 		
 	} 
 	
 	public function getAssignmentTutorsForUser(User $user) {
 		return $this->assignmentTutors()->whereHas('subjectMember', function($q) use($user) {
			$q->where('user_id', $user->id);
 		})->get();
 	}
	
	public function isVisible() {
		return (! ($this->visibility == AssignmentVisibility::hidden));
	}
	
	public function markingHasStarted() {
		return $this->marking_started == PlatypusBool::true;
	}
	
	public function hasQuestionsWithSolution() {
		foreach ($this->all_questions as $question) {
			if($question->hasSolution()) return true;
		}
		return false;
	}
	
	public function hasQuestionsWithMarkingScheme() {
		foreach ($this->all_questions as $question) {
			if($question->hasMarkingScheme()) return true;
		}
		return false;
	}
	
	public function usesPeerReview() {
		return $this->number_of_peers > 0;
	}
	
	public function usesTutorMarking() {
		return $this->mark_by_tutors != AssignmentMarkByTutors::none;
	}
	
	public function keepAssignmentTogetherWhenShuffling() {
		return $this->shuffle_mode == AssignmentShuffleMode::wholeassignments;
	}
	
	public function tutorsAreIdenticalforAllQuestions() {
		$base = null;
		foreach($this->all_questions as $question) {
			$tutors = $this->getTutorsForQuestionQuery($question)->distinct()->lists('id');
			if (is_null($base) ) {
				$base = $tutors;
			} else {
				if (!empty(array_diff($base, $tutors))) return false;
				if (!empty(array_diff($tutors, $base))) return false;
			}
		}
		return true;
	}
	
	public function usesGroups() {
		return $this->group_work_mode != AssignmentGroupWorkMode::no;
	}

	public function usesGroupMarkMode() {
		if (!$this->usesGroups()) return false;
		if (!$this->group_mark_mode == AssignmentGroupMarkMode::group) return false;
		return true;
	}
	
	public function onlyOneAnswerPerGroup() {
		return $this->group_work_mode == AssignmentGroupWorkMode::groupsolutions;
	}
	
	public function studentsCanAssignGroupsThemselves() {
		return $this->group_selection_mode == AssignmentGroupSelectionMode::selfservice;
	}
	
	public function studentsMayGuessTheirMarks() {
		return $this->guess_marks != AssignmentGuessMarks::no;
	}
	
	public function studentsMustGuessTheirMarks() {
		return $this->guess_marks == AssignmentGuessMarks::yes;
	}
	
	public function autoCreateSingleGroupsOnSubmission() {
		if (!$this->usesGroups()) return false;
		if ($this->group_selection_mode != AssignmentGroupSelectionMode::selfservice) return false;
		if ($this->group_size_min > 1) return false;
		return true;
	}
	
	public function mayAnswerAssignment(User $user) {
		if (!$this->mayViewAssignment($user)) return false;
		if (!$this->isActiveStudent($user)) return false;
		if (!$this->isOpenForSubmissions()) return false;		
		return true;		
	}
	
	public function maySeeAssignmentAnswerPage(User $user) {
		if ($this->mayAnswerAssignment($user)) return true;
		if ($this->getUserAnswersQuery($user)->where('submitted', PlatypusBool::true)->exists()) return true;
		return false;
	}
	
	public function mayViewAssignment(User $user) {
		if (!$this->subject->mayView($user)) return false;
		if ($user->isAdmin()) return true;
		if ($this->isLecturer($user)) return true;
		if ($this->isQuestionObserver($user)) return true;
		if ($this->isFullObserver($user)) return true;
		if ($this->isSolutionEditor($user)) return true;		
		if ($this->isStudent($user) && $this->isVisible()) return true;
		if ($this->isTutor($user) && $this->isVisible()) return true;
		return false;
	}
	
	public function mayManageAssignment(User $user) {
		if (!$this->mayViewAssignment($user)) return false;
		if ($user->isAdmin()) return true;
		if ($this->isLecturer($user)) return true;
		return false;
	}	
	
	public function mayBrowseStudents(User $user) {
		if (!$this->mayViewAssignment($user)) return false;
		if ($user->isAdmin()) return true;
		if ($this->isLecturer($user)) return true;
		if ($this->isFullObserver($user)) return true;
		return false;		
	}
	
	public function mayEditAssignment(User $user) {
		return $this->mayManageAssignment($user);
	}

	public function maySeeAssignmentEditor(User $user) {
		if ($this->mayEditAssignment($user)) return true;
		if ($this->mayManageAssignment($user)) return true;
		if ($this->isSolutionEditor($user)) return true;
		return false;
	}
	
	public function mayEditIntroduction(User $user) {
		return $this->mayEditQuestions($user);
	}	
	
	public function mayEditQuestions(User $user) {
		if ($this->markingHasStarted()) return false;
		return $this->mayManageAssignment($user);
	}
	
	public function mayAddQuestions(User $user) {
		return ($this->mayEditQuestions($user));
	}	
	
	public function mayEditSolutions(User $user) {
		return $this->mayManageAssignment($user);
	}
	
	public function mayEditAnySolutions(User $user) {
		if ($this->mayEditSolutions($user)) return true;
		if ($this->isSolutionEditor($user)) return true;
		return false;
	}

	public function maySetSolutionEditor(User $user) {
		return $this->mayManageAssignment($user);
	}

	public function mayEditMarkingSchemes(User $user) {
		return $this->mayManageAssignment($user);
	}
	
	public function mayViewAllSolutions(User $user) {
		if ($this->mayManageAssignment($user)) return true;
		if ($this->isQuestionObserver($user)) return true;
		if ($this->isFullObserver($user)) return true;
		if ( ( ($this->visibility == AssignmentVisibility::withsolutions) || ($this->visibility == AssignmentVisibility::withmarkingscheme) ) && $this->markingHasStarted() ) {
			if($this->isActiveStudent($user)) return true;
			if($this->isTutor($user)) return true;
		}
		return false;
	}

	public function mayViewAllMarkingSchemes(User $user) {
		if ($this->mayManageAssignment($user)) return true;
		if ($this->isQuestionObserver($user)) return true;
		if ($this->isFullObserver($user)) return true;
		if ( ($this->visibility == AssignmentVisibility::withmarkingscheme) && $this->markingHasStarted() ) {
			if($this->isActiveStudent($user)) return true;
			if($this->isTutor($user)) return true;
		}
		return false;
	}
	
	public function mayViewAllSubmittedReviews(User $user) {
		if ($this->mayManageAssignment($user)) return true;
		if ($this->isFullObserver($user)) return true;
		return false;
	}
	
	public function mayViewAllReviewFlags(User $user) {
		if ($this->mayManageAssignment($user)) return true;
		if ($this->isFullObserver($user)) return true;
		return false;
	}
	

	public function mayEditQuestionPercentages(User $user) {
		// a shortcut for faster evaluation if marking hasn't started.
		if (!$this->markingHasStarted()) {
			return  $this->mayManageAssignment($user);
		} else {

			if (!$this->mayManageAssignment($user)) return false;
			
			if ($this->assignmentMarks()->exists()) return false;

			return true;
		}
	}

	public function mayMoveQuestions(User $user) {
		return $this->mayEditQuestions($user);
	}
	
	public function mayDeleteQuestions(User $user) {
		return $this->mayEditQuestions($user);
	}
	
	public function mayDeleteQuestionAnswers(User $user) {
		return $this->mayEditQuestions($user);
	}
	
	
	
	public function mayViewAssignmentGroups(User $user) {
		if (!$this->usesGroups()) return false;
		if ($this->mayManageAssignment($user)) return true;
		if ($this->subject->isFullObserver($user)) return true;
	}
	
	public function mayEditAssignmentGroups(User $user) {
		if (!$this->usesGroups()) return false;
		return $this->mayManageAssignment($user) && !$this->markingHasStarted();
	}
	
	public function mayViewReviews(User $user) {
		if (!$this->markingHasStarted()) return false;
		return $this->mayManageAssignment($user) || $this->isFullObserver($user);
	}

	public function mayEditReviews(User $user) {
		if (!$this->markingHasStarted()) return false;
		return $this->mayManageAssignment($user);
	}
	
	
	public function maySetFinalMarks(User $user) {

		if (!$this->markingHasStarted()) return false;
		
		if (!$this->mayViewAssignment($user)) return false;
		if ($this->isLecturer($user)) return true;
		return false;
	}	
	
	public function mayViewAllMarks(User $user) {
		return $this->mayBrowseStudents($user);
	}
	
	public function mayRateReviews(User $user) {
		if($this->maySetFinalMarks($user)) return true;
		if($this->isTutor($user)) {
			if ($this->mark_by_tutors == AssignmentMarkByTutors::all) {
				return true;
			}
		}
		return false;
	}	
	
	public function maySuggestStudentGroup(User $user) {
		if (!$this->studentsCanAssignGroupsThemselves()) return false;
		if (!$this->isActiveStudent($user)) return false;
		if (!is_null($this->getGroup($user))) return false;
		return true;
	}
	
	public function mayViewControlPanel(User $user) {
		if ($user->isAdmin()) return true;
		if ($this->isLecturer($user)) return true;
		if ($this->isFullObserver($user)) return true;
		return false;		
	}
	
	public function mayManageMarkingProcess(User $user) {
		return $this->mayManageAssignment($user);
	}
	
	public function assignmentIsreadyToStartMarking() {
		if ($this->markingHasStarted()) return false;
		if ($this->answers_due <= Carbon::now()) return true;
		return false;
	}
	
	public function mayStartMarking(User $user) {
		if (!$this->assignmentIsreadyToStartMarking()) return false;		
		return $this->mayManageMarkingProcess($user);
	}
	
	public function mayCancelMarking(User $user) {
		if (!$this->markingHasStarted()) return false;		
		return $this->mayManageMarkingProcess($user);
	}
	
	// this is an odd one. It is meant to be true if the user has review tasks (open or submitted) and the page to work on them should be shown.
	public function mayWriteReviews(User $user) {
		if ($this->getUserReviewsQuery($user)->exists()) return true;
		
		if ($this->markingHasStarted()) {
			if (!$this->isMember($user)) return false;
			if($this->maySetFinalMarks($user)) return true;
		}
		
		return false;
	}
	
	public function maySeeResultsPageAsStudent(User $user) {
		
		if (!$this->markingHasStarted()) return false;
		
		if (!$this->isActiveStudent($user)) return false;
	
		switch($this->marks_released) {
			case AssignmentMarksReleased::none:
				return false;
				break;
			case AssignmentMarksReleased::rapidrelease:
			case AssignmentMarksReleased::rapidreleasequestionmarks:
			case AssignmentMarksReleased::rapidreleasefinal:
			case AssignmentMarksReleased::reviews:
			case AssignmentMarksReleased::questionmarks:
			case AssignmentMarksReleased::finalmarks:
				return true;
				break;
		}
		App::abort(500, 'This is a bug');
	}
	
	public function maySeeResultsPageAsMarkerOrObserver(User $user) {
		if (!$this->markingHasStarted()) return false;
		if ($this->maySetFinalMarks($user)) return true;
		if ($this->isFullObserver($user)) return true;
		if ($this->mayManageAssignment($user)) return true;
		return false;
	}
	
	
	public function maySeeResultsPage(User $user) {
		return $this->maySeeResultsPageAsStudent($user) || $this->maySeeResultsPageAsMarkerOrObserver($user);
	}	

	public function isBlockedDueToRapidRelease(User $user) {
		if (!$this->isActiveStudent($user)) return false;
		
		switch($this->marks_released) {
			case AssignmentMarksReleased::none:
			case AssignmentMarksReleased::reviews:
			case AssignmentMarksReleased::questionmarks:
			case AssignmentMarksReleased::finalmarks:
				return false;
				break;
			case AssignmentMarksReleased::rapidrelease:
			case AssignmentMarksReleased::rapidreleasequestionmarks:
			case AssignmentMarksReleased::rapidreleasefinal:
				if ($this->hasUnfinishedReviewTasks($user)) {
					return true;
				} else {
					return false;
				}
		}
		App::abort(500, 'This is a bug');
	}

	public function maySeeReceivedReviews(User $user) {
		if (!$this->isActiveStudent($user)) return false;
		
		switch($this->marks_released) {
			case AssignmentMarksReleased::none:
				return false;
				break;
			case AssignmentMarksReleased::rapidrelease:
			case AssignmentMarksReleased::rapidreleasequestionmarks:
			case AssignmentMarksReleased::rapidreleasefinal:
				if ($this->hasUnfinishedReviewTasks($user)) {
					return false;
				} else {
					return true;
				}				
			case AssignmentMarksReleased::reviews:
			case AssignmentMarksReleased::questionmarks:
			case AssignmentMarksReleased::finalmarks:
				return true;
				break;
		}
		App::abort(500, 'This is a bug');
	}
	
	public function maySeeReceivedQuestionMarks(User $user) {
		if (!$this->isActiveStudent($user)) return false;
		
		switch($this->marks_released) {
			case AssignmentMarksReleased::none:
			case AssignmentMarksReleased::rapidrelease:
			case AssignmentMarksReleased::reviews:
				return false;
				break;
			case AssignmentMarksReleased::rapidreleasequestionmarks:
			case AssignmentMarksReleased::rapidreleasefinal:
				if ($this->hasUnfinishedReviewTasks($user)) {
					return false;
				} else {
					return true;
				}				
			case AssignmentMarksReleased::questionmarks:
			case AssignmentMarksReleased::finalmarks:
				return true;
				break;
		}
		App::abort(500, 'This is a bug');
	}
	
	public function maySeeReceivedFinalMark(User $user) {
		if (!$this->isActiveStudent($user)) return false;
		
		switch($this->marks_released) {
			case AssignmentMarksReleased::none:
			case AssignmentMarksReleased::rapidrelease:
			case AssignmentMarksReleased::rapidreleasequestionmarks:
			case AssignmentMarksReleased::reviews:
			case AssignmentMarksReleased::questionmarks:
				return false;
				break;
				
			case AssignmentMarksReleased::rapidreleasefinal:
				if ($this->hasUnfinishedReviewTasks($user)) {
					return false;
				} else {
					return true;
				}
				
			case AssignmentMarksReleased::finalmarks:
				return true;
				break;
		}
		App::abort(500, 'This is a bug');
	}
	
	public function allMarksAreReleased() {
		return ($this->marks_released == AssignmentMarksReleased::finalmarks);
	} 
	
	public function tutorsCanSeeStudentReviews() {
		return ($this->mark_by_tutors == AssignmentMarkByTutors::all) || ($this->mark_by_tutors == AssignmentMarkByTutors::allNoRateReviews);
	}
	
	
	public function getGroup($user) {
		return StudentGroup::findGroup($this, $user);
	}
	
	private function calculateQuestionPercentagesForSetOfQuestions($questions) {
		$result = array();
		
		$definedSum = 0;
		$autoCount = 0;
		foreach ($questions as $question) {
			if ($question->mark_percentage == Question::autoMarkPercentage) {
				$autoCount++;
			} else {
				$definedSum+=$question->mark_percentage;
			}
		}
		
		$definedSum = min(100, $definedSum);
		
		foreach ($questions as $question) {
			if ($question->mark_percentage == Question::autoMarkPercentage) {
				$result[$question->id] = (100-$definedSum) / $autoCount;
			} else {
				$result[$question->id] = $question->mark_percentage;
			}
		}
		
		return $result;
		
	}
	
	public function getQuestionPercentages() {
		$result = static::calculateQuestionPercentagesForSetOfQuestions($this->questions);
		
		foreach ($this->questions as $question) {
			if ($question->isMaster()) {
				$result += static::calculateQuestionPercentagesForSetOfQuestions($question->subquestions);
			}
		}
		return $result;	
	}
	
	public function createOrGetAllAnswersSave($user) {
		$result = array();
		foreach($this->getQuestionsOrderedWithSubquestions() as $question) {
			if (!$question->isMaster()) {
				$result[$question->id] = $question->createOrGetAnswerSave($user);
			}
		}
		return $result;
	}

	public function deleteEmptyAnswersSave(User $user) {
		foreach(getUserAnswers($user) as $answer) {
			if($answer->isEmpty()) {
				$answer->delete();
			}
		}
	}
	
	public function getUserAnswersQuery($user) {
		return $this->allAnswers()->where('user_id', $user->id);
	}
		
	public function getUserAnswers($user) {
		return $this->getUserAnswersQuery()->get();
	}
	
	public function getUserAnswersSubmittedQuery(User $user) {
		return $this->getUserAnswersQuery($user)->where('submitted', PlatypusBool::true);
	}
	
	public function getUserAnswersSubmitted(User $user) {
		return $this->getUserAnswersSubmittedQuery($user)->get();
	}	
	
	public function getUserAnswersWithoutMarkQuery(User $user) {
		return $this->getUserAnswersSubmittedQuery($user)->whereHas('question', function($q) use($user) {
			$q->whereHas('questionMarks', function($q) use($user) {
				$q->where('user_id', $user->id);
			}, '=', 0);
		});
	} 
	
	public function studentIsStillWaitingForAnswersToBeMarked(User $user) {
		$users = array($user);
		
		if($this->onlyOneAnswerPerGroup()) {
			$group = $this->getStudentGroup($user);
			if(isset($group)) {
				$users = $group->users;
			}
		}
		
		$user_ids = array();
		foreach($users as $item) $user_ids[] = $item->id;
		
		return $this->submittedAnswers()
			->whereIn('user_id', $user_ids)
			->whereHas('question', function($q) use($user) {
				$q->whereHas('questionMarks', function($q) use($user) {
					$q->where('user_id', $user->id);
				}, '=', 0);
			})
			->exists();
	}
	
	public function getUserAnswersWithoutMark(User $user) {
		return $this->getUserAnswersWithoutMarkQuery($user)->get();
	}
	
	public function studentHasAnswers(User $user) {
		return !is_null(getUserAnswersQuery($user)->first());
	}
	
	public function numberOfAnswersStudentHasDue(User $user) {
		return $this->questionsWithSubquestionsWithoutMasters()
			->whereHas('answers', function($q) use($user) {
				$q->where('submitted', PlatypusBool::true)
				->where('user_id', $user->id);
			}, '=', 0)
			->count();
	}
	
	public function studentHasNonEmptyAnswers(User $user) {
	
		foreach(getUserAnswers($user) as $answer) {
			if(!$answer->isEmpty()) return true;
		}
	
		return false;
	}
	
	public function hasUnfinishedReviewTasks($user) {
		return $this->getUserReviewsQuery($user)->where('status', ReviewStatus::task)->exists();
	}
	
	public function getUserReviewsQuery($user) {
		return $this->allReviews()->where('user_id', $user->id);
	}
	
	public function getUserReviews($user) {
		return $this->getUserReviewsQuery($user)->get();
	}

	public function getUserReviewsOneUserOrderedQuery($user_id, $answer_user_id) {
			if ($user_id instanceof User) {
			$user_id = $user_id->id;
		}
		
		if ($answer_user_id instanceof User) {
			$answer_user_id = $answer_user_id->id;
		}
		
		$result = Review::where('user_id',$user_id)
			->whereHas('answer', function($q) use($answer_user_id) {
				$q->join('questions', 'questions.id','=','answers.question_id')
					->leftJoin('questions as master_questions', 'questions.master_question_id', '=', 'master_questions.id')
					->where('user_id', $answer_user_id)
					->where(function($q) {
						$q->where('master_questions.assignment_id', $this->id)->orWhere('questions.assignment_id',$this->id);
					});
			});
			
		// order by the master question order.
		$result->orderBy(DB::raw('(
					SELECT coalesce(master_questions.position, questions.position) FROM answers
						INNER JOIN questions ON answers.question_id = questions.id
						LEFT JOIN questions AS master_questions ON questions.master_question_id = master_questions.id
						WHERE answers.id = reviews.answer_id 
				)'));

		// order by sub question order
		$result->orderBy(DB::raw('(
					SELECT questions.position FROM answers
						INNER JOIN questions ON answers.question_id = questions.id
						WHERE answers.id = reviews.answer_id
				)'));
		
		return $result;
	
	}
	
	// we need to do everything by hand here. It is complicated
	public function getUserReviewDataOrderedQuery($user, $showCompleted, $reviewsOnly = false, $forceByQuestion = false, $onlyQuestion = null, $viewGroup = 0) {

		if($reviewsOnly && !$this->maySetFinalMarks($user)) {
			$reviewsOnly = true;
		}
		
		if(isset($onlyQuestion)) {
			$forceByQuestion = true;
		}
		
		// prepare a part of the where clause for later.
		if ($this->isLecturer($user)) {
			$roleSelector = '(reviews_sub.reviewer_role = '.ReviewReviewerRole::student.' OR reviews_sub.reviewer_role = '.ReviewReviewerRole::lecturer.')';
		}else{
			$roleSelector = '(reviews_sub.reviewer_role = '.ReviewReviewerRole::student.')';
		}
		
		if (!is_numeric($user->id)) {
			App::abort(500); // we are using the user id in an sql string. Thus, make 100% sure it is a harmless number.
		}
		
		if (!is_numeric($this->id)) {
			App::abort(500); // we are using the id in an sql string. Thus, make 100% sure it is a harmless number.
		}
		
		if ( ($this->shuffle_mode == AssignmentShuffleMode::shufflequestions) || $forceByQuestion) {
			
			// make a select for pairs of (master) question ids and user ids. The user is the user who wrote the answer. 
			$result = DB::table('questions')
				->select(DB::raw('coalesce(master_questions.id,questions.id) as the_question_id'), 'answers.user_id as answer_user_id')
				->distinct()
				
				->leftJoin('questions as master_questions', 'questions.master_question_id', '=', 'master_questions.id')
				->join('answers', 'questions.id','=','answers.question_id')

				->where(function($q) {
					$q->where('master_questions.assignment_id', $this->id)->orWhere('questions.assignment_id',$this->id);
				})
				
				->where('answers.submitted', PlatypusBool::true);
			
			if($onlyQuestion) {
				$result->where(function($q) use ($onlyQuestion) {
					$q->where('master_questions.id', $onlyQuestion)->orWhere('questions.id',$onlyQuestion);
				});
			}
				
			if($reviewsOnly) {
				$result
					->join('reviews', 'answers.id','=','reviews.answer_id')
					->where('reviews.user_id', $user->id);

				if(!$showCompleted) {
					$result->where('reviews.status', ReviewStatus::task);
				}
				
			} else {
				$result->leftJoin('question_marks', function($q) {
					$q->on('question_marks.question_id', '=', 'questions.id')
					->on('question_marks.user_id', '=', 'answers.user_id');
				});
				
				if(!$showCompleted) {
					$result->whereNull('question_marks.question_id');
					
				} else {
					// join the reviews table anyway for sorting.
					$result->leftJoin('reviews', function($q) use($user) {
						$q->on('answers.id','=','reviews.answer_id')
							->where('reviews.user_id', '=', $user->id);
						});
						
				}
					
			}

			
				
				
			// first order by the correct master questions.
			$result->orderBy(DB::raw('(
					SELECT questions.position FROM questions WHERE questions.id = the_question_id
		    )'));
			
			// if we are a tutor or lecturer, we want to show those with less missing reviews first. 
			if ($this->isLecturer($user) || ($this->tutorsCanSeeStudentReviews() && $this->isTutor($user))) {
				
				if ($showCompleted) {
					if($reviewsOnly) {
						$result->orderBy('reviews.time_submitted', 'desc');
					} else {
						$result->orderBy(DB::raw('GREATEST(COALESCE(reviews.time_submitted, \'0000-01-01\'), COALESCE(question_marks.updated_at,\'0000-01-01\'))'), 'desc');
					}
				}
				
				// order by the number of missing reviews for the lecturers/tutor's convenience
				$result->orderBy(DB::raw('(
						SELECT count(reviews_sub.id) FROM reviews AS reviews_sub
							INNER JOIN `answers` as answers_sub on `reviews_sub`.`answer_id` = `answers_sub`.`id`
							INNER JOIN `questions` as questions_sub on answers_sub.question_id = `questions_sub`.`id`
							WHERE answers_sub.user_id = answer_user_id
								AND reviews_sub.user_id <> '.$user->id.'
								AND '.$roleSelector.'
								AND reviews_sub.status = '.ReviewStatus::task.'
								AND ( questions_sub.id = the_question_id OR questions_sub.master_question_id = the_question_id )
					)'));
			}

			// now we look for all reviews that a certain user has for a particular (master) question. We take the random value of the first one we find for that user.
			$result->orderBy(DB::raw('(
						SELECT reviews_sub.random FROM reviews AS reviews_sub
							INNER JOIN `answers` as answers_sub on `reviews_sub`.`answer_id` = `answers_sub`.`id`
							INNER JOIN `questions` as questions_sub on answers_sub.question_id = `questions_sub`.`id`
							WHERE answers_sub.user_id = answer_user_id
								AND reviews_sub.user_id = '.$user->id.'
								AND ( questions_sub.id = the_question_id OR questions_sub.master_question_id = the_question_id )
							ORDER BY reviews_sub.id
							LIMIT 1
					)'));			

			
		} else {
			
			// make a select answer_user ids. The user is the user who wrote the answer. We treat assignments as a whole here.
			$result = DB::table('questions')
			->select('answers.user_id as answer_user_id')
			->distinct()
			
			->leftJoin('questions as master_questions', 'questions.master_question_id', '=', 'master_questions.id')
			->join('answers', 'questions.id','=','answers.question_id')
			
			->where(function($q) {
				$q->where('master_questions.assignment_id', $this->id)->orWhere('questions.assignment_id',$this->id);
			})
			->where('answers.submitted', PlatypusBool::true);
				
			
			if($reviewsOnly) {
				$result
				->join('reviews', 'answers.id','=','reviews.answer_id')
				->where('reviews.user_id', $user->id);
			
				if(!$showCompleted) {
					$result->where('reviews.status', ReviewStatus::task);
				}
			
			} else {
				$result->leftJoin('question_marks', function($q) {
					$q->on('question_marks.question_id', '=', 'questions.id')
					->on('question_marks.user_id', '=', 'answers.user_id');
				});
			
				if(!$showCompleted) {
					$result->whereNull('question_marks.question_id');
					
				} else {
					// join the reviews table anyway for sorting.
					$result->leftJoin('reviews', function($q) use($user) {
						$q->on('answers.id','=','reviews.answer_id')
							->where('reviews.user_id', '=', $user->id);
						});
				}
			}			
			
						
			// if we are a tutor or lecturer, we want to show those with less missing reviews first.
			if ($this->isLecturer($user) || ($this->tutorsCanSeeStudentReviews() && $this->isTutor($user))) {
				
				if ($showCompleted) {
					if($reviewsOnly) {
						$result->orderBy('reviews.time_submitted', 'desc');
					} else {
						$result->orderBy(DB::raw('GREATEST(COALESCE(reviews.time_submitted, \'0000-01-01\'), COALESCE(question_marks.updated_at,\'0000-01-01\'))'), 'desc');
					}
				}
				
				// order by the number of missing reviews for the lecturers/tutor's convenience
				$result->orderBy(DB::raw('(
						SELECT count(reviews_sub.id) FROM reviews AS reviews_sub
							INNER JOIN `answers` as answers_sub on `reviews_sub`.`answer_id` = `answers_sub`.`id`
							INNER JOIN `questions` as questions_sub on answers_sub.question_id = `questions_sub`.`id`
							LEFT JOIN `questions` as questions_master_sub on questions_sub.master_question_id = `questions_master_sub`.`id`
							WHERE answers_sub.user_id = answer_user_id
								AND reviews_sub.user_id <> '.$user->id.'
								AND '.$roleSelector.'
								AND reviews_sub.status = '.ReviewStatus::task.'
								AND ( questions_sub.assignment_id = '.$this->id.' OR questions_master_sub.assignment_id = '.$this->id.' )
					)'));
			}
			
			// now we look for all reviews that a certain user has for a particular assignment. We take the random value of the first one we find for that user.
			$result->orderBy(DB::raw('(
						SELECT reviews_sub.random FROM reviews AS reviews_sub
							INNER JOIN `answers` as answers_sub on `reviews_sub`.`answer_id` = `answers_sub`.`id`
							INNER JOIN `questions` as questions_sub on answers_sub.question_id = `questions_sub`.`id`
							LEFT JOIN `questions` as questions_master_sub on questions_sub.master_question_id = `questions_master_sub`.`id`
							WHERE answers_sub.user_id = answer_user_id
								AND reviews_sub.user_id = '.$user->id.'
								AND ( questions_sub.assignment_id = '.$this->id.' OR questions_sub.master_question_id = '.$this->id.' )
							ORDER BY reviews_sub.id
							LIMIT 1
					)'));
			
				
		}

		// Limit the query if suggesting the number of individual reviews from the group pool
		if ($this->isStudent($user) && (($this->usesGroupMarkMode() && $viewGroup == 0) || !$this->usesGroupMarkMode())) {
			$reviewQuery = DB::table('reviews')
				->select('student_group_memberships.student_group_id')
				->join('users', 'users.id', '=', 'reviews.user_id')
				->join('answers', 'answers.id', '=', 'reviews.answer_id')
				->join('questions', 'questions.id', '=', 'answers.question_id')
				->join('subject_members', 'subject_members.user_id', '=', 'answers.user_id')
				->join('student_group_memberships', 'student_group_memberships.subject_member_id', '=', 'subject_members.id')
				->join('student_groups', 'student_groups.id', '=', 'student_group_memberships.student_group_id')
				->where('reviews.user_id', $user->id)
				->where('questions.assignment_id', $this->id)
				->where('student_groups.assignment_id', $this->id);

			$markedReviewQuery = $reviewQuery;
			$markedCount = 0;
			if ($this->shuffle_mode == AssignmentShuffleMode::wholeassignments) {
				$reviewQuery = $reviewQuery
					->distinct('student_group_memberships.student_group_id');
				$markedReviewQuery = $markedReviewQuery
					->select('student_group_memberships.student_group_id', DB::raw('min(reviews.status) as status_min'))
					->groupby('student_group_memberships.student_group_id');
				foreach($markedReviewQuery->get() as $r) {
					if ($r->status_min == ReviewStatus::completed) {
						$markedResults += 1;
					}
				}
			} else {
				$markedReviewQuery = $markedReviewQuery
					->where('reviews.status', ReviewStatus::completed);
				$markedCount = count($markedResults);
			}

			$totalReviewCount = $reviewQuery->count('student_group_memberships.student_group_id');

			Log::debug('Here');

			Log::debug($markedCount);

			$studentGroup = DB::table('student_groups')
				->select('student_groups.id')
				->join('student_group_memberships', 'student_group_memberships.student_group_id', '=', 'student_groups.id')
				->join('subject_members', 'subject_members.id', '=', 'student_group_memberships.subject_member_id')
				->where('student_groups.assignment_id', $this->id)
				->where('subject_members.user_id', $user->id);

			if ($studentGroup->count() < 1) {
				return $result;
			}

			$studentGroup = $studentGroup->first();

			$groupMembers = DB::table('student_group_memberships')
				->join('subject_members', 'subject_members.id', '=', 'student_group_memberships.subject_member_id')
				->select('subject_members.user_id')
				->where('student_group_memberships.student_group_id', $studentGroup->id);

			$groupCount = $groupMembers->count();

			$groupMembers = $groupMembers->get();
			$groupPos = 0;
			foreach($groupMembers as $member) {
				if ($member->user_id == $user->id){
					break;
				}
				$groupPos += 1;
			}
			//Log::debug('Here');
			if ($groupCount <= 1) {
				return $result;
			}

			$suggestedCount = ceil($totalReviewCount / $groupCount);

			$toShow = 0;
			if ($markedCount < $suggestedCount) {
				$toShow = $suggestedCount - $markedCount;
			}

			if ($this->usesGroupMarkMode() && $showCompleted){
				$toShow = $suggestedCount;
			}
//			Log::debug('Data');
//			Log::debug($groupCount);
//			Log::debug($groupPos);
//			Log::debug($totalReviewCount);
//			Log::debug($groupCount);
//			Log::debug($suggestedCount);
//			Log::debug($markedCount);
//			Log::debug($toShow);
//			Log::debug($showCompleted);
//			Log::debug($this->usesGroupMarkMode());
//			Log::debug($groupPos * $suggestedCount);

			$result->skip($groupPos * $suggestedCount)->take($toShow);
		}

		return $result;
	}
	
	public function getUserReviewsCompletedQuery($user) {
		return $this->getUserReviewsQuery($user)->where('status', ReviewStatus::completed);
	}
	
	public function getUserReviewsCompleted($user) {
		return $this->getUserReviewsCompletedQuery($user)->get();
	}
	
	public function getDispatchedReviewsQuery($user) {
		return $this->allReviews()->whereHas('answer', function($q) use ($user) {
			$q->where('user_id', $user->id);
		});
	}
	
	public function getDispatchedReviews($user) {
		return $this->getDispatchedReviewsQuery($user)->get();
	}
	
	public function getUserQuestionMarks($user) {
		return QuestionMark::whereHas('question', function($q){
			$q->leftJoin('questions as master_questions', 'questions.master_question_id', '=', 'master_questions.id')->where(function($q) {
				$q->where('questions.assignment_id', $this->id)->orWhere('master_questions.assignment_id', $this->id);
			});
		})
		->where('user_id', $user->id)
		->get();		
	}
	
	public function getUserAssignmentMark($user) {
		return $this->assignmentMarks()->where('user_id',$user->id)->first();
	}

	public function setUserAssignmentMarkSave($user, $mark, $automatic = false) {
		$model = $this->getUserAssignmentMark($user);
		if(!isset($model)) {
			$model = new AssignmentMark();
			$model->user_id = $user->id;
			$model->assignment_id = $this->id;
		}
		
		$model->automatic = $automatic;
		$model->mark = $mark;
		$model->save();
	}
	
	
	
	public function isOpenForSubmissions() {
		if ($this->visibility == AssignmentVisibility::hidden) {
			return false;
		}
		
		switch($this->late_policy) {
			case AssignmentLatePolicy::donotaccept:
				if (Carbon::now() <= $this->answers_due) {
					return true;
				} else {
					return false;
				}
				break;
			case AssignmentLatePolicy::acceptbeforemarking:
				if ($this->markingHasStarted()) {
					return false;
				} else {
					return true;
				}
				break;
			case AssignmentLatePolicy::markbytutor:
				return true;
				break;
			case AssignmentLatePolicy::markbylecturer:
				return true;
				break;
		}
		
		// we should never reach this line.
		App::abort(500);
	}
	

	public function logEvent($level, $text) {
		$event = new AssignmentEvent();
		$event->level = $level;
		$event->text = $text;
		$this->events()->save($event);
	}

	// create the initial database tables
	static function createTable() {
		Schema::create('assignments', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
			
			$table->integer('subject_id')->unsigned();
			$table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
			
			$table->string('title', 1000)->index();
			
			$table->integer('introduction_id')->unsigned();
			$table->foreign('introduction_id')->references('id')->on('text_blocks')->onDelete('restrict');			
			
			$table->tinyInteger('offline')->default(PlatypusBool::false);
			$table->tinyInteger('visibility')->default(AssignmentVisibility::hidden);
			$table->timestamp('answers_due')->index();
			$table->tinyInteger('group_work_mode')->default(AssignmentGroupWorkMode::no);
			$table->tinyInteger('group_selection_mode')->default(AssignmentGroupSelectionMode::lecturer);
			$table->integer('group_size_min')->unsigned()->default(2);
			$table->integer('group_size_max')->unsigned()->default(4);
			$table->tinyInteger('group_mark_mode')->default(AssignmentGroupMarkMode::group);
			$table->tinyInteger('guess_marks')->default(AssignmentGuessMarks::no);
			$table->tinyInteger('mark_by_tutors')->default(AssignmentMarkByTutors::all);
			$table->timestamp('tutors_due')->nullable()->index();
			$table->integer('number_of_peers')->unsigned()->default(0);
			$table->timestamp('peers_due')->nullable()->index()->default(NULL);
			$table->tinyInteger('shuffle_mode')->default(AssignmentShuffleMode::shufflequestions);
			$table->tinyInteger('marking_started')->default(PlatypusBool::false);
			$table->timestamp('autostart_marking_time')->nullable()->index()->default(NULL);
			$table->tinyInteger('late_policy')->default(AssignmentLatePolicy::donotaccept);
			$table->tinyInteger('marks_released')->default(AssignmentMarksReleased::none);
			
			$table->index(array (
					'marking_started',
					'autostart_marking_time' 
			));
		});

	}
}


Assignment::$rules = array (
		'subject_id' => 'required|integer',
		'title' => 'required|min:3|max:200',
		'offline' => 'required|boolean',
		// visibility is enum
		'answers_due' => 'required|date|carbon',
		// group_work_mode is enum
		// group_selection_mode is enum
		// group_mark_mode is enum
		'group_size_min' => 'required_if:group_selection_mode,' . AssignmentGroupSelectionMode::selfservice . '|integer|between:1,100',
		'group_size_max' => 'required_if:group_selection_mode,' . AssignmentGroupSelectionMode::selfservice . '|integer|between:2,100|equallarger:group_size_min',
		// guess_marks is enum
		// mark_by_tutors is enum
		'tutors_due' => 'date|carbon|afterfield:answers_due',
		'number_of_peers' => 'integer|between:0,9999',
		'peers_due' => 'required_if_not:number_of_peers,0|date|carbon|afterfield:answers_due',
		// shuffle_mode is enum
		'marking_started' => 'required', 
		'autostart_marking_time' => 'date|carbon|afterequalfield:answers_due|beforefield:peers_due|beforefield:tutors_due|beforefield:peers_due',
// late_policy is enum
// marks_released is enum
);


// Add code to attach a text block if the assignment is new.
Assignment::creating(function($assignment) {
	if (!isset($assignment->introduction_id)) {
		$introduction = new TextBlock;
		$introduction->save();
		$assignment->introduction_id = $introduction->id;
	}
});


// Add code to delete groups if group mode has been disabled.
Assignment::saved(function($assignment) {
	if (!$assignment->usesGroups()) {
		StudentGroup::where('assignment_id', $assignment->id)->delete();
		StudentGroupSuggestion::where('assignment_id', $assignment->id)->delete();
	}
});
	
	
// A presenter that formats the dates when showing them in a view.
class AssignmentPresenter extends PlatypusBasePresenter {

	
	public function answers_due() {
		if (is_null($this->resource->answers_due)) return '';
		return $this->resource->answers_due->format('d/m/Y (H:i:s)');
	}
	
	public function peers_due() {
		if (is_null($this->resource->peers_due)) return '';
		return $this->resource->peers_due->format('d/m/Y (H:i:s)');
	}
	
	public function tutors_due() {
		if (is_null($this->resource->tutors_due)) return '';
		return $this->resource->tutors_due->format('d/m/Y (H:i:s)');
	}
	
	public function autostart_marking_time() {
		if (is_null($this->resource->autostart_marking_time)) return '';
		return $this->resource->autostart_marking_time->format('d/m/Y (H:i:s)');
	}
	
	
	public function group_size_max() {
		if ($this->resource->group_size_max == 0) return '';
		return $this->resource->group_size_max;
	}

	public function group_size_min() {
		if ($this->resource->group_size_min == 0) return '';
		return $this->resource->group_size_min;
	}
	
	public function group_size() {
		if ($this->resource->group_size_min == $this->resource->group_size_max) {
			return $this->resource->group_size_min;
		} else {
			return '' . $this->resource->group_size_min . ' to ' . $this->resource->group_size_max;
		}
		
	}

	public static $explainGuessMarks = array (
				AssignmentGuessMarks::yes => "The students must guess their own marks when submitting an answer.",
				AssignmentGuessMarks::optional => "The students can optionally guess their own marks when submitting an answer.",
				AssignmentGuessMarks::no => "The students aren't asked to guess their own marks when submitting an answer.",
	);
	
	public static $explainVisibility = array (
			AssignmentVisibility::hidden => 'The assignment is invisible to students and tutors.',
			AssignmentVisibility::posted => 'The assignment is active and the questions are visible to students.', 
			AssignmentVisibility::withsolutions => 'The assignment is active and the questions are visible to students. The solutions are shown after the marking process has started.',
			AssignmentVisibility::withmarkingscheme => 'The assignment is active and the questions are visible to students. The solutions and marking scheme are shown after the marking process has started.', 
	);
	
	public static $explainLatePolicy = array (
			AssignmentLatePolicy::donotaccept => 'Do not accept late submissions of student answers.',
			AssignmentLatePolicy::acceptbeforemarking => 'Accept late submissions as long as marking hasn\'t started.',
			AssignmentLatePolicy::markbytutor => 'Accept late submissions. If marking has started they will be marked by the tutors (no peer review).',
			AssignmentLatePolicy::markbylecturer => 'Accept late submissions. If marking has started they will be marked by the lecturer only.',
			);
	
	public static $explainShuffleMode = array (
			AssignmentShuffleMode::shufflequestions => 'The questions are shuffled individually before assigning them for peer review.',
			AssignmentShuffleMode::wholeassignments => 'Assignment sheets are kept together when assigning them for peer review.',
	);

	public static $explainMarkByTutors = array (
			AssignmentMarkByTutors::none => 'No marking is done by the tutors.',
			AssignmentMarkByTutors::all => 'All student answers have to be marked by the tutors. Tutors can see and rate peer reviews.',
			AssignmentMarkByTutors::allNoRateReviews => 'All student answers have to be marked by the tutors. Tutors can see but not rate peer reviews.',
			AssignmentMarkByTutors::allNoViewReviews => 'All student answers have to be marked by the tutors. Tutors cannot see peer reviews.',			
	);

	public static $explainMarksReleased = array (
			AssignmentMarksReleased::none => 'Do not show any reviews or marks to students.',
			AssignmentMarksReleased::rapidrelease => 'Students can see the available reviews they received after they finished their marking tasks.',
			AssignmentMarksReleased::rapidreleasequestionmarks => 'Students can see their received reviews and marks for questions after they finished their marking tasks.',
			AssignmentMarksReleased::rapidreleasefinal => 'Students can see all their results after they finished their marking tasks.',
			AssignmentMarksReleased::reviews => 'All students can see the reviews they received.',
			AssignmentMarksReleased::questionmarks => 'All students can see their received reviews and marks for questions.',
			AssignmentMarksReleased::finalmarks => 'All final marks and reviews are released.' 
	);

	public static $explainGroupWorkMode = array (
			AssignmentGroupWorkMode::no => 'No group work allowed.',
			AssignmentGroupWorkMode::individualsolutions => 'Group work allowed, but individual submissions are required.',
			AssignmentGroupWorkMode::groupsolutions => 'Group work allowed, only one submission per group is allowed.', 
	);

	public static $explainGroupSelectionMode = array (
			AssignmentGroupSelectionMode::lecturer => 'Groups are only assigned by the lecturer.',
			AssignmentGroupSelectionMode::selfservice => 'Students can self-assign their groups.',
	);

	public static $explainGroupMarkMode = array(
			AssignmentGroupMarkMode::group => 'Assignments are marked by the group. The group can see all assigned reviews, with each student allotted a suggested amount of reviews.',
			AssignmentGroupMarkMode::individual => 'Reviews are assigned to the individual to be marked individually.',
	);
	
	
	public function getEditorWarnings() {
		$result = array ();
		

		if ($this->resource->questions()->count() > 0) {
			// Let's see if the percentages sum up to 100.
			$percentageSum = 0;
			$autoQuestionsAvailable = false;
			foreach ( $this->resource->questions as $question ) {
				if ($question->useAutoMarkPercentage()) {
					$autoQuestionsAvailable = true;
				} else {
					$percentageSum += $question->mark_percentage;
				}
			}
			if (($percentageSum > 100.00001) || (($percentageSum < 100.0001) && ! $autoQuestionsAvailable)) {
				$result [] = "The marking percentages of the questions sum up to " . roundPercentage($percentageSum) . "%. This should be 100%.";
			}
			
			// Let's do it again for all master-questions.
			foreach ( $this->resource->questions as $masterQuestion ) {
				if ($masterQuestion->isMaster()) {
					if ($masterQuestion->subquestions()->count() == 0) {
						$result [] = "Question " . $masterQuestion->position . " does not have sub-questions.";
					} else {
						$percentageSum = 0;
						$autoQuestionsAvailable = false;
						foreach ( $masterQuestion->subquestions as $question ) {
							if ($question->useAutoMarkPercentage()) {
								$autoQuestionsAvailable = true;
							} else {
								$percentageSum += $question->mark_percentage;
							}
						}
						if (($percentageSum > 100.00001) || (($percentageSum < 100.0001) && ! $autoQuestionsAvailable)) {
							$result [] = "The marking percentages of the sub-questions of question " . $masterQuestion->position . " sum up to " . roundPercentage($percentageSum) . "%. This should be 100%.";
						}
					}
				}
			}
		} else {
			$result [] = "The assignment does not contain any questions.";
		}
		
		return $result;
	}

	public function status() {
		if (!$this->isVisible()) return 'Hidden';
		
		$user = Auth::user();
		
		if ($this->markingHasStarted()) {
			if($this->isStudent($user) || $this->isTutor($user)) {
				$numberDue = $this->allReviews()->where('user_id', Auth::user()->id)->where('status', ReviewStatus::task)->count();
				if($numberDue > 0) {
					if($this->isStudent($user)) {
						$dueDate = $this->peers_due;
					} else {
						$dueDate = $this->tutors_due;
					}
					$s = ($numberDue == 1) ? '' : 's';
					return "$numberDue review$s due on $dueDate"; 
				}
			}
			
			if($this->isStudent($user)) {
				if($this->maySeeReceivedFinalMark($user)) {
					$mark = $this->getUserAssignmentMark($user);
					if(isset($mark)) {
						return "Final mark: ".$mark->presenter()->mark;
					}
				}
			}
			
			if($this->allMarksAreReleased()) {
				return 'Marks released';
			}
			
			return 'Marking has started';
		}
		
		if ($this->isOpenForSubmissions()) {
			
			if($this->isStudent(Auth::user())) {
				$numberDue = $this->numberOfAnswersStudentHasDue(Auth::user());
				if ($numberDue == 0) {
					return "All answers submitted";
				} else if($numberDue < $this->questionsWithSubquestionsWithoutMasters()->count()) {
					$s = ($numberDue == 1) ? '' : 's';
					return "$numberDue answer$s due on ". $this->answers_due;
				}
				
				return "Answers due ". $this->answers_due;
			}
			
			return "Open for submissions";
			 
			
		}
		
		return "Due ". $this->answers_due;
	}
	
}



