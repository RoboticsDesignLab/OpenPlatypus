<?php 


class LocalAuthenticator implements AuthenticationInterface {
	

	// The name of the authenticator.
	// This value is shown to the user in case there are several authenticators on offer.
	// Then the user can choose which way to log in.
	public function getAuthenticatorName() {
		return "Local account";
	}	
	
	// returns true if Platypus is allowed to change user data (names, email, student_id)
	// if false is returned, Platypus does not offer for admins to change those fields in the database.
	//
	// Instead of a blanket "true" or "false", the function may also return an array with the fields
	// first_name, last_name, student_id and email set to either true or false individually.
	public function userDataChangeable() {
		return true;
	}	
	
	// returns true if Platypus is allowed to change user's password
	// if false is returned, Platypus does not offer for admins to change this field in the database.
	public function userPasswordChangeable() {
		return true;
	}
	
	
	// returns the currently authenticated user.
	// this should be an instance of the "User" model.
	// returns false if no user is authenticated via this authenticator.
	public function authenticatedUser() {
		if (Auth::check()) {
			$user = Auth::user();
			if ($user->authentication_domain == AuthenticationDomain::local) {
				return $user;
			}
		}
		
		return false;
	}
	
	
	// this method is called when a login needs to be performed.
	//
	// Think of it as a Laravel Controller function. Thus the returned value should be a view or a redirect request.
	public function handleLogin() {
		return Redirect::action('LocalAuthenticatorController@login');
	}
	
	
	// this method is called when a logout needs to be performed.
	//
	// Think of it as a Laravel Controller function. Thus the returned value should be a view or a redirect request.
	public function handleLogout($user) {
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
		if (User::where('email', $email)->count() != 0) {
			return false;
		}		
		
		// if a student id is given it should be unique.
		if ($student_id != "") {
			if (User::where('student_id', $student_id)->count() != 0) {
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
		
		$user->authentication_domain = AuthenticationDomain::local;
		
		// TODO: here we should set a random password.
		// This should also trigger code to send an email to the user.
		//
		// ideally the email sending code is written in such a way that the email is only sent if the database transaction succeeds.
		// e.g. the email is written to an email-queue-table and then processed by a queue handler. That way, if the transaction rolls
		// back, the email never gets sent. 
		if ($testRun) {
			$user->autoHashPasswordAttributes = false;
			$user->password = 'xxxxxx';
		} else {
			$user->password = '123123';
		}
		$user->password_confirmation = $user->password;
		
		
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
		return 'local';
	}
	
	
}