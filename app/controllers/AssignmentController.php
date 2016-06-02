<?php



use Platypus\Helpers\PlatypusBool;
use Illuminate\Support\MessageBag;

class AssignmentController extends BaseController {

	public function create($class_id) {
		return Platypus::transaction(function () use($class_id) {
			$subject = Subject::findOrFail($class_id);
			if (! $subject->mayCreateAssignment(Auth::user()))
				App::abort(403);
			$assignment = new Assignment();
			return View::make('assignment.assignment_new_layout', array (
					'assignment' => $assignment,
					'subject' => $subject 
			));
		});
	}

	public function createPost($class_id) {
		return Platypus::transaction(function () use($class_id) {
			

			$subject = Subject::findOrFail($class_id);
			if (! $subject->mayCreateAssignment(Auth::user())) {
				App::abort(403);
			}
		
			$onlyValues = array (
				'title',
				//'offline',
				//'visibility',
				'answers_due',
				//'group_work_mode',
				//'group_selection_mode',
				//'group_size_min',
				//'group_size_max',
				//'mark_by_tutors',
				//'tutors_due',
				//'number_of_peers',
				//'peers_due',
				//'shuffle_mode',
				//'marking_started',
				//'autostart_marking_time',
				//'late_policy',
				//'marks_released',
			);
			$input = Input::only($onlyValues);
			
			$assignment = new Assignment($input);
			
			$assignment->subject_id = $subject->id;
			
			// We need to set a valid due date for the reviews. Otherwise validation will fail. Because it depends on (possibly) weird user input, we have to be a bit careful with it...
			try {
				$review_due_date = parseDeadline($input ['answers_due']);
				if (is_a($review_due_date, 'Carbon')) {
					$review_due_date = $review_due_date->addWeeks(2);
					if($review_due_date > $subject->end_date) {
						$review_due_date = $subject->end_date;
					}
				} else {
					$review_due_date = '';
				}
			} catch ( Exception $e ) {
				$review_due_date = '';
			}
			$assignment->visibility = AssignmentVisibility::hidden;
			$assignment->tutors_due = $review_due_date;
			$assignment->peers_due = $review_due_date;
			
			if (! $assignment->validate()) {
				Input::flashOnly($onlyValues);
				return Redirect::route('newAssignment', $subject->id)->withErrors($assignment->validationErrors);
			} else {
				$assignment->save();
				return Redirect::route('editAssignment', $assignment->id)->with('success', 'The assignment ' . htmlentities($assignment->title) . ' has been created. You can now change the detailed settings and add the assignment questions.')->with('showEditForm', true);
			}
		});
	}
	
