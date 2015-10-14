<?php
use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;
use Illuminate\Database\Eloquent\Model;
use LaravelBook\Ardent\Ardent;
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Platypus\Helpers\PlatypusBool;


new AuthenticationDomain;

class User extends PlatypusBaseModel implements UserInterface, RemindableInterface {
	
	use UserTrait, RemindableTrait;
	
	// The database table used by the model.
	protected $table = 'users';
	
	// the name of our presenter class
	public static $presenterClass = 'UserPresenter';
	
	// fields we can fill directly from user input.
	protected $fillable = array('first_name', 'last_name', 'student_id', 'email', 'password', 'password_confirmation');	
	
	// define the relationships to other models
	public static $relationsData = array(
			'permissions'  => array(self::HAS_MANY, 'Permission'),
			'subjectMembers'  => array(self::HAS_MANY, 'SubjectMember'), 
			//'subjects'  => array(self::BELONGS_TO_MANY, 'Subject', 'table' => 'subject_members'), // Note: use for reading only!!! Don't write via this relationship.
			'reviews' => array (self::HAS_MANY,'Review', 'foreignKey' => 'user_id'),
	);
	
	// pseudo relationships go here
	function subjects() {
		return Subject::whereHas('subjectMembers', function($q) {
			$q->where('user_id', $this->id);	
		});
	}

	function visibleSubjects() {
		return Subject::whereHas('subjectMembers', function($q) {
				$q->where('user_id', $this->id);
			})
			->where(function($q) {
				$q->where('visibility', PlatypusBool::true)
					->orWhereHas('subjectMembers', function($q) {
						$q->where('user_id', $this->id)
							->where('role', '<>', SubjectMemberRoles::student)
							->where('role', '<>', SubjectMemberRoles::tutor);
					});
			});
	}	
	
	function visibleSubjectsOrdered() {
		return $this->visibleSubjects()->orderby('start_date');
	}
		
	// The attributes excluded from the model's JSON form.
	protected $hidden = array (
			'password',
			'remember_token' 
	);
	
	public $autoPurgeRedundantAttributes = true;
	public static $passwordAttributes  = array('password');
	public $autoHashPasswordAttributes = true;

	// define the types that are taken as enum. These will automatically be validated against the proper range.
	public static $enumColumns = array (
			'authentication_domain' => 'AuthenticationDomain',
	);	

	
	public static $rules = array (
			'first_name' => 'required|min:1|max:50',
			'last_name' => 'required|min:1|max:50',
			'student_id' => 'max:50',
			'authentication_id' => 'max:1000',
			'email' => 'required|email|max:200',
			'password' => 'required|min:6|max:50',
			'password_confirmation' => 'required|same:password' 
	); 

	

	public function hasPermission($permission, $default = false) {
		if (!is_integer($permission)) App::abort(500, 'Invalid permission value or type');
		$permissionObject = $this->permissions()->where('permission','=',$permission)->first();
		if (is_null($permissionObject)) return $default;
		return ( $permissionObject->value == PermissionValue::granted );
	}
	
	public function getAuthenticator() {
		return AuthenticationDomainDefinitions::createAuthenticator($this->authentication_domain);
	}
	
	public function userDataChangeable() {
		$result = $this->getAuthenticator()->userDataChangeable();
		if(!is_array($result)) {
			$result = array(
				'first_name' => $result,
				'last_name' => $result,
				'student_id' => $result,
				'email' => $result,
			);
		}
		return $result;
	}
	
	public function userPasswordChangeable() {
		return $this->getAuthenticator()->userPasswordChangeable();
	}
	
	
	public function isAdmin() {
		return $this->hasPermission(PermissionType::admin);
	}
	
	public function isDebugger() {
		return $this->hasPermission(PermissionType::debug);
	}
	
	public function mayCreateClass() {
		if ($this->isAdmin()) return true;
		return $this->hasPermission(PermissionType::create_class);
	}
	
	public function isUserManager() {
		return $this->isAdmin();
	}
	
	
	static function findByEmailOrId($string) {
		$string = trim($string);
		
		if((substr($string, 0, 1) == '#') && (is_numeric(substr($string,1)))) {
			return self::find(substr($string,1));
		}
		
		$candidates = self::where('email', 'LIKE', trim($string));
		if ($candidates->count()!=1) {
			$candidates = self::where('student_id', trim($string));
		}
		if ($candidates->count()!=1) {
			return null;
		} else {
			return $candidates->get()[0];
		}		
	}
	
	static function findByEmailOrIdInSubject($subject, $string) {
		$string = trim($string);
		
		if((substr($string, 0, 1) == '#') && (is_numeric(substr($string,1)))) {
			return $subject->users()->where('user_id', substr($string,1))->first();
		}
		
		$candidates = $subject->users()->where('email', 'LIKE', trim($string));
		if ($candidates->count()!=1) {
			$candidates = $subject->users()->where('student_id', trim($string));
		}
		if ($candidates->count()!=1) {
			return null;
		} else {
			return $candidates->get()[0];
		}
	}
	
	
	static function createTable() {
		Schema::create('users', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
		
			$table->increments('id');
			$table->string('first_name', 1000)->nullable()->index();
			$table->string('last_name', 1000)->nullable()->index();
			$table->string('student_id', 1000)->nullable()->index();
			$table->tinyInteger('authentication_domain');
			$table->string('authentication_id', 1000)->nullable()->index();
			$table->string('password', 1000)->nullable();
			$table->string('email', 200)->unique();
			$table->rememberToken();
		
			$table->index(array (
					'authentication_domain',
					'authentication_id'
			));
		});
	}
	
}


class UserPresenter extends BasePresenter {
	
	public function first_name() {
		return ucfirst($this->resource->first_name);
	}
	
	public function last_name() {
		return ucfirst($this->resource->last_name);
	}

	public function name() {
		$result = $this->first_name . ' ' . $this->last_name;
		if (empty($result)) {
			return '[unknown #' . $this->id . ']';
		} else {
			return $result;
		}
	}
	
	public function permissions() {
		$result = '';
		foreach($this->resource->permissions as $permission) {
			if ($result != '') {
				$result .= ', ';
			}
			$result .= $permission->presenter()->permission_text;			
		}
		return $result;
	}		
	
	public function archive_file_name() {
		$alphas = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'), array('_'));
		foreach($alphas as &$char) { $char="$char"; } unset($char);
		
		$result = $this->name.'_'.$this->id;
		$result = str_split($result);
		
		foreach($result as &$char) {
			if(!in_array($char, $alphas, true)) {
				$char = '_';
			}
		} unset($char);
		
		$result = implode($result);
		$result = preg_replace('/__*/', '_', $result);
		return $result;
	}
	
}

