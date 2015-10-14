<?php



class AssignmentBrowseStudentsController extends BaseController {

	public function browseStudentList($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayBrowseStudents(Auth::user()))
				App::abort(403);
			
			$users = $assignment->allStudents();
			$users = UserController::autoPaginateUsers($users);
			
			return View::make('assignment.assignment_browseStudentList')->withAssignment($assignment)->withUsers($users);
		});
	}
	
	public function browseStudentShow($assignment_id, $user_id) {
		return Platypus::transaction(function () use($assignment_id, $user_id) {
				
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayBrowseStudents(Auth::user()))
				App::abort(403);
			
			$user = User::findOrFail($user_id);
			
			if(!$assignment->isStudent($user)) {
				App::abort(404);
			}
				
			return View::make('assignment.assignment_browseStudentShow')->withAssignment($assignment)->withUser($user);
		});
	}

	public function jumpToReview($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			
			if (! $assignment->mayBrowseStudents(Auth::user())) {
				App::abort(403);
			}
			
			$found = false;
			
			do {
				if (! Input::has('review'))	break;
				
				$review_id = Input::get('review');
				
				if (! is_numeric($review_id)) break;
				
				$review = Review::find($review_id);
				
				if (! isset($review)) break;
				
				$found = true;
				
			} while ( false );
			
			
			if ($found) {
				return Redirect::to(URL::route('assignmentBrowseStudentShow', array('assignment_id' => $assignment->id, 'user_id' => $review->answer->user_id)).'#target_review_'.$review->id);
			} else {
				return Redirect::route('assignmentBrowseStudentList', $assignment->id)->withDanger('The review number you entered could not be found.');
			}
			
		});
		
	}
	
	public function showAllStudentsMonster($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
				
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayBrowseStudents(Auth::user()))
				App::abort(403);
	
			return View::make('assignment.assignment_browseStudentShowAll')->withAssignment($assignment);
		});
	}	
	
	
}

