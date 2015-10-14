<?php

use Platypus\Helpers\PlatypusBool;

// Not really a controller.
// This class holds the logic that is used to shuffel marking tasks
class MarkingShuffler {
	
	// this function is called when an answer is submitted after marking has started.
	// the marking task is then assigned to a tutor or to the lecturer (depending on the settings of Assignment::late_policy)
	public static function assignLateSubmission(Answer $answer) {
		$assignment = $answer->assignment;
		$question = $answer->question;
		
		$fallBackToLecturer = false;
		
		// handle assignment to tutor
		if ($assignment->late_policy == AssignmentLatePolicy::markbytutor) {
			$tutors = array();
			if ($assignment->shuffle_mode == AssignmentShuffleMode::wholeassignments) {
				// figure out if there is are already marking requests for this user. if so, use them.
				$tutors = $assignment->getUserReviewsQuery($answer->user)->where('reviewer_role', ReviewReviewerRole::tutor)->distinct()->lists('user_id');
			} else if ($answer->question->isSubquestion()) {
				// if we are a subquestion, figure out whether there are already marking requests for the same master-question.
				$siblingQuestionIds = $answer->question->master_question->subquestions()->distinct()->lists('id');
				$tutors = $assignment->getUserReviewsQuery($answer->user)
					->where('reviewer_role', ReviewReviewerRole::tutor)
					->whereHas('answer', function($q) use ($siblingQuestionIds) {
						$q->whereIn('question_id', $siblingQuestionIds);
					})
					->distinct()->lists('user_id');
			}
			
			// make sure we only use active tutors.
			if (empty($tutors)) {
				$tutors = $assignment->getTutorsForQuestionQuery($answer->question)->whereIn('id', $tutors)->distinct()->lists('id');
			}
			
			// if we don't have any tutors yet, use any that are active.
			if (empty($tutors)) {
				$tutors = $assignment->getTutorsForQuestionQuery($answer->question)->distinct()->lists('id');
			}
			
			if (empty($tutors)) {
				$fallBackToLecturer = true;
				$assignment->logEvent(AssignmentEventLevel::warning, "(question ". $question->presenter()->full_position ."): There are no tutors to review a late submission by ". $answer->user->presenter()->name .". Assigning to lecturer instead.");
			} else {
				
				// try to get the tutor that has the least reviews about this question. To be a bit fair.
				shuffle($tutors);
				$bestCount = INF;
				foreach($tutors as $candidate) {
					$reviewCount = Review::where('user_id', $candidate)->whereHas('answer', function($q) use ($answer) {
						$q->where('question_id', $answer->question_id);
					})->count();
					if ($reviewCount < $bestCount) {
						$bestCount = $reviewCount;
						$tutor = $candidate;
					}
				}
				
				Review::createReviewTask($answer->id, $tutor, ReviewReviewerRole::tutor);
				$assignment->logEvent(AssignmentEventLevel::info, "(question ". $question->presenter()->full_position ."): Assigned late submission by ". $answer->user->presenter()->name ." to tutor.");
				return;
			}			
		}
		
		// handle assignment to lecturer.
		if (($assignment->late_policy == AssignmentLatePolicy::markbylecturer) || $fallBackToLecturer) {
			// if there are several lecturers we create a group review request.
			$lecturer_ids = $assignment->subject->lecturers()->lists('user_id');
			if (empty($lecturer_ids)) {
				$assignment->logEvent(AssignmentEventLevel::error, "(question ". $question->presenter()->full_position ."): There are no lecturers to review a late submission by ". $answer->user->presenter()->name .". Giving up.");
			} else {
				Review::createReviewTask($answer->id, $lecturer_ids, ReviewReviewerRole::lecturer);
				$assignment->logEvent(AssignmentEventLevel::info, "(question ". $question->presenter()->full_position ."): Assigned late submission by ". $answer->user->presenter()->name ." to lecturer.");
			}
			return;
		}
		
		$assignment->logEvent(AssignmentEventLevel::error, "(question ". $question->presenter()->full_position ."): Failed to assign a review for a late submission by ". $answer->user->presenter()->name .". This is a bug.");
	}
	

	
	public static function ensureEachAnswerHasLecturerReviewTask(Assignment $assignment) {
		
		// find the answers with missing reviews
		$answersIds = $assignment->submittedAnswers()->whereHas('reviews', function($q) {
				$q->where('reviewer_role', ReviewReviewerRole::lecturer);
			}, '<', 1)->distinct()->lists('id');
		
		// get the available lecturers.
		$lecturerIds = $assignment->subject->lecturers()->lists('user_id');
				
		if (empty($lecturerIds)) {
			$assignment->logEvent(AssignmentEventLevel::error, "There are no lecturers to assign reviews to. Giving up.");
		} else {
			$tasks = array();
			
			foreach($answersIds as $answerId) {
				$task = array();
				$task['answer_id'] = $answerId;
				$task['reviewer_role'] = ReviewReviewerRole::lecturer;
				$task['user_id'] = $lecturerIds;
				$tasks[] = $task;				
			}
			
			$reviewCount = Review::createReviewTasks($tasks);

			$assignment->logEvent(AssignmentEventLevel::info, "Assigned $reviewCount review tasks to lecturers.");
		}		
		
	}
	
