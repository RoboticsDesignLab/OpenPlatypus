@extends('assignment.assignment_template')
@section('title')
Answer @parent
@stop 


@section('content')
@parent


@if(!$assignment->isOpenForSubmissions())
	<div class="add-bottom-margin">
		<div class="alert alert-warning">
			<strong>Submissions have been closed by the lecturer and it is not possible to submit answers for this assignment.</strong>
		</div>	
	</div>
@endif

<div class="row add-bottom-margin">
	<div class="col-md-12">
		@include('...textblock.textblock_show_insert', array('textBlock' => $assignment->introduction, 'showEditLink' => false, 'role' => TextBlockRole::assignmentintroduction))
	</div>
</div>

@foreach($assignment->getQuestionsOrderedWithSubquestions() as $question)
	
	<?php $question = $question->presenter(); ?>
	
	<div class="row updatable" data-resourceid="question_{{{ $question->id }}}">
		@include('assignment.assignment_answer_questionpanel_insert', array('question' => $question, 'answer' => (isset($answers[$question->id])?$answers[$question->id]:null) ))
	</div>
	
@endforeach



@stop
