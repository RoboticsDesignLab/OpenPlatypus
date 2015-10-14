@extends('templates.master') 

@section('title') 
Login 
@stop

@section('content') 

{{ Form::open_horizontal(array('action' => 'LocalAuthenticatorController@loginPost')) }}

{{ Form::input_group('text', 'email', 'Email or student id', '', $errors) }}

{{ Form::input_group('password', 'password', 'Password', '', $errors) }}

{{ Form::submit_group(array('submit_title' => 'Login')) }}
				
{{ Form::close() }}

@stop
