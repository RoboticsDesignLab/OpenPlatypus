@extends('...templates.master')

@section('title') 
Create a new class 
@stop




@section('content') 

{{ Form::open_horizontal(array('route' => 'newSubject')) }}


{{ Form::input_group('text', 'code', 'Code', $subject->code, $errors, NULL, 'The code of the class, for example <em>METR4202</em>') }}

{{ Form::input_group('text', 'title', 'Title', $subject->title, $errors, NULL, 'The full title of the class, for example <em>Advanced Control and Robotics</em>') }}

{{ Form::input_group('text', 'start_date', 'Start date', $subject->start_date, $errors, array('placeholder' => 'DD/MM/YYYY'), 
	'The date the class is starting. This should be the start of the semester or the date of the first session.') }}

{{ Form::input_group('text', 'end_date', 'End date', $subject->end_date, $errors, array('placeholder' => 'DD/MM/YYYY'), 
	'The date the class is finishing. This should be the end of the semester or	the date of the last session.') }}

{{ Form::checkbox_group('visibility', 'Make the class visible for enrolled students and tutors.', 1, $subject->visibility, $errors) }}


{{ Form::submit_group(array('submit_title' => 'Create this class now')) }}
				


{{ Form::close() }}

@stop
