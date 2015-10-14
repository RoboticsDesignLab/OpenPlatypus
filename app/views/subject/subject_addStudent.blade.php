@extends('subject.subject_template')
 
@section('title') 
Manage students for {{{ $subject->code }}}
@stop 

@section('content')
@parent



<div class="row">
<div class="col-md-12">

<?php
 
if($showEdit) {
	
	$buttonGenerator = function($user) use($subject) {
		$result = "";
		$result .= '<div class="ajaxFormWrapper">';
		$result .= View::make('subject.subject_addStudentButton_insert')->withSubject($subject)->withUser($user)->render();
		$result .= '</div>';
		return $result;
	};

} else {
	$buttonGenerator = function($user) use($subject) {
		return $subject->isMember($user) ? $subject->getMembership($user)->presenter()->role_complete : "";
	};
}

?>

@include('user.user_list_insert', array('users' => $users, 'buttonGenerator' => $buttonGenerator))

</div>
</div>

@stop
