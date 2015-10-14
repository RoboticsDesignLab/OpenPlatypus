@extends('templates.master') 

@section('title')
Add new user account
@stop 

@section('sub_title') 
@stop


@section('content')


{{ Form::open_horizontal(array('route' => array('newUser'))) }}


{{ Form::input_group('text', 'first_name', 'First name', '', $errors) }}

{{ Form::input_group('text', 'last_name', 'Last name', '', $errors) }}

{{ Form::input_group('text', 'email', 'Email address', '', $errors) }}

{{ Form::input_group('text', 'student_id', 'Student ID', '', $errors) }}

@if(isset($authenticators))

	<?php
		$authenticator_choices = array();
		foreach($authenticators as $authenticator) {
			$authenticator_choices[$authenticator[0]] = (new $authenticator[1])->getAuthenticatorName();
		}
	?>

	{{ Form::radio_group_vertical('domain', 'domain', $authenticator_choices, -1, $errors) }}
@endif


{{ Form::submit_group(array('submit_title' => 'Add user', 'cancel_url' => route('listUsersForUserManager'), 'cancel_title' => 'Cancel')) }}

{{ Form::close() }}


@stop