	// assign all answers to tutors but try to keep the answers by the same students together
	// for the questions that are passed.
	private static function ensureEachAnswerHasTutorReviewTask_questionGroup($questions, $logger) {
		
		// get the array of question_ids so we can use them in sql.
		$question_ids = array();
		foreach($questions as $question) {
			$question_ids[] = $question->id;
		}
		
		// determine the default tutor for each user.
		$existingReviews = Review::whereHas('answer', function($q) use($question_ids) {
					$q->whereIn('question_id', $question_ids);
				})
			->where('reviewer_role', ReviewReviewerRole::tutor)
			->with('answer')
			->get();
		$defaultTutors = array();
		foreach($existingReviews as $review) {
			$defaultTutors[$review->answer->user_id] = $review->user_id;
		}
		
		// get the list of relevant tutors.
		$tutor_ids = $questions[0]->assignment->getTutorsForQuestionQuery($questions[0])->distinct()->lists('id');
		shuffle($tutor_ids);
		
		// figure out the current load of each tutor
		$tutorLoad = array();
		foreach($tutor_ids as $id) {
			$tutorLoad[$id] = 0;
		}
		foreach($existingReviews as $review) {
			if(!isset($tutorLoad[$review->user_id])) {
				$tutorLoad[$review->user_id] = 0;
			}
			$tutorLoad[$review->user_id]++;
		}
		
		
		// find the answers with missing reviews
		// we want to ignore the ones that have a final mark already.
		$answers = Answer::whereIn('question_id', $question_ids)
			->where('submitted', PlatypusBool::true)
			->whereHas('reviews', function($q) {
				$q->where('reviewer_role', ReviewReviewerRole::tutor);
				}, '<', 1)
			->whereNotExists(function($q) {
					$q->select(DB::raw(1))
                		->from('question_marks')
                		->whereRaw('answers.user_id = question_marks.user_id')
                		->whereRaw('answers.question_id = question_marks.question_id');                     
				})
			->get();
		
		// shuffle the answers so they end up in a more random order. (we need to convert the collection to a proper array first.
		$answersArray=array();
		foreach($answers as $answer) {
			$answersArray[] = $answer;
		}
		$answers = $answersArray;
		shuffle($answers);
		
		// create the actual review tasks
		$reviewTasks = array();
		foreach($answers as $answer) {
			if(isset($defaultTutors[$answer->user_id])) {
				$tutor = $defaultTutors[$answer->user_id];
			} else {
				$tutor = $tutor_ids[0];
				$bestLoad = $tutorLoad[$tutor];
				foreach($tutor_ids as $tutor_id) {
					if($tutorLoad[$tutor_id] < $bestLoad) {
						$tutor = $tutor_id;
						$bestLoad = $tutorLoad[$tutor];
					}
				}
				$defaultTutors[$answer->user_id] = $tutor;
			}
			$tutorLoad[$tutor]++;
			$newTask = array('answer_id' => $answer->id, 'user_id' => $tutor, 'reviewer_role' => ReviewReviewerRole::tutor);
			$reviewTasks[] = $newTask;
		}
		$count = Review::createReviewTasks($reviewTasks);
		
		$logger(AssignmentEventLevel::info, "$count tutor review requests created.");
		
	}
	
