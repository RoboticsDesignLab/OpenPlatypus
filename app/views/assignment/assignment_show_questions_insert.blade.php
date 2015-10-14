<?php 
	if(!isset($border)) {
		$border = true;
	}


	if(!isset($attachmentPathPrefix)) {
		$attachmentPathPrefix = '';
	}
		
	
?>

@if($border)
<div class="panel panel-default">
<div class="panel-body">
@endif

<div class="add-bottom-margin">
	@include('...textblock.textblock_show_insert', array(
		'textBlock' => $assignment->introduction, 
		'showEditLink' => false, 
		'role' => TextBlockRole::assignmentintroduction,
		'attachmentPathPrefix' => $attachmentPathPrefix.'introduction_',
	))
</div>

@foreach($assignment->getQuestionsOrderedWithSubquestions() as $question)
	@include('...question.question_show_insert', array('question' => $question, 'attachmentPathPrefix' => $attachmentPathPrefix.'question'.$question->presenter()->full_position.'_'))
@endforeach

@if($border)
</div>
</div>
@endif
