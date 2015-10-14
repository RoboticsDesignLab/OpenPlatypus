<?php
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Carbon\Carbon;
use Platypus\Helpers\PlatypusBool;


class AssignmentEventLevel extends PlatypusEnum {
	const info = 0;
	const warning = 1;
	const error = 2;
	
	const endOfNormalRange = 100; // we also store submission events, but they will be displayed differently.
	const submission = 101; 
}


class AssignmentEvent extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'assignment_events';
	
	// define the types that are taken as enum. These will automatically be validated against the proper range.
	public static $enumColumns = array (
			'level' => 'AssignmentEventLevel',
	);
	

	// fields we can fill directly from user input.
	protected $fillable = array ();
	
	// fields we set with default values
	public static $defaultValues = array(
			'level' => AssignmentEventLevel::info,
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
			'assignment' => array (self::BELONGS_TO,'Assignment', 'foreignKey' => 'assignment_id'),
	);	
	
	// set the validation rules that apply to this model.
	public static $rules = array ();
	
	// point out that this model uses a presenter to tweak fields whe n showing them in views.
	public static $presenterClass='AssignmentEventPresenter';
	
	
	// create the initial database tables
	static function createTable() {
		Schema::create('assignment_events', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
				
			$table->increments('id');
				
			$table->integer('assignment_id')->unsigned();
			$table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
				
			$table->tinyInteger('level')->default(AssignmentEventLevel::info);				
			
			$table->longText('text');

			$table->index(array (
					'assignment_id',
					'level'
			));
		});
	}
	
	
}




	
// A presenter that formats the dates when showing them in a view.
class AssignmentEventPresenter extends PlatypusBasePresenter {

	public static $presentLevel = array(
			AssignmentEventLevel::info => 'information',
			AssignmentEventLevel::warning => 'warning',
			AssignmentEventLevel::error => 'error',
			AssignmentEventLevel::submission => '',
	);
	
	public function date() {
		if (is_null($this->resource->created_at)) return '';
		return $this->resource->created_at->format('d/m/Y (H:i:s)');
	}
	

}



