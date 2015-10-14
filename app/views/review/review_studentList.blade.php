@extends('assignment.assignment_template') 

@section('title') 
Reviews
@stop 


@section('content')
@parent


<?php

$roleGenerator = function ($user) use($assignment) {
	return $assignment->subject->getMembership($user->resource)->presenter()->role_complete;
};

$reviewSummaryGenerator = function ($user) use($assignment) {
	$result = '';
	$result .= View::make('review.review_studentList_summary_insert')->withUser($user)->withAssignment($assignment)->render();
	return $result;
};

$appendColumns = array(
		array('title' => 'Role', 'generator' => $roleGenerator),
		array('title' => 'Completed', 'generator' => $reviewSummaryGenerator),
);

$urlGenerator = function($user) use ($assignment) {
	return route('manageReviewsUser', array('assignment_id' => $assignment->id, 'user_id' => $user->id));
};

?>

<div class="row">
	<div class="col-md-12">
		@include('user.user_list_insert', array('users' => $users, 'urlGenerator' => $urlGenerator, 'appendColumns' => $appendColumns))
	</div>
</div>

@stop
