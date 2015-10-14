@extends('...templates.master')
 
@section('title') 
Switch user
@stop 

@section('sub_title') 
@stop


@section('content')

<div class="row">
<div class="col-md-12">

<?php 
$buttonGenerator = function($user) {
	return Form::post_button_primary( array('route' => array('debugSwitchUserPost','id' => $user->id) ),'Switch user');
};
?>

@include('user.user_list_insert', array('users' => $users, 'buttonGenerator' => $buttonGenerator))

</div>
</div>

@stop
