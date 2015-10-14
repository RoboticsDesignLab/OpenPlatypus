<?php

Breadcrumbs::register('home', function($breadcrumbs) {
    $breadcrumbs->push('Home', route('home'));
});

Breadcrumbs::register('newSubject', function($breadcrumbs) {
    $breadcrumbs->parent('home');
	$breadcrumbs->push('New class', route('newSubject'));
});

Breadcrumbs::register('showSubject', function($breadcrumbs, $class_id) {
	$subject = Subject::findOrFail($class_id);
	
	$breadcrumbs->parent('home');
	$breadcrumbs->push($subject->presenter()->code, route('showSubject', $subject->id));
});

Breadcrumbs::register('editSubject', function($breadcrumbs, $class_id) {
	$subject = Subject::findOrFail($class_id);
	
	$breadcrumbs->parent('showSubject', $subject->id);
	$breadcrumbs->push('Edit', route('editSubject', $subject->id));
});

Breadcrumbs::register('deleteSubject', function($breadcrumbs, $class_id) {
	$subject = Subject::findOrFail($class_id);

	$breadcrumbs->parent('showSubject', $subject->id);
	$breadcrumbs->push('Delete', route('deleteSubject', $subject->id));
});
	

foreach(array('manageExistingSubjectMembers', 'manageSubjectMembers', 'createUserAndAddToSubject', 'addExistingUserToSubjectConfirm', 'massUploadStudentsToSubject') as $route)
Breadcrumbs::register($route, function($breadcrumbs, $class_id) use ($route) {
	$subject = Subject::findOrFail($class_id);

	$breadcrumbs->parent('showSubject', $subject->id);
	$breadcrumbs->push("Members", route($route, $class_id));
});
	
	
Breadcrumbs::register('newAssignment', function($breadcrumbs, $class_id) {

	$breadcrumbs->parent('showSubject', $class_id);
	$breadcrumbs->push('New assignment', route('newAssignment', $class_id));
});

Breadcrumbs::register('showAssignment', function($breadcrumbs, $assignment_id) {
	$assignment = Assignment::findOrFail($assignment_id);
	$subject = $assignment->subject;
	
	$breadcrumbs->parent('showSubject', $assignment->subject->id);
	$breadcrumbs->push($assignment->presenter()->title, route('showAssignment', $assignment_id));
});

Breadcrumbs::register('showAssignmentControlPanel', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);
	
	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('Control panel', route('showAssignmentControlPanel', $assignment_id));
});

Breadcrumbs::register('editAssignment', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);
	
	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('Edit', route('editAssignment', $assignment_id));
});

Breadcrumbs::register('editAssignmentTutors', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);

	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('Manage tutors', route('editAssignmentTutors', $assignment_id));
});
	
Breadcrumbs::register('editStudentGroups', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);

	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('Student groups', route('editStudentGroups', $assignment_id));
});

Breadcrumbs::register('deleteAssignment', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);

	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('Delete', route('editAssignment', $assignment_id));
});

Breadcrumbs::register('manageReviews', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);
	
	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('Manage reviews', route('manageReviews', $assignment_id));
});
	
Breadcrumbs::register('manageReviewsUser', function ($breadcrumbs, $assignment_id, $user_id) {
	Assignment::findOrFail($assignment_id);
	$user = User::findOrFail($user_id);

	$breadcrumbs->parent('manageReviews', $assignment_id);
	$breadcrumbs->push($user->presenter()->name, route('manageReviewsUser', array('assignment_id' => $assignment_id, 'user_id' => $user_id) ));
});
	
Breadcrumbs::register('showReviewTasks', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);

	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('Marking', route('showReviewTasks', $assignment_id));
});
	

Breadcrumbs::register('answerAssignment', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);
	
	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('Answer', route('answerAssignment', $assignment_id));
});
	
Breadcrumbs::register('showAssignmentResults', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);

	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('Results', route('showAssignmentResults', $assignment_id));
});
	

Breadcrumbs::register('showGroupForStudent', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);

	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('My group', route('showGroupForStudent', $assignment_id));
});
	
	
Breadcrumbs::register('assignmentBrowseStudentList', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);
	
	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('Browse students', route('assignmentBrowseStudentList', $assignment_id));
});
	
Breadcrumbs::register('assignmentBrowseStudentShow', function ($breadcrumbs, $assignment_id, $user_id) {
	Assignment::findOrFail($assignment_id);
	$user = User::findOrFail($user_id);

	$breadcrumbs->parent('assignmentBrowseStudentList', $assignment_id);
	$breadcrumbs->push($user->presenter()->name, route('assignmentBrowseStudentShow', $assignment_id));
});
	
Breadcrumbs::register('assignmentShowAllStudentsMonster', function ($breadcrumbs, $assignment_id) {
	Assignment::findOrFail($assignment_id);
	
	$breadcrumbs->parent('showAssignment', $assignment_id);
	$breadcrumbs->push('Show all student data', route('assignmentShowAllStudentsMonster', $assignment_id));
});
	
	

Breadcrumbs::register('listUsersForUserManager', function ($breadcrumbs) {
	$breadcrumbs->parent('home');
	$breadcrumbs->push('Users', route('listUsersForUserManager'));
});
	
Breadcrumbs::register('newUser', function ($breadcrumbs) {
	$breadcrumbs->parent('listUsersForUserManager');
	$breadcrumbs->push('New user', route('newUser'));
});

Breadcrumbs::register('editUser', function ($breadcrumbs, $user_id) {
	$user = User::findOrFail($user_id);

	$breadcrumbs->parent('listUsersForUserManager');
	$breadcrumbs->push($user->presenter()->name, route('editUser', $user_id));
});
	