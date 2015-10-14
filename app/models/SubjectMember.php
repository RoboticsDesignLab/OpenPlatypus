<?php
use Platypus\Helpers\PlatypusEnum;
use Platypus\Helpers\PlatypusBool;



class SubjectMemberRoles extends PlatypusEnum {

	const student = 0;

	const lecturer = 1;

	const tutor = 2;

	const questionobserver = 3;

	const fullobserver = 4;
}



class SubjectMember extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'subject_members';


	public static $enumColumns = array (
			'role' => 'SubjectMemberRoles',
			'suspended' => 'PlatypusBool',
	);

	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array('created_at', 'updated_at');
	}
	
	public static $presenterClass = 'SubjectMemberPresenter';

	
	// define the relationships to other models
	public static $relationsData = array(
			'user'  => array(self::BELONGS_TO, 'User'),
			'subject'  => array(self::BELONGS_TO, 'Subject'),
			'studentGroupSuggestions'  => array(self::BELONGS_TO_MANY, 'StudentGroupSuggestion', 'table' => 'student_group_suggestion_memberships', 'foreignKey' => 'subject_member_id', 'otherKey' => 'student_group_suggestion_id', 'pivotKeys' => array('accepted'), 'timestamps' => true),
			'studentGroups'  => array(self::BELONGS_TO_MANY, 'StudentGroup', 'table' => 'student_group_memberships', 'foreignKey' => 'subject_member_id', 'otherKey' => 'student_group_id', 'timestamps' => true),
			'assignmentTutors'  => array(self::HAS_MANY, 'AssignmentTutor', 'foreignKey' => 'subject_member_id'),
	);	
	
	static function createTable() {
		Schema::create('subject_members', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id'); // this is a membership table, but we do want an id here so we can restrict via foreign key constraints.
			
			$table->integer('subject_id')->unsigned();
			$table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
			
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
			
			$table->tinyInteger('role')->unsigned();
			
			$table->tinyInteger('suspended')->unsigned()->default(PlatypusBool::false);
				
			$table->unique(array (
					'user_id',
					'subject_id' 
			));
			$table->index(array (
					'subject_id',
					'role',
					'suspended',
					'user_id' 
			));
		});
	}
}


class SubjectMemberPresenter extends PlatypusBasePresenter {
	
	public static $presentRole = array(
			SubjectMemberRoles::student => 'student',
			SubjectMemberRoles::lecturer => 'lecturer',
			SubjectMemberRoles::tutor => 'tutor',
			SubjectMemberRoles::questionobserver => 'question observer',
			SubjectMemberRoles::fullobserver => 'full observer',
	);
	
	public static $explainRole = array(
			SubjectMemberRoles::student => 'Student of the class.',
			SubjectMemberRoles::lecturer => 'Lecturer of the class.',
			SubjectMemberRoles::tutor => 'Tutor of the class. Tutors can mark assignments.',
			SubjectMemberRoles::questionobserver => 'Has permission to view assignment questions, solutions and marking schemes. This includes hidden assignments and questions.',
			SubjectMemberRoles::fullobserver => 'Is allowed to view everything the lecturer sees but cannot make changes.',
	);
	
	public function role() {
		return self::$presentRole[$this->resource->role];
	}
	
	public function role_complete() {
		$result = $this->role;
		if ($this->resource->suspended) {
			$result .= ' (suspended)';
		}
		return $result;
	}
	
}
