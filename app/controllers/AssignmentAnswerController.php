<?php


class AssignmentAnswerController extends BaseController {

	
	public function show($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
			
			$assignment = Assignment::findOrFail($assignment_id);
			
			if (! $assignment->maySeeAssignmentAnswerPage(Auth::user())) {
				App::abort(403);
			}
			
			$subject = $assignment->subject;
			
			$answers = $assignment->createOrGetAllAnswersSave(Auth::user());
			
			return View::make('assignment.assignment_answer', array (
					'subject' => $subject,
					'assignment' => $assignment,
					'answers' => $answers,
			));		
		
		});
	}
	
	public function reviewAnswerPost($answer_id) {
		return Platypus::transaction(function () use($answer_id) {
			$answer = Answer::findOrFail($answer_id);
			
			$user = Auth::user();
			
			if ($answer->user->id != $user->id) App::abort(404);
			
			$json = array();
			$json['success'] = true;

			if ($answer->text->isEmpty()) {
				$json['alert'] = "You cannot submit an empty answer.";
			} else if (!$answer->maySubmit($user)) {
				$json['alert'] = "You cannot submit this answer at the moment.";
			} else {
				$json['modal'] = View::make('answer.answer_submitConfirmation_modal')->withAnswer($answer)->render();
			}

			$json['html'] = View::make('answer.answer_submitButton_insert')->withAnswer($answer)->render();
			
			return Response::json($json);
			
		});
	}
	
	public function submitAnswerPost($answer_id) {
		return Platypus::transaction(function () use($answer_id) {
			$answer = Answer::findOrFail($answer_id);
			$originalAnswer = clone $answer;
			
			$user = Auth::user();
			
			if ($answer->user->id != $user->id) App::abort(404);
			
			$json = array();
			$json['success'] = true;
			$json['update'] = array();

			if ($answer->text->isEmpty()) {
				$json['alert'] = "You cannot submit an empty answer.";
			} else if (!$answer->maySubmit($user)) {
				$json['alert'] = "You cannot submit this answer at the moment.";
			} else {
				if ($answer->studentsMayGuessTheirMarks() && Input::has('guessed_mark')) {
					$answer->guessed_mark = Input::get('guessed_mark');
				} else {
					$answer->guessed_mark = null;
				}
				$answer->submit($user);
				
				if (!$answer->validate()) {
					$json['modal'] = View::make('answer.answer_submitConfirmation_modal')->withAnswer($originalAnswer)->withErrors($answer->errors())->render();
					$answer = $originalAnswer;
				} else {				
					$answer->save();
					$json['update']['question_' . $answer->question->id] = View::make('assignment.assignment_answer_questionpanel_insert')
							->withQuestion($answer->question)->withAnswer($answer)->render();
				}
			}
			
			$json['update']['submit_button_' . $answer->id] = View::make('answer.answer_submitButton_insert')->withAnswer($answer)->render();
			
			
			return Response::json($json);
			
		});	
	}

	public function retractAnswerPost($answer_id) {
		return Platypus::transaction(function () use($answer_id) {
			$answer = Answer::findOrFail($answer_id);
			$originalAnswer = clone $answer;
				
			$user = Auth::user();
				
			if ($answer->user->id != $user->id) App::abort(404);
			
			$json = array();
			$json['success'] = true;
			$json['update'] = array();
	
			if ($answer->submitted) {
				if ($answer->mayRetract($user)) {
					$answer->retract($user);
					$answer->save();
					
					$json['update']['question_' . $answer->question->id] = View::make('assignment.assignment_answer_questionpanel_insert')
						->withQuestion($answer->question)->withAnswer($answer)->render();					
				} else {
					$json['alert'] = "You cannot retract this answer at the moment.";
				}
			} 
						
			$json['update']['submit_button_' . $answer->id] = View::make('answer.answer_submitButton_insert')->withAnswer($answer)->render();
				
			return Response::json($json);
				
		});
	}
	
	
		
}

