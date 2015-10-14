<?php
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Carbon\Carbon;
use Platypus\Helpers\PlatypusBool;



class Answer extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'answers';
	
	// define the types that are taken as enum. These will automatically be validated against the proper range.
	public static $enumColumns = array (
			'submitted' => 'PlatypusBool',
	);
	

	// fields we can fill directly from user input.
	protected $fillable = array (
	);
	
	// fields we set with default values
	public static $defaultValues = array(
			'submitted' => PlatypusBool::false,
	);
	
	
	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array (
				'created_at',
				'updated_at',
				'time_submitted'
		);
	}
	

	// define the relationships to other models
	public static $relationsData = array (
			'question' => array (self::BELONGS_TO, 'Question', 'foreignKey' => 'question_id'),
			'user' => array (self::BELONGS_TO, 'User', 'foreignKey' => 'user_id'),
			'text'  => array(self::BELONGS_TO, 'TextBlock', 'foreignKey' => 'answer_text_id'),
			'answerText'  => array(self::BELONGS_TO, 'TextBlock', 'foreignKey' => 'answer_text_id'), // same as text.
			'reviews' => array (self::HAS_MANY, 'Review', 'foreignKey' => 'answer_id'),
	);
	
	// pseudo relations
	public function assignment() {
		return $this->question->assignmentReal();
	}
	

	public function finalMarks() {
		return QuestionMark::where('user_id', $this->user_id)->where('question_id', $this->question_id);
	}

	public function allReviewsOrdered() {
		return $this->reviews()->orderBy('reviewer_role');
	}
	
	
	public function submittedReviewsOrdered() {
		return $this->reviews()->where('status', ReviewStatus::completed)->orderBy('reviewer_role');
	}
	
	// set the validation rules that apply to this model.
	public static $rules = array (
			'guessed_mark' => 'numeric|min:0|max:100',
	);

	// point out that this model uses a presenter to tweak fields whe n showing them in views.
	public static $presenterClass='AnswerPresenter';
	
		
	// this function is called before validation starts.
	protected function prepareForValidation() {
		if ($this->guessed_mark == "") {
			$this->guessed_mark = null;
		}
		
		if (isset($this->guessed_mark) && !$this->studentsMayGuessTheirMarks()) {
			$this->guessed_mark = null;
		}
	}
	
	
	// receives a copy of the current rules for self-validation
	// has to return the new rules.
	// can be overridden in derived models.
	protected function mangleRulesForValidation($rules) {
	
		$newRules = parent::mangleRulesForValidation($rules);
		
		if ($this->studentsMustGuessTheirMarks()) {
			array_unshift($newRules['guessed_mark'], 'required_if:submitted,'.PlatypusBool::true);
		};
	
		return $newRules;
	
	}	
	
	
	public function getReviewQuery(User $user) {
		return $this->reviews()->where('user_id', $user->id);
	}
	
	public function getReview(User $user) {
		return $this->getReviewQuery($user)->first();
	}
	
	
	public function mayView(User $user) {
		// do the obvious checks.
		if ($user->isAdmin()) return true;
		
		if(!$this->assignment->mayViewAssignment($user)) return false; // we can't see the assignment, so we can't see its answers.
		
		if ($this->user->id == $user->id) return true;
		
		if ($this->isSubmitted()) {
			if ($this->assignment->mayBrowseStudents($user)) return true; // we are allowed to see all submitted answers.
			if ($this->assignment->maySetFinalMarks($user)) return true; // we are a super-marker.
			if ($this->getReviewQuery($user)->exists()) return true;	// we are a reviewer for this answer.
		}
		
		return false;
	}
	
	public function mayEdit(User $user) {
		if(!$this->assignment->mayViewAssignment($user)) return false;
		if ($this->submitted) return false;
		if (!$this->assignment->isOpenForSubmissions()) return false;
		if ($this->user->id != $user->id) return false;
		return true;
	}
	
	public function maySubmit(User $user, $allowEmpty = false, &$error = null) {
		$error = null;
		
		if (!$this->question->getAssignmentReal()->isOpenForSubmissions()) {
			$error = 'Assignment closed for submissions.';
			return false;
		}
		
		if (!$this->mayEdit($user)) {
			$error = 'Submission forbidden.';
			return false;
		}

		if (!$allowEmpty) {
			if ($this->text->isEmpty()) {
				$error = 'Submission of empty answer.';
				return false;
			}
		}

		$assignment = $this->question->getAssignmentReal();
		
		if ($assignment->usesGroups()) {
			$group = StudentGroup::findGroup($assignment, $user);
			if (is_null($group)) {
				if (!$assignment->autoCreateSingleGroupsOnSubmission()) {
					$error = 'You must form a student group before submission.';
					return false;
				}
			} else {
				if ($assignment->onlyOneAnswerPerGroup()) {
					if ($group->questionsWithSubmittedAnswers()->where('questions.id', $this->question->id)->exists()) {
						$error = 'There is already a submitted answer for your student group.';
						return false;
					}
				}
			}
		}
		return true;
	}
	
	public function submit(User $user, $force = false) {
		if (!$force) {
			if (!$this->maySubmit($user)) {
				App::abort(500);
			}
		}
				
		$this->submitted = true;
		$this->time_submitted = Carbon::now();
		
	}
	
	public function mayRetract(User $user) {
		if ($this->user->id != $user->id) return false;
		if (!$this->submitted) return false;
		if ($this->assignment->marking_started) return false;
		if (!$this->assignment->isOpenForSubmissions()) return false;
		if (Carbon::now() > $this->assignment->answers_due) return false;
		return true;
	}	
	
	public function retract(User $user) {
		if (!$this->mayRetract($user)) {
			App::abort(500);
		}
	
		$this->submitted = false;
		$this->time_submitted = 0;
		$this->guessed_mark = null;	
	}
	
	
	
	public function studentsMayGuessTheirMarks() {
		return $this->assignment->studentsMayGuessTheirMarks();
	}
	
	public function studentsMustGuessTheirMarks() {
		return $this->assignment->studentsMustGuessTheirMarks();
	}
	
	public function isEmpty() {
		if ($this->submitted) return false;
		if (!$this->text->isEmpty()) return false;
		return true;
	}
	
	public function isLate() {
		if (!$this->submitted) return false;
		return $this->time_submitted->gt($this->assignment->resource->answers_due);
	}

	public function isSubmitted() {
		return (bool) $this->submitted;
	}
	
	
	public function hasFinalMark() {
		return count($this->final_marks) > 0;
	}
	
	public function setFinalMark($mark, &$deletedReviewIds = 'DELETED_REVIEW_IDS_NOT_NEEDED') {
		if($this->assignment->onlyOneAnswerPerGroup()) {
			$group = $this->assignment->getStudentGroup($this->user);
			if(isset($group)) {
				$users = $group->users;
			} else {
				$users = array($this->user);
			} 
		} else {
			$users = array($this->user);
		}
		
		if($deletedReviewIds == 'DELETED_REVIEW_IDS_NOT_NEEDED') {
			$getDeletedReviewIds = false;
		} else {
			$getDeletedReviewIds = true;
			$deletedReviewIds = array();
		}
		
		
		foreach ($users as $user) {
			if($getDeletedReviewIds) {
				$deletedIds = array();
				$this->question->setUserMarkSave($user, $mark, $deletedIds);
				foreach($deletedIds as $id) $deletedReviewIds[] = $id;
			} else {
				$this->question->setUserMarkSave($user, $mark);
			}
		}
	}
	
	
	public function hasGuessedMark() {
		return !is_null($this->guessed_mark);
	}
	
	// create the initial database tables
	static function createTable() {
		Schema::create('answers', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
			$table->timestamp('time_submitted');
			$table->integer('question_id')->unsigned();
			$table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
						
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
			
			$table->tinyInteger('submitted')->default(PlatypusBool::false);
			
			$table->integer('answer_text_id')->unsigned();
			$table->foreign('answer_text_id')->references('id')->on('text_blocks')->onDelete('restrict');
			
			$table->double('guessed_mark')->nullable()->default(NULL);
			
			$table->unique(array (
					'question_id',
					'user_id' 
			));
			$table->index(array (
					'user_id',
					'submitted' 
			));
			$table->index(array (
					'question_id',
					'submitted' 
			));
		});
		
	}
}



