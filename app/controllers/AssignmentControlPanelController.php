<?php



use Platypus\Helpers\PlatypusBool;
use Illuminate\Support\MessageBag;
use League\Csv\Writer;
use Carbon\Carbon;

class AssignmentControlPanelController extends BaseController {


	
	public function showControlPanel($id) {
		return Platypus::transaction(function() use($id) {
		
			$assignment = Assignment::findOrFail($id);
			if (! $assignment->mayViewControlPanel(Auth::user())) App::abort(403);

			return View::make('assignment.assignment_controlPanel')->withAssignment($assignment);
		});
	}
	
	
	static public function makeMarkingPanelResponse($assignment) {
		
		$assignment->invalidateRelations();
		
		$json = array ();
		$json ['success'] = true;
		$json ['html'] = View::make('assignment.assignment_controlPanel_marking_insert')->withAssignment($assignment)->render();
		$json['script'] = '$(\'.updatableAssignmenNavigationBar\').trigger("manualupdate");';
		$json ['script'] .= '$(\'[data-resourceid="assignment_log_panel"]\').trigger("manualupdate");';
		return Response::json($json);
	}
	
	public function startMarkingPost($id) {
		return Platypus::transaction(function () use($id) {
			$assignment = Assignment::findOrFail($id);
			if (! $assignment->mayStartMarking(Auth::user())) App::abort(403);
			
			if ($assignment->markingHasStarted()) {
				return Response::json(array(
					'success' => true,
					'alert' => 'This assignment is in the marking phase already.',
				));
			}
			
			if ($assignment->answers_due > Carbon::now()) {
				return Response::json(array(
					'success' => true,
					'alert' => 'The answers for this assignment aren\'t due yet.',
				));
			}
			
			MarkingShuffler::shuffleAssignment($assignment);

			return self::makeMarkingPanelResponse($assignment);
			
		});		
	}

	// automatically start the marking process. This is meant to be called from an Artisan command.
	static public function autostartMarking() {
		
		$candidate_id = '';
		
		while (true) {

			// we want to do each assignent in an individual transaction.
			$candidate = Assignment::where('autostart_marking_time', '<=', Carbon::now())->first();
			if(!isset($candidate)) break;
		
			if($candidate->id == $candidate_id) {
				// simple check to catch bugs that cause an infinite loop.
				App::abort(500,'The same assignment was encountered twice. This is a bug.');
			}
			$candidate_id = $candidate->id;
		
		 	Platypus::transaction(function () use($candidate_id) {

				// get the assignment again inside the transaction.
		 		$assignment = Assignment::where('autostart_marking_time', '<=', Carbon::now())->where('id', $candidate_id)->first();
		 		if(!isset($assignment)) return;
			
		 		if (!$assignment->assignmentIsreadyToStartMarking()) {
					// this should not happen normally, but if it does, we clear the autostart.
					$message =  'The autostart marking time has been reached, but the assignment is not ready to start the marking process. Aborting.';
					$assignment->logEvent(AssignmentEventLevel::error, $message);
					echo "ERROR Assignment ".$assignment->id.": $message\n";
					//$assignment->autostart_marking_time = null;
					if(!$assignment->save()) {
						App::abort(500, 'Could not save assignment');
					}						
					return;
		 		}
		 		
		 		$message = 'The autostart marking time has been reached, starting the marking process.';
		 		$assignment->logEvent(AssignmentEventLevel::info, $message);
		 		echo "Assignment ".$assignment->id.": $message\n";
		 		
		 		MarkingShuffler::shuffleAssignment($assignment);
		 		
			});
		 	
		}		
	}
	
	public function cancelMarkingPost($id) {
		return Platypus::transaction(function() use($id) {
	
			$assignment = Assignment::findOrFail($id);
			if (! $assignment->mayCancelMarking(Auth::user())) App::abort(403);
	
			if (Input::get('doit') == 'now') {
				$assignment->allReviews()->delete();
				$assignment->allQuestionMarks()->delete();				
				$assignment->assignmentMarks()->delete();
				
				$assignment->marking_started = false;
				$assignment->save();
				
				$assignment->logEvent(AssignmentEventLevel::warning, "The marking process has been cancelled and all reviews and marks deleted.");
			}
			
			return self::makeMarkingPanelResponse($assignment);
		});
	}
	
