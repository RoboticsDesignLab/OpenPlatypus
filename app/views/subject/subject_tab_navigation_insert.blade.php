<?php 

if(!isset($forMainMenu)) {
	$forMainMenu = false;
}

if(!isset($currentRoute)) {
	$currentRoute = Route::current()->getName();
}

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
} else if ($subject->mayViewMembers(Auth::user())) {
	$tabs[] = array('title' => "Class members", 'route' => array('manageExistingSubjectMembers', $subject->id));
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

@if($forMainMenu)
	@if(empty($tabs))
		<li class="dropdown"><a href="{{{ route('showSubject', $subject->id) }}}">{{{ $subject->code }}}</span></a></li>
	@else
		<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">{{{ $subject->code }}} <span class="caret"></span></a>
			<ul class="dropdown-menu" role="menu">
				@include('templates.tab_navigation_insert', array('tabs' => $tabs, 'forMainMenu' => true))
			</ul>
		</li>
	@endif
@else

	@include('templates.tab_navigation_insert', array('tabs' => $tabs, 'currentRoute' => $currentRoute, 'forMainMenu' => $forMainMenu ))
	
@endif
