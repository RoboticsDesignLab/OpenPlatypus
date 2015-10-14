@extends('templates.master') 

@section('title')
Edit user
@stop 

@section('sub_title') 
@stop


@section('content')


{{ Form::open_horizontal(array('route' => array('editUser', $user->id))) }}


<?php 
	$disabledAttributes = function($field) use ($userDataChangeable) {
		if($userDataChangeable[$field]) {
			return null;
		} else {
			return array("disabled" => "");
		}
	}
	
?>

{{ Form::input_group('text', 'first_name', 'First name', $user->first_name, $errors, $disabledAttributes('first_name')) }}

{{ Form::input_group('text', 'last_name', 'Last name', $user->last_name, $errors, $disabledAttributes('last_name')) }}

{{ Form::input_group('text', 'student_id', 'Student ID', $user->student_id, $errors, $disabledAttributes('student_id')) }}

{{ Form::input_group('text', 'email', 'Email address', $user->email, $errors, $disabledAttributes('email')) }}

@if($user->userPasswordChangeable())
{{ Form::input_group('password', 'password', 'New password', '', $errors, null, "Leave the password fields empty if you do not want to change the user's password.") }}

{{ Form::input_group('password', 'password_confirmation', 'Password confirmation', '', $errors, null, "Leave the password fields empty if you do not want to change the user's password.") }}
@endif

{{ form::checkbox_group('create_class', 'User may create classes', 1, $user->hasPermission(PermissionType::create_class), $errors) }}

{{ form::checkbox_group('admin', 'User is admin', 1, $user->hasPermission(PermissionType::admin), $errors) }}

{{ form::checkbox_group('debug', 'User may access debug functions', 1, $user->hasPermission(PermissionType::debug), $errors) }}


{{ Form::submit_group(array('submit_title' => 'Save changes', 'cancel_url' => route('listUsersForUserManager'), 'cancel_title' => 'Cancel' )) }}
			
{{ Form::close() }}


@stop
