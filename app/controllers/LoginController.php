<?php

use Platypus\Helpers\PlatypusEnum;
class LoginController extends BaseController {

	// let the user decide how he wants to log in.
	public function showLoginSelectionScreen() {
		$authenticators = array();
		
		foreach(AuthenticationDomainDefinitions::getDomainAuthenticators() as $authenticatorData) {
			$authenticator = new $authenticatorData[1]();
			$authenticators[] = array($authenticatorData[0], $authenticatorData[1], $authenticator->getAuthenticatorName());
		}
		
		return View::make('login.login_showSelectionScreen')->with('authenticators', $authenticators);
	}
	
	// show the login screen of a particular login method.
	public function runLoginMethod($authenticationDomain) {
		if (!AuthenticationDomain::isValid($authenticationDomain)) {
			App::abort(404);
		}

		$result = AuthenticationDomainDefinitions::createAuthenticator($authenticationDomain)->handleLogin();
		
		if ($result) {
			return $result;
		} else {
			app::abort(500);
		}
		
	}
	
	// handles a login request. If there is only one authenticator, go directly to it, otherwise show the selection screen.
	public function login() {
		
		$authenticators = AuthenticationDomainDefinitions::getDomainAuthenticators();
		if (count($authenticators) == 1 ) {
			return $this->runLoginMethod($authenticators[0][0]);
		} else {
			return $this->showLoginSelectionScreen();
		}		
	}
	
	// handles a logout request.
	public function logout() {
		if (Auth::check()) {
			$user = Auth::user();
			
			Auth::logout();
			Session::flush();
			
			$result = AuthenticationDomainDefinitions::createAuthenticator($user->authentication_domain)->handleLogout($user);
			if (!is_null($result)) {
				return $result;
			}
		} else {
			// just to be sure.
			Auth::logout();
			Session::flush();
		}
		return Redirect::route('home')->with('success', 'You have been logged out.');
	}
	
	static public function afterLogin() {
		if (Auth::user()->visibleSubjects->count() == 1) {
			$class_url = route('showSubject', Auth::user()->visibleSubjects()->first()->id);
			$intended_url = Session::get('url.intended', $class_url);
			if(($intended_url == route('home')) || ($intended_url == '/')) {
				return Redirect::to($class_url);
			} else {
				return Redirect::intended($class_url);
			}
		} else {
			return Redirect::intended(route('home'));
		}
	}

}