	public static function ensureEachAnswerHasTutorReviewTask(Assignment $assignment) {
		
		// Create all the tutor reviews.
		if ($assignment->keepAssignmentTogetherWhenShuffling() && $assignment->tutorsAreIdenticalforAllQuestions()) {
			
			$logger = function($level, $text) use ($assignment) {
				$assignment->logEvent($level, "Creating tutor review tasks: ". $text);
			};			
			
			$questions = $assignment->getQuestionsOrderedWithSubquestions();
			self::ensureEachAnswerHasTutorReviewTask_questionGroup($questions, $logger);
		
		} else {
		
			foreach($assignment->questions_ordered as $question) {
		
				$logger = function($level, $text) use ($assignment, $question) {
					$assignment->logEvent($level, "Creating tutor review tasks (question ". $question->presenter()->full_position ."): ". $text);
				};
		
				$questions = array($question);
				if($question->isMaster()) {
					foreach($question->subquestions as $sub) {
						$questions[] = $sub;
					}
				}
				
				self::ensureEachAnswerHasTutorReviewTask_questionGroup($questions, $logger);
			}
		}		

	
	}
	
	
	
	// returns the users nicely grouped. The key of the array is either the group_id or the negative of the user_id (if there is no group).
	private static function collectGroups($assignment, $user_ids) {
		$groupDefinition = array();
		
		$groupList = StudentGroup::getUserGroupList($assignment, $user_ids, $assignment->onlyOneAnswerPerGroup());


		
		foreach($user_ids as $id) {
			if (!isset($groupList[$id])) {
				$groupList[$id] = -$id;
			}
			if(!isset($groupDefinition[$groupList[$id]])) {
				$groupDefinition[$groupList[$id]] = array();
			}
			$groupDefinition[$groupList[$id]][] = $id;
		}
		
		return array($groupList, $groupDefinition);		
	}
	
	// shuffles relatively deterministically by shifting everything around in a circle.
	private static function doRotationShuffle($groupDefinition, $requestedNumberOfPeers, $logger) {
		
		// find the largest group
		$largestGroup = 0;
		foreach($groupDefinition as $group) {
			$largestGroup = max($largestGroup, count($group));
		}
		
		// flatten the array
		$flattenedUsers = array();
		foreach($groupDefinition as $group) {
			$flattenedUsers = array_merge($flattenedUsers, $group);
		}
		$totalCount = count($flattenedUsers);

		// check if our algorithm is feasible.
		$numberOfPeers = $requestedNumberOfPeers;
		if ($totalCount < 2*$largestGroup + $numberOfPeers - 1) {
			$numberOfPeers = $totalCount - 2*$largestGroup + 1;
			if ($numberOfPeers < 1) {
				$logger(AssignmentEventLevel::error, "There are not enough submitted answers for the peer review process.");
				return array();
			} else {
				$logger(AssignmentEventLevel::warning, "Not enough submitted answers for $requestedNumberOfPeers peer reviews per submission. Only creating $numberOfPeers review tasks per submission.");
			}
		}
		
		// get the random offsets.
		$offsets = array();
		while(count($offsets) < $numberOfPeers) {
			$offset = rand($largestGroup, $totalCount-$largestGroup);
			$offsets[$offset] = $offset;
		}
		
		$result = array();
		foreach ($offsets as $offset) {
			for($i = 0; $i<$totalCount; $i++) {
				$source = $flattenedUsers[$i];
				$target = $flattenedUsers [($i + $offset) % $totalCount];
				if (! isset($result [$target])) {
					$result [$target] = array (	$source	);
				} else {
					$result [$target] [] = $source;
				}
			}
		}
		
		return $result;
		
	}
	
