@extends('templates.master') 

@section('title')
Manage users
@stop 

@section('sub_title') 
@stop


@section('content')

<div class="row"><div class="col-md-12 text-right">
<a href="{{ route('newUser') }}"><button type="button" class="btn btn-primary">Add new user</button></a>
</div></div>

<div class="row"><div class="col-md-12">


<?php 
$urlGenerator = function($user) {
	$authenticator = AuthenticationDomainDefinitions::createAuthenticator($user->authentication_domain);
	if(!isset($authenticator) || ($authenticator === false)) return null;
	return route('editUser', $user->id);
};

$permissionsGenerator = function ($user)  {
	return $user->presenter()->permissions;
};

$authInfoGenerator = function ($user) {
	$user = $user->resource;
	$authenticator = AuthenticationDomainDefinitions::createAuthenticator($user->authentication_domain);
	if(!isset($authenticator) || ($authenticator === false)) return '?';
	return $authenticator->getAuthInfoForDisplay($user);
};


$appendColumns = array(
	array('title' => "Authentication", 'generator' => $authInfoGenerator),
	array('title' => "Permissions", 'generator' => $permissionsGenerator),
)

?>

@include('user.user_list_insert', array('users' => $users, 'urlGenerator' => $urlGenerator, 'appendColumns' => $appendColumns))

</div></div>

@stop
