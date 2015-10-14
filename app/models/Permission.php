<?php

use Platypus\Helpers\PlatypusEnum;
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;




class Permission extends PlatypusBaseModel {

	// The database table used by the model.
	protected $table = 'permissions';

	// the name of our presenter class
	public static $presenterClass = 'PermissionPresenter';	
	
	// define the types that are taken as enum. These will automatically be validated against the proper range.
	public static $enumColumns = array (
		'value' => 'PermissionValue',
		'permission' => 'PermissionType',
	);
	
	public static $rules = array (
		'value' => 'required',
		'permission' => 'required',
	);
	
	public function user()
	{
		return $this->belongsTo('User');
	}	
	
	public function __construct($permission = NULL, $value = NULL) {
		parent::__construct();
		// We set permission to NULL by default. This way any uninitialised permission will cause an error when stored in the database.
		$this->permission = $permission;
		$this->value = $value;
	}	
	

	
	static function createTable() {
		Schema::create('permissions', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
		
			$table->increments('id');
		
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
		
			$table->integer('permission')->unsigned();
			$table->integer('value')->unsigned();
					
			$table->unique(array (
					'permission',
					'user_id'
			));
		});
	}	
	
}



class PermissionPresenter extends BasePresenter {
	
	
public static $explainPermission = array (
		PermissionType::create_class => "The user may create new classes.",
		PermissionType::admin => "The user is an administrator.",
		PermissionType::debug => "The user is a developer and allowed to access debugging functions.",
);

public function permission_text() {
	switch($this->resource->permission) {
		case PermissionType::create_class:
			return 'Create classes';
			break;
		case PermissionType::admin:
			return 'Administrator';
			break;
		case PermissionType::debug:
			return 'Debugger';
			break;
		default:
			return 'Unknown';
	}
}


}