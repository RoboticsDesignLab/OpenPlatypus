@extends('subject.subject_template')

<?php use Platypus\Helpers\PlatypusBool; ?>

@section('title') 
New assignment for {{{$subject->code}}} 
@stop




@section('content') 
@parent

{{ Form::open_horizontal(array('route' => array('newAssignment', $subject->id))) }}


{{ Form::input_group('text', 'title', 'Title', $assignment->title, $errors, NULL, 'A meaningful title of the assignment sheet.') }}

{{ Form::input_group('text', 'answers_due', 'Due date', $assignment->answers_due, $errors, array('placeholder' => 'DD/MM/YYYY HH:MM:SS'), 
	'The date (and time) the answers for this assignment are due.') }}

{{ Form::submit_group(array('submit_title' => 'Create this assignment now')) }}

{{ Form::close() }}


@stop

