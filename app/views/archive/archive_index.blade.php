@extends('archive.archive_master_template') 

@section('title') 
{{{ $assignment->title }}}
@stop 

@section('sub_title') 
{{{ $assignment->subject->presenter()->title }}}
@stop 

@section('content')
@parent


<h3>Assignment</h3>
<ul>
<li><a href="assignment.html">Assignment sheet</a></li>
<li><a href="results.csv">Results table</a></li>
</ul>

<h3>Students</h3>


<?php 
$urlGenerator = function($user) {
	return $user->presenter()->archive_file_name.'.html';
};


?>

@include('user.user_list_insert', array('users' => $users, 'urlGenerator' => $urlGenerator))


@stop
