@extends('templates.master')
 
@section('title') 
{{{ $subject->code }}}@stop 

@section('sub_title') 
{{{ $subject->title }}}
@stop


<?php 

$tabs = array();
$tabs[] = array('title' => "Overview", 'route' => array('showSubject', $subject->id));

if( $subject->mayEdit(Auth::user())) {
	$tabs[] = array('title' => "Edit", 'route' => array('editSubject', $subject->id));
}

if( $subject->mayManageMembers(Auth::user())) {
	$subtabs = array();
	$subtabs[] = array('title' => "Class members", 'route' => array('manageExistingSubjectMembers', $subject->id));
	$subtabs[] = array('title' => "Add member from list", 'route' => array('manageSubjectMembers', $subject->id));
	$subtabs[] = array('title' => "Add new member", 'route' => array('createUserAndAddToSubject', $subject->id), 'alternateRoutes' => array('addExistingUserToSubjectConfirm'));
	$subtabs[] = array('title' => "Mass upload student list", 'route' => array('massUploadStudentsToSubject', $subject->id));
	$tabs[] = array('title' => "Members", 'dropdown' => $subtabs);
}

if($subject->mayCreateAssignment(Auth::user())) {
	$tabs[] = array('title' => "New assignment", 'route' => array('newAssignment', $subject->id));
}


if( $subject->mayEdit(Auth::user())) {
	$tabs[] = array('title' => "Delete", 'route' => array('deleteSubject', $subject->id));
}

// if there is only the overview, hide the navigation.
if(count($tabs) <= 1) {
	$tabs = array();
}

?>

@section('navbarextras')
	@include('subject.subject_tab_navigation_insert', array('subject' => $subject, 'forMainMenu' => true))
@stop


@section('content')

<div class="row hidden-xs">
	<div class="col-md-12">
		@include('subject.subject_tab_navigation_insert', array('subject' => $subject, 'forMainMenu' => false))
	</div>
</div>

@stop