	public function show($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			
			if (! $assignment->mayViewAssignment(Auth::user())) {
				App::abort(403);
			}
			
			$subject = $assignment->subject;
			
			return View::make('assignment.assignment_show_layout', array (
					'subject' => $subject,
					'assignment' => $assignment 
			));		
		
		});
	}
	
	public function updateNavigationBarAjax($assignment_id, $route_name, $for_main_menu = 0) {
		return Platypus::transaction(function () use($assignment_id, $route_name, $for_main_menu) {

			$forMainMenu = ($for_main_menu == 1);
			
			$assignment = Assignment::findOrFail($assignment_id);
				
			if (! $assignment->mayViewAssignment(Auth::user())) {
				App::abort(403);
			}
				
			$subject = $assignment->subject;
				
			
			$json = array ();
			$json ['success'] = true;
			$json ['html'] = View::make('assignment.assignment_tab_navigation_insert')
				->withAssignment($assignment)
				->with('currentRoute', $route_name)
				->with('forMainMenu', $forMainMenu)
				->render();
			return Response::json($json);		
		});
	}
	
		
	
	public function editTutors($id) {
		return Platypus::transaction(function () use($id) {
				
			$assignment = Assignment::findOrFail($id);
			if (! $assignment->mayManageAssignment(Auth::user())) App::abort(403);

			$users = $assignment->subject->activeTutors();
			$users = UserController::autoPaginateUsers($users);

			return View::make('assignment.assignment_editTutors')->withAssignment($assignment)->withUsers($users);
	
		});
	}
		
	public function editTutorsAjax($id, $user_id, $question_id, $status) {
		
		if ($status == 0) {
			$status = false;
		} else if ($status == 1) {
			$status = true;
		} else {
			App::abort(404);
		}	
		
		return Platypus::transaction(function () use($id, $user_id, $question_id, $status) {
				
			$assignment = Assignment::findOrFail($id);
			if (! $assignment->mayManageAssignment(Auth::user())) App::abort(403);
			
			$user = User::findOrFail($user_id);
			
			$subject = $assignment->subject;
			
			if ($question_id == 0) {
				$question = null;
			} else {
				$question = Question::findOrFail($question_id);
				if ($question->assignment_real->id != $assignment->id) {
					App::abort(404);
				}
				
				if ($question->isSubquestion()) {
					App::abort(404);
				}
			}
			
			$json = array ();
			$json ['success'] = true;

			
			if(!$status) {
				$query = AssignmentTutor::whereHas('subjectMember', function($q) use($user) {
					$q->where('user_id', $user->id);
				})->where('assignment_id', $assignment->id);
				
				if(isset($question)) {
					$query->where('question_id', $question->id);
				}
				
				$query->delete();
				
				$json ['growl'] = 'The tutor has beeen removed.';
				
			} else {
				if (!$subject->isActiveTutor($user)) {
					$json ['alert'] = 'The user is not a tutor of this class.';
				} else {
					$membership = $subject->getMembership($user);
					
					// get possible conflicting memberships out of the way.
					$query = AssignmentTutor::where('subject_member_id', $membership->id)->where('assignment_id', $assignment->id);
					
					if(isset($question)) {
						$query->where(function($q) use($question) {
							$q->where('question_id', $question->id)->orWhereNull('question_id');
						});
					}
					
					$query->delete();
					
					// make the new tutor role;
					$assignmentTutor = new AssignmentTutor();
					$assignmentTutor->subject_member_id = $membership->id;
					$assignmentTutor->assignment_id = $assignment->id;
					if(isset($question)) {
						$assignmentTutor->question_id = $question->id;
					}
					$assignmentTutor->save();
					$assignment->invalidateRelations();
					
					$json ['growl'] = 'The tutor has beeen added.';
				}
			}
			
			$json ['html'] = View::make('assignment.assignment_editTutorButton_insert')->withAssignment($assignment)->withUser($user)->render();
			return Response::json($json);
	
		});
	}
	
	
	private static function showResultsAsStudent($assignment) {
			if (! $assignment->maySeeResultsPageAsStudent(Auth::user())) {
				App::abort(403);
			}
	
			return View::make('assignment.assignment_showOwnResults', array (
					'user' => Auth::user(),
					'assignment' => $assignment
			));
	}
		
	private static function showResultsAsMarkerOrObserver($assignment) {
		if (! $assignment->maySeeResultsPageAsMarkerOrObserver(Auth::user())) {
			App::abort(403);
		}
	
		$users = UserController::autoPaginateUsers($assignment->activeStudents());
		
		return View::make('assignment.assignment_editFinalMarks', array (
				'users' => $users,
				'assignment' => $assignment
		));
	}
	
	
	
	public function showResults($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
				
			$user = Auth::user();
			
			$assignment = Assignment::findOrFail($assignment_id);
				
			if (! $assignment->maySeeResultsPage($user)) {
				App::abort(403);
			}
			
					if($assignment->maySeeResultsPageAsStudent($user)) {
				return self::showResultsAsStudent($assignment);
			}
				
			if($assignment->maySeeResultsPageAsMarkerOrObserver($user)) {
				return self::showResultsAsMarkerOrObserver($assignment);
			}
			
			App::abort(404); // we should never reach this point.				
		
		});		
	}
	
	
	public function setFinalAssignmentMarkAjax($assignment_id, $user_id) {
		return Platypus::transaction(function () use($assignment_id, $user_id) {
			
		
			$assignment = Assignment::findOrFail($assignment_id);
			if(!$assignment->maySetFinalMarks(Auth::user())) {
				App::abort(403);
			}
			
			$user = User::findOrFail($user_id);
			if(!$assignment->isStudent($user)) {
				App::abort(404);
			}

			$json = array ();
			$json ['success'] = true;
						
			if(Input::has('mark')) {
				$mark = Input::get('mark');
				
				if($mark == 'unset') {
					$model = $assignment->getUserAssignmentMark($user);
					if(isset($model)) {
						$model->delete();
					}
				} else {
					if (validateMark($mark, false, true, $assignment->mark_limit)) {
						$assignment->setUserAssignmentMarkSave($user, $mark);
					} else {
						$json ['alert'] = 'Please enter a valid mark as percentage between 0 and ' . $assignment->mark_limit . '.';
					}
				}
			}
			
			$assignment->invalidateRelations();
			AssignmentMark::updateAutomaticMark($assignment, $user);
			
			$json ['html'] = View::make('assignment.assignment_editMarkButton_insert')
					->withUser($user)
					->withAssignment($assignment)
					->with('offerEdit', true)
					->render();

			return Response::json($json);
		
		});
	}

	public function setFinalQuestionMarkAjax($assignment_id, $question_id, $user_id) {
		return Platypus::transaction(function () use($assignment_id, $question_id, $user_id) {
		
			$assignment = Assignment::findOrFail($assignment_id);
			if(!$assignment->maySetFinalMarks(Auth::user())) {
				App::abort(403);
			}
			
			$question = Question::findOrFail($question_id);
			if($question->isMaster() || ($question->assignment_real->id != $assignment->id)) {
				App::abort(404);
			}
				
			$user = User::findOrFail($user_id);
			if(!$assignment->isStudent($user)) {
				App::abort(404);
			}
		
			$json = array ();
			$json ['success'] = true;
		
			if(Input::has('mark')) {
				$mark = Input::get('mark');
		
				if($mark == 'unset') {
					$model = $question->getUserMarkModel($user);
					if(isset($model)) {
						$model->delete();
					}
				} else {
					if (validateMark($mark, false, true, $assignment->mark_limit)) {
						$question->setUserMarkSave($user, $mark);
					} else {
						$json ['alert'] = 'Please enter a valid mark as percentage between 0 and ' . $assignment->mark_limit . '.';
					}
				}
			}
				
			$assignment->invalidateRelations();
			$question->invalidateRelations();
			AssignmentMark::updateAutomaticMark($assignment, $user);
				
			$isLastColumn = false;
			
			$json ['html'] = View::make('assignment.assignment_editMarkButton_insert')
				->withUser($user)
				->withAssignment($assignment)
				->withQuestion($question)
				->with('offerEdit', true)
				->with('isLastColumn', $isLastColumn)
				->render();
			
			$json ['update'] = array(
				'final_mark_button_'.$user->id => View::make('assignment.assignment_editMarkButton_insert')
													->withUser($user)
													->withAssignment($assignment)
													->with('offerEdit', true)
													->render(),
				);
			
		
			return Response::json($json);
		
		});	
	}
	
	public function autoMarkShow($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
				
			$assignment = Assignment::findOrFail($assignment_id);
				
			if (! $assignment->maySetFinalMarks(Auth::user())) {
				App::abort(403);
			}
				
			$subject = $assignment->subject;
				
			$marksToSet = Session::get('marksToSet', null);
			
			if(isset($marksToSet)) {
				$answerIds = array();
				foreach($marksToSet as $item) {
					$answerIds[] = $item['answer'];
				}

				$answers = $assignment->submittedAnswers()->whereIn('id',$answerIds)->with('reviews')->with('user')->get();
				$answersById = array();
				foreach($answers as $answer) {
					$answersById[$answer->id] = $answer;
				}
				
				foreach($marksToSet as &$item) {
					if(isset($answersById[$item['answer']])) {
						$item['answer'] = $answersById[$item['answer']];
					} else {
						$item = null; 
					}
				} unset($item);
				
			}
			
			return View::make('assignment.assignment_autoMark', array (
					'subject' => $subject,
					'assignment' => $assignment,
					'marksToSet' => $marksToSet,
			));
		
		});		
	}

	
	public function autoMarkPost($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
	
			$assignment = Assignment::findOrFail($assignment_id);
	
			if (! $assignment->maySetFinalMarks(Auth::user())) {
				App::abort(403);
			}
	
			$rules = array ();
			
			
			foreach(array('any', 'peer', 'tutor', 'lecturer') as $who) {
				$rules["min_$who"] = 'required|numeric|min:0|max:100';
				$rules["max_$who"] = "required|numeric|min:0|max:100|equallarger:min_$who";
				
				$rules["worsethan_$who"] = 'required|numeric|min:0|max:100';
				$rules["betterthan_$who"] = 'required|numeric|min:0|max:100';
				
				if($who != 'any' ) {
					$rules["pending_$who"] = 'required|integer|min:0|max:1';
				}
			}
			
			foreach(ReviewFlag::getValues() as $flag) {
				if($flag == ReviewFlag::none) continue;
				$rules["flag_$flag"] = 'required|integer|min:0|max:1';
			}

			$rules["rating_unrated"] = 'required|integer|min:0|max:1';
			foreach(ReviewRating::getValues() as $rating) {
				$rules["rating_$rating"] = 'required|integer|min:0|max:1';
			}

			foreach($assignment->questions_with_subquestions_without_masters_ordered as $question) {
				$rules["question_".$question->id] = 'required|integer|min:0|max:1';
			}
			
			$rules['mark_mode'] = 'required|integer|min:1|max:4';

			// make sure we have enough reviews to mark at all.
			if(Input::get('mark_mode') == 3) {
				$rules["min_tutor"] .= '|min:1';
			} else if(Input::get('mark_mode') == 4) {
				$rules["min_lecturer"] .= '|min:1';
			} else {
				$rules["min_any"] .= '|min:1';
			}
				
			$onlyValues = array_keys($rules);
			
			
			$input = Input::only($onlyValues);
			
			
			$validator = Validator::make($input, $rules);
			
			Input::flashOnly($onlyValues); // we always want the input on the session, even in the success case.
			if ($validator->fails()) {
				return Redirect::route('autoMarkAssignment', $assignment->id)->withErrors($validator->messages());
			}
			
			// ok, the input is clean now. Let's build a database query to filter some of the stuff already.
			$query = $assignment->submittedAnswers();
			
			// make sure no mark exists
			$query->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('question_marks')
                      ->whereRaw('question_marks.question_id = answers.question_id')
                      ->whereRaw('question_marks.user_id = answers.user_id');
            });
			
			// a little helpers for adding where clauses
			$addReviewerRoleWhere = function($q, $who) {
				
					switch($who) {
						case 'peer':
							$q->where('reviewer_role', ReviewReviewerRole::student);
							break;
						case 'tutor':
							$q->where('reviewer_role', ReviewReviewerRole::tutor);
							break;
						case 'lecturer':
							$q->where('reviewer_role', ReviewReviewerRole::lecturer);
							break;
						default:
					}
			};
			
			$addReviewerRoleAndRatingWhere = function($q, $who) use ($input, $addReviewerRoleWhere) {

				$q->where('status', ReviewStatus::completed);

				$addReviewerRoleWhere($q, $who);
				
				$q->where(function($q) use ($input) { 
					
					$q->whereRaw('0=1'); // just to make sure we don't have an empty where clause if nothing is selected.
					
					if($input['rating_unrated'] == 1) {
						$q->orWhere('review_rated', PlatypusBool::false);
					}
					
					foreach(ReviewRating::getValues() as $rating) {
						if($input["rating_$rating"] == 1) {
							$q->orWhere(function($q) use($rating) {
								$q->where('review_rated', PlatypusBool::true)
								->where('review_rating', $rating);
							});
						}
					}
	  			});
			};
			
			
			// filter by pending reviews
			foreach(array('peer', 'tutor', 'lecturer') as $who) {
				if ($input["pending_$who"] != 1) {
					$query->whereHas('reviews', function($q) use($input, $who, $addReviewerRoleWhere) {
						$addReviewerRoleWhere($q, $who);
						$q->where('status', ReviewStatus::task);
					}, '=', 0);
				}
			}
			
			
			// filter based on the review count
			// this already takes care of the review ratings.
			foreach(array('any', 'peer', 'tutor', 'lecturer') as $who) {
				
				$query->whereHas('reviews', function($q) use($input, $who, $addReviewerRoleAndRatingWhere) {
					$addReviewerRoleAndRatingWhere($q, $who);
				}, '>=', $input["min_$who"]);
				
				$query->whereHas('reviews', function($q) use($input, $who, $addReviewerRoleAndRatingWhere) {
					$addReviewerRoleAndRatingWhere($q, $who);
				}, '<=', $input["max_$who"]);
			}
			
			// filter based on flags that might be present
			foreach(ReviewFlag::getValues() as $flag) {
				if($flag == ReviewFlag::none) continue;
				if ($input["flag_$flag"] != 1) {
					$query->whereHas('reviews', function($q) use($input, $flag, $addReviewerRoleAndRatingWhere) {
						$addReviewerRoleAndRatingWhere($q, 'any');
						$q->where('flag', $flag);
					}, '=', 0);
				}
			}
	
				
			// filter by question
			$questionIds = array ();
			foreach ( $assignment->questions_with_subquestions_without_masters_ordered as $question ) {
				if ($input ["question_" . $question->id] == 1) $questionIds [] = $question->id;
			}
			
			$query->whereIn('question_id', $questionIds);
					
			
			// the only thing we haven't filtered for yet is the difference to the mark to be set.
			// we have to do that manually once we calculated the mark.
			
			// get the answers we want to mark
			$answers = $query->with('reviews')->get();
			
			
			// a little helper to filter reviews
			$considerThisReview = function($review) use ($input) {
				if (!$review->isCompleted()) return false;
				
				if ($review->isRated()) {
					foreach(ReviewRating::getValues() as $rating) {
						if( ($input["rating_$rating"] != 1) && ($review->review_rating == $rating) ) {
							return false;
						}
					}
				} else {
					if($input['rating_unrated'] != 1) {
						return false;
					}
				}
			
				return true;					
			};

			$considerThisReviewforMarkCalculation = function($review) use ($input, $considerThisReview) {
			
				switch($input['mark_mode']) {
					case 3:
						if(!$review->isTutorReview()) return false;
						break;
					case 4:
						if(!$review->isLecturerReview()) return false;
						break;
					default:
				}
			
				return $considerThisReview($review);
			};
				
			// the array that contains the calculated marks
			$marksToSet = array();
			
			// process the answers one by one
			foreach($answers as $answer) {
				$marks = array();
				
				foreach($answer->reviews as $review) {
					if ($considerThisReviewforMarkCalculation($review)) {
						$marks[] = $review->mark;
					}
				}
				
				// check if we have any marks.
				if(count($marks) < 1) {
					App::abort(500, 'This should not happen this is a bug.');
				}
				
				// calculate the mark.
				switch($input['mark_mode']) {
					case 1:
					case 3:
					case 4:
						$mark = array_mean($marks);
						break;
					case 2:
						$mark = array_median($marks);
						break;
					default:
						App::abort(500, 'We should never reach this.');
				}			
				
				// check the difference constraints.
				foreach($answer->reviews as $review) {
					if(!$considerThisReview($review)) continue;
					
					if($mark - $review->mark > $input['betterthan_any']) continue 2;
					if($review->mark - $mark > $input['worsethan_any']) continue 2;
					
					if($review->isStudentReview()) {
						if($mark - $review->mark > $input['betterthan_peer']) continue 2;
						if($review->mark - $mark > $input['worsethan_peer']) continue 2;
					}
					if($review->isTutorReview()) {
						if($mark - $review->mark > $input['betterthan_tutor']) continue 2;
						if($review->mark - $mark > $input['worsethan_tutor']) continue 2;
					}
					if($review->isLecturerReview()) {
						if($mark - $review->mark > $input['betterthan_lecturer']) continue 2;
						if($review->mark - $mark > $input['worsethan_lecturer']) continue 2;
					}
						
				}
				
				// we are still going. We store the result
				$marksToSet[] = array('answer' => $answer->id, 'mark' => $mark);
				
			}
			
			//dd($marksToSet);
			
			$result = Redirect::route('autoMarkAssignment', $assignment->id);
			if(count($marksToSet) > 0) {
				$result->withWarning('Please review and confirm the calculated marks below')->with('marksToSet', $marksToSet);
			} else {
				$result->withDanger('No answers match your filter criterions');
			}
			return $result;
	
		});
	}
	
	
	public function autoMarkSetRealPost($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
		
			$assignment = Assignment::findOrFail($assignment_id);
		
			if (! $assignment->maySetFinalMarks(Auth::user())) {
				App::abort(403);
			}
			
			$marksToSet = array();
			
			foreach(Input::all() as $key => $value) {
				if(substr($key, 0, strlen('set_mark_for_answer_')) == 'set_mark_for_answer_') {
					$answer_id = substr($key, strlen('set_mark_for_answer_'));
					if(!is_numeric($answer_id)) {
						$result = Redirect::route('autoMarkAssignment', $assignment->id);
						$result->withDanger('Some marks are unable to pass validation. All marks must be numerical values.');
						return $result;
					}
					if(!validateMark($value, false, true, $assignment->mark_limit)) {
						$result = Redirect::route('autoMarkAssignment', $assignment->id);
						$result->withDanger('Some marks are unable to pass validation. Values must be between 0 and '. $assignment->mark_limit .'.');
						return $result;
					}
					$marksToSet[] = array('answer' => $answer_id, 'mark' => $value);
				}
			}
			
			$answerIds = array();
			foreach($marksToSet as $item) {
				$answerIds[] = $item['answer'];
			}
			
			$answers = $assignment->submittedAnswers()->whereIn('id',$answerIds)->with('question')->with('user')->get();
			$answersById = array();
			foreach($answers as $answer) {
				$answersById[$answer->id] = $answer;
			}
			
			foreach($marksToSet as &$item) {
				if(isset($answersById[$item['answer']])) {
					$item['answer'] = $answersById[$item['answer']];
				} else {
					$item = null;
				}
			} unset($item);
			
			$successCount = 0;
			$usersToUpdate = array();
			foreach($marksToSet as $item) {
				if(!isset($item)) continue;
				$answer = $item['answer'];
				$answer->question->setUserMarkSave($answer->user, $item['mark']);
				$usersToUpdate[$answer->user->id] = $answer->user;
				$successCount++;
			}
			
			foreach($usersToUpdate as $user) {
				AssignmentMark::updateAutomaticMark($assignment, $user);
			}
			
			return Redirect::route('autoMarkAssignment', $assignment->id)->withSuccess("$successCount marks have been set.");
			
		});
	}
	
}