	// tries to improve the randomness by switching pairs of marking requests. Use it as a post-processing step for
	// a shuffling algorithm that is too deterministic.
	private static function improveShuffleRandomness($groupList, $shuffledData, $logger) {

		// collect the request positions we have.
		$allPositions = array();
		foreach($shuffledData as $originalUser => $requests) {
			foreach($requests as $requestIndex => $request) {
				$allPositions[] = array($originalUser, $requestIndex);		
			}
		}
		
		$positionsCount = count($allPositions);
		
		// iterate over all marking requests.
		$allPositionsCopy = $allPositions;
		foreach($allPositionsCopy as $sourcePositionData) {
			$sourceUser = $sourcePositionData[0];
			$sourcePosition = $sourcePositionData[1];
			$source = $shuffledData[$sourceUser][$sourcePosition];

			// try to find a target. We do up to 10 attempts to find a partner to swap with.
			for($i=0; $i < min($positionsCount,10); $i++) {
				$swap = rand($i, $positionsCount-1);
				$tmp = $allPositions[$i];
				$allPositions[$i] = $allPositions[$swap];
				$allPositions[$swap] = $tmp;
			
				$targetUser = $allPositions[$i][0];
				$targetPosition = $allPositions[$i][1];
				$target = $shuffledData[$targetUser][$targetPosition];
				
				// we have a target, so check if the swap would be valid.
				if ($groupList[$targetUser] == $groupList[$source]) continue;
				if ($groupList[$sourceUser] == $groupList[$target]) continue;
				if (in_array($source, $shuffledData[$targetUser])) continue;
				if (in_array($target, $shuffledData[$sourceUser])) continue;
				
				// all good, let's do the swap.
				$shuffledData[$targetUser][$targetPosition] = $source;
				$shuffledData[$sourceUser][$sourcePosition] = $target;
				break;						
			}
		}
		
		return $shuffledData;
	}
	

	// mass create the review tasks.
	// only pass the GroupList if you want to create child review tasks for all group members.
	// 
	// the shuffleData is an array of the following structure.
	// The main key is the user_id of the reviewer. The content of each field is an array of user_ids. 
	// The meaning is every reviewer gets all relevant answers of the userids in his/her list.
	// It is not necessary/possible to pass individual answers in the shuffleData structure.
	private static function createReviewTasks($questionOrAssignment, $shuffledData, $role, $groupList = array()) {
						
		// get all the answers
		$userAnswers = array();
		foreach($questionOrAssignment->submittedAnswers()->get(array('id','user_id')) as $answer) {
			if (!isset($userAnswers[$answer['user_id']])) $userAnswers[$answer['user_id']] = array();
			$userAnswers[$answer['user_id']][] = $answer['id'];
		}
		
		$reviewTasks = array();
		foreach($shuffledData as $user_id => $tasks) {
			foreach($tasks as $task) {
				foreach($userAnswers[$task] as $answer_id) {
					$newTask = array('answer_id' => $answer_id, 'user_id' => $user_id, 'reviewer_role' => $role);
					
					// create the child tasks if necessary
					if(isset($groupList[$user_id])) {
						$group = array();
						foreach(array_keys($groupList, $groupList[$user_id]) as $member) {
							if ($member != $user_id) {
								$group[] = $member;
							}
						}
						if (count($group) > 0) {
							$newTask['other_group_members'] = $group;
						}
					}
					
					$reviewTasks[] = $newTask; 
				}
			}
		}
		
		return Review::createReviewTasks($reviewTasks);
		
	} 
	
