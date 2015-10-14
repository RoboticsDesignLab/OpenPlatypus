<?php

/*
|--------------------------------------------------------------------------
| Register The Laravel Class Loader
|--------------------------------------------------------------------------
|
| In addition to using Composer, you may use the Laravel class loader to
| load your controllers and models. This is useful for keeping all of
| your classes in the "global" namespace without Composer updating.
|
*/

ClassLoader::addDirectories(array(

	app_path().'/commands',
	app_path().'/controllers',
	app_path().'/models',
	app_path().'/database/seeds',

));

/*
|--------------------------------------------------------------------------
| Application Error Logger
|--------------------------------------------------------------------------
|
| Here we will configure the error logger setup for the application which
| is built on top of the wonderful Monolog library. By default we will
| build a basic log file setup which creates a single file for logs.
|
*/

Log::useFiles(storage_path().'/logs/laravel.log');

/*
|--------------------------------------------------------------------------
| Application Error Handler
|--------------------------------------------------------------------------
|
| Here you may handle any errors that occur in your application, including
| logging them or displaying custom views for specific errors. You may
| even register several error handlers to handle different types of
| exceptions. If nothing is returned, the default error view is
| shown, which includes a detailed stack trace during debug.
|
*/



use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use	Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Redirect;

// If it is an ajax request, always handle the error.
App::error(function(Exception $exception) {
	if (Config::get('app.debug')) return;

	if (Request::ajax()) {
		return HomeController::makeJsonErrorResponse(0);
	}
});

// Handle 404 and 403 errors.
App::error(function(HttpException $exception) {
	if (Config::get('app.debug')) return;
	
	$code = $exception->getStatusCode();
	if( ($code == 404)||($code == 403)) {
		if (Request::ajax()) {
			return HomeController::makeJsonErrorResponse($code);
		} else {
			return Redirect::route('error', $code);
		} 
	}
});

// catch failures of csrf tokens. Usually due to expired sessions.
App::error(function(TokenMismatchException $exception) {
	if (Config::get('app.debug')) return;

	if (Request::ajax()) {
		return HomeController::makeJsonErrorResponse(401);
	} else {
		return Redirect::route('error', 401);
	}
});

// turn model not found exceptions into 404 errors.
App::error(function(ModelNotFoundException $exception) {
	if (Config::get('app.debug')) return;
	
	if (Request::ajax()) {
		return HomeController::makeJsonErrorResponse(404);
	} else {
		return Redirect::route('error', 404);
	}
});

// turn wrong Method errors into 404 errors.
App::error(function(MethodNotAllowedHttpException $exception) {
	if (Config::get('app.debug')) return;
	
	if (Request::ajax()) {
		return HomeController::makeJsonErrorResponse(404);
	} else {
		return Redirect::route('error', 404);
	}
});
	

// make the log handler last so it is always fired.
App::error(function(Exception $exception, $code) {
	Log::error('Request: '. $_SERVER["REQUEST_URI"]);
	$user = Auth::user();
	if(isset($user)) {
		Log::error('User: '. $user->id .' ('.$user->presenter()->name.')');
	}
	Log::error('Browser: '. $_SERVER["HTTP_USER_AGENT"]);
	Log::error($exception);
});


// an extra error handler for the fucking annoying and super buggy Trend Micro server scan
// The main effect is to keep our log clean by superceeding the above log handler.
App::error(function(MethodNotAllowedHttpException $exception) {
	if (Config::get('app.debug')) return;

	if ($_SERVER["HTTP_USER_AGENT"] == 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)') {
		return Redirect::route('error', 404);
	}
});
	
	

/*
|--------------------------------------------------------------------------
| Maintenance Mode Handler
|--------------------------------------------------------------------------
|
| The "down" Artisan command gives you the ability to put an application
| into maintenance mode. Here, you will define what is displayed back
| to the user if maintenance mode is in effect for the application.
|
*/

App::down(function()
{
	return Response::make("Be right back!", 503);
});

/*
|--------------------------------------------------------------------------
| Require The Filters File
|--------------------------------------------------------------------------
|
| Next we will load the filters file for the application. This gives us
| a nice separate location to store our route and application filter
| definitions instead of putting them all in the main routes file.
|
*/

require app_path().'/filters.php';

require_once app_path().'/helperfiles/platypus_helpers.php';

require_once app_path().'/validators/PlatypusValidators.php';



// set transaction isolation level to serialisable.
if (!DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;')) {
	// could not set transaction isolation level to serialisable.
	App::abort(500);
}




