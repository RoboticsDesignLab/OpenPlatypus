@extends('templates.modal')
 
@section('title')
Do you want to submit your answer as shown here?
@stop


@section('body')
	@include('textblock.textblock_show_insert', array('textBlock' => $answer->text, 'showEditLink' => false, 'role' => TextBlockRole::studentanswer))
	
	@if($answer->studentsMayGuessTheirMarks())
	<hr>
	@endif
	
	<div class="ajaxFormWrapper">
	{{ Form::open_horizontal(array('route' => array('submitAnswerPost','answer_id' => $answer->id), 'id' => 'answer_submission_form_'.$answer->id )) }}
	
	@if($answer->studentsMayGuessTheirMarks())
	{{ Form::input_group('text', 'guessed_mark', 'Guess&nbsp;your&nbsp;mark:', "", $errors, NULL, 'Please enter the mark you think you will receive for this question as percentage from 0 to 100.') }}
	@endif
		
	{{ Form::close() }}
	</div>
	
@stop

@section('footer')
	<button type="button" class="btn btn-primary" onclick="$('#answer_submission_form_{{{ $answer->id }}}').submit();">Submit now</button>
	<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
@stop