	// create the shuffling data for the peer review process.
	private static function createShuffleDataForPeerReview($assignment, $user_ids, $logger) {
		
		list($groupList, $groupDefinition) = self::collectGroups($assignment, $user_ids);
		$shuffledData = self::doRotationShuffle($groupDefinition, $assignment->number_of_peers, $logger);
		
		$shuffledData = self::improveShuffleRandomness($groupList, $shuffledData, $logger);
		
		return $shuffledData;
	}
	
	// create the shuffling data for the tutor reviews.
	private static function createShuffleDataForTutors($tutor_ids, $user_ids, $role, $logger){
		$result = array();
		
		// nothing to do?
		if (count($user_ids) == 0) return $result;
		
		// sanity check.
		$tutorsCount = count($tutor_ids);
		if ($tutorsCount == 0) {
			$logger(AssignmentEventLevel::error, "There is no $role to distribute marking tasks to.");
			return $result;
		}

		// add some randomness.
		shuffle($tutor_ids);
		shuffle($user_ids);
		
		// initialise the buckets.
		foreach($tutor_ids as $tutor) {
			$result[$tutor] = array();
		}
		
		// assign the marking tasks sequentially.
		$count = 0;
		foreach($user_ids as $user) {
			$result[$tutor_ids[$count % $tutorsCount]][] = $user;
			$count++;
		}
		
		return $result;		
	}
	

	// process a question or (if the assignment is to be treated as a whole) the entire assignment.
	private static function shuffleQuestionOrAssignmentPeers($questionOrAssignment, $logger) {
	
		// make sure we have the actual assignment
		if ($questionOrAssignment instanceof Question) {
			$assignment = $questionOrAssignment->assignment_real;
		} else {
			$assignment = $questionOrAssignment;
		}

		if ($assignment->usesPeerReview()) {
			
			// get all users that have answers.
			$user_ids = $questionOrAssignment->submittedAnswers()->distinct()->lists('user_id');
			
			if (empty($user_ids)) {
				$logger(AssignmentEventLevel::warning, "There are no submitted answers.");
				return;
			}
	
			// process the shuffle data for peer review.
			$shuffledData = self::createShuffleDataForPeerReview($assignment, $user_ids, $logger);
			$groupList = array();
			if ($assignment->onlyOneAnswerPerGroup()) {
				list($groupList, $groupDefinition) = self::collectGroups($assignment, $user_ids);
			}
			$peerReviewCount = self::createReviewTasks($questionOrAssignment, $shuffledData, ReviewReviewerRole::student, $groupList);
			
			$logger(AssignmentEventLevel::info, "$peerReviewCount peer review requests created.");
			
		}
	}
	
	// process a question or (if the assignment is to be treated as a whole) the entire assignment.
	private static function shuffleQuestionOrAssignmentTutors($questionOrAssignment, $logger) {
	
		// make sure we have the actual assignment
		if ($questionOrAssignment instanceof Question) {
			$assignment = $questionOrAssignment->assignment_real;
			$question = $questionOrAssignment;
		} else {
			$assignment = $questionOrAssignment;
			$question = $questionOrAssignment->questionsOrdered()->first(); // the question is only used to determine the active tutors. If we have a whole sheet, we take the ones for the first question.
			
			if (is_null($question)) return; // if the assignment doesn't have any questions, we stop.
		}

		if ($assignment->usesTutorMarking()) {
				

			// get all users that have answers.
			$user_ids = $questionOrAssignment->submittedAnswers()->distinct()->lists('user_id');
			
			if (empty($user_ids)) {
				$logger(AssignmentEventLevel::warning, "There are no submitted answers.");
				return;
			}
	
	
			// process the tutor review tasks
			$tutor_ids = $assignment->getTutorsForQuestionQuery($question)->distinct()->lists('id');
			$shuffledData = self::createShuffleDataForTutors($tutor_ids, $user_ids, 'tutor', $logger);
			$tutorReviewCount = self::createReviewTasks($questionOrAssignment, $shuffledData, ReviewReviewerRole::tutor);
			
			$logger(AssignmentEventLevel::info, "$tutorReviewCount tutor review requests created.");
		}
	
	}
	
