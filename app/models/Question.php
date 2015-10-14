<?php
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Carbon\Carbon;
use Platypus\Helpers\PlatypusBool;



class QuestionType extends PlatypusEnum {
	const simple = 0;
	const master = 1;
	const subquestion = 2;
}



class Question extends PlatypusBaseModel {
	
	const autoMarkPercentage = -1;
	
	// The database table used by the model.
	protected $table = 'questions';
	
	// define the types that are taken as enum. These will automatically be validated against the proper range.
	public static $enumColumns = array (
			'type' => 'QuestionType',
	);
	

	// fields we can fill directly from user input.
	protected $fillable = array (
	);
	
	// fields we set with default values
	public static $defaultValues = array(
			'type' => QuestionType::simple,
			'mark_percentage' => Question::autoMarkPercentage,
	);
	
	
	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array (
				'created_at',
				'updated_at',
		);
	}
	

	// define the relationships to other models
	public static $relationsData = array (
			'subject' => array (self::BELONGS_TO, 'Subject', 'foreignKey' => 'subject_id'),
			'assignment' => array (self::BELONGS_TO, 'Assignment', 'foreignKey' => 'assignment_id'),
			'subquestions'  => array(self::HAS_MANY, 'Question', 'foreignKey' => 'master_question_id'),
			'masterQuestion' => array (self::BELONGS_TO, 'Question', 'foreignKey' => 'master_question_id'),
			'text' => array (self::BELONGS_TO, 'TextBlock', 'foreignKey' => 'text_id'), 
			'solution' => array (self::BELONGS_TO, 'TextBlock', 'foreignKey' => 'solution_id'), 
			'markingScheme' => array (self::BELONGS_TO, 'TextBlock', 'foreignKey' => 'marking_scheme_id'), 
			'answers' => array (self::HAS_MANY, 'Answer', 'foreignKey' => 'question_id'), 
			'answerRestriction' => array (self::BELONGS_TO, 'TextBlockRestriction', 'foreignKey' => 'answer_restriction_id'),
			'questionMarks' =>  array (self::HAS_MANY, 'QuestionMark', 'foreignKey' => 'question_id'), 
			'solutionEditor' => array (self::BELONGS_TO, 'SubjectMember', 'foreignKey' => 'solution_editor_id'),
	);
	
	// pseudo relations
	// This relations returns the subquestions correctly ordered as they should be displayed.
	public function subquestionsOrdered() {
		return $this->subquestions()->orderBy('position')->orderBy('id');
	}	
	
	public function assignmentReal() {
		if ($this->isSubquestion()) {
			return $this->master_question->assignment();
		} else {
			return $this->assignment();
		}
	}
	
	// in case of master questions we give all answers that belong to sub-questions. 
	public function submittedAnswers() {
		if ($this->isMaster()) {
			return Answer::where('submitted', PlatypusBool::true)->whereHas('question', function($q) {
				$q->where('master_question_id', $this->id);
			});
		} else {
			return $this->answers()->where('submitted', PlatypusBool::true);
		}
	}
	
	public function reviews() {
		return Review::whereHas('answer', function($q) {
			$q->where('question_id', $this->id);
		});
	}
	
	public function reviewTasks() {
		return $this->reviews()->where('status', ReviewStatus::task);
	}
	
	// set the validation rules that apply to this model.
	public static $rules = array (
	);

	// point out that this model uses a presenter to tweak fields whe n showing them in views.
	public static $presenterClass='QuestionPresenter';
	
	

	
	public function getNextUnusedSubquestionPosition() {
		$maxUsed = $this->subquestions()->max('position');
		if (is_null($maxUsed)) {
			return 1;
		} else {
			return $maxUsed + 1;
		}
	}
	
	public function isMaster() {
		return $this->type == QuestionType::master;
	}
	
	public function isSubquestion() {
		return $this->type == QuestionType::subquestion;
	}
	
	public function hasSolution() {
		if(!$this->solution->isEmpty()) return true;
		if($this->isMaster()) {
			foreach($this->subquestions as $question) {
				if($question->hasSolution()) return true;
			}
		}
		return false;
	}
	
	public function hasMarkingScheme() {
		if(!$this->marking_scheme->isEmpty()) return true;
		if($this->isMaster()) {
			foreach($this->subquestions as $question) {
				if($question->hasMarkingScheme()) return true;
			}
		}
		return false;
	}
	
	public function mayViewQuestion(User $user) {
		if ($user->isAdmin()) return true;
		if ($this->subject->isLecturer($user)) return true;
		if ($this->assignment_real->mayViewAssignment($user)) return true;
		return false;		
	}
	
	public function mayViewSolution(User $user) {
		if ($this->assignment_real->mayViewAllSolutions($user)) return true;
		if ($this->reviews()->where('user_id', $user->id)->exists()) return true;
		return false;		
	}
	
	public function mayViewAllMarkingSchemes(User $user) {
		if ($this->assignment_real->mayViewAllSolutions($user)) return true;
		if ($this->reviews()->where('user_id', $user->id)->exists()) return true;
		return false;
	}
	
	public function mayViewMarkingScheme(User $user) {
		if ($this->assignment_real->mayViewAllMarkingSchemes($user)) return true;
		if ($this->reviews()->where('user_id', $user->id)->exists()) return true;
		return false;
	}	
	
	public function mayEditQuestion(User $user) {
		return $this->assignment_real->mayEditQuestions($user);
	}
		
	public function mayEditSolution(User $user) {
		if ($this->assignment_real->mayEditSolutions($user)) return true;
		if ($this->isSolutionEditor($user)) return true;
		return false;
	}
	
	public function mayEditMarkingScheme(User $user) {
		return $this->assignment_real->mayEditMarkingSchemes($user);
	}
	
	public function mayEditQuestionPercentage(User $user) {
		return $this->assignment_real->mayEditQuestionPercentages($user);
	}
	
	
	public function hasAnswers() {
		return $this->answers()->exists();
	}
	
	public function hasNonEmptyAnswers() {
		foreach($this->answers as $answer) {
			if (!$answer->isEmpty()) return true;
		}
		return false;
	}
	
	public function useAutoMarkPercentage() {
		return $this->mark_percentage == static::autoMarkPercentage;
	}
	
	public function getAssignmentReal() {
		if ($this->isSubquestion()) {
			$result =  $this->master_question->assignment;
		} else {
			$result =  $this->assignment;
		}
		if (is_a($result, 'PlatypusBasePresenter')) {
			return $result->resource;
		} else {
			return $result;
		}
	}
	
	public function belongsToAssignment(Assignment $assignment) {
		return ($this->getAssignmentReal()->id == $assignment->id);
	}
	
	public function belongsToAssignmentOrFail(Assignment $assignment) {
		if (!$this->belongsToAssignment($assignment)) {
			App::abort(404);
		}
	}
	
	public function getSolutionEditorUser() {
		if (isset($this->solution_editor_id)) {
			return $this->solution_editor->user;
		} else {
			return null;
		}
	}
	
	public function isSolutionEditor(User $user) {
		if (isset($this->solution_editor_id)) {
			return $this->solution_editor->user_id = $user->id;
		} else {
			return false;
		}
	}
	
	
	public function getAnswer($user) {
		return $this->answers()->where('user_id',$user->id)->first();
	}
	
	public function getSubmittedAnswer($user) {
		return $this->submittedAnswers()->where('user_id',$user->id)->first();
	}
		
	
	public function hasAnswer($user) {
		return $this->answers()->where('user_id',$user->id)->exists();
	}
	
	public function hasSubmittedAnswer($user) {
		return $this->submittedAnswers()->where('user_id',$user->id)->exists();
	}
	
	public function getSubmittedGroupAnswer(User $user) {
		
		if(!$this->assignment_real->onlyOneAnswerPerGroup()) return null;
		
		$group = $this->assignment_real->getGroup($user);
		if(!isset($group)) return null;
		
		return $this->submittedAnswers()->whereHas('user', function($q) use ($group) {
			$q->whereHas('subjectMembers', function($q) use($group) {
				$q->whereHas('studentGroups', function($q) use($group) {
					$q->where('student_group_id', $group->id);
				});
			});
		})->first();
	}
	
	
	
	public function createOrGetAnswerSave($user) {
		$answer = $this->getAnswer($user);
		
		if (is_null($answer)) {
			$answer = new Answer();
			$answer->user_id = $user->id;
			$this->answers()->save($answer);
			
		}
		
		return $answer;
	}
	
	public function getUserReviewsOneUserOrderedQuery($user_id, $answer_user_id) {
		if ($user_id instanceof User) {
			$user_id = $user_id->id;
		}
	
		if ($answer_user_id instanceof User) {
			$answer_user_id = $answer_user_id->id;
		}
		
		if ($this->isMaster()) {
	
			$result = Review::where('user_id',$user_id)
				->whereHas('answer', function($q) use($answer_user_id) {
					$q->whereHas('question', function ($q) {
						$q->where('master_question_id', $this->id);
					})
					->where('user_id',$answer_user_id);
				});
				
			// order by sub question order
			$result->orderBy(DB::raw('(
					SELECT questions.position FROM answers
						INNER JOIN questions ON answers.question_id = questions.id
						WHERE answers.id = reviews.answer_id
				)'));
			
		} else {
			$result = Review::where('user_id',$user_id)
			->whereHas('answer', function($q) use($answer_user_id) {
				$q->where('question_id', $this->id)
				->where('user_id',$answer_user_id);
			});
		}
		
		return $result;
	
	}	
	
	public function getAllCompletedReviewsForUser(User $user) {
		return Review::
			where('status', ReviewStatus::completed)
			->whereHas('answer', function($q) use($user) {
				$q->where('question_id', $this->id)->where('user_id',$user->id);
			})
			->get();
	}
	
	public function getAllCompletedReviewsFromUser(User $user) {
		return Review::
			where('status', ReviewStatus::completed)
			->where('user_id', $user->id)
			->whereHas('answer', function($q) {
				$q->where('question_id', $this->id);
			})
			->get();
	}
	
	public function getAllReviewsFromUser(User $user) {
		return Review::
		where('user_id', $user->id)
		->whereHas('answer', function($q) {
			$q->where('question_id', $this->id);
		})
		->get();
	}	
	
	public function getUserMarkModel(User $user) {
		return $this->questionMarks()->where('user_id', $user->id)->first();
	}
	
	public function getUserMark(User $user) {
		$model = $this->getUserMarkModel($user);
		if(isset($model)) {
			return $model->mark;
		} else {
			return null;
		}
	}

	public function setUserMarkSave(User $user, $mark, &$deletedReviewIds = 'DELETED_REVIEW_IDS_NOT_NEEDED') {
		$model = $this->getUserMarkModel($user);
		
		if(!isset($model)) {
			$model = new QuestionMark();
			$model->question_id = $this->id;
			$model->user_id = $user->id;
		}		

		$model->mark = $mark;
		
		if($deletedReviewIds != 'DELETED_REVIEW_IDS_NOT_NEEDED') {
			$deletedReviewIds = $model->reviewsToDeleteIfSaved()->lists('id');
		} else {
			$deletedReviewIds = array();
		}
		
		if($model->save()) {
			$this->invalidateRelations();
		} else {
			$deletedReviewIds = array();
		}		
		
	} 
	
	public function getMarkPercentageGlobal() {
		$percentages = $this->assignment_real->getQuestionPercentages();
		if ($this->isSubquestion()) {
			return $percentages[$this->id] * $percentages[$this->master_question->id] / 100;
		} else {
			return $percentages [$this->id];
		}
	}
	
	// create the initial database tables
	static function createTable() {
		Schema::create('questions', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
			
			$table->tinyInteger('type');
			
			$table->integer('subject_id')->unsigned();
			$table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
			
			$table->integer('assignment_id')->unsigned()->nullable()->default(NULL);
			$table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
				
			$table->integer('master_question_id')->unsigned()->nullable()->default(NULL);
			$table->foreign('master_question_id')->references('id')->on('questions')->onDelete('cascade');
			
			$table->integer('position')->unsigned();		

			$table->double('mark_percentage')->default(Question::autoMarkPercentage);
			
			$table->integer('solution_editor_id')->unsigned()->nullable()->default(NULL);
			$table->foreign('solution_editor_id')->references('id')->on('subject_members')->onDelete('set null');
			
			$table->integer('text_id')->unsigned();
			$table->foreign('text_id')->references('id')->on('text_blocks')->onDelete('restrict');
			
			$table->integer('solution_id')->unsigned();
			$table->foreign('solution_id')->references('id')->on('text_blocks')->onDelete('restrict');
			
			$table->integer('marking_scheme_id')->unsigned();
			$table->foreign('marking_scheme_id')->references('id')->on('text_blocks')->onDelete('restrict');

			$table->integer('answer_restriction_id')->unsigned();
			$table->foreign('answer_restriction_id')->references('id')->on('text_block_restrictions')->onDelete('restrict');
		});
		
	}
}



// Add code to the hook to attach the text blocks if the question is new.
Question::creating(function($question) {
	
	if (!isset($question->text_id)) {
		$block = new TextBlock;
		$block->save();
		$question->text()->associate($block);
	}
	
	if (!isset($question->solution_id)) {
		$block = new TextBlock;
		$block->save();
		$question->solution()->associate($block);
	}
	
	if (!isset($question->marking_scheme_id)) {
		$block = new TextBlock;
		$block->save();
		$question->markingScheme()->associate($block);
	}
	
	if (!isset($question->answer_restriction_id)) {
		$restriction = new TextBlockRestriction;
		$restriction->save();
		$question->answerRestriction()->associate($restriction);
	}
	
});
	

// A presenter for the question
class QuestionPresenter extends PlatypusBasePresenter {

	public function position() {
		if ($this->resource->isSubquestion()) {
			$result = 'a';
			$count = 1;
			while ($count < $this->resource->position) {
				$result++;
				$count++;
			}
			return $result;
		} else {
			return $this->resource->position;
		}
	}
	
	public function full_position() {
		if ($this->resource->isSubquestion()) {
			return $this->resource->master_question->presenter()->position . $this->position;
		} else {
			return $this->position;
		}
	}
	
	public function mark_percentage_mode() {
		if ($this->resource->useAutoMarkPercentage()) {
			return "auto";		
		} else {
			return "fixed";
		}
	}
	
	public function mark_percentage() {
		return roundPercentage($this->resource->getAssignmentReal()->getQuestionPercentages()[$this->id]);
	}
	
	public function mark_percentage_global() {
		return roundPercentage($this->resource->getMarkPercentageGlobal());
	}
	
	public function solution_editor_name() {
		$user = $this->resource->getSolutionEditorUser();
		if(isset($user)) {
			return $user->presenter()->name;
		} else {
			return '---';
		}
	}
};


