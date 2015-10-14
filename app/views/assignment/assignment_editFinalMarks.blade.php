@extends('assignment.assignment_template') 

@section('title') 
Set final marks
@stop 


@section('content_wide')
@parent


<?php


if($assignment->mayBrowseStudents(Auth::user())) {
	$urlGenerator = function($user) use ($assignment) {
		return route('assignmentBrowseStudentShow', array('assignment_id' => $assignment->id, 'user_id' => $user->id));
	};
} else {
	$urlGenerator = null;
}


$offerEdit = $assignment->maySetFinalMarks(Auth::user());

$markGenerator = function ($user, $question = null, $isLastColumn = false) use($assignment, $offerEdit) {
	
	$result = '';
	if(isset($question)) {
		$resourceid ='final_mark_button_'.$user->id.'_'.$question->id;
	} else {
		$resourceid = 'final_mark_button_'.$user->id;
	} 
	$result .= '<div class="ajaxFormWrapper updatable" data-resourceid="'.$resourceid.'">';
	
	$result .= View::make('assignment.assignment_editMarkButton_insert')
		->withUser($user)
		->withAssignment($assignment)
		->withQuestion($question)
		->with('offerEdit', $offerEdit)
		->with('isLastColumn', $isLastColumn)
		->render();
	
	$result .= '</div>';
	
	return $result;
	
};


$appendColumns = array(
		array(
				'title' => '<div class="text-center" style="min-width: 120px;">Final Mark</div>', 
				'generator' => $markGenerator
		),
);

$questions = array();
foreach($assignment->getQuestionsOrderedWithSubquestions() as $question) {
	if($question->isMaster()) continue;
	$questions[] = $question;
}

for($i=0; $i<count($questions); $i++) {
	$question = $questions[$i];
	
	$isLastColumn = ($i == count($questions)-1);
	
	$appendColumns[] = array(
		'title' => '<div class="text-center" style="min-width: 120px;">Question '.$question->presenter()->full_position.'</div>',
		'generator' => function($user) use ($question, $markGenerator, $isLastColumn) { return $markGenerator($user, $question, $isLastColumn); },
	);	
}


?>



<div class="row">

	<div class="col-md-12">
	
		<span class="pull-right">
		<a href="{{{route('getAssignmentResultsAsCsv', $assignment->id)}}}" class="btn btn-primary">Download results as csv</a>
		
		@if($offerEdit)
			<a href="{{{route('autoMarkAssignment', $assignment->id)}}}" class="btn btn-primary">Assign marks semi-automatically</a>
		@endif
		</span>
	
		@include('user.user_list_insert', array(
			'users' => $users, 
			'urlGenerator' => $urlGenerator,
			'clickableRows' => false,
			'stickyHeaders' => true,
			'appendColumns' => $appendColumns,
		))
	</div>
</div>

@stop
