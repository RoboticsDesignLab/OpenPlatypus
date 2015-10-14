

<?php 

$makeRoleChangingLink = function ($text, $question_id, $status, $confirmation = null) use($assignment, $user) {

	$statusValue = $status;
	if ($statusValue === true) $statusValue = 1;
	if ($statusValue === false) $statusValue = 0;
	
	$attributes = array();
	$attributes['data-url'] = route('editAssignmentTutorsAjax', array('id' => $assignment->id, 'userid' => $user->id, 'question_id' => $question_id, 'status' => $statusValue ));
	$attributes['data-_token'] = csrf_token();
	$attributes['class'] = 'ajaxPost';
	if (!is_null($confirmation)) {
		$attributes['data-confirmationdialog'] = $confirmation;
	}
	return link_to('#', $text, $attributes );
};

$assignmentTutors = $assignment->getAssignmentTutorsForUser($user->resource);

$isWildcardTutor = false;
$questions = array();

foreach($assignmentTutors as $tutor) {
	if (is_null($tutor->question_id)) {
		$isWildcardTutor = true;
	} else {
		$questions[] = $tutor->question_id;
	}
}

if ($isWildcardTutor) {
	$tutorFor = 'Marks all questions';
} else {
	$tutorFor = '';
	$questionCount = 0;
	
	foreach($assignment->questions_ordered as $question) {
		foreach($assignmentTutors as $tutor) {
			if($tutor->question_id == $question->id) {
				if (!empty($tutorFor)) {
					$tutorFor .= ', ';
				}
				$tutorFor .= $question->presenter()->position;
				$questionCount++;
			}
		}
	}
	
	if ($questionCount == 0) {
		$tutorFor = '---';
	} else if ($questionCount == 1) {
		$tutorFor = 'Marks question '.$tutorFor;
	} else {
		$tutorFor = 'Marks questions '.$tutorFor;
	}
}

?>

<div class="dropdown">
	<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
		{{{ $tutorFor }}} <span class="caret"></span>
	</button>

	<ul class="dropdown-menu" role="menu">
		
		@if($isWildcardTutor || !empty($questions))
			<li>{{ $makeRoleChangingLink('Tutor marks no questions', 0, false) }}</li>
		@endif
		@if(!$isWildcardTutor)
			<li>{{ $makeRoleChangingLink('Tutor marks all questions', 0, true) }}</li>
		@endif
		@foreach($assignment->questions_ordered as $question)
			@if(in_array($question->id, $questions))
				<li>{{ $makeRoleChangingLink('Tutor does not mark question '.$question->presenter()->position, $question->id, false) }}</li>
			@else
				<li>{{ $makeRoleChangingLink('Tutor marks question '.$question->presenter()->position, $question->id, true) }}</li>
			@endif
		@endforeach
		
		
	</ul>
</div>