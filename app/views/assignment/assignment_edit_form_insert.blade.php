<?php 
	use Platypus\Helpers\PlatypusBool; 
	use Platypus\Helpers\PlatypusEnum;

	$disabledIfMarking = function($attributes = array()) use ($assignment) {
		if ($assignment->markingHasStarted()) {
			$attributes['disabled'] = 'disabled';
		}
		return $attributes;
	}

?>

{{ Form::open_horizontal(array('route' => array('editAssignmentAjax', $assignment->id))) }}


{{ Form::input_group('text', 'title', 'Title', $assignment->title, $errors, NULL, 'A meaningful title of the assignment sheet.') }}


{{ Form::radio_group_vertical('visibility', 'visibility', Assignment::explainVisibility(), $assignment->visibility, $errors) }}



{{ Form::input_group('text', 'answers_due', 'Due date', $assignment->answers_due, $errors, $disabledIfMarking(array('placeholder' => 'DD/MM/YYYY HH:MM:SS')), 
	'The date (and time) the answers for this assignment are due.') }}

{{ Form::radio_group_vertical('late_policy', 'late_policy', Assignment::explainLatePolicy(), $assignment->late_policy, $errors) }}
		

{{ Form::input_group('text', 'autostart_marking_time', 'Autostart marking', $assignment->autostart_marking_time, $errors, $disabledIfMarking(array('placeholder' => 'DD/MM/YYYY HH:MM:SS')), 
	'Enter a date (and time) when the marking phase is supposed to start automatically. Leave this field empty to disable automatic starting of the marking phase.') }}	

	
{{ Form::radio_group_vertical('guess_marks', 'guess_marks', Assignment::explainGuessMarks(), $assignment->guess_marks, $errors, $disabledIfMarking()) }}
	
		
{{ Form::input_group('text', 'number_of_peers', 'Number of peers', $assignment->number_of_peers, $errors, $disabledIfMarking(), 
	'Each solution is marked by a number of different students. This number selects how many times a solution is marked by other students.') }}	
	

{{ Form::radio_group_vertical('shuffle_mode', 'shuffle_mode', Assignment::explainShuffleMode(), $assignment->shuffle_mode, $errors, $disabledIfMarking()) }}

		
		
{{ Form::input_group('text', 'peers_due', 'Peer review due date', $assignment->peers_due, $errors, array('placeholder' => 'DD/MM/YYYY HH:MM:SS'), 
	'The date (and time) the students have to finish their marking by. This date is informative only and not enforced.') }}	
	
<?php 
// we need to be a bit more selective when it comes to the tutor options and we are marking.
$fieldOptions = array();
if($assignment->markingHasStarted()) {
	if($assignment->usesTutorMarking()) {
		$fieldOptions[AssignmentMarkByTutors::none] = array('disabled' => 'disabled');
	} else {

		foreach(AssignmentMarkByTutors::getValues() as $key) {
			$fieldOptions[$key] = array('disabled' => 'disabled');
		}
		
	}
}

?>

{{ Form::radio_group_vertical('mark_by_tutors', 'mark_by_tutors', Assignment::explainMarkByTutors(), $assignment->mark_by_tutors, $errors, array(), null, true, $fieldOptions) }}
	
{{ Form::input_group('text', 'tutors_due', 'Tutor marks due date', $assignment->tutors_due, $errors, array('placeholder' => 'DD/MM/YYYY HH:MM:SS'), 
	'The date (and time) the tutors have to have completed their marking by. This date is informative only and not enforced.') }}	



{{ Form::radio_group_vertical('marks_released', 'marks_released', Assignment::explainMarksReleased(), $assignment->marks_released, $errors) }}
	


{{ Form::radio_group_vertical('group_work_mode', 'group_work_mode', Assignment::explainGroupWorkMode(), $assignment->group_work_mode, $errors, $disabledIfMarking()) }}


{{ Form::radio_group_vertical('group_selection_mode', 'group_selection_mode', Assignment::explainGroupSelectionMode(), $assignment->group_selection_mode, $errors, $disabledIfMarking()) }}

{{ Form::input_group('text', 'group_size_min', 'Minimum group size', $assignment->group_size_min, $errors, $disabledIfMarking(),
	'The minimum size a group is allowed to have. (Only enforced when students self-assign groups.)') }}

{{ Form::input_group('text', 'group_size_max', 'Maximum group size', $assignment->group_size_max, $errors, $disabledIfMarking(),
	'The maximum size a group is allowed to have. (Only enforced when students self-assign groups.)') }}

	




{{ Form::submit_group(array('submit_title' => 'Save changes', 'cancel_url' => route('editAssignmentAjaxShow', $assignment->id), 'cancel_title' => 'Cancel' )) }}
				

{{ Form::close() }}

