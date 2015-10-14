<?php 




interface AuthenticationInterface {

	// The name of the authenticator.
	// This value is shown to the user in case there are several authenticators on offer.
	// Then the user can choose which way to log in.
	public function getAuthenticatorName();
	
	// returns true if Platypus is allowed to change user data (names, email, student_id)
	// if false is returned, Platypus does not offer for admins to change those fields in the database.
	//
	// Instead of a blanket "true" or "false", the function may also return an array with the fields 
	// first_name, last_name, student_id and email set to either true or false individually.
	public function userDataChangeable();
	
	// returns true if Platypus is allowed to change user's password
	// if false is returned, Platypus does not offer for admins to change this field in the database.
	public function userPasswordChangeable();
	
	
	// returns the currently authenticated user.
	// this should be an instance of the "User" model.
	// returns false if no user is authenticated.
	// 
	// an easy implementation could just return the value of Auth::user()
	public function authenticatedUser();
	

	// this method is called when a login needs to be performed.
	// 
	// Think of it as a Laravel Controller function. Thus the returned value should be a view or a redirect request. 
	//
	// Actually receiving and processing the login information is beyond the scope of this interface.
	// you have to set up your own routes and controller for that. 
	public function handleLogin();
	
	
	// this method is called when a logout needs to be performed.
	// 
	// Think of it as a Laravel Controller function. Thus the returned value should be a view or a redirect request.
	// If the function does not return anything, a standard logout screen is show.
	// Thus, if is valid to leave this function empty. 
	//
	// At the time of calling the user is already logged out from the session, so Auth::user() is empty.
	// The previous value of Auth::user() is passed as argument $user.
	public function handleLogout($user);
	
	
	// check whether it would be valid to create a user with the given information.
	// some of the strings may be empty.
	// returns true if this authenticator could create a user based on the given information
	// returns false otherwise.
	//
	// In case of SSO, this method would check whether the user can be found in the central database and returns true if so.
	// In case of local user handling, this method would do some sanity checks and check for duplicates, etc.   
	public function userIsValidToBeCreated($first_name, $last_name, $student_id, $email);
	
	
	// the logical step to follow "userIsValidToCreate": create a user based on the given information.
	// returns the newly created user.
	//
	// This method is only called within a database transaction. Thus, on any errors an exception should be thown.
	//
	// The testRun option is set to true if it is already determined to roll back the database transaction.
	// The intention is that certain things can be optimised, e.g. password hashing is expensive and could be skipped. 
	public function createUser($first_name, $last_name, $student_id, $email, $testRun = false);

	
	// Platypus requests to check whether the user information has changed.
	// This makes sense if a central authentication database is used. That way changes are
	// transferred into Platypus. If this feature is not needed, simply do nothing. 
	public function refreshUserInformation($user);
	
	// Platypus wants to display some authentication information (a short informative string).
	// generate this string here.
	public function getAuthInfoForDisplay($user);
	
}