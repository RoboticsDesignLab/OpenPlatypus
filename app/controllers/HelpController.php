<?php
use Platypus\Helpers\PlatypusEnum;



class HelpController extends BaseController {

	public function showTopic($topic) {
		switch ($topic) {
			case 'assignmentsettings':
				return View::make('help.help_assignment_settings');
		}
		
		App::abort(404);
	}
}
