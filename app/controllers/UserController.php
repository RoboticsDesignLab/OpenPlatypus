<?php



use Platypus\Helpers\PermissionType;

class UserController extends BaseController {

	static function autoPaginateUsers($users) {
		$allowed_columns = [ 
				'first_name',
				'last_name',
				'student_id',
				'email' 
		];
		
		$sort = Session::pull('user_sort', 'last_name');
		$sort = in_array(Input::get('sort'), $allowed_columns) ? Input::get('sort') : $sort;
		Session::put('user_sort', $sort);
		
		$order = Session::pull('user_order', 'asc');
		$order = Input::get('order', $order) === 'desc' ? 'desc' : 'asc';
		Session::put('user_order', $order);
		
		$perpage = Session::pull('user_perpage', 20);
		$perpage = Input::get('perpage', $perpage);
		validateOrAbortSimple($perpage, 'required|integer|min:1|max:1000');
		Session::put('user_perpage', $perpage);
		
		$users = $users->orderBy($sort, $order);
		
		// if we are ordering by the names, then we add a secondary order.
		if ($sort == 'first_name') {
			$users->orderBy('last_name', $order);
		} else if ($sort == 'last_name') {
			$users->orderBy('first_name', $order);
		}
		
		$users = $users->paginate($perpage);
		$users->addQuery('order', $order)->addQuery('sort', $sort)->addQuery('perpage', $perpage);
		
		return $users;
	}
	
	public function showAllUsersForUserManager() {
		return Platypus::transaction(function () {
			if (! Auth::user()->isUserManager()) {
				App::abort(403);
			}
			
			$users = User::query();
			
			$users = static::autoPaginateUsers($users);
			
			return View::make('user.user_list_for_manager', array (
					'users' => $users,
			));
		});
	}

	public function createUser() {
		return Platypus::transaction(function () {
			if (! Auth::user()->isUserManager()) {
				App::abort(403);
			}
								
			$authenticators = AuthenticationDomainDefinitions::getDomainAuthenticators();
			if (count($authenticators) == 1 ) {
				return View::make('user.user_new');
			} else {
				return View::make('user.user_new')->withAuthenticators($authenticators);
			}
			
			
		});
	}
	
	public function createUserPost() {
		return Platypus::transaction(function () {
			if (! Auth::user()->isUserManager()) {
				App::abort(403);
			}
			
			$onlyValues = array (
					'first_name',
					'last_name',
					'email',
					'student_id',
					'domain'
			);
			$input = array_map('trim', Input::only($onlyValues));
			
			if (!isset($input['domain']) || ($input['domain']=='')  ) {
				$authenticators = AuthenticationDomainDefinitions::getDomainAuthenticators();
				if (count($authenticators) == 1 ) {
					$input['domain'] = $authenticators[0][0];
				}
			}
			
			$rules = array ();
			$rules ["first_name"] = User::$rules ["first_name"];
			$rules ["last_name"] = User::$rules ["last_name"];
			$rules ["student_id"] = User::$rules ["student_id"];
			$rules ["email"] = User::$rules ["email"];
			$rules ["domain"] = 'required|enumrange:AuthenticationDomain';
			
			$validator = Validator::make($input, $rules);
			
			if ($validator->passes()) {
				$authenticator = AuthenticationDomainDefinitions::createAuthenticator($input ['domain']);
				
				if ($authenticator->userIsValidToBeCreated($input ['first_name'], $input ['last_name'], $input ['student_id'], $input ['email'])) {
					$user = $authenticator->createUser($input ['first_name'], $input ['last_name'], $input ['student_id'], $input ['email']);
					
					return Redirect::route('editUser', $user->id)->with('success', 'The user account has been created.');
				} else {
					
					Input::flashOnly($onlyValues);
					return Redirect::route('newUser')->with('danger', 'The user account could not be created. Maybe the user exists already?');
				}
				
			}

			Input::flashOnly($onlyValues);
			return Redirect::route('newUser')->withErrors($validator);
		});
	}
	
	public function editUser($user_id) {
		return Platypus::transaction(function () use($user_id) {
			if (! Auth::user()->isUserManager()) {
				App::abort(403);
			}
			
			$user = User::findOrFail($user_id);
			
			return View::make('user.user_edit')->with('user', $user)->with('userDataChangeable', $user->userDataChangeable());
		});		
	} 
	
	
	public function editUserPost($user_id) {
		return Platypus::transaction(function () use($user_id) {
			if (! Auth::user()->isUserManager()) {
				App::abort(403);
			}
			
			$user = User::findOrFail($user_id);
			
			$valid = true;
				
			// then handle changing the user data.
			$onlyValuesBase = array('first_name', 'last_name', 'email', 'student_id');
			$onlyValues = array();
			$userDataChangeable = $user->userDataChangeable();
			foreach($onlyValuesBase as $field) {
				if($userDataChangeable[$field]) $onlyValues[] = $field; 
			}
			
			if (!empty($onlyValues) || $user->userPasswordChangeable()) {
				
				$flashOnlyValues = $onlyValues;
				
				if ($user->userPasswordChangeable()) {
					$onlyValues [] = 'password';
					$onlyValues [] = 'password_confirmation';
				}
				
				$input = Input::only($onlyValues);
				
				if (empty($input['password'])) {
					unset($input['password']);
					unset($input['password_confirmation']);
				}
				
				$user->fill($input);
				
				if (! $user->validate()) {
					$valid = false;
				}
			}
			$permissions = array (
					PermissionType::admin => 'admin',
					PermissionType::create_class => 'create_class', 
					PermissionType::debug => 'debug',
			);
			
			$permissionChanges = array ();
			
			foreach ( $permissions as $key => $value ) {
				$flashOnlyValues[] = $value;
				if (! Input::has($value)) {
					continue;
				}
				$granted = (Input::get($value) == "1") ? true : false;
				if ($user->hasPermission($key) != $granted) {
					$permissionChanges [$key] = $granted;
				}
			}
			
			// ensure there is at least one admin left at all times.
			if (isset($permissionChanges [PermissionType::admin]) && ($permissionChanges [PermissionType::admin] == false)) {
				$numberOfAdmins = User::whereHas('permissions', function ($q) {
					$q->where('permission', PermissionType::admin)->where('value', PermissionValue::granted);
				})->count();
				
				echo "Number of Admins: " . $numberOfAdmins;
				
				if ($numberOfAdmins <= 1) {
					$valid = false;
					$user->validationErrors->add('admin', 'You shall not remove the last admin.');
				}
			}
			
			if (! $valid) {
				Input::flashOnly($flashOnlyValues);
				return Redirect::route('editUser', $user->id)->withErrors($user->validationErrors);
			} else {
				
				$user->save();
				foreach ( $permissionChanges as $permission => $granted ) {
					if ($granted) {
						$user->permissions()->save(new Permission($permission, PermissionValue::granted));
					} else {
						$user->permissions()->where('permission', $permission)->delete();
					}
				}
				

				return Redirect::route('listUsersForUserManager')->with('success', 'Your changes have been saved.');
			}
		});
	}	
	
	

}