// Add code to the hook to attach the text block if the answer is new.
Answer::creating(function($answer) {
	
	if (!isset($answer->answer_text_id)) {
		$block = new TextBlock;
		$block->restriction_id = $answer->question->answer_restriction->id;
		$block->save();
		$answer->text()->associate($block);
	}
	
});

// make sure a group is created on the fly if the assignment is in group work mode and single user groups are allowed.
Answer::saving(function($answer) {
	if ($answer->submitted && $answer->isDirty('submitted')) {
		$assignment = $answer->assignment;
		if ($assignment->usesGroups()) {
			$group = StudentGroup::findGroup($assignment, $answer->user);
			if (is_null($group)) {
				if ($assignment->autoCreateSingleGroupsOnSubmission()) {
					StudentGroup::makeGroup($assignment, array($answer->user));
				}
			}
		}
	}
});

// if the assignment is a late submission, then assign it to a marker.
Answer::saved(function($answer) {
	if($answer->isDirty('submitted')) {
		if($answer->submitted) {
			$message = 'Submission';
		} else {
			$message = 'RETRACTION';
		}
		$message .= ' for question '.$answer->question->presenter()->full_position;
		$message .= ' by ' . $answer->user->presenter()->name . ' ('. $answer->user->presenter()->email .', '. $answer->user->presenter()->student_id .')';
		$answer->assignment->logEvent(AssignmentEventLevel::submission, $message);
	}
	
	if ($answer->submitted && $answer->isDirty('submitted') && $answer->assignment->marking_started) {
		MarkingShuffler::assignLateSubmission($answer);
	}
	
	if($answer->assignment->marking_started) {
		AssignmentMark::updateAutomaticMark($answer->assignment, $answer->user);
	}
});



// A presenter for the question
class AnswerPresenter extends PlatypusBasePresenter {
	
	public function guessed_mark() {
		$value = $this->resource->guessed_mark;
		if (isset($value)){
			return roundPercentage($value);
		} else {
			return '';
		}
	}
	
	public function late_by() {
		if (!$this->resource->isLate()) return '';
		return $this->resource->assignment->resource->answers_due->diffForHumans($this->resource->time_submitted, true);
	}
	
	public function time_submitted() {
		if (is_null($this->resource->time_submitted)) return '';
		return $this->resource->time_submitted->format('d/m/Y (H:i:s)');
	}
	
};


