<?php
use Platypus\Helpers\PlatypusEnum;


class HomeController extends BaseController {

	public function home() {
		return Platypus::transaction(function () {
			if (Auth::check()) {
				
				// if we are only part of one class and the referer is external, we go directly to the class page. 
				if (Auth::user()->visibleSubjects->count() == 1) {

					if(isset($_SERVER['HTTP_REFERER'])) {
						$referingHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
						if ( ($referingHost != Request::server ("SERVER_NAME"))
								&& ($referingHost != Request::server ("HTTP_HOST"))
								&& ($referingHost != parse_url ( Config::get('app.url'), PHP_URL_HOST)) ) {

									return Redirect::route('showSubject', Auth::user()->visibleSubjects()->first()->id);
									
								}
					} else {
						return Redirect::route('showSubject', Auth::user()->visibleSubjects()->first()->id);
					}
					
					
				}

				return View::make('layouts.home_user');
				
			} else {
				return View::make('layouts.home_guest');
			}
		});
	}

	public function showHeartBeat() {
		return Platypus::transaction(function () {
			if(!Auth::user()->isAdmin()) App::abort(403);
			return View::make('general.general_heartbeat');
		});		
	}

	public function showErrorPage($code) {
		
		if(!is_numeric($code)) App::abort(500);
		$code=$code+0;
		if(!is_int($code)) App::abort(500);
		if(($code<100)||($code>999)) App::abort(500);
		
		// make sure we don't send invalid http status codes
		if (in_array($code, array(403,404,500))) {
			$statusCode = $code;
		} else if ($code == 401) {
			$statusCode = 403;
		} else {
			$statusCode = 500;
		}
		
		return Response::make(View::make('general.general_error')->withCode($code), $statusCode);
	}
	
	public function showBrowserErrorPage() {
		return Response::make(View::make('general.general_oldBrowser'));
	}
	
	static public function makeJsonErrorResponse($code) {
		$json=array();
		$json['success'] = false;
		if($code == 404) {
			$json['alert'] = "We are very sorry. The resource you are trying to access no longer exists.";
		} else if($code == 403) {
			$json['alert'] = "We are very sorry. You do no longer have permission to access this resource.";
		} else if($code == 401) {
			$json['alert'] = "Your session seems to have expired. Please log in again.";
		} else {
			$json['alert'] = "We are very sorry. An error occured while processing your request.";
		}

		return json_encode($json); // we use the php native function because Response is no longer available inside an error handler.
	}

	public function quiet404() {
		return Response::make('404 - not found',404);
	}
	
}
