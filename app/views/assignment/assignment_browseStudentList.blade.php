@extends('assignment.assignment_template') 

@section('title') 
Browse students
@stop 


@section('content')
@parent


<?php


$urlGenerator = function($user) use ($assignment) {
	return route('assignmentBrowseStudentShow', array('assignment_id' => $assignment->id, 'user_id' => $user->id));
};

?>

<div class="row">
	<div class="col-md-12 text-right">
		{{ Form::open_inline(array('route' => array('assignmentJumpToReview', $assignment->id))) }}
		
		<strong>Review #</strong> <input type="text" name="review" class="form-control">
		
		<input type="submit" class="btn btn-primary" value="Search">
		
		{{ Form::close() }}
	</div>

	<div class="col-md-12">
		@include('user.user_list_insert', array('users' => $users, 'urlGenerator' => $urlGenerator))
	</div>
</div>

@stop
