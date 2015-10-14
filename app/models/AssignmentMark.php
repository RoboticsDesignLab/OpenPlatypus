<?php
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Carbon\Carbon;
use Platypus\Helpers\PlatypusBool;



class AssignmentMark extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'assignment_marks';
	
	// define the types that are taken as enum. These will automatically be validated against the proper range.
	public static $enumColumns = array (
		'automatic' => 'PlatypusBool',
	);	

	// fields we can fill directly from user input.
	protected $fillable = array ();
	
	// fields we set with default values
	public static $defaultValues = array(
		'automatic' => true,
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
			'user' => array (self::BELONGS_TO,'User', 'foreignKey' => 'user_id'),
			'assignment' => array (self::BELONGS_TO, 'Assignment', 'foreignKey' => 'assignment_id'),
	);	
	
	// pseudo relationhips go here

	
	// set the validation rules that apply to this model.
	// the rules are set below. This is a workaround for a stupid php limitation.
	public static $rules = array (
		'mark' => 'numeric|min:0|max:100'
	);
	
	// point out that this model uses a presenter to tweak fields whe n showing them in views.
	public static $presenterClass='AssignmentMarkPresenter';
	
		
	public function isAutomatic() {
		return (bool) $this->automatic;
	}
	
	// this function is called to check if an automatic mark is to be created or updated.
	static function updateAutomaticMark(Assignment $assignment, User $user) {
		
		if (!$assignment->markingHasStarted()) {
			// we aren't in the marking phase yet, so do nothing.
			return;
		}
		
		$model = static::where('assignment_id', $assignment->id)->where('user_id', $user->id)->first();
		
		if(isset($model) && !$model->isAutomatic()) {
			// the mark was set manually, so don't touch it.
			return;
		}
		
		// check if a mark has been set for all answers.
		if($assignment->studentIsStillWaitingForAnswersToBeMarked($user)) {

			// we have to wait for further question marks. Delete the assignment mark.
			if(isset($model)) {
				$model->delete();
			}			
		} else {
						
			// everything that was submitted seems marked. So we calculate the final mark.
			$mark = 0;
			foreach($assignment->getUserQuestionMarks($user) as $questionMark) {
				$mark += $questionMark->mark * $questionMark->question->getMarkPercentageGlobal() / 100;
			}
			
			// clip to range.
			if ($mark < 0) $mark = 0;
			if ($mark > 100) $mark = 100;
			
			if(isset($model) && ($model->mark == $mark)) {
				// the mark is up to date, so don't do anything.
				return;
			}
			
			if(!isset($model)) {
				// we need to create a new model
				$model = new static;
				$model->user_id = $user->id;
				$model->assignment_id = $assignment->id;
				$model->automatic = true;
			}
				
			// save our value
			$model->mark = $mark;
			$model->save();			
			
			// clear the relations cache.
			$assignment->invalidateRelations();
			$user->invalidateRelations();
		}
		
	}

	// create the initial database tables
	static function createTable() {
		
		Schema::create('assignment_marks', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
				
			$table->tinyInteger('automatic')->default(PlatypusBool::true);
			
			$table->integer('assignment_id')->unsigned();
			$table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
			
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
				
			$table->float('mark');
			
			$table->unique(array (
					'assignment_id',
					'user_id'
			));
			
		});

	}
}



// A presenter that formats the dates when showing them in a view.
class AssignmentMarkPresenter extends PlatypusBasePresenter {
	public function mark() {
		if (is_null($this->resource->mark)) {
			return null;
		} else {
			return roundPercentage($this->resource->mark);
		}
	}
}



