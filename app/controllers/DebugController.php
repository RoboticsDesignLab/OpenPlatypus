<?php

use Platypus\Helpers\PlatypusBool;
/* The purpose of this controller is to bundle functions that are only useful during development.
 * 
 * It is better to put them here in one place so we always know what needs to be deactivated.
 */

class DebugController extends BaseController {
	
	
	public function switchUser() {
		return Platypus::transaction(function () {
			if (!Auth::user()->isDebugger()) { App::abort(403);	}
			
			$users = UserController::autoPaginateUsers(User::query());
			return View::make('debug.debug_switchUser')->withUsers($users);
		});
	}	
	
	public function switchUserPost($id) {
		return Platypus::transaction(function() use($id) {
			if (!Auth::user()->isDebugger()) { App::abort(403);	}
			
			$old_user_id = Auth::user()->id;
			
			if(Session::has('debuggerMayReturnToUser')) {
				$old_user_id = Session::get('debuggerMayReturnToUser');
			}
			
			$user = User::findOrFail($id);
			
			Session::flush();
			Auth::login($user);
			
			Session::put('debuggerMayReturnToUser', $old_user_id);
			
			return LoginController::afterLogin();
		});
	}
	
	public function returnUser() {
		return Platypus::transaction(function() {
			$old_user_id = Session::pull('debuggerMayReturnToUser', null);
			if (is_null($old_user_id)) {
				if(Auth::user()->isDebugger()) {
					// the user is a debugger and is in his own account again already. Send him home.
					return LoginController::afterLogin();
				} 
				App::abort(404); 
			}
			$user = User::findOrFail($old_user_id);
				
			Session::flush();
			Auth::login($user);
			
			return LoginController::afterLogin();
		});
	}
	
	
	public function deleteAllStudents() {
		return Platypus::transaction(function() {
			if (!Auth::user()->isDebugger()) { App::abort(403);	}
			
			$members = SubjectMember::where('role',SubjectMemberRoles::student)->get();
			foreach($members as $member) {
				$user = $member->user;
				$member->delete();
				$user->delete();
			}
			return 'All deleted.';
		});
	}
	
	public function answerEightyPercent($assignment_id) {
		return Platypus::transaction(function() use ($assignment_id) {
			if (!Auth::user()->isDebugger()) { App::abort(403);	}
			
			$assignment = Assignment::findOrFail($assignment_id);
			
			foreach($assignment->active_students as $user) {
				$answers = $assignment->createOrGetAllAnswersSave($user);
				
				foreach($answers as $answer) {
					if (rand(1,100) > 80) continue;
				
					if ($answer->text->isEmpty()) {					
						$answer->text->text = "Automatic answer";
					}
					
					if($answer->submitted == PlatypusBool::false) {
						$answer->submit($user);
						$answer->save();
					}
				}			
				
			}
			
			
		});
	}
	
	
	public function phpInfo() {
		if (!Auth::user()->isDebugger()) { App::abort(403);	}
		
		// hide everything from _ENV because it contains database passwords, etc.
		foreach($_ENV as $key => &$value) {
			$value = '*** value hidden ***';
			if(isset($_SERVER[$key])) {
				$_SERVER[$key] = '*** value hidden ***';
			}
			putenv("$key=*** value hidden ***");
		} unset($value);
		
		phpinfo();
		die();
	}
}