	// process a question or (if the assignment is to be treated as a whole) the entire assignment.
	private static function shuffleQuestionOrAssignmentLecturer($questionOrAssignment, $logger) {
	
		// make sure we have the actual assignment
		if ($questionOrAssignment instanceof Question) {
			$assignment = $questionOrAssignment->assignment_real;
		} else {
			$assignment = $questionOrAssignment;
		}
	
	
		// get all users that have answers.
		$user_ids = $questionOrAssignment->submittedAnswers()->distinct()->lists('user_id');
	
		if (empty($user_ids)) {
			return;
		}
		
		// check if there are any answers that are not scheduled for review. If so, give it to the lecturer...
		$missing_ids = $questionOrAssignment->submittedAnswers()->has('reviews','<',1)->distinct()->lists('user_id');
		if (count($missing_ids) > 0) {
			$lecturer_ids = $assignment->subject->lecturers()->distinct()->lists('user_id');
			$shuffledData = self::createShuffleDataForTutors($lecturer_ids, $missing_ids, 'lecturer', $logger);
			$lecturerReviewCount = self::createReviewTasks($questionOrAssignment, $shuffledData, ReviewReviewerRole::lecturer);
			
			$logger(AssignmentEventLevel::info, "$lecturerReviewCount lecturer review requests created.");
		}
		
	}	
	
	
	
	public static function shuffleAssignment(Assignment $assignment) {
		if ($assignment->markingHasStarted()) {
			App::abort(500, 'This assignment is already in the marking phase.');			
		}

		set_time_limit(300);
		
		$assignment->logEvent(AssignmentEventLevel::info, "Marking phase started.");
		
		// Create all the student reviews.
		if ($assignment->keepAssignmentTogetherWhenShuffling()) {
			
			$logger = function($level, $text) use ($assignment) {
				$assignment->logEvent($level, "Creating peer review tasks: ". $text);
			};				
			
			self::shuffleQuestionOrAssignmentPeers($assignment, $logger);
		} else {
			
			foreach($assignment->questions_ordered as $question) {
				
				$logger = function($level, $text) use ($assignment, $question) {
					$assignment->logEvent($level, "Creating peer review tasks (question ". $question->presenter()->full_position ."): ". $text);
				};
				
				self::shuffleQuestionOrAssignmentPeers($question, $logger);
			}				
		}
		
		// Create all the tutor reviews.
		if ($assignment->keepAssignmentTogetherWhenShuffling() && $assignment->tutorsAreIdenticalforAllQuestions()) {
				
			$logger = function($level, $text) use ($assignment) {
				$assignment->logEvent($level, "Creating tutor review tasks: ". $text);
			};
				
			self::shuffleQuestionOrAssignmentTutors($assignment, $logger);
		} else {
				
			foreach($assignment->questions_ordered as $question) {
		
				$logger = function($level, $text) use ($assignment, $question) {
					$assignment->logEvent($level, "Creating tutor review tasks (question ". $question->presenter()->full_position ."): ". $text);
				};
		
				self::shuffleQuestionOrAssignmentTutors($question, $logger);
			}
		}
		
		

		// create the lecturer reviews
		foreach($assignment->questions_ordered as $question) {
		
			$logger = function($level, $text) use ($assignment, $question) {
				$assignment->logEvent($level, "Creating lecturer review tasks (question ". $question->presenter()->full_position ."): ". $text);
			};
		
			self::shuffleQuestionOrAssignmentLecturer($question, $logger);
		}
		
		
		$assignment->marking_started = true;
		$assignment->autostart_marking_time = null;
		$assignment->save() || App::abort(500, 'Could not save changes.');
		
	}
	
};
