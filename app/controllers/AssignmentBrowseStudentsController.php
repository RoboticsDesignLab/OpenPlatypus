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

			$student = null;
			$review = null;
			if (Input::has('review')) {
				$review = $this->getReviewFromInput(Input::get('review'));
			}

			if (Input::has('student')) {
				$student = $this->getStudentFromInput($assignment->subject, Input::get('student'));
			}

			if ($student == null && $review != null) {
				$student = $review->answer->user;
			} elseif (($student != null && $review != null) && ($student->id != $review->answer->user_id)) {
				return Redirect::route('assignmentBrowseStudentList', $assignment->id)->withDanger('The Student ID supplied was not issued this review.');
			} elseif ($student == null && $review == null){
				return Redirect::route('assignmentBrowseStudentList', $assignment->id)->withDanger('The IDs entered could not be found.');
			}

			return Redirect::to(URL::route('assignmentBrowseStudentShow', array('assignment_id' => $assignment->id, 'user_id' => $student->id)) . (($review !=null) ? '#target_review_'.$review->id : ''));
			
		});
		
	}

	private function getReviewFromInput($input) {
		if (! is_numeric($input)) return null;

		$review = Review::find($input);

		if (! isset($review)) return null;

		return $review;
	}

	private function getStudentFromInput($subject, $input) {
		$student = User::findByEmailOrIdInSubject($subject, $input);

		if (! isset($student)) return null;

		return $student;
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

