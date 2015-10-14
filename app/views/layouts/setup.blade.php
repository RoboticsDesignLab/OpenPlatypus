@extends('templates.master') 

@section('title') 
Initialise the database 
@stop

@section('navbarbuttons')
@overwrite


@section('content') 

<p>You are about to initialise the Platypus database. Please enter your details below so you can be made the administrator of this installation.</p>  

{{ Form::open_horizontal(array('url' => 'setup')) }}


{{ Form::input_group('text', 'first_name', 'First name', '', $errors, NULL) }}

{{ Form::input_group('text', 'last_name', 'Last name', '', $errors, NULL) }}

{{ Form::input_group('text', 'email', 'Email address', '', $errors) }}

{{ Form::input_group('password', 'password', 'Password', '', $errors) }}

{{ Form::input_group('password', 'password_confirmation', 'Password confirmation', '', $errors) }}


{{ Form::submit_group(array('submit_title' => 'Initialise database now.')) }}
				
{{ Form::close() }}

@stop
