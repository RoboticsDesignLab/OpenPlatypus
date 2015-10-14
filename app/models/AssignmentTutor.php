<?php
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Carbon\Carbon;
use Platypus\Helpers\PlatypusBool;


class AssignmentTutor extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'assignment_tutors';
	
	// define the relationships to other models
	public static $relationsData = array (
			'assignment' => array (self::BELONGS_TO,'Assignment', 'foreignKey' => 'assignment_id'),
			'question' => array (self::BELONGS_TO,'Question', 'foreignKey' => 'question_id'),
			'subjectMember'  => array(self::BELONGS_TO, 'SubjectMember', 'foreignKey' => 'subject_member_id'),
	);	

	public function user() {
		return User::whereHas('subjectMembers', function($q) {
			$q->where('id',$this->subject_member_id);
		});
	} 
	
	
	// set the validation rules that apply to this model.
	// the rules are set below. This is a workaround for a stupid php limitation.
	public static $rules = array ();
	
	// point out that this model uses a presenter to tweak fields whe n showing them in views.
	public static $presenterClass='AssignmentTutorPresenter';
	
	
	// create the initial database tables
	static function createTable() {
		
		Schema::create('assignment_tutors', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->integer('assignment_id')->unsigned();
			$table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
			
			$table->integer('subject_member_id')->unsigned();
			$table->foreign('subject_member_id')->references('id')->on('subject_members')->onDelete('cascade');
			
			$table->integer('question_id')->unsigned()->nullable();
			$table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
			
			$table->unique(array (
					'assignment_id',
					'subject_member_id',
					'question_id',
			), 'assignment_tutors_all_unique'); // give it a name, otherwise mysql complains about the name being too long...
			
			
		});		
		
	}
}

	
	
// A presenter that formats the dates when showing them in a view.
class AssignmentTutorPresenter extends PlatypusBasePresenter {
}



