<?php 

$auth_php_file = stream_resolve_include_path ("uq/auth.php");
if ($auth_php_file === false) {
	function auth_redirect() { echo 'UQ Single Sign On is not available on this server.'; die(); }
	function auth_get_payload() { return false; }
	
} else {
	require_once $auth_php_file;
}

class UqSsoAuthenticator implements AuthenticationInterface {
	

	// The name of the authenticator.
	// This value is shown to the user in case there are several authenticators on offer.
	// Then the user can choose which way to log in.
	public function getAuthenticatorName() {
		return "UQ Single Sign On";
	}	
	
	// returns true if Platypus is allowed to change user data (names, email, student_id)
	// if false is returned, Platypus does not offer for admins to change those fields in the database.
	//
	// Instead of a blanket "true" or "false", the function may also return an array with the fields
	// first_name, last_name, student_id and email set to either true or false individually.
	public function userDataChangeable() {
		return array(
			'first_name' => false,
			'last_name' => false,
			'student_id' => true, // the student ID isn't retrievable from SSO. So we make it changeable.
			'email' => false,
		);
	}	
	
	// returns true if Platypus is allowed to change user's password
	// if false is returned, Platypus does not offer for admins to change this field in the database.
	public function userPasswordChangeable() {
		return false;
	}
	
	
	// returns the currently authenticated user.
	// this should be an instance of the "User" model.
	// returns false if no user is authenticated via this authenticator.
	public function authenticatedUser() {
		if (Auth::check()) {
			$user = Auth::user();
			if ($user->authentication_domain == AuthenticationDomain::uqsso) {
				return $user;
			}
		} else {
			$authData = auth_get_payload();
			if(!is_array($authData)) return false;
			if(!isset($authData['user'])) return false;
			
			// try to find the user by userid
			$user = User::where('authentication_domain', AuthenticationDomain::uqsso)->where('authentication_id', $authData['user'])->first();
			
			// try to find an un-associated user by email
			if(!isset($user) && isset($authData['email'])) {
				$user = User::where('authentication_domain', AuthenticationDomain::uqsso)
					->where('email', $authData['email'])
					->where(function($q) {
						$q->whereNull('authentication_id')->orWhere('authentication_id', '');
					})
					->first();
			}
			
			// try to find an un-associated user by email derived from user name
			if(!isset($user) && isset($authData['email'])) {
				$user = User::where('authentication_domain', AuthenticationDomain::uqsso)
				->where('email', $authData['user'].'@student.uq.edu.au')
				->where(function($q) {
					$q->whereNull('authentication_id')->orWhere('authentication_id', '');
				})
				->first();
			}

			// try to find an un-associated user by email derived from user name another way
			if(!isset($user) && isset($authData['email'])) {
				$user = User::where('authentication_domain', AuthenticationDomain::uqsso)
				->where('email', $authData['user'].'@uq.edu.au')
				->where(function($q) {
					$q->whereNull('authentication_id')->orWhere('authentication_id', '');
				})
				->first();
			}
			
			// try to derive a student id from the email address and use that
			if(!isset($user) && isset($authData['email'])) {
				
				do{
					$email = explode('@', $authData['email']); // split the addy at the @.
					if(count($email)!=2) break; // if we don't get two parts something is wrong.
					if($email[1] != 'student.uq.edu.au') break; // we only want to deal with the student domain.
					if(substr($email[0], 0, 1) != 's') break; // make sure it starts with s.
					
					$number = substr($email[0], 1); // get the number part.
					if(!is_numeric($number)) // make sure it is an actual number.
					
					// now we have to add the checksum. We simepy try all 10 possibilities.
					$possibleIds = array();
					for($digit = 0; $digit<10; $digit++) {
						$possibleIds[] = "".$number.$digit;						
					}
					
					// try to find a user like that.
					$candidates = User::where('authentication_domain', AuthenticationDomain::uqsso)
						->whereIn('student_id', $possibleIds)
						->where(function($q) {
							$q->whereNull('authentication_id')->orWhere('authentication_id', '');
						})
						->take(2)->get();
						
					// we only proceed if the match is unique.
					if(count($candidates) == 1) {
						$user = $candidates[0];
					}
					
				} while (false);
			}
			
				
			
			if(!isset($user)) {
				if(!isset($authData['user'])) return false;
				if(!isset($authData['email'])) return false;
				if(!isset($authData['firstname'])) return false;
				if(!isset($authData['lastname'])) return false;
				
				if (!$this->userIsValidToBeCreated($authData['firstname'], $authData['lastname'], '', $authData['email'])) return false;
				$user = $this->createUser($authData['firstname'], $authData['lastname'], '', $authData['email']);
				
				$user->authentication_id = $authData['user'];
				$user->save();
				$user = User::findOrFail($user->id); // reload the user just to be sure...
				
			} else {
				// update the user information
				
				if(isset($authData['user'])) {
					$user->authentication_id = $authData['user'];
				}				
				
				if(isset($authData['email'])) {
					$user->email = $authData['email'];
				}
				
				if(isset($authData['firstname'])) {
					$user->first_name = $authData['firstname'];
				}
				
				if(isset($authData['lastname'])) {
					$user->last_name = $authData['lastname'];
				}

				if($user->isDirty()) {
					$user->save();
					$user = User::findOrFail($user->id); // reload the user just to be sure...
				}
				
			}
			
			Auth::login($user);
			return $user;
		}
		
		return false;
	}
	
	
	// this method is called when a login needs to be performed.
	//
	// Think of it as a Laravel Controller function. Thus the returned value should be a view or a redirect request.
	public function handleLogin() {
		$user = $this->authenticatedUser();
		if (isset($user) && ($user !== false)) {
			return LoginController::afterLogin();
		} else {
			$authData = auth_get_payload();
			if (is_array($authData) && isset($authData['user'])) {
				return View::make('login.uqSsoDebug')->with('authData', $authData);
			} else {
				auth_redirect();
				die();
			}
		}
	}
	
	
	// this method is called when a logout needs to be performed.
	//
	// Think of it as a Laravel Controller function. Thus the returned value should be a view or a redirect request.
	public function handleLogout($user) {
		Auth::logout();
		return Redirect::to('https://api.uqcloud.net/logout/');
	}
	
	
	// check whether it would be valid to create a user with the given information.
	// some of the strings may be empty.
	// returns true if this authenticator could create a user based on the given information
	// returns false otherwise.
	//
	// In case of SSO, this method would check whether the user can be found in the central database and returns true if so.
	// In case of local user handling, this method would do some sanity checks and check for duplicates, etc.
	public function userIsValidToBeCreated($first_name, $last_name, $student_id, $email) {
		
		// check if the given data passes basic validation rules. All fields must validate because we can't get extra information from external sources.
		$data = array();
		$data["first_name"] = $first_name;
		$data["last_name"] = $last_name;
		$data["student_id"] = $student_id;
		$data["email"] = $email;
		
		$rules = array();
		$rules["first_name"] = User::$rules["first_name"];
		$rules["last_name"] = User::$rules["last_name"];
		$rules["student_id"] = User::$rules["student_id"];
		$rules["email"] = User::$rules["email"];
		
		$validator = Validator::make($data, $rules);
		
		if ($validator->fails()) {
			return false;
		}
		
		// we want unique emails.
		if (User::where('authentication_domain', AuthenticationDomain::uqsso)->where('email', $email)->count() != 0) {
			return false;
		}		
		
		// if a student id is given it should be unique.
		if ($student_id != "") {
			if (User::where('authentication_domain', AuthenticationDomain::uqsso)->where('student_id', $student_id)->count() != 0) {
				return false;
			}
		}
		
		// we don't care about duplicate names.
		
		return true;
				
	}
	
	
	// the logical step to follow "userIsValidToCreate": create a user based on the given information.
	// returns the newly created user.
	//
	// This method is only called within a database transaction. Thus, on any errors an exception should be thown.
	public function createUser($first_name, $last_name, $student_id, $email, $testRun = false) {
		
		if (!$this->userIsValidToBeCreated($first_name, $last_name, $student_id, $email)) {
			throw new Exception('User not valid to create.');
		}
		
		$user = new User();
		
		$user->first_name = $first_name;
		$user->last_name = $last_name;
		$user->student_id = $student_id;
		$user->email = $email;
		
		$user->authentication_domain = AuthenticationDomain::uqsso;
		
		if (!$user->validate()) {
			throw new Exception('User not valid to create.');
		}
		
		$user->save();

		return $user;		
	}
	
	// Platypus requests to check whether the user information has changed.
	// This makes sense if a central authentication database is used. That way changes are
	// transferred into Platypus. If this feature is not needed, simply do nothing.
	public function refreshUserInformation($user) {
	}
		
	// Platypus wants to display some authentication information (a short informative string).
	// generate this string here.
	public function getAuthInfoForDisplay($user) {
		$uqId = $user->authentication_id;
		if(isset($uqId) && !empty($uqId)) {
			return "UQ SSO: $uqId";
		} else {
			return "UQ SSO";
		}
	}
	
	
}

