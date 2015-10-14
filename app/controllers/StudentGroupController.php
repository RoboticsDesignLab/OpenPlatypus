<?php

use Platypus\Helpers\PlatypusBool;
use Illuminate\Support\MessageBag;

class StudentGroupController extends BaseController {

	public function editStudentGroups($assignment_id) {
		return Platypus::transaction(function() use($assignment_id) {
				
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayViewAssignmentGroups(Auth::user())) App::abort(403);

			if (!$assignment->usesGroups()) App::abort(404);
			
			$editable = $assignment->mayEditAssignmentGroups(Auth::user());
			

			// This is ugly, because we're effectively reading all users first to geth the ids and then 
			// put them into another database query so pagination works nicely.
			//
			// It would be nice to do it directly via a SQL union, but Eloquent adds extra columns for pivot queries
			// which makes the union fail.
			//
			// I don't want to write a hand crafted SQL thing in case the implementation of  active_students changes for example.
			// It shouldn't be too much of a performance issue unless we have ridiculously large classes one day.   
			$user_ids = array();
			foreach($assignment->active_students as $user) {
				$user_ids[] = $user->id;
			}
			foreach($assignment->grouped_students as $user) {
				$user_ids[] = $user->id;
			}
				
				
			$users = User::whereIn('id', $user_ids);
			
			$users = UserController::autoPaginateUsers($users);
			

			return View::make('groups.groups_editGroups')->withAssignment($assignment)->withUsers($users)->withEditable($editable);
		});
	}
	
	private function createAjaxUpdateForUsers($assignment, $users) {
		$result = array();
		foreach($users as $user) {
			$result['user_button_'. $user->id] = View::make('groups.groups_editGroupsButton_insert')->withAssignment($assignment)->withUser($user)->render();
			$group = StudentGroup::findGroup($assignment, $user);
			$result['user_group_'. $user->id] = View::make('groups.groups_editGroupsSummary_insert')->withGroup($group)->render();
		}
		return $result;
	}
	
	public function removeUserFromStudentGroup($assignment_id, $user_id) {
		return Platypus::transaction(function() use($assignment_id, $user_id) {
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayEditAssignmentGroups(Auth::user())) App::abort(403);
			if (!$assignment->usesGroups()) App::abort(404);
				
			$user = User::findOrFail($user_id);
			
			$group = StudentGroup::findGroup($assignment, $user);
			
			$json = array ();
			$json ['success'] = true;
				
			
			if (!is_null($group)) {
				$users_to_update = $group->users;
				$group->removeUserSave($user);
				if ($group->isEmpty()) {
					$group->delete();
				}
				
				$json ['growl'] = 'User removed from group.';
				$json['update'] = $this->createAjaxUpdateForUsers($assignment, $users_to_update);
			}
			
			
			$json ['html'] = View::make('groups.groups_editGroupsButton_insert')->withAssignment($assignment)->withUser($user)->render();
			return Response::json($json);
			
		});
	}	

	public function dissolveStudentGroupOfUser($assignment_id, $user_id) {
		return Platypus::transaction(function() use($assignment_id, $user_id) {
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayEditAssignmentGroups(Auth::user())) App::abort(403);
			if (!$assignment->usesGroups()) App::abort(404);
	
			$user = User::findOrFail($user_id);
				
			$group = StudentGroup::findGroup($assignment, $user);
				
			$json = array ();
			$json ['success'] = true;
	
				
			if (!is_null($group)) {
				$users_to_update = $group->users;
				
				$group->delete();

				$json ['growl'] = 'group dissolved.';
				$json['update'] = $this->createAjaxUpdateForUsers($assignment, $users_to_update);
			}
				
				
			$json ['html'] = View::make('groups.groups_editGroupsButton_insert')->withAssignment($assignment)->withUser($user)->render();
			return Response::json($json);
				
		});
	}	
	
	public function addUserToOtherUsersStudentGroup($assignment_id, $user_id) {
		return Platypus::transaction(function() use($assignment_id, $user_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayEditAssignmentGroups(Auth::user())) App::abort(403);
			if (!$assignment->usesGroups()) App::abort(404);

			$originaluser = User::findOrFail($user_id);
			$user = $originaluser;
	
			
			$json = array ();
			$json ['success'] = true;
			
			$other_user_input = Input::get("other_user");
			$errors = array();
			if (!validateSimple($other_user_input, 'required|emailorstudentid', $errors)) {
				$json ['alert'] = 'The email address or student ID you entered is invalid.';
				return Response::json($json);
			}
			
			$otherUser = User::findByEmailOrIdInSubject($assignment->subject, Input::get("other_user"));
			
			if (is_null($otherUser)) {
				
				$json ['alert'] = 'Could not find user';
				
			} else {
				
				$group = StudentGroup::findGroup($assignment, $user);
				
				$otherGroup = StudentGroup::findGroup($assignment, $otherUser);
				
				if (is_null($group) && !is_null($otherGroup)) {
					// let's switch user and otherUser around.
					$tmp = $user;
					$user = $otherUser;
					$otherUser = $tmp;
					
					$tmp = $group;
					$group = $otherGroup;
					$otherGroup = $tmp;
				}
				
				
				$done = false;
				if (is_null($group)) {
					$group = StudentGroup::makeGroup($assignment, $user);
					
					if ($user->id == $otherUser->id) {
						$done = true;
						$json ['growl'] = 'User is now in group with itself.';
						$json['update'] = $this->createAjaxUpdateForUsers($assignment, $group->users);
					}
				}

				if (! $done) {
					$reason = '';
					if ($group->canAddUser($otherUser, $reason)) {
						$group->addUserSave($otherUser);
						$json ['growl'] = 'User added to group';
						$json ['update'] = $this->createAjaxUpdateForUsers($assignment, $group->users);
					} else {
						// roll back in case we created $group anew.
						DB::rollback();
						$json ['alert'] = 'Could not add ' . $otherUser->presenter()->name .' to group. ' . $reason;
					}
				}
				
			}
			
			$json ['html'] = View::make('groups.groups_editGroupsButton_insert')->withAssignment($assignment)->withUser($originaluser)->render();
			return Response::json($json);
			
			
		});
	}
		
}

