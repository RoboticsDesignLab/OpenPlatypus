@extends('assignment.assignment_template')
 
@section('title') 
Manage tutors
@stop 

@section('content')
@parent


<div class="panel panel-default"><div class="panel-heading">Manage active tutors</div><div class="panel-body">
<p>
Here you can select explicitely which tutors are active for this assignment or certain questions.
When marking tasks are created, the tasks are distributed evenly amongst the tutors that are marked
as active for the question the task belongs to. 
</p>
<p>   
When no tutors are selected explicitely, all tutors are considered to be active for this question or assignment.
Thus, it is not necessary to make changes here if all tutors are supposed to mark all questions evenly.
</p>
</div></div>

<div class="row">
<div class="col-md-12">

<?php 
$buttonGenerator = function($user) use($assignment) {
	$result = "";
	$result .= '<div class="ajaxFormWrapper">';
	$result .= View::make('assignment.assignment_editTutorButton_insert')->withAssignment($assignment)->withUser($user)->render();
	$result .= '</div>';
	return $result;
};
?>

@include('user.user_list_insert', array('users' => $users, 'buttonGenerator' => $buttonGenerator))

@if(count($users)==0)
<p><strong>You don't seem to have any tutors assigned to this class.</strong></p>

<p>
To add tutors, please go back the the main page of the class and use the function to manage class members.
</p>	
@endif


</div>
</div>

@stop