	public function ensureEachAnswerHasLecturerReviewTaskPost($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
			$assignment = Assignment::findOrFail($assignment_id);
			if (!$assignment->mayManageAssignment(Auth::user())) App::abort(403);
			if (!$assignment->markingHasStarted()) App::abort(403, 'This assignment is not yet in the marking phase.');
				
			MarkingShuffler::ensureEachAnswerHasLecturerReviewTask($assignment);
			
			return self::makeMarkingPanelResponse($assignment);
				
		});		
	}
	
	public function deletePendingTurorReviewTasksPost($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
			$assignment = Assignment::findOrFail($assignment_id);
			if (!$assignment->mayManageAssignment(Auth::user())) App::abort(403);
			if (!$assignment->markingHasStarted()) App::abort(403, 'This assignment is not yet in the marking phase.');
	
			$assignment->allReviews(ReviewReviewerRole::tutor)->where('status',ReviewStatus::task)->delete();
			
			return self::makeMarkingPanelResponse($assignment);
	
		});
	}

	public function ensureEachAnswerHasTutorReviewTaskPost($assignment_id) {
		return Platypus::transaction(function () use($assignment_id) {
			$assignment = Assignment::findOrFail($assignment_id);
			if (!$assignment->mayManageAssignment(Auth::user())) App::abort(403);
			if (!$assignment->markingHasStarted()) App::abort(403, 'This assignment is not yet in the marking phase.');
	
			MarkingShuffler::ensureEachAnswerHasTutorReviewTask($assignment);
				
			return self::makeMarkingPanelResponse($assignment);
	
		});
	}
	
	
	
	public function showLogPanel($id) {
		return Platypus::transaction(function () use($id) {
			$assignment = Assignment::findOrFail($id);
			if (! $assignment->mayViewControlPanel(Auth::user())) App::abort(403);
					
			$json = array ();
			$json ['success'] = true;
			$json ['html'] = View::make('assignment.assignment_controlPanel_log_insert')->withAssignment($assignment)->render();
			return Response::json($json);
				
		});
	}
	
	
	public function deleteAssignmentPost($id) {
		return Platypus::transaction(function() use($id) {
	
			$assignment = Assignment::findOrFail($id);
			if (! $assignment->mayManageAssignment(Auth::user())) App::abort(403);
	
			if (Input::get('doit') == 'now') {
				$subject = $assignment->subject;
				$assignment->delete();
				return Redirect::route('showSubject', $subject->id)->withSuccess('The assignment has been deleted.');
			} else {
				return Redirect::route('showAssignmentControlPanel', $assignment->id)->withDanger('You have to confirm the deletion.');
			}
		});
	}	
	
	
	
	static public function getCsvFileContent($assignment) {

			$users = $assignment->all_students_ordered;
			$questions = $assignment->getQuestionsOrderedWithSubquestionsWithoutMasters();
				
			$maxReviews = array();
			$hasGuessedMarks = array();
			foreach($questions as $question) {
				$maxReviews[$question->id] = 0;
				$hasGuessedMarks[$question->id] = false;
			}
				
			$showGroupColumn = false;
			$groupMappings = array();
	
			$maxWrittenReviews = 0;
				
			$columndata = array();
				
			foreach($users as $user) {
				$data = array();
				$data['first_name'] = $user->first_name;
				$data['last_name'] = $user->last_name;
				$data['student_id'] = $user->student_id;
				$data['email'] = $user->email;
				$data['status'] = $assignment->isActiveStudent($user) ? 'active' : 'suspended';
	
				$group = $assignment->getStudentGroup($user);
				if(isset($group)) {
					$showGroupColumn = true;
					if(!isset($groupMappings[$group->id])) {
						$groupMappings[$group->id] = count($groupMappings)+1;
					}
					$data['group'] = $groupMappings[$group->id];
				} else {
					$data['group'] = null;
				}
	
				$mark = $assignment->getUserAssignmentMark($user);
				$data['mark'] = isset($mark) ? $mark->mark : '';
				$data['auto'] = isset($mark) ? $mark->isAutomatic() ? 'auto' : 'manual' : '';
	
				$data['questions'] = array();
				$data['written_reviews'] = array();
				foreach($questions as $question) {
					$qdata = array();
						
					$qdata['question_id'] = $question->id;
					$qdata['mark'] = $question->getUserMark($user);
						
					$answer = $question->getAnswer($user);
					if(isset($answer)  && $answer->isSubmitted()) {
	
						$qdata['submitted'] = $answer->presenter()->time_submitted;
	
						if($answer->hasGuessedMark()) {
							$hasGuessedMarks[$question->id] = true;
							$qdata['guess'] = $answer->guessed_mark;
						} else {
							$qdata['guess'] = null;
						}
	
						$rdata = array();
						foreach($answer->submitted_reviews_ordered as $review) {
							$rdata[] = array(
									'id' => $review->id,
									'question' => $question->presenter()->full_position,
									'role' => $review->presenter()->reviewer_role,
									'mark' => $review->mark,
									'rating' => $review->isRated() ? $review->review_rating : null,
							);
						}
						$maxReviews[$question->id] = max($maxReviews[$question->id], count($rdata));
	
						$qdata['reviews'] = $rdata;
	
					} else {
						$qdata['submitted'] = null;
						$qdata['guess'] = null;
						$qdata['reviews'] = array();
					}
						
					$data['questions'][] = $qdata;
						
						
					// now we want to add the reviews the student has written
					foreach($question->getAllCompletedReviewsFromUser($user) as $review) {
						$data['written_reviews'][] = array(
								'id' => $review->id,
								'question' => $question->presenter()->full_position,
								'role' => $review->presenter()->reviewer_role,
								'mark' => $review->mark,
								'rating' => $review->isRated() ? $review->review_rating : null,
						);
					}
						
				}
	
	
				$maxWrittenReviews = max($maxWrittenReviews, count($data['written_reviews']));
	
				$columndata[] = $data;
			}
				
			$writer = Writer::createFromFileObject(new SplTempFileObject());
				
			$preamble = array();
				
			$preamble[] = array(
					'Class',
					$assignment->subject->presenter()->code,
					$assignment->subject->presenter()->title,
			);
				
			$preamble[] = array(
					'Assignment',
					$assignment->presenter()->title,
			);
	
			$preamble[] = array(
					'Due date',
					$assignment->presenter()->answers_due,
			);
				
			$preamble[] = array(
					'Date of this report',
					Carbon::now()->format('d/m/Y (H:i:s)'),
			);
	
				
	
				
			$header = array();
			$header[] = 'First name';
			$header[] = 'Last name';
			$header[] = 'Student ID';
			$header[] = 'Email';
			$header[] = 'Status';
			if($showGroupColumn) $header[] = 'Group';
			$header[] = 'Mark';
			$header[] = 'Mark mode';
				
				
			foreach($questions as $question) {
				$code = 'Question '.$question->presenter()->full_position;
				$header[] = "$code mark";
				$header[] = "$code submit date";
	
				if($hasGuessedMarks[$question->id]) {
					$header[] = "$code guessed mark";
				}
	
				for($i=0; $i<$maxReviews[$question->id]; $i++) {
					$rpos = $i+1;
					$header[] = "$code review $rpos #";
					$header[] = "$code review $rpos role";
					$header[] = "$code review $rpos mark";
					$header[] = "$code review $rpos rating";
				}
			}
				
			for($i=0; $i<$maxWrittenReviews; $i++) {
				$rpos = $i+1;
				$header[] = "Written review $rpos #";
				$header[] = "Written review $rpos question";
				$header[] = "Written review $rpos role";
				$header[] = "Written review $rpos mark";
				$header[] = "Written review $rpos rating";
			}
				
			$preamble[]=array(); // add an empty line
			foreach($preamble as $row) {
				while(count($row) < count($header)) {
					$row[] = null;
				}
					
				$writer->insertOne($row);
			}
				
			$writer->insertOne($header);
				
			foreach($columndata as $data) {
				$row = array();
				$row[] = $data['first_name'];
				$row[] = $data['last_name'];
				$row[] = $data['student_id'];
				$row[] = $data['email'];
				$row[] = $data['status'];
				if($showGroupColumn) $row[] = $data['group'];
				$row[] = $data['mark'];
				$row[] = $data['auto'];
	
				foreach($data['questions'] as $qdata) {
					$row[] = $qdata['mark'];
					$row[] = $qdata['submitted'];
						
					if($hasGuessedMarks[$qdata['question_id']]) {
						$row[] = $qdata['guess'];
					}
						
					for($i=0; $i<$maxReviews[$qdata['question_id']]; $i++) {
						if(isset($qdata['reviews'][$i])) {
							$rdata = $qdata['reviews'][$i];
								
							$row[] = $rdata['id'];
							$row[] = $rdata['role'];
							$row[] = $rdata['mark'];
							$row[] = $rdata['rating'];
						} else {
							$row[] = null;
							$row[] = null;
							$row[] = null;
							$row[] = null;
						}
					}
				}
	
				for($i = 0; $i < $maxWrittenReviews; $i ++) {
					if (isset($data ['written_reviews'] [$i])) {
						$rdata = $data ['written_reviews'][$i];
	
						$row [] = $rdata ['id'];
						$row [] = $rdata ['question'];
						$row [] = $rdata ['role'];
						$row [] = $rdata ['mark'];
						$row [] = $rdata ['rating'];
					} else {
						$row [] = null;
						$row [] = null;
						$row [] = null;
						$row [] = null;
						$row [] = null;
					}
				}
	
					
				$writer->insertOne($row);
			}
				
			return $writer->__toString();
	}
		
	
	
	public function downloadCsvFile($assignment_id) {
		return Platypus::transaction(function() use($assignment_id) {
		
			$assignment = Assignment::findOrFail($assignment_id);
			if (! $assignment->mayViewAllMarks(Auth::user())) App::abort(403);

			return Response::make(
					static::getCsvFileContent($assignment),
					200, 
					array(
							'Content-Type' => 'text/csv',
							'Content-Disposition' => 'attachment; filename="results.csv"',
					)
				);
			
		});		
	}
	
}

