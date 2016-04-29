<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Redirect;
class ReviewController extends BaseController {

	public function showManageReviews($assignment_id) {
		return Platypus::transaction(function() use($assignment_id) {
				
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayViewReviews(Auth::user())) App::abort(403);

			$users = $assignment->subject->users();
			
			$users = UserController::autoPaginateUsers($users);

			return View::make('review.review_studentList')->withAssignment($assignment)->withUsers($users);
		});
	}
	
	public function showManageReviewsUser($assignment_id, $user_id) {
		return Platypus::transaction(function() use($assignment_id, $user_id) {
	
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayViewReviews(Auth::user())) App::abort(403);
	
			$user = User::findOrFail($user_id);
			if (! $assignment->isMember($user)) App::abort(403);
			
			$editable = $assignment->mayEditReviews(Auth::user());
				
			$reviews = $assignment->getUserReviewsQuery($user)->with('answer.question.assignment', 'answer.user')->get();
			$reviews->sortBy(function($review) {
				$result = $review->question->presenter()->full_position;
				$result .=' '. $review->answer->user->last_name . ' ' . $review->answer->user->first_name;
				return $result;
			});
			
			$dispatchedReviews = $assignment->getDispatchedReviewsQuery($user)->with('answer.question.assignment', 'user')->get();
			$dispatchedReviews->sortBy(function($review) {
				$result = $review->question->presenter()->full_position;
				$result .=' '. $review->user->last_name . ' ' . $review->user->first_name;
				return $result;
			});
					
			
			return View::make('review.review_manageUser')->withAssignment($assignment)->withUser($user)->withReviews($reviews)->with('dispatchedReviews', $dispatchedReviews);
		});
	}
	
	public function manageReviewsUserPost($assignment_id, $user_id) {
		return Platypus::transaction(function() use($assignment_id, $user_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayViewReviews(Auth::user())) App::abort(403);
			
			$user = User::findOrFail($user_id);
			if (! $assignment->mayEditReviews(Auth::user())) App::abort(403);
				
			$onlyValues = array('reassignee'); 
			$review_ids = array();
			foreach(Input::all() as $key => $value) {
				if ($value != "1") continue;
				if (substr($key, 0, 7) == 'review_') {
					$onlyValues[] = $key;
					$id = substr($key,7);
					if (is_numeric($id)) {
						$review_ids[] = $id;
					} else {
						App::abort(404);
					}
					
				}
			}
			$reviewsQuery = $assignment->allReviews()->whereIn('id',$review_ids)->where('status', ReviewStatus::task);
			
			$button = Input::get('button');
			$success = null;
			$warning = null;
			$danger = null;
			$errors = new MessageBag();
			
			
			if ($button == 'delete') {
				
				$count = $reviewsQuery->delete();
				if ($count > 0) {
					$success = "The selected review tasks have been deleted.";
				} else {
					$warning = "You did not select any review tasks to delete.";
				}
												
			} else if ($button == 'reassign') {
				
				$ok = false;
				do {
					
					$reviews = $reviewsQuery->get();
					
					if (count($reviews) == 0) {
						$warning = "You did not select any review tasks to re-assign.";
						break;
					}
					
					$reassignee = Input::get('reassignee');
					$messages = array ();
					if (! validateSimple($reassignee, 'required|emailorstudentid', $messages)) {
						foreach ( $messages as $message ) {
							$errors->add('reassignee', $message);
						}
						$danger = 'The user you entered is not valid.';
						break;
					}
					
					$reassignee = User::findByEmailOrIdInSubject($assignment->subject, $reassignee);
					if (is_null($reassignee)) {
						$errors->add('reassignee', 'The user could not be found');
						$danger = 'The user you entered could not be found.';
						break;
					}
					
					if ($assignment->isActiveStudent($reassignee)) {
						$role = ReviewReviewerRole::student;
					} else if ($assignment->isLecturer($reassignee)) {
						$role = ReviewReviewerRole::lecturer;
					} else if ($assignment->subject->isActiveTutor($reassignee)) {
						$role = ReviewReviewerRole::tutor;
					} else {
						$errors->add('reassignee', 'The user cannot review for this assignment.');
						$danger = 'The user you entered cannot review for this assignment.';
						break;
					}					
					
					
					foreach ( $reviews as $review ) {
						if ($review->user_id == $reassignee->id) continue;
						
						if ($review->answer->user_id == $reassignee->id) {
							$danger = "You are trying to let a student review his/her own answers. This cannot be right.";
							DB::rollback();
							break 2;
						}

						// attach a new text block since it is a new user. 
						$textBlock = new TextBlock();
						$textBlock->save();
						$review->text_id = $textBlock->id;
						
						$review->user_id = $reassignee->id;
						
						$review->save();	
					}
					
					$success = 'The selected review tasks have been re-assigned.';
					$ok  =true;
					
				} while ( false );
				
				if(!$ok) {
					Input::flashOnly($onlyValues);
				}
				
			} else {
				App::abort(404);
			}
			
			
			return Redirect::route('manageReviewsUser', array('assignment_id' => $assignment->id, 'user_id' => $user->id))
				->withErrors($errors)
				->withSuccess($success)
				->withWarning($warning)
				->withDanger($danger);
			
			
		});
	}
	
	
	
	private static function prepareQueryStringData($assignment, $defaults = array()) {
		
		$fields = array('page', 'perpage', 'completed', 'reviewsonly', 'ergonomic', 'automatic', 'sortbyquestion', 'user', 'onlyquestion', 'viewgroup');
		
		$result = array();
		
		foreach($fields as $field) {
			$value = Input::get($field, null);
			if(validateSimple($value, 'required|numeric|integer|min:0')) {
				$result[$field] = $value+0;
			} else {
				if (isset($defaults[$field])) {
					$result[$field] = $defaults[$field];
				} else {
					$result[$field] = null;
				}
			}
		}
		
		// it makes sense to couple the automatic feature to the completed one.
		// we combine them here and don't offer the button for automatic in the gui.
		if ($result['completed'] == 1) {
			$result['automatic'] = 0;
		} else {
			$result['automatic'] = 1;
		}
		
		if(isset($result['onlyquestion'])) {
			if(!$assignment->allQuestions()->where('id', $result['onlyquestion'])->exists()) {
				$result['onlyquestion'] = null;
			}
		}
		
		if(isset($result['user'])) {
			if(!$assignment->maySetFinalMarks(Auth::user())) App::abort(403);
			if(!$assignment->mayBrowseStudents(Auth::user())) App::abort(403);
			$user = User::findOrFail($result['user']);
			if(!$assignment->isStudent($user)) App::abort(404);
			$result['user'] = $user->id;
			
			// if we have only one user, we force the options.
			$result['completed'] = 1;
			$result['automatic'] = 0;
			$result['reviewsonly'] = 0;
			$result['sortbyquestion'] = 0;
			$result['ergonomic'] = 1;
			$result['onlyquestion'] = null;
		} else {
			$result['user'] = null;
		}
		
		if ( $assignment->isStudent(Auth::user())) {
			$result['ergonomic'] = 0;
		} 
		
		return $result;
		
	} 
	
	private static function prepareQueryString($assignment, $defaults = array()) {
		return http_build_query(self::prepareQueryStringData($assignment, $defaults));
	}
	
	private static function prepareDataToShowReviewTasks($assignment) {
		// see if we should show the completed reviews as well. Default to "no".
		
			
		$user = Auth::user();
			
			
		// start building some defaults.
		$queryData = array();
		
		$queryData['page'] = 1;
		
		// get a sensible default for the perPage variable
		if ($assignment->shuffle_mode == AssignmentShuffleMode::wholeassignments) {
			$queryData['perpage'] = 1;
		} else {
			$queryData['perpage'] = 5;
		}
		
		$queryData['completed'] = 0;
		
		if ($assignment->isLecturer(Auth::user()) || $assignment->isTutor(Auth::user())) {
			$queryData['ergonomic'] = 1;
			$queryData['automatic'] = 1;
		}
		
		if ($assignment->maySetFinalMarks(Auth::user())) {
			$queryData['reviewsonly'] = 0;
		}
		
	
		// merge the defaults with the query string we got.
		$queryData = self::prepareQueryStringData($assignment, $queryData);
		
		if(isset($queryData['user'])) {
			$onlyMarkUser = User::findOrFail($queryData['user']);
		} else {
			$onlyMarkUser = null;
		}

		$reviewsOnly = ($queryData['reviewsonly'] == 1) || !$assignment->maySetFinalMarks(Auth::user());
		$showCompleted = ($queryData['completed'] == 1);
		$sortByQuestion = ($queryData['sortbyquestion'] == 1);
		$onlyQuestion = $queryData['onlyquestion'];
		$viewGroup = ($queryData['viewgroup'] == 1);

		// prepare the data we want to show.
		// the result is a paginator, but we have to do it manually since we're not starting with a query. 
		$reviewQuery = $assignment->getUserReviewDataOrderedQuery($user, $showCompleted, $reviewsOnly, $sortByQuestion, $onlyQuestion, $viewGroup);
		
		if (isset($onlyMarkUser)) {
			$reviewQuery->where('answers.user_id', $onlyMarkUser->id);
		}
		
		$allData = $reviewQuery->get();
		
		// convert into an associative array for convenience
		$newData = array();
		foreach($allData as $record) {
			$newItem = array();
			$newItem['user_id'] = $record->answer_user_id;
			if(isset($record->the_question_id)) {
				$newItem['question_id'] = $record->the_question_id;
			}
			$newData[] = $newItem;
		}
		$allData = $newData;
		
		// we must make sure the order doesn't jump too much between requests. 
		// So we compare the data with the last request we had and if nothing got added, we keep the order.
		if(Session::has('lastReviewDataArray')) {
			$oldData = Session::pull('lastReviewDataArray');
			if(isset($oldData[0])) {
				$oldData = $oldData[0];
			}
			if(is_array($oldData)) {
				$ok = true;
				foreach($allData as $item) {
					if (array_search($item, $oldData, true) === false) {
						$ok = false;
						break;
					}
				}
				if($ok) {
					$newData = array();
					foreach($oldData as $item) {
						if (array_search($item, $allData, true) !== false) {
							$newData[] = $item;
						}
					}
					$allData = $newData;					
				}
			}
		}
		$oldData = Session::push('lastReviewDataArray', $allData);
		
		$maxPage = ceil(count($allData) / $queryData['perpage']);
		if ($queryData['page'] > $maxPage) {
			$queryData['page'] = $maxPage;
		}
		
		
		// continue with making the paginator
		$reviewQuery = $reviewQuery->getConnection()->getPaginator()->make(
				array_slice(
						$allData,($queryData['page']-1)*$queryData['perpage'],
						$queryData['perpage']
				), 
				count($allData), $queryData['perpage']);
		
		// set the base url manually in case we're in an ajax request.
		$reviewQuery->setBaseUrl(route('showReviewTasks', $assignment->id));
		
		// add the query parameters.
		foreach($queryData as $key => $value) {
			$reviewQuery->addQuery($key, $value);
		}
			
		
		// do we want to show related reviews?
		$showRelatedStudentReviews = false;
		$showRelatedTutorReviews = false;
		
		if($assignment->isLecturer($user)){
			$showRelatedStudentReviews = true;
			$showRelatedTutorReviews = true;
		}
		
		if ($assignment->tutorsCanSeeStudentReviews() && $assignment->isTutor($user)) {
			$showRelatedStudentReviews = true;
		}
		
		
		// cache the question objects with some of the relationships
		$questions = array();
		foreach($assignment->allQuestions()->with('text.attachments', 'solution.attachments', 'markingScheme.attachments', 'masterQuestion.text')->get() as $question) {
			$question->injectCachedRelation('assignment', $assignment);
			$question->injectCachedRelation('assignment_real', $assignment);
			//$question->dumpRelations();
			$questions[$question->id] = $question;
		}
		
			
		// Let's prepare the actual data we want to display.
		$reviewData = array();
		foreach($reviewQuery as $record) {
			$data = array();
			$data['user'] = User::findOrFail($record['user_id']);
			if (isset($record['question_id'])) {
				if (!isset($questions[$record['question_id']])) App::abort(500, 'This should not happen. This is a bug.');
				$question = $questions[$record['question_id']];
				$data['question'] = $question;
				if ($reviewsOnly) {
					$data['reviews'] = $question->getUserReviewsOneUserOrderedQuery($user, $data['user'], $viewGroup)->get();
					$data['answers'] = array();
				} else {
					$data['reviews'] = array();
					$data['answers'] = $question->submittedAnswers()->where('user_id', $data['user']->id)->get();
				}
				
			} else {
				if ($reviewsOnly) {
					$data['reviews'] = $assignment->getUserReviewsOneUserOrderedQuery($user, $data['user'], $viewGroup)->get();
					$data['answers'] = array();
				} else {
					$data['reviews'] = array();
					$data['answers'] = $assignment->submittedAnswers()->where('user_id', $data['user']->id)->get();
				}
			}
			if( (count($data['reviews']) == 0) && (count($data['answers']) == 0) ) {
				App::abort(500, 'This should not happen. This is a bug.');
			}
		
			$reviewData[] = $data;
		}
			
		
		// let's eager load some relationships manually to reduce the amount of db queries.
		// get the ids of stuff we need.
		$textBlockIds = array();
		$answerIds = array();
		foreach($reviewData as $data) {
			foreach($data['reviews'] as $review) {
				$textBlockIds[] = $review->text_id;
				$answerIds[] = $review->answer_id;
				if (isset($review->review_feedback_id)) $textBlockIds[] = $review->review_feedback_id;
			}
		}
			
		// load the textblocks
		$textBlocks = array();
		foreach(TextBlock::whereIn('id', $textBlockIds)->with('attachments')->get() as $item) {
			$textBlocks[$item->id] = $item;
		}
			
		// load the answers
		$answers = array();
		foreach(Answer::whereIn('id', $answerIds)->with('text.attachments', 'user')->get() as $item) {
			if (!isset($questions[$item->question_id])) App::abort(500, 'This should not happen. This is a bug.');
			$item->injectCachedRelation('question',$questions[$item->question_id]);
			$answers[$item->id] = $item;
		}
			
		// inject the relations.
		$textBlockIds = array();
		$answerIds = array();
		foreach($reviewData as $data) {
			foreach($data['reviews'] as $review) {
				$review->injectCachedRelation('text', $textBlocks[$review->text_id]);
				$review->injectCachedRelation('answer', $answers[$review->answer_id]);
				$review->injectCachedRelation('question', $review->answer->question);
				$review->injectCachedRelation('assignment', $assignment);
				if (isset($review->review_feedback_id)) $review->injectCachedRelation('reviewFeedback', $textBlocks[$review->review_feedback_id]);
			}
		}

		$viewOptions = array(
				'showCompleted' => isset($queryData['completed']) && ($queryData['completed'] == 1),
				'urlQueryString' => http_build_query($queryData),
				'urlQueryData' => $queryData,
				'showRelatedStudentReviews' => $showRelatedStudentReviews,
				'showRelatedTutorReviews' => $showRelatedTutorReviews,
				'useWide' => $showRelatedStudentReviews || 	$showRelatedTutorReviews,
				'ergonomic' => isset($queryData['ergonomic']) && ($queryData['ergonomic'] == 1),
		);
		
		return array(
			'reviewData' => $reviewData,
			'reviews' => $reviewQuery,
			'options' => $viewOptions,
		);
		
	}
	
	public function showReviewTasks($assignment_id) {
		return Platypus::transaction(function() use($assignment_id) {
		
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayWriteReviews(Auth::user())) App::abort(403);



			return View::make('review.review_writeMany')
				->withAssignment($assignment)
				->with(static::prepareDataToShowReviewTasks($assignment));
		});		
	}
		
	
	private static function validateMark(&$mark, $allowEmpty = true, $allowDecimals = false) {
		// we moved this to a general helper function.
		return validateMark($mark, $allowEmpty, $allowDecimals);
	}
	
	public function setReviewMarkAjax($review_id) {
		return Platypus::transaction(function () use($review_id) {
				
			$review = Review::findOrFail($review_id);
			
			if (!$review->mayEditTextAndMark(Auth::user())) App::abort(403);
							
			$mark = Input::get("mark");


			$ok = validateMark($mark, true, !$review->assignment->isStudent(Auth::user()), $review->assignment->mark_limit);

			if ($ok) {
				$review->mark = $mark;
	
				$review->save();
	
				$json = array ();
				$json ['success'] = true;
				$json ['html'] = View::make('review.review_edit_mark_insert')->withReview($review)->render();
				$json ['growl'] = "Your change has been saved.";
				return Response::json($json);
			} else {
				$json = array ();
				$json ['success'] = false;
				if ($review->assignment->isStudent(Auth::user())) {
					$json ['alert'] = 'Please enter an integer value between 0 and ' . $review->assignment->mark_limit . '.';
				} else {
					$json ['alert'] = 'Please enter a value between 0 and ' . $review->assignment->mark_limit  . '.';
				}
				return Response::json($json);
			}
		});
	}	
	
	public function setReviewFlagAjax($review_id) {
		return Platypus::transaction(function () use($review_id) {
	
			$review = Review::findOrFail($review_id);
				
			if (!$review->mayEditTextAndMark(Auth::user())) App::abort(403);
				
			$flag = Input::get("flag");
	
			$ok = in_array($flag, $review->getAllowedFlags());
			
			if ($ok) {
				$review->flag = $flag;
	
				$review->save();
	
				$json = array ();
				$json ['success'] = true;
				$json ['html'] = View::make('review.review_edit_flag_insert')->withReview($review)->render();
				$json ['growl'] = "Your flag has been saved.";
				return Response::json($json);
			} else {
				$json = array ();
				$json ['success'] = false;
				$json ['html'] = View::make('review.review_edit_flag_insert')->withReview($review)->render();
				$json ['alert'] = 'You are not allowed to set this flag value.';
				return Response::json($json);
			}
		});
	}
	
	
	private static function addAutomatedScrollingToJson(&$json, $assignment, $queryData) {
		$displayData = static::prepareDataToShowReviewTasks($assignment);
		
		$json['update'] = array();
		$json['update']['paginationLinks'] = $displayData['reviews']->links()->render();
		
		$json ['order'] = array();
		$json ['orderother'] = 'panel_groups_order_container';
		
		$showEditorIfEmpty = true;
		
		foreach($displayData['reviewData'] as $data) {
				
			if(!empty($data['reviews'])) {
				$panelId = "review_group_panel_".$data['reviews'][0]->id;
			} else {
				$panelId = "answer_group_panel_".$data['answers'][0]->id;
			}
			
			$json ['order'][] = array(
					'id' => $panelId,
					'html' => View::make('review.review_writeGroup_insert')
					->with(array('reviews' => $data['reviews'],
							'answers' => $data['answers'],
							'options' => array_merge($displayData['options'], array('showEditorIfEmpty' => $showEditorIfEmpty,)),
					))->render(),
			);
		
// 			if($showEditorIfEmpty) {
// 				foreach($data['reviews'] as $review) {
// 					$json ['script'] .= '$(\'.emptyTextBlockEditButton[data-textblock-id="'.$review->text->id.'"]\').click();';
// 				}
// 			}
				
			$showEditorIfEmpty = false;
		}		
	}
	
	public function submitReview($review_id) {
		return Platypus::transaction(function () use($review_id) {
			$review = Review::findOrFail($review_id);
			$review_id = $review->id;
			$user = $review->answer->user;
			$assignment = $review->assignment;
			$answer = $review->answer;
			$question = $review->question;
			
			// we do the permission checking further down this time because there are two distinct tasks we are dealing with.
			// we make a quick sanity check though.
			if($review->user_id != Auth::user()->id) {
				App::abort(404);
			}
			
			// check whether the user is already done with this business. (needed later for determining animations)
			$reviewWasFinished = true;
			if (!$review->isCompleted()) $reviewWasFinished = false;
				
			if ($assignment->maySetFinalMarks(Auth::user())) {
				$markModel = $answer->question->getUserMarkModel($user);
				if (!isset($markModel)) $reviewWasFinished = false;
			}
			
			
			// check which button has been used and set our options
			
			$button = Input::get('button');
			switch ($button) {
				case 'normal-review':
					$submitReview = true;
					$setFinalMark = false;
					$finalMark = null;
					break;
					
				case 'submit-final-mark':
					$submitReview = true;
					$setFinalMark = true;
					$finalMark = $review->mark;
					break;
				
				case 'submit-final-mark-mean':
					$submitReview = true;
					$setFinalMark = true;
					$finalMark = Input::get('mean');
					break;
							
				case 'submit-final-mark-median':
					$submitReview = true;
					$setFinalMark = true;
					$finalMark = Input::get('median');
					break;
							
				case 'final-mark':
					$submitReview = false;
					$setFinalMark = true;
					$finalMark = Input::get('mark');
					break;
				
				case 'final-mark-mean':
					$submitReview = false;
					$setFinalMark = true;
					$finalMark = Input::get('mean');
					break;
							
				case 'final-mark-median':
					$submitReview = false;
					$setFinalMark = true;
					$finalMark = Input::get('median');
					break;
							
				default:
					// no button detected ==> throw an error and do nothing.
					$json = array ();
					$json ['success'] = true;
					$json ['alert'] = 'Please use a button to submit this form.' . $button;
					return Response::json($json);
				
			}
			
			return self::submitReview_real($review, $answer, $submitReview, $setFinalMark, $finalMark);
			
			
			
		});
	}
	
	
	private static function submitReview_real($review, $answer, $submitReview = false, $setFinalMark = false, $finalMark = null) {

		if(!$submitReview && !$setFinalMark) {
			App::abort(500, 'This is a bug. Someone forgot to tell us what to do.');
		}
		
		if(isset($review)) {
			$review_id = $review->id;
			$answer = $review->answer;
		} else {
			if ($submitReview) {
				App::abort(500, 'This is a bug. This should not happen.');
			}
		}

		$user = $answer->user;
		$assignment = $answer->assignment;
		$question = $answer->question;
		
		
		// we do the proper permission checking further down this time because there are two distinct tasks we are dealing with.
		// we make a quick sanity check though.
		if (isset($review)) {
			// if we are dealing with a review, the user should be the owner of the review.
			if($review->user_id != Auth::user()->id) {
				App::abort(404);
			}
		} else {
			// if there is no review, we have to set final marks.
			if(!$assignment->maySetFinalMarks(Auth::user())) {
				App::abort(403);
			}
		}
				
		// check whether the user is already done with this business. (needed later for determining animations)
		$reviewWasFinished = true;
		if (isset($review)) {
			if( !$review->isCompleted()) $reviewWasFinished = false;
		} else {
			$potentialReview = $answer->getReview(Auth::user());
			if (isset($potentialReview) && !$potentialReview->isCompleted()) $reviewWasFinished = false;
		}

		if ($assignment->maySetFinalMarks(Auth::user())) {
			$markModel = $answer->question->getUserMarkModel($user);
			if (!isset($markModel)) $reviewWasFinished = false;
		}
				

		// if we are setting a final mark, we don't want to create an empty review.
		if ($setFinalMark && $submitReview && $review->text->isEmpty()) {
			$submitReview = false;
		}
				
		// start the actual work.
		
		$permissionsChecked = false;
		$success = true;
		$errors = array();
	
		$json = array ();
		$json ['success'] = true;
		$json ['script'] = '';
				
		// submit the review if the user requested it.
		if ($submitReview) {
			if (!$review->maySubmitReview(Auth::user())) App::abort(403);
			$permissionsChecked = true;
			$success = $review->submitSave($errors);
		}
				
		// set the final mark if the user requested it
		$deletedReviewIds = array();
		if($success && $setFinalMark) {
			if (!$assignment->maySetFinalMarks(Auth::user())) App::abort(403);
			$permissionsChecked = true;
			if (validateMark($finalMark, false, true, $assignment->mark_limit)) {
				$answer->setFinalMark($finalMark, $deletedReviewIds);
			} else {
				$success = false;
				$errors[] = "You must set a valid percentage as final mark.";
			}
		}
	
		// final check that permissions have been dealt with before we create a response.
		if (!$permissionsChecked) {
			App::abort(403); // we should never reach this, but let's have it for paranoia.
		}
				
				
		// the actual data manipulation is done already.
		// Now we have to create the response.
	
				
		// reload the review because it might have been deleted when we set the mark.
		if(isset($review)) {
			$review = Review::find($review->id); // is null if it was deleted.
		}
				
		// figure out whether the user is done with this review now.
		$reviewFinished = $success;
		if (isset($review)) {
			if( !$review->isCompleted()) $reviewFinished = false;
		} else {
			$potentialReview = $answer->getReview(Auth::user());
			if (isset($potentialReview) && !$potentialReview->isCompleted()) $reviewFinished = false;
		}
		
		if ($assignment->maySetFinalMarks(Auth::user())) {
			$markModel = $answer->question->getUserMarkModel($user);
			if (!isset($markModel)) $reviewFinished = false;
		}		
		
				
		// get the query string which contains some options.
		$queryData = self::prepareQueryStringData($assignment);
				
		$assignment->invalidateRelations();
				
		if ($success) {
			$json ['growl'] = "Your review/mark has been saved.";
			
			foreach($deletedReviewIds as $reviewIdToDelete) {
				$json ['script'] .= '$(".reviewDisplayPanel[data-review-id='.$reviewIdToDelete.']").remove();';
			}				
	
			// make the panel collapse for the user's convenience if the user is done with the task.
			if ($reviewFinished && !$reviewWasFinished && ($queryData['automatic'] == 1)) {
				$domUidV2 = "v2_".$answer->id;
				if(isset($review_id)) {
					$domUid = "v1_".$review_id;
				} else {
					$domUid = $domUidV2;
				}
				
				$json ['script'] .= '$("#showedit_panel_'.$domUid.'").filter(".in").collapse("hide");';
				
				if(isset($review_id) && $assignment->maySetFinalMarks(Auth::user())) {
					// this is in case the review was created on the fly.
					$json ['script'] .= '$("#showedit_panel_'.$domUidV2.'").filter(".in").collapse("hide");';
				}
			}
		} else {
			DB::rollback();
			$json ['alert'] = implode(" ", $errors);
		}
				
		// create the immediate response for the request is (the review panel or the marking panel)
		$json ['html'] = View::make('review.review_edit_real_insert')
			->withReview($review)
			->withAnswer($answer)
			->withOptions(array(
					'urlQueryString' => http_build_query($queryData),
			))
			->render();
	
		// handle the automatic scrolling business.
		if (isset($queryData['automatic']) && ($queryData['automatic'] == 1) && isset($queryData['page']) && isset($queryData['perpage']) ) {
			self::addAutomatedScrollingToJson($json, $assignment, $queryData);
		}
				
		return Response::json($json);
			
	}
		
	
	private static function switchFromErgonomicToFullReviewEditor($answer, $error = null) {
		$json = array ();
		$json ['success'] = true;

		$review = $answer->getReview(Auth::user());
		
		$review = self::createOrFillReviewWhereNecessary($answer, $review, Input::get('mark'), Input::get('text'), true);
		
		$json ['html'] = View::make('review.review_edit_real_insert')
			->withReview($review)
			->withAnswer($answer)
			->withOptions(array(
				'urlQueryString' => self::prepareQueryString($answer->assignment),
				'alwaysShowEditor' => true,
			))
			->render();
			
		if (isset($error)) {
			$json ['alert'] = "$error";
		}
		
		return Response::json($json);		
	}
	
	private static function createOrFillReviewWhereNecessary($answer,$review, $mark, $text, $alwaysCreate = false) {
		
		if (!isset($review)) {
			$review = $answer->getReview(Auth::user());
		}
		
		$assignment = $answer->assignment;
		
		$allowDecimals = ! (isset($review) && $review->isStudentReview());
		if (!validateMark($mark, true, $allowDecimals, $assignment->mark_limit)) {
			$mark = null;
		}
		
		if($alwaysCreate || isset($review) || (isset($text) && !empty(trim($text)))) {
			if(!isset($review)) {
				if (!$answer->assignment->maySetFinalMarks(Auth::user())) {
					App::abort(403);
				}
				if ($assignment->isStudent(Auth::user())) {
					$role = ReviewReviewerRole::student;
				} else if ($assignment->isTutor(Auth::user())) {
					$role = ReviewReviewerRole::tutor;
				} else if ($assignment->isLecturer(Auth::user())) {
					$role = ReviewReviewerRole::lecturer;
				} else {
					App::abort(404);
				}
				$review = Review::createReviewTask($answer->id, Auth::user()->id, $role);
			}
			
			$review->text->setHtmlText($text);
			$review->text->save();
			$review->mark = $mark;
			$review->save();
			$review = Review::find($review->id);
		}
		
		return $review;
	}
	
	public function submitErgonomicReviewAjax($question_id, $user_id) {
		return Platypus::transaction(function () use($question_id, $user_id) {
			$question = Question::findOrFail($question_id);
			$user = User::findOrFail($user_id);
			$assignment = $question->assignment_real;
			
			$answer = $question->getAnswer($user);
			if(!isset($answer)) {
				App::abort(404);
			}
			
			$review = $answer->getReview(Auth::user()); // might be null.
			
			if(isset($review)) {
				if(!$review->isEmpty()) {
					if($review->isCompleted() || $review->mayEditTextAndMark(Auth::user())) {
						return self::switchFromErgonomicToFullReviewEditor($answer, 'The review is not empty any more. Please re-submit it.');
					} else {
						App::abort(403);
					}
				}
			}
	
			$button = Input::get('button');
			switch ($button) {
				case 'normal-review':
					$submitReview = true;
					$setFinalMark = false;
					$finalMark = Input::get('mark');
					break;
						
				case 'submit-final-mark':
					$submitReview = true;
					$setFinalMark = true;
					$finalMark = Input::get('mark');
					break;
			
				case 'submit-final-mark-mean':
					$submitReview = true;
					$setFinalMark = true;
					$finalMark = Input::get('mean');
					break;
						
				case 'submit-final-mark-median':
					$submitReview = true;
					$setFinalMark = true;
					$finalMark = Input::get('median');
					break;
						
				case 'full-editor':
					return self::switchFromErgonomicToFullReviewEditor($answer);
					break;
						
				default:
					// no button detected ==> throw an error and do nothing.
					$json = array ();
					$json ['success'] = true;
					$json ['alert'] = 'Please use a button to submit this form.' . $button;
					return Response::json($json);
			
			}
			
			if($submitReview) {
				$review = self::createOrFillReviewWhereNecessary($answer, $review, $finalMark, Input::get('text'), !$setFinalMark);
				if (is_null($review)) {
					$submitReview = false;
				}
			}
			
			return self::submitReview_real($review, $answer, $submitReview, $setFinalMark, $finalMark);

		});
	}	
	
	public function addAdHocReviewAjax($question_id, $user_id) {
		return Platypus::transaction(function () use($question_id, $user_id) {
			$question = Question::findOrFail($question_id);
			$user = User::findOrFail($user_id);
			$assignment = $question->assignment_real;
				
			if(!$assignment->maySetFinalMarks(Auth::user())) {
				App::abort(403);
			}
			
			$answer = $question->getAnswer($user);
			if(!isset($answer)) {
				App::abort(404);
			}
				
			$review = $answer->getReview(Auth::user()); // should be null.
				
			if(!isset($review)) {
				if ($assignment->isStudent(Auth::user())) {
					$role = ReviewReviewerRole::student;
				} else if ($assignment->isTutor(Auth::user())) {
					$role = ReviewReviewerRole::tutor;
				} else if ($assignment->isLecturer(Auth::user())) {
					$role = ReviewReviewerRole::lecturer;
				} else {
					App::abort(404);
				}
				
				$review = Review::createReviewTask($answer->id, Auth::user()->id, $role);
			}
			
			$json = array ();
			$json ['success'] = true;
			
			$queryData = self::prepareQueryStringData($assignment);
			
			// create the immediate response for the request is (the review panel or the marking panel)
			$json ['html'] = View::make('review.review_edit_real_insert')
				->withReview($review)
				->withAnswer($review->answer)
				->withOptions(array(
					'urlQueryString' => http_build_query($queryData),
					'alwaysShowEditor' => true,
				))
			->render();			
			
			return Response::json($json);
						
	
		});
	}	
	
	public function retractReview($review_id) {
		return Platypus::transaction(function () use($review_id) {
			$review = Review::findOrFail($review_id);
				
			if (!$review->mayRetractReview(Auth::user())) App::abort(403);
				
			$review->retractSave();
			
			$json = array ();
			$json ['success'] = true;
			$json ['growl'] = "The review has been retracted.";
			$json ['html'] = View::make('review.review_edit_real_insert')
				->withReview($review)
				->withOptions(array(
						'urlQueryString' => getPaginationQueryString(),
						'ergonomic' => false,
				))
				->render();
	
			return Response::json($json);
				
		});
	}
	
	public function setFinalQuestionMarkAjax($question_id, $user_id) {
		return Platypus::transaction(function () use($question_id, $user_id) {
			$question = Question::findOrFail($question_id);
			$assignment = $question->assignment_real;
			
			if (!$assignment->maySetFinalMarks(Auth::user())) {
				App::abort(403);
			}
			
			$user = User::findOrFail($user_id);

			$answer = $question->getAnswer($user);
			if (!isset($answer)) {
				App::abort(404);
			}
			
			$reviewWasFinished = $answer->hasFinalMark();
				
			$json = array ();
			$json ['success'] = true;
			$json ['script'] = '';
			
			$mark = Input::get('mark','');
			if (validateMark($mark, false, true, $assignment->mark_limit)) {
				$deletedReviewIds = array();
				$answer->setFinalMark($mark, $deletedReviewIds);
				$success = true;
				$json ['growl'] = "The mark has been saved.";
				foreach($deletedReviewIds as $reviewIdToDelete) {
					$json ['script'] .= '$(".reviewDisplayPanel[data-review-id='.$reviewIdToDelete.']").remove();';
				}
			} else {
				$success = false;
				$json ['alert'] = "You must set a valid percentage as final mark.";
			}
			
			$review = $answer->getReview(Auth::user()); // might be null.

			// get the query string which contains some options.
			$queryData = self::prepareQueryStringData($assignment);
			
			// make the view.
			$json ['html'] = View::make('review.review_edit_real_insert')
			->withAnswer($answer)
			->withReview($review)
			->withOptions(array(
					'urlQueryString' => http_build_query($queryData),
			))
			->render();
				
			// collapse the panel if appropriate.
			if ($success && !$reviewWasFinished) {
				if(isset($review)) {
					$domUid = "v1_".$review->id;
					$json ['script'] .= '$("#showedit_panel_'.$domUid.'").filter(".in").collapse("hide");';
				}
				$domUid = "v2_".$answer->id;
				$json ['script'] .= '$("#showedit_panel_'.$domUid.'").filter(".in").collapse("hide");';
				
				
				// handle the automatic scrolling business.
				if (isset($queryData['automatic']) && ($queryData['automatic'] == 1) && isset($queryData['page']) && isset($queryData['perpage']) ) {
					self::addAutomatedScrollingToJson($json, $assignment, $queryData);
				}
				
			}			
			
			return Response::json($json);
		
		});		
	}

	public function rateReviewAjax($review_id) {
		return Platypus::transaction(function () use($review_id) {
			$review = Review::findOrFail($review_id);
	
			if (!$review->assignment->mayRateReviews(Auth::user())) App::abort(403);

			$json = array ();
			$json ['success'] = true;
			
			if(Input::has('rating')) {
				$review->review_rated = true;
				$review->review_rating = Input::get('rating');
				
				if($review->review_rating == 'unrated' ) {
					$review->review_rated = false;
					$review->review_rating = null;
				}
				
				if($review->validate()) {
					if ($review->save()) {
						$json ['growl'] = "Your rating has been saved.";
					} else {
						$json ['alert'] = "An error occurred. Please try again.";
					}
				} else {
					$json ['alert'] = "Your rating was invalid and gould not be saved.";
				}
			} else {
				$json ['alert'] = "No rating submitted.";
			}
			
			$json ['html'] = View::make('review.review_ratingControls_insert')
				->withReview($review)
				->render();
			
			$json['update'] = array(
						'review_rating_'.$review->id => View::make('review.review_showRating_insert')->withReview($review)->render(),
					);
	
			return Response::json($json);
	
		});
	}
	
	
	
}

