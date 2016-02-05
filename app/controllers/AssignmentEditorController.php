<?php



use Platypus\Helpers\PlatypusBool;

class AssignmentEditorController extends BaseController {


	public function edit($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->maySeeAssignmentEditor(Auth::user())) {
				App::abort(403);
			}
			
			$subject = $assignment->subject;
			
			return View::make('assignment.assignment_edit_layout', array (
					'assignment' => $assignment,
					'subject' => $subject,
					'questions' => $assignment->getQuestionsOrderedWithSubquestions() 
			));
		});
	}

	public function editAjaxPost($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayEditAssignment(Auth::user())) {
				App::abort(403);
			}
			
			if ($assignment->markingHasStarted()) {
				$onlyValues = array (
						'title',
						// 'offline',
						'visibility',
						//'answers_due',
						//'group_work_mode',
						//'group_selection_mode',
						//'group_size_min',
						//'group_size_max',
						//'guess_marks',
						//'mark_by_tutors',
						'tutors_due',
						//'number_of_peers',
						'peers_due',
						//'shuffle_mode',
						// 'marking_started',
						//'autostart_marking_time',
						'late_policy',
						'marks_released'
				);
				
			} else {
				$onlyValues = array (
						'title',
						// 'offline',
						'visibility',
						'answers_due',
						'group_work_mode',
						'group_selection_mode',
						'group_mark_mode',
						'group_size_min',
						'group_size_max',
						'guess_marks',
						'mark_by_tutors',
						'tutors_due',
						'number_of_peers',
						'peers_due',
						'shuffle_mode',
						// 'marking_started',
						'autostart_marking_time',
						'late_policy',
						'marks_released' 
				);
			}
			$input = Input::only($onlyValues);
			
			$originalAssignment = clone $assignment;
			
			$assignment->fill($input);
			if($assignment->markingHasStarted() && Input::has('mark_by_tutors')) {
				foreach(AssignmentMarkByTutors::getValues() as $key) {
					if($key == Input::get('mark_by_tutors')) {
						if($assignment->usesTutorMarking()) {
							if($key != AssignmentMarkByTutors::none) {
								$assignment->mark_by_tutors = $key;
							}
						}					
					}
				}
			}
			
			if (! $assignment->validate()) {
				$json = array ();
				$json ['success'] = false;
				$json ['html'] = View::make('assignment.assignment_edit_form_insert')->with('assignment', $originalAssignment)->with('errors', $assignment->errors())->render();
				$json ['growl'] = "your changes could not be saved.";
				return Response::json($json);
			} else {

				$assignment->save();
				
				$json = array ();
				$json ['success'] = true;
				$json ['html'] = View::make('assignment.assignment_edit_show_insert')->with('assignment', $assignment)->render();
				$json ['growl'] = "your changes have been saved.";
				$json['script'] = '$(\'.updatableAssignmenNavigationBar\').trigger("manualupdate");';		
				return Response::json($json);
			}
		});
	}
	

	
	public function editAjax($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayEditAssignment(Auth::user())) {
				App::abort(403);
			}
			
			$json = array ();
			$json ['success'] = true;
			$json ['html'] = View::make('assignment.assignment_edit_form_insert')->with('assignment', $assignment)->render();
			
			return Response::json($json);
		});
	}

	public function editShowAjax($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {

			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayEditAssignment(Auth::user())) {
				App::abort(403);
			}
			
			$json = array ();
			$json ['success'] = true;
			$json ['html'] = View::make('assignment.assignment_edit_show_insert')->with('assignment', $assignment)->render();
			return Response::json($json);
		});
	}
	
	
	private function getMarkPercentageUpdates($questions) {
		$result = array();
		foreach ($questions as $question) {
			$questionPresenter = new QuestionPresenter($question);
			
			$result["question_" . $question->id . "_percentage"] = $questionPresenter->mark_percentage;
			$result["question_" . $question->id . "_percentage_mode"] = $questionPresenter->mark_percentage_mode;
			$result["question_" . $question->id . "_percentage_global"] = $questionPresenter->mark_percentage_global;
		}
		return $result;
	}
	
	private function makeRefreshQuestionDisplayResponseJson($assignment, $directlyInsertQuestionId = null) {
		$assignment->invalidateRelations();		
		
		$first = true;
		$orderData = array();
		$update = array();
		
		$percentages = $assignment->getQuestionPercentages();
				
		$questionsOrderedWithSubquestions = $assignment->getQuestionsOrderedWithSubquestions();
		foreach ($questionsOrderedWithSubquestions as $question) {
		
			// check whether we want to send the whole view or just the auto-loader in case the page is out of sync.
			if ($question->id == $directlyInsertQuestionId) {
				$html = View::make('question.edit_insert', array('question' => $question, 'assignment' => $assignment))->render();
			} else {
				$html = View::make('question.autoload_edit_insert', array('question' => $question, 'assignment' => $assignment))->render();
			}
			
			// add the data to the response-array
			$orderData[] = array("id" => $question->id, "html" => $html);
			
			// add update data to update the numbering in existing sections.
			$questionPresenter = new QuestionPresenter($question);
			$update["question_" . $question->id . "_position"] = $questionPresenter->position;

			$update["question_" . $question->id . "_percentage"] = $questionPresenter->mark_percentage;
			$update["question_" . $question->id . "_percentage_mode"] = $questionPresenter->mark_percentage_mode;
			$update["question_" . $question->id . "_percentage_global"] = $questionPresenter->mark_percentage_global;
		}
		
		$update += static::getMarkPercentageUpdates($questionsOrderedWithSubquestions);		
		
		$json = array();
		$json['success'] = true;
		$json['order'] = $orderData;
		$json['update'] = $update;
		
		return $json;
	}

	public function addQuestionAjax($assignment_id, $type, $master_question_id = null) {
		return Platypus::transaction(function () use($assignment_id, $type, $master_question_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayAddQuestions(Auth::user())) {
				App::abort(403);
			}
			

			$question = new Question();
			$question->subject_id = $assignment->subject_id;
			
			switch ($type) {
				case QuestionType::simple :
				case QuestionType::master :
					$question->type = $type;
					$question->position = $assignment->getNextUnusedQuestionPosition();
					
					$assignment->questions()->save($question);
					
					break;
				
				case QuestionType::subquestion :
					$question->type = QuestionType::subquestion;
					$master = Question::findOrFail($master_question_id);
					$master->belongsToAssignmentOrFail($assignment);
					
					if (! $master->isMaster()) {
						App::abort(404);
					}
					
					$question->position = $master->getNextUnusedSubquestionPosition();

					$master->subquestions()->save($question);
					
					break;
			}
			
			return Response::json(self::makeRefreshQuestionDisplayResponseJson($assignment, $question->id));
		});
	}
	
	
	public function deleteQuestionAjax($assignment_id, $question_id) {
		return Platypus::transaction(function () use($assignment_id, $question_id) {
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayDeleteQuestions(Auth::user())) {
				App::abort(403);
			}
			
			$question = Question::findOrFail($question_id);
			$question->belongsToAssignmentOrFail($assignment);
			
			if ($question->hasNonEmptyAnswers()) {
				$json = array ();
				$json ['success'] = true;
				$json ['alert'] = 'Students have already submitted answers to this questions. Not deleting question. Please delete the answers first.';
				return Response::json($json);
			} else {
				
				// first update the position information for all related questions.
				if ($question->isSubquestion()) {
					$orderedQuestions = $question->master_question->subquestions_ordered;
				} else {
					$orderedQuestions = $assignment->questions_ordered;
				}
				$position = 0;
				for($i = 0; $i < count($orderedQuestions); $i ++) {
					if ($orderedQuestions [$i]->id != $question->id) {
						$position ++;
						$orderedQuestions [$i]->position = $position;
						$orderedQuestions [$i]->save();
					}
				}
				
				// now delete the question itself.
				$question->delete();
				

				return Response::json(self::makeRefreshQuestionDisplayResponseJson($assignment));
			}
		});		
	}
	
	public function deleteAnswersAjax($assignment_id, $question_id) {
		return Platypus::transaction(function () use($assignment_id, $question_id) {
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayDeleteQuestionAnswers(Auth::user())) {
				App::abort(403);
			}
			
			$question = Question::findOrFail($question_id);
			$question->belongsToAssignmentOrFail($assignment);
			
			$question->answers()->delete();

			$json = array ();
			$json ['success'] = true;
			$json ['growl'] = 'All answers have been deleted.';
			return Response::json($json);
		});		
	}
	
	public function moveQuestionAjax($assignment_id, $question_id, $direction) {
		return Platypus::transaction(function () use($assignment_id, $question_id, $direction) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayMoveQuestions(Auth::user())) {
				App::abort(403);
			}
			
			if (($direction != '1') && ($direction != '-1')) {
				App::abort(404);
			}
			
			$question = Question::findOrFail($question_id);
			$question->belongsToAssignmentOrFail($assignment);			
			
			if ($question->isSubquestion()) {
				$orderedQuestions = $question->master_question->subquestions_ordered;
			} else {
				$orderedQuestions = $assignment->questions_ordered;
			}
			
			$changeMade = false;
			for($i = 0; $i < count($orderedQuestions); $i ++) {
				if ($orderedQuestions [$i]->id == $question_id) {
					if (($i + $direction >= 0) && ($i + $direction < count($orderedQuestions))) {
						$tmp = $orderedQuestions [$i];
						$orderedQuestions [$i] = $orderedQuestions [$i + $direction];
						$orderedQuestions [$i + $direction] = $tmp;
						$changeMade = true;
						break;
					}
				}
			}
			
			if ($changeMade) {
				for($i = 0; $i < count($orderedQuestions); $i ++) {
					$orderedQuestions [$i]->position = $i + 1;
					$orderedQuestions [$i]->save();
				}
			}

			return Response::json(self::makeRefreshQuestionDisplayResponseJson($assignment));
		});
	}	
	
	public function getQuestionAjax($assignment_id, $question_id) {
		return Platypus::transaction(function () use($assignment_id, $question_id) {
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayEditAssignment(Auth::user())) {
				App::abort(403);
			}
			
			$question = Question::findOrFail($question_id);
			$question->belongsToAssignmentOrFail($assignment);		
			
			if (! $question->mayEditQuestion(Auth::user())) {
				App::abort(403);
			}
			
			$json = array ();
			$json ['success'] = true;
			$json ['html'] = View::make('question.edit_insert', array (
					'question' => $question,
					'assignment' => $assignment 
			))->render();
			return Response::json($json);
		});	

	}

	public function setQuestionMarkPercentage($assignment_id, $question_id) {
		return Platypus::transaction(function () use($assignment_id, $question_id) {
			
			$question = Question::findOrFail($question_id);
			
			if ($question->assignment_real->id != $assignment_id) {
				App::abort(404);
			}
			
			if (! $question->mayEditQuestionPercentage(Auth::user())) {
				App::abort(403);
			}
			
			$assignment = $question->assignment_real;
			
			$mark_percentage = Input::get("mark_percentage", Question::autoMarkPercentage);
			
			if (is_numeric($mark_percentage) && (($mark_percentage == Question::autoMarkPercentage) || (($mark_percentage >= 0) && ($mark_percentage <= 100)))) {
				$question->mark_percentage = $mark_percentage;
				
				$question->save();
				
				$json = array ();
				$json ['success'] = true;
				$json ['html'] = View::make('question.question_edit_percentage_insert', array (
						'assignment' => $assignment,
						'question' => $question 
				))->render();
				$json ['growl'] = "Your change has been saved.";
				$json ['update'] = static::getMarkPercentageUpdates($assignment->getQuestionsOrderedWithSubquestions());
				return Response::json($json);
			} else {
				$json = array ();
				$json ['success'] = false;
				$json ['alert'] = 'Please enter a value between 0 and 100.';
				return Response::json($json);
			}
		});
	}
	
	public function setSolutionEditor($assignment_id, $question_id) {
		return Platypus::transaction(function () use($assignment_id, $question_id) {
				
			$question = Question::findOrFail($question_id);
				
			if ($question->assignment_real->id != $assignment_id) {
				App::abort(404);
			}
				
			$assignment = $question->assignment_real;

			if (! $assignment->maySetSolutionEditor(Auth::user())) {
				App::abort(403);
			}				
			
			$json = array ();
			$json ['success'] = true;
			
			$user_id = Input::get('tutor', '');
			
			if($user_id == 0) {
				$question->solution_editor_id = null;
			} else {
				if(!is_numeric($user_id)) App::abort(404);
				$user = User::findOrFail($user_id);
			
				if(!$assignment->isTutor($user)) {
					App::abort(404);
				}
			
				$membership = $assignment->subject->getMembership($user);
				$question->solution_editor_id = $membership->id;
			}
			
			$question->save();
			$question->invalidateRelations();
				
			$json ['growl'] = "Your change has been saved.";

			$json ['html'] = View::make('question.question_edit_solutionEditor_insert', array (
					'assignment' => $assignment,
					'question' => $question
			))->render();
			
			return Response::json($json);
				
		
		});
	}
	
	public function showInfopanel($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayEditAssignment(Auth::user())) {
				App::abort(403);
			}
			
			$json = array ();
			$json ['success'] = true;
			$json ['html'] = View::make('assignment.assignment_infopanel_insert', array (
					'assignment' => $assignment 
			))->render();
			return Response::json($json);
		});
	}
	
		
}

