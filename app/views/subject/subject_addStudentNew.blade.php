@extends('subject.subject_template')

@section('title') 
Add student to {{{ $subject->code }}}
@stop 


@section('content')
@parent

{{ Form::open_horizontal(array('route' => array('createUserAndAddToSubject', $subject->id))) }}


{{ Form::input_group('text', 'first_name', 'First name', '', $errors) }}

{{ Form::input_group('text', 'last_name', 'Last name', '', $errors) }}

{{ Form::input_group('text', 'email', 'Email address', '', $errors) }}

{{ Form::input_group('text', 'student_id', 'Student ID', '', $errors) }}

{{ Form::radio_group_vertical('role', 'role', SubjectMember::explainRole(), SubjectMemberRoles::student, $errors) }}



{{ Form::submit_group(array('submit_title' => 'Add new student')) }}

{{ Form::close() }}


@stop
