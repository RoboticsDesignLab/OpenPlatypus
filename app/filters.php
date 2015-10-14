<?php

use Illuminate\Support\Facades\Redirect;
use Browser\Browser;

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function($request)
{
	//
});


App::after(function($request, $response)
{
	//
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

Route::filter('auth', function() {

	// little hack to force loading of the AuthenticationDomain file.
	new AuthenticationDomain();

	
	$authenticated = false;
	
	// handle the case that a user is logged in.
	if (Auth::check()) {
		
		// we have a user. But we need to check the authenticator because maybe 
		// it is requested that the user is validated for every request.
		
		$authenticator = AuthenticationDomainDefinitions::createAuthenticator(Auth::user()->authentication_domain);
		
		$authenticatedUser = $authenticator->authenticatedUser();
		if (($authenticatedUser) && ($authenticatedUser->id == Auth::user()->id)) {
			$authenticated = true;
		} else {
			// the authenticator doesn't warrant for our user any more, so we log out.
			Auth::logout();
		}
	
	}	
	
	// If we don't have a user, check if one of the authenticators magically has one for us.
	if (!$authenticated) {
		foreach (AuthenticationDomainDefinitions::getDomainAuthenticators() as $authenticatorDefinition) {
				
			$authenticator = new $authenticatorDefinition[1]();
		
			$authenticatedUser = $authenticator->authenticatedUser();
			if ($authenticatedUser) {
				Auth::login($authenticatedUser);
				$authenticated = true;
				break;
			}
		}
	}
	
	// Handle the case that we couldn't authenticate anything so far.
	if (!$authenticated) {
		if (Request::ajax()) {
			return HomeController::makeJsonErrorResponse(401);
			//return Response::make('Unauthorized', 401);
		} else {
			return Redirect::guest('login');
		}
	}
});


// Route::filter('auth.basic', function()
// {
// 	return Auth::basic();
// });

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest', function()
{
	if (Auth::check()) return Redirect::to('/');
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/




Route::filter('modernbrowser', function() {

	$browser = new Browser;
	if ($browser->getName() === $browser::IE && $browser->getVersion() < 10) {
		return Redirect::route('errorBrowser');
	}
	
});


Route::filter('csrf', function()
{
	if (Session::token() != Input::get('_token'))
	{
		throw new Illuminate\Session\TokenMismatchException;
	}
});


Route::filter('fromlocalhost', function() {
	if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
		App::abort(404);
	}
});


Route::filter('databaseok', function () {
	if (!DB::select('show tables;'))
		App::abort(404);
});


Route::filter('numericid', function ($route) {
	$id = $route->getParameter('id'); // use the key you defined
	if (! is_numeric($id))
		App::abort(404);
});


Route::filter('numeric', function ($route, $request, $value) {
	$argument = $route->getParameter($value); // use the key you defined
	if (! is_numeric($argument))
		App::abort(404);
});

Route::filter('alpha_num', function ($route, $request, $value) {
	$argument = $route->getParameter($value); // use the key you defined
	if (! validateSimple($argument, 'required|alpha_num'))
		App::abort(404);
});
	
	
	

Route::filter('classmember', function ($route, $request, $value) {
	$class_id = $route->getParameter($value); // use the key you defined
	if (! is_numeric($class_id))
		App::abort(404);
	$subject = Subject::findOrFail($class_id);
	if (! $subject->isMember(Auth::user()))
		App::abort(403);
});

Route::filter('classlecturer', function ($route, $request, $value) {
	$class_id = $route->getParameter($value); // use the key you defined
	if (! is_numeric($class_id))
		App::abort(404);
	$subject = Subject::findOrFail($class_id);
	if (! $subject->isLecturer(Auth::user()))
		App::abort(403);
});

Route::filter('maycreateassignment', function ($route, $request, $value) {
	$class_id = $route->getParameter($value); // use the key you defined
	if (! is_numeric($class_id)) App::abort(404);
	
	$subject = Subject::findOrFail($class_id);
	if (! $subject->mayCreateAssignment(Auth::user()))
		App::abort(403);
});

Route::filter('mayeditassignment', function ($route, $request, $value) {
	$assignment_id = $route->getParameter($value); // use the key you defined
	if (! is_numeric($assignment_id)) App::abort(404);
	
	$assignment = Assignment::findOrFail($assignment_id);
	
	if (! $assignment->mayEditAssignment(Auth::user()))	App::abort(403);
});


Route::filter('mayeditusers', function () {
	if (!Auth::user()->isUserManager()) {
		App::abort(403);
	}
});
	
		

Route::filter('isDebugger', function () {
	if (!Auth::user()->isDebugger()) {
		App::abort(403);
	}
});
	
	


