<?php
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Carbon\Carbon;
use Platypus\Helpers\PlatypusBool;



class QuestionMark extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'question_marks';
	
	// define the types that are taken as enum. These will automatically be validated against the proper range.
	public static $enumColumns = array ();	

	// fields we can fill directly from user input.
	protected $fillable = array ();
	
	// fields we set with default values
	public static $defaultValues = array();
	
	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array (
				'created_at',
				'updated_at',
		);
	}
	
	// define the relationships to other models
	public static $relationsData = array (
			'user' => array (self::BELONGS_TO,'User', 'foreignKey' => 'user_id'),
			'question' => array (self::BELONGS_TO, 'Question', 'foreignKey' => 'question_id'),
	);	
	
	// pseudo relationhips go here
	public function assignment() {
		return $this->question->assignmentReal();
	}
	
	public function reviewsToDeleteIfSaved() {
		return $this->question->reviews()
			->whereHas('answer', function($q) {
				$q->where('user_id', $this->user_id);
			})
			->where('status', ReviewStatus::task);		
	}
	
	// set the validation rules that apply to this model.
	// the rules are set below. This is a workaround for a stupid php limitation.
	public static $rules = array (
		'mark' => 'numeric|min:0|max:9999'
	);

	protected function prepareForValidation() {
		$rules['mark'] = sprintf('numeric|min:0|max:%u', $this->assignment->mark_limit);
	}
	
	// point out that this model uses a presenter to tweak fields whe n showing them in views.
	public static $presenterClass='QuestionMarkPresenter';

	// create the initial database tables
	static function createTable() {
		Schema::create('question_marks', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
			
			$table->integer('question_id')->unsigned();
			$table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
			
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
			
			$table->float('mark');
		});
	}
}

QuestionMark::saved(function($mark) {
	$mark->reviewsToDeleteIfSaved()->delete();
	
	AssignmentMark::updateAutomaticMark($mark->assignment, $mark->user);
});

QuestionMark::deleted(function($mark) {
	AssignmentMark::updateAutomaticMark($mark->assignment, $mark->user);
});
	


// A presenter that formats the dates when showing them in a view.
class QuestionMarkPresenter extends PlatypusBasePresenter {
	public function mark() {
		if (is_null($this->resource->mark)) {
			return null;
		} else {
			return roundPercentage($this->resource->mark);
		}
	}
}



