<?php

class LocalAuthenticatorController extends BaseController {


	
	public function login() {
		return View::make('login.localAuthenticator_login');
	}

	public function loginPost() {
		return Platypus::transaction(function () {
			
			$input = Input::only('email', 'password');
			
			if ((! empty($input ['email'])) && (! empty($input ['password']))) {
				
				$userQuery = User::where('email', $input ['email'])->where('authentication_domain', AuthenticationDomain::local);
				$user = null;
				if ($userQuery->count() > 0) {
					$user = $userQuery->first();
				} else {
					$userQuery = User::where('student_id', $input ['email'])->where('authentication_domain', AuthenticationDomain::local);
					
					if ($userQuery->count() > 0) {
						$user = $userQuery->first();
					}
				}
				
				if (! is_null($user)) {
					if (Hash::check($input ['password'], $user->password)) {
						if (Hash::needsRehash($user->password)) {
							$user->password = Hash::make($input ['password']);
							$user->save();
						}
						Auth::login($user);
						return LoginController::afterLogin();
					}
				}
			}
			return Redirect::action('LocalAuthenticatorController@login')->with('danger', 'The combination of email address and password you provided is invalid.');
		});
	}

}