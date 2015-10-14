<?php 

if(!isset($forMainMenu)) {
	$forMainMenu = false;
}

if(!isset($currentRoute)) {
	$currentRoute = Route::current()->getName();
}

$tabs = array();
$tabs[] = array('title' => "Overview", 'route' => array('showAssignment', $assignment->id));

if ($assignment->mayViewControlPanel(Auth::user())) {
	$tabs[] = array('title' => "Control panel", 'route' => array('showAssignmentControlPanel', $assignment->id));
}

if($assignment->maySeeAssignmentEditor(Auth::user())) {
	$tabs[] = array('title' => "Edit assignment", 'route' => array('editAssignment', $assignment->id), 'alternateRoutes' => array('editAssignmentAjax'));
}

if($assignment->mayManageAssignment(Auth::user())) {
	$tabs[] = array('title' => "Manage tutors", 'route' => array('editAssignmentTutors', $assignment->id));
}

if($assignment->mayViewAssignmentGroups(Auth::user())) {
	$tabs[] = array('title' => "Student groups", 'route' => array('editStudentGroups', $assignment->id));
}

if($assignment->mayManageAssignment(Auth::user())) {
	
	if($assignment->mayViewReviews(Auth::user())) {
		$tabs[] = array('title' => "Manage reviews", 'route' => array('manageReviews', $assignment->id), 'alternateRoutes' => array('manageReviewsUser'));
	}
	
}

if($assignment->maySeeAssignmentAnswerPage(Auth::user())) {
	$tabs[] = array('title' => "Submit answers", 'route' => array('answerAssignment', $assignment->id));
}

if($assignment->isActiveStudent(Auth::user()) && $assignment->usesGroups()) {
	$tabs[] = array('title' => "My group", 'route' => array('showGroupForStudent', $assignment->id));
}

if($assignment->mayWriteReviews(Auth::user())) {
	$tabs[] = array('title' => "Mark answers", 'route' => array('showReviewTasks', $assignment->id));
}

if($assignment->mayBrowseStudents(Auth::user())) {
	$tabs[] = array('title' => "Browse students", 'route' => array('assignmentBrowseStudentList', $assignment->id), 'alternateRoutes' => array('assignmentBrowseStudentShow', 'assignmentShowAllStudentsMonster'));
}

if ($assignment->maySeeResultsPage(Auth::user())) {
	$tabs[] = array('title' => "Results", 'route' => array('showAssignmentResults', $assignment->id), 'alternateRoutes' => array('autoMarkAssignment'));
}

// if there is only the overview, hide the navigation.
if(!$forMainMenu && (count($tabs) <= 1)) {
	$tabs = array();
}

?>

@include('templates.tab_navigation_insert', array('tabs' => $tabs, 'currentRoute' => $currentRoute, 'forMainMenu' => $forMainMenu ))
