<?php

use Platypus\Helpers\PlatypusBool;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Redirect;

class StudentGroupSuggestionController extends BaseController {

	private static function makeShowView(Assignment $assignment, User $user) {
		$group = StudentGroup::findGroup($assignment, $user);
		$suggestions = StudentGroupSuggestion::findSuggestions($assignment, $user);
		return View::make('groups.groups_showGroupForStudent')
			->withUser($user)->withAssignment($assignment)
			->withGroup($group)
			->withSuggestions($suggestions);		
	}
	

	public function showForStudent($assignment_id) {
		return Platypus::transaction(function() use($assignment_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			
			$user = Auth::user();
			
			// make sure the user is part of the assignment.
			if (!$assignment->isActiveStudent($user)) {
				App::abort(404);
			}

			if (!$assignment->usesGroups()) {
				return Redirect::route('showAssignment', $assignment->id)->withDanger('This assignment does not allow group work.');
			}
			
			return static::makeShowView($assignment, $user);
			
		});
	}
	
	public function acceptGroupSuggestionPost($assignment_id, $suggestion_id) {
		return Platypus::transaction(function() use($assignment_id, $suggestion_id) {
			$suggestion = StudentGroupSuggestion::findOrFail($suggestion_id);
			$assignment = $suggestion->assignment;
			if ($assignment_id != $assignment->id) {
				App::abort(404);
			}
			
			$user = Auth::user();
			
			if (!$suggestion->isMember($user)) {
				App::abort(404);
			}
			
			$suggestion->acceptSuggestionSave($user);

			if ($suggestion->isAcceptedByAll()) {
				StudentGroup::makeGroup($assignment, $suggestion->users);
				return Redirect::route('showGroupForStudent', $assignment->id)->withSuccess('Your group is now confirmed.');
			} else {
				return Redirect::route('showGroupForStudent', $assignment->id)->withSuccess('You have accepted the group suggestion. The group will be confirmed once all other students have confirmed the group.');
			}			
		});
	}
	
	public function rejectGroupSuggestionPost($assignment_id, $suggestion_id) {
		return Platypus::transaction(function() use($assignment_id, $suggestion_id) {
			$suggestion = StudentGroupSuggestion::findOrFail($suggestion_id);
			$assignment = $suggestion->assignment;
			if ($assignment_id != $assignment->id) {
				App::abort(404);
			}
				
			$user = Auth::user();
				
			if (!$suggestion->isMember($user)) {
				App::abort(404);
			}
				
			$suggestion->delete();

			return Redirect::route('showGroupForStudent', $assignment->id)->withSuccess('The group suggestion has been rejected.');
		});
	}
	
	
	public function suggestGroupPost($assignment_id) {
		return Platypus::transaction(function() use($assignment_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
				
			$user = Auth::user();
			
			// make sure the user is part of the assignment.
			if (!$assignment->isActiveStudent($user)) {
				App::abort(404);
			}			
			
			// make sure the user is allowed to suggest groups.
			if (!$assignment->maySuggestStudentGroup($user)) {
				return Redirect::route('showGroupForStudent', $assignment->id);
			}
			
			$errors = new MessageBag();
			$ok = true;
			
			// Because we might have many fields, we generate the onlyValues in loop.
			$onlyValues = array();
			for($i = 2; $i<= $assignment->group_size_max; $i++) {
				$field = 'member_'.$i;
				$onlyValues[$i] = $field;
				$rules[$field] = 'emailorstudentid';
			}
			
			// get the input
			$inputs = Input::only($onlyValues);
			
			// remove all the empty ones from the input for consistency.			
			foreach($inputs as $field => $value) {
				if (empty($value)) { 
					unset($inputs[$field]);
				}
			}
			
			// determine the highest field we have
			$showLinesCount = 0;
			for($i = 2; $i<= $assignment->group_size_max; $i++) {
				if(isset($inputs['member_'.$i])) {
					$showLinesCount = $i;
				}
			}
						
			// the user himselve is always part of the group.
			$candidates = array($user);
			
			// validate the fields we have and try to retrieve the users.
			foreach($inputs as $field => $value) {
				$messages = array();
				if (!validateSimple($value, 'required|emailorstudentid', $messages)) {
					$ok = false;
					foreach($messages as $message) {
						$errors->add($field, $message);
					}					
				} else {
					$candidate = User::findByEmailOrIdInSubject($assignment->subject, $value);
					if (is_null($candidate)) {
						$ok = false;
						$errors->add($field, "This student could not be found.");
					} else if (!$assignment->isActiveStudent($candidate)) {
						$ok = false;
						$errors->add($field, "This student is no longer part of this class.");
					} else {
						$candidates[$field] = $candidate;
					}					
				}
			}
			
			// check if we have enough fields
			if (count($inputs)+1 < $assignment->group_size_min) {
				$ok = false;
				for($i = 1; $i<= $assignment->group_size_min; $i++) {
					if (!isset($inputs[$i])) {
						$errors->add('member_'.$i, "A group has to have at least ".$assignment->group_size_min." students.");
					}
				}				
			}
			
			// abort here if the basic checks failed
			if (!$ok) {
				Input::flashOnly($onlyValues);
				return Redirect::route('showGroupForStudent', $assignment->id)->withErrors($errors)->with('showLinesCount', $showLinesCount);
			}
			
			// Check if we can create the group.
			$errorsMessages = array();
			if (!StudentGroupSuggestion::canMakeGroupSuggestion($assignment, $candidates, $errorsMessages)) {
				$ok = false;
				foreach($errorsMessages as $field => $message) {
					$errors->add($field, $message);
				}
				Input::flashOnly($onlyValues);
				return Redirect::route('showGroupForStudent', $assignment->id)->withErrors($errors)->with('showLinesCount', $showLinesCount);
			}
			
			// create the group.
			StudentGroupSuggestion::makeGroupSuggestionSave($assignment, $candidates, $user);			
			
			return Redirect::route('showGroupForStudent', $assignment->id)->withSuccess('Your group suggestion has been created.');
			
		});		
	}
	
}

