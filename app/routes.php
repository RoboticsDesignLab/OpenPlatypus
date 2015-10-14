<?php

use Platypus\Helpers\PlatypusBool;
/*
 * |-------------------------------------------------------------------------- | Application Routes |-------------------------------------------------------------------------- | | Here is where you can register all of the routes for an application. | It's a breeze. Simply tell Laravel the URIs it should respond to | and give it the Closure to execute when that URI is requested. |
 */

// Event::listen('illuminate.query', function($query)
// {
// 	var_dump($query);
// });

/**
 * PHPUnit Test Routes
 *
 * (A controller action must be "routable"
 * to be used in a PHPUnit test using action() or call() (see Laravel unit testing)
 */

$PHPUnitTestRouteCount = 0;
$PHPUnitTestRoute = function () use (&$PHPUnitTestRouteCount) {
    return "phpunit" . ++$PHPUnitTestRouteCount;
};

Route::any($PHPUnitTestRoute(), 'UserFileController@receiveFile');
Route::any($PHPUnitTestRoute(), 'UserFileController@createFile');

/**
 * End PHPUnit Test Routes
 */


// silence some common requests that should trigger a 404 but are no error.
Route::get('robots.txt', array('uses' => 'HomeController@quiet404' ) );
Route::get('favicon.ico', array('uses' => 'HomeController@quiet404' ) );
Route::get('apple-touch-icon{any}', array('uses' => 'HomeController@quiet404' ) );
Route::get('browserconfig.xml', array('uses' => 'HomeController@quiet404' ) );


Route::group(array('before' => 'databaseok'), function() {
	Route::get('error/browser', array('as' => 'errorBrowser', 'uses' => 'HomeController@showBrowserErrorPage' ) );
	Route::get('error/{code}', array('as' => 'error', 'before' => 'numeric:code', 'uses' => 'HomeController@showErrorPage' ) );
});


Route::group(array('before' => 'modernbrowser|databaseok'), function() {

	Route::get('/', array('as' => 'home', 'uses' => 'HomeController@home' ) );
	

	Route::get('login', array('as' => 'login', 'uses' => 'LoginController@login' ) );
	Route::get('login/{authentication_domain}', array('as' => 'loginDomain', 'before' => 'numeric:authentication_domain' ,'uses' => 'LoginController@runLoginMethod' ) );
	Route::get('logout', array('as' => 'logout', 'uses' => 'LoginController@logout' ) );
	
	Route::get('login/domain/local', array('uses' => 'LocalAuthenticatorController@login' ) );
	Route::post('login/domain/local', array('before' => 'csrf', 'uses' => 'LocalAuthenticatorController@loginPost' ) );

	
	
	Route::group(array('before' => 'auth'), function() {

		Route::get('help/{topic}', array('as' => 'showHelpPage', 'before' => '', 'uses' => 'HelpController@showTopic' ) );
		
		Route::get('admin/heartbeat', array('as' => 'showHeartBeat', 'before' => '', 'uses' => 'HomeController@showHeartBeat' ) );
		
		Route::get('admin/users', array('as' => 'listUsersForUserManager', 'before' => 'mayeditusers', 'uses' => 'UserController@showAllUsersForUserManager' ) );
		Route::get('admin/users/new', array('as' => 'newUser', 'before' => 'mayeditusers', 'uses' => 'UserController@createUser' ) );
		Route::post('admin/users/new', array('as' => 'newUser', 'before' => 'csrf|mayeditusers', 'uses' => 'UserController@createUserPost' ) );
		Route::get('admin/users/edit/{user_id}', array('as' => 'editUser', 'before' => 'mayeditusers', 'uses' => 'UserController@editUser' ) );
		Route::post('admin/users/edit/{user_id}', array('as' => 'editUser', 'before' => 'csrf|mayeditusers', 'uses' => 'UserController@editUserPost' ) );
		
		
		Route::get('class/new', array('as' => 'newSubject', 'uses' => 'SubjectController@create' ) );
		Route::post('class/new', array('as' => 'newSubject', 'before' => 'csrf', 'uses' => 'SubjectController@createPost' ) );
		Route::get('class/all', array('as' => 'listAllSubjects', 'before' => '', 'uses' => 'SubjectController@listAll' ) );
		
		Route::get('class/{id}', array('as' => 'showSubject', 'before' => 'numericid', 'uses' => 'SubjectController@show' ) );
		Route::get('class/{id}/edit', array('as' => 'editSubject', 'before' => 'numericid', 'uses' => 'SubjectController@edit' ) );
		Route::post('class/{id}/edit', array('as' => 'editSubject', 'before' => 'csrf|numericid', 'uses' => 'SubjectController@editPost' ) );
		Route::get('class/{id}/delete', array('as' => 'deleteSubject', 'before' => 'numericid', 'uses' => 'SubjectController@delete' ) );
		Route::post('class/{id}/delete', array('as' => 'deleteSubject', 'before' => 'csrf|numericid', 'uses' => 'SubjectController@deletePost' ) );
		
		Route::get('class/{id}/managemembers', array('as' => 'manageExistingSubjectMembers', 'before' => 'numericid', 'uses' => 'SubjectController@manageExistingMembers' ) );
		Route::get('class/{id}/managemembers/list', array('as' => 'manageSubjectMembers', 'before' => 'numericid', 'uses' => 'SubjectController@manageMembers' ) );
		Route::post('class/{id}/managemembers/ajax/{userid}/{role}/{suspended}', array('as' => 'changeSubjectMembershipAjax', 'before' => 'csrf|numericid|numeric:userid|numeric:role|numeric:suspended', 'uses' => 'SubjectController@changeMembershipAjax' ) );
				
		Route::get('class/{id}/managemembers/new', array('as' => 'createUserAndAddToSubject', 'before' => 'numericid', 'uses' => 'SubjectController@createUserAndAddToSubject' ) );
		Route::post('class/{id}/managemembers/new', array('as' => 'createUserAndAddToSubject', 'before' => 'csrf|numericid', 'uses' => 'SubjectController@createUserAndAddToSubjectPost' ) );
		Route::get('class/{id}/managemembers/add/{userid}/{role}', array('as' => 'addExistingUserToSubjectConfirm', 'before' => 'numericid|numeric:userid|numeric:role', 'uses' => 'SubjectController@addExistingUserToSubjectConfirm' ) );
		Route::post('class/{id}/managemembers/add/{userid}/{role}', array('as' => 'addExistingUserToSubjectConfirm', 'before' => 'csrf|numericid|numeric:userid|numeric:role', 'uses' => 'SubjectController@addExistingUserToSubjectConfirmPost' ) );
		
		Route::get('class/{id}/managemembers/upload', array('as' => 'massUploadStudentsToSubject', 'before' => 'numericid', 'uses' => 'SubjectController@massUploadStudents' ) );
		Route::post('class/{id}/managemembers/upload', array('as' => 'massUploadStudentsToSubject', 'before' => 'csrf|numericid', 'uses' => 'SubjectController@massUploadStudentsPost' ) );
		
		
		
		// AssignmentController
		Route::get('assignment/{assignment_id}', array('as' => 'showAssignment', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentController@show' ) );
		Route::get('assignment/{assignment_id}/results', array('as' => 'showAssignmentResults', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentController@showResults' ) );
		Route::get('assignment/{assignment_id}/automark', array('as' => 'autoMarkAssignment', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentController@autoMarkShow' ) );
		Route::post('assignment/{assignment_id}/automark', array('as' => 'autoMarkAssignment', 'before' => 'csrf|numeric:assignment_id', 'uses' => 'AssignmentController@autoMarkPost' ) );
		Route::post('assignment/{assignment_id}/automark/set', array('as' => 'autoMarkSetAssignment', 'before' => 'csrf|numeric:assignment_id', 'uses' => 'AssignmentController@autoMarkSetRealPost' ) );
		Route::get('assignment/{assignment_id}/ajax/navigationbar/{route_name}/{for_main_menu}', array('as' => 'updateAssignmentNavigationBarAjax', 'before' => 'numeric:assignment_id|alpha_num:route_name', 'uses' => 'AssignmentController@updateNavigationBarAjax' ) );
		Route::get('class/{class_id}/assignment/new', array('as' => 'newAssignment', 'before' => 'maycreateassignment:class_id', 'uses' => 'AssignmentController@create' ) );
		Route::post('class/{class_id}/assignment/new', array('as' => 'newAssignment', 'before' => 'csrf|maycreateassignment:class_id', 'uses' => 'AssignmentController@createPost' ) );
		Route::get('assignment/{assignment_id}/edit/tutors', array('as' => 'editAssignmentTutors', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentController@editTutors' ) );
		Route::post('assignment/{assignment_id}/edit/tutors/ajax/{userid}/{question_id}/{status}', array('as' => 'editAssignmentTutorsAjax', 'before' => 'csrf|numeric:assignment_id|numeric:user_id|numeric:question_id|numeric:status', 'uses' => 'AssignmentController@editTutorsAjax' ) );
		Route::post('assignment/{assignment_id}/results/ajax/set/user/{user_id}', array('as' => 'setFinalAssignmentMarkAjax', 'before' => 'csrf|numeric:assignment_id|numeric:user_id', 'uses' => 'AssignmentController@setFinalAssignmentMarkAjax' ) );
		Route::post('assignment/{assignment_id}/results/ajax/set/question/{question_id}/user/{user_id}', array('as' => 'setFinalAssignmentQuestionMarkAjax', 'before' => 'csrf|numeric:assignment_id|numeric:user_id|numerid:question_id', 'uses' => 'AssignmentController@setFinalQuestionMarkAjax' ) );
		
		
		
		// AssignmentControlPanelController
		Route::get('assignment/{assignment_id}/controlpanel', array('as' => 'showAssignmentControlPanel', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentControlPanelController@showControlPanel' ) );
		Route::post('assignment/{assignment_id}/ajax/startmarking', array('as' => 'startAssignmentMarking', 'before' => 'csrf|numeric:assignment_id', 'uses' => 'AssignmentControlPanelController@startMarkingPost' ) );
		Route::post('assignment/{assignment_id}/ajax/cancelmarking', array('as' => 'cancelAssignmentMarking', 'before' => 'csrf|numeric:assignment_id', 'uses' => 'AssignmentControlPanelController@cancelMarkingPost' ) );
		Route::post('assignment/{assignment_id}/ajax/assigntolecturer', array('as' => 'ensureEachAnswerHasLecturerReviewTask', 'before' => 'csrf|numeric:assignment_id', 'uses' => 'AssignmentControlPanelController@ensureEachAnswerHasLecturerReviewTaskPost' ) );
		Route::post('assignment/{assignment_id}/ajax/assigntotutors', array('as' => 'ensureEachAnswerHasTutorReviewTaskPost', 'before' => 'csrf|numeric:assignment_id', 'uses' => 'AssignmentControlPanelController@ensureEachAnswerHasTutorReviewTaskPost' ) );
		Route::post('assignment/{assignment_id}/ajax/deletependingtutorreviews', array('as' => 'deletePendingTurorReviewTasksPost', 'before' => 'csrf|numeric:assignment_id', 'uses' => 'AssignmentControlPanelController@deletePendingTurorReviewTasksPost' ) );
		Route::get('assignment/{assignment_id}/ajax/eventlog', array('as' => 'updateAssignmentLogPanel', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentControlPanelController@showLogPanel' ) );
		Route::post('assignment/{assignment_id}/delete', array('as' => 'deleteAssignmentPost', 'before' => 'csrf|numeric:assignment_id', 'uses' => 'AssignmentControlPanelController@deleteAssignmentPost' ) );
		Route::get('assignment/{assignment_id}/results/csv', array('as' => 'getAssignmentResultsAsCsv', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentControlPanelController@downloadCsvFile' ) );		
		
		
		// AssignmentEditorController
		Route::get('assignment/{assignment_id}/edit', array('as' => 'editAssignment', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentEditorController@edit' ) );
		Route::get('assignment/{assignment_id}/edit/ajax/show', array('as' => 'editAssignmentAjaxShow', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentEditorController@editShowAjax' ) );
		Route::get('assignment/{assignment_id}/edit/ajax/edit', array('as' => 'editAssignmentAjax', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentEditorController@editAjax' ) );
		Route::post('assignment/{assignment_id}/edit/ajax/edit', array('as' => 'editAssignmentAjax', 'before' => 'csrf|numeric:assignment_id', 'uses' => 'AssignmentEditorController@editAjaxPost' ) );
		Route::get('assignment/{assignment_id}/edit/ajax/infopanel', array('as' => 'editAssignmentInfopanelAjax', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentEditorController@showInfopanel' ) );
		Route::post('assignment/{assignment_id}/edit/ajax/addquestion/{type}/master/{master_question_id}', array('as' => 'editAssignmentAddQuestionAjax', 'before' => 'csrf|numeric:assignment_id|numeric:type|numeric:master_question_id', 'uses' => 'AssignmentEditorController@addQuestionAjax' ) );
		Route::get('assignment/{assignment_id}/edit/ajax/question/{question_id}/get', array('as' => 'editAssignmentGetQuestionAjax', 'before' => 'numeric:assignment_id|numeric:question_id', 'uses' => 'AssignmentEditorController@getQuestionAjax' ) );
		Route::post('assignment/{assignment_id}/edit/ajax/question/{question_id}/delete', array('as' => 'editAssignmentDeleteQuestionAjax', 'before' => 'csrf|numeric:assignment_id|numeric:question_id', 'uses' => 'AssignmentEditorController@deleteQuestionAjax' ) );
		Route::post('assignment/{assignment_id}/edit/ajax/question/{question_id}/deleteAnswers', array('as' => 'editAssignmentDeleteQuestionAnswersAjax', 'before' => 'csrf|numeric:assignment_id|numeric:question_id', 'uses' => 'AssignmentEditorController@deleteAnswersAjax' ) );
		Route::post('assignment/{assignment_id}/edit/ajax/question/{question_id}/move/{direction}', array('as' => 'editAssignmentMoveQuestionAjax', 'before' => 'csrf|numeric:assignment_id|numeric:question_id|numeric:direction', 'uses' => 'AssignmentEditorController@moveQuestionAjax' ) );
		Route::post('assignment/{assignment_id}/edit/ajax/question/{question_id}/mark_percentage', array('as' => 'editAssignmentQuestionMarkPercentageAjax', 'before' => 'csrf|numeric:assignment_id|numeric:question_id', 'uses' => 'AssignmentEditorController@setQuestionMarkPercentage' ) );
		Route::post('assignment/{assignment_id}/edit/ajax/question/{question_id}/solutioneditor', array('as' => 'editAssignmentQuestionSolutionEditorAjax', 'before' => 'csrf|numeric:assignment_id|numeric:question_id', 'uses' => 'AssignmentEditorController@setSolutionEditor' ) );
		
		// AssignmentAnswerController
		Route::get('assignment/{assignment_id}/answer', array('as' => 'answerAssignment', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentAnswerController@show' ) );
		Route::post('answer/{answer_id}/ajax/review', array('as' => 'reviewAnswerPost', 'before' => 'csrf|numeric:answer_id', 'uses' => 'AssignmentAnswerController@reviewAnswerPost' ) );
		Route::post('answer/{answer_id}/ajax/submit', array('as' => 'submitAnswerPost', 'before' => 'csrf|numeric:answer_id', 'uses' => 'AssignmentAnswerController@submitAnswerPost' ) );
		Route::post('answer/{answer_id}/ajax/retract', array('as' => 'retractAnswerPost', 'before' => 'csrf|numeric:answer_id', 'uses' => 'AssignmentAnswerController@retractAnswerPost' ) );
		
		// AssignmentBrowseStudentsController
		Route::get('assignment/{assignment_id}/browse/list', array('as' => 'assignmentBrowseStudentList', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentBrowseStudentsController@browseStudentList' ) );
		Route::post('assignment/{assignment_id}/browse/find', array('as' => 'assignmentJumpToReview', 'before' => 'csrf|numeric:assignment_id', 'uses' => 'AssignmentBrowseStudentsController@jumpToReview' ) );
		Route::get('assignment/{assignment_id}/browse/show/all', array('as' => 'assignmentShowAllStudentsMonster', 'before' => 'numeric:assignment_id', 'uses' => 'AssignmentBrowseStudentsController@showAllStudentsMonster' ) );
		Route::get('assignment/{assignment_id}/browse/{user_id}', array('as' => 'assignmentBrowseStudentShow', 'before' => 'numeric:assignment_id|numeric:user_id', 'uses' => 'AssignmentBrowseStudentsController@browseStudentShow' ) );
		
		// ZipArchiveController
		Route::get('assignment/{assignment_id}/results/archive', array('as' => 'assignmentDownloadZip', 'before' => 'numeric:assignment_id', 'uses' => 'ZipArchiveController@downloadAssignmentArchive' ) );
		
		// ReviewController
		Route::get('assignment/{assignment_id}/reviews/manage', array('as' => 'manageReviews', 'before' => 'numeric:assignment_id', 'uses' => 'ReviewController@showManageReviews' ) );
		Route::get('assignment/{assignment_id}/reviews/user/{user_id}', array('as' => 'manageReviewsUser', 'before' => 'numeric:assignment_id|numeric:user_id', 'uses' => 'ReviewController@showManageReviewsUser' ) );
		Route::post('assignment/{assignment_id}/reviews/user/{user_id}/edit', array('as' => 'manageReviewsUserPost', 'before' => 'csrf|numeric:assignment_id|numeric:user_id', 'uses' => 'ReviewController@manageReviewsUserPost' ) );
		Route::get('assignment/{assignment_id}/reviews', array('as' => 'showReviewTasks', 'before' => 'numeric:assignment_id', 'uses' => 'ReviewController@showReviewTasks' ) );
		Route::post('review/ajax/{review_id}/setmark', array('as' => 'setReviewMarkAjax', 'before' => 'csrf|numeric:review_id', 'uses' => 'ReviewController@setReviewMarkAjax' ) );
		Route::post('review/ajax/{review_id}/setflag', array('as' => 'setReviewFlagAjax', 'before' => 'csrf|numeric:review_id', 'uses' => 'ReviewController@setReviewFlagAjax' ) );
		Route::post('review/ajax/{review_id}/submit', array('as' => 'submitReviewAjax', 'before' => 'csrf|numeric:review_id', 'uses' => 'ReviewController@submitReview' ) );
		Route::post('review/ajax/{review_id}/retract', array('as' => 'retractReviewAjax', 'before' => 'csrf|numeric:review_id', 'uses' => 'ReviewController@retractReview' ) );
		
		Route::post('review/ajax/question/{question_id}/user/{user_id}/submit/quick', array('as' => 'submitErgonomicReviewAjax', 'before' => 'csrf|numeric:question_id|numeric:user_id', 'uses' => 'ReviewController@submitErgonomicReviewAjax' ) );
		Route::post('marking/ajax/question/{question_id}/user/{user_id}/set', array('as' => 'setFinalQuestionMarkAjax', 'before' => 'csrf|numeric:question_id|numeric:user_id', 'uses' => 'ReviewController@setFinalQuestionMarkAjax' ) );
		Route::post('review/ajax/question/{question_id}/user/{user_id}/addnew', array('as' => 'addAdHocReviewAjax', 'before' => 'csrf|numeric:question_id|numeric:user_id', 'uses' => 'ReviewController@addAdHocReviewAjax' ) );
				
		Route::post('review/ajax/{review_id}/rate', array('as' => 'rateReviewAjax', 'before' => 'csrf|numeric:review_id', 'uses' => 'ReviewController@rateReviewAjax' ) );
		
		// StudentGroupController
		Route::get('assignment/{assignment_id}/edit/groups', array('as' => 'editStudentGroups', 'before' => 'numeric:assignment_id', 'uses' => 'StudentGroupController@editStudentGroups' ) );
		Route::post('assignment/{assignment_id}/edit/groups/ajax/addto/{user_id}', array('as' => 'addUserToOtherUsersStudentGroup', 'before' => 'csrf|numeric:assignment_id|numeric:user_id', 'uses' => 'StudentGroupController@addUserToOtherUsersStudentGroup' ) );
		Route::post('assignment/{assignment_id}/edit/groups/ajax/remove/{user_id}', array('as' => 'removeUserFromStudentGroup', 'before' => 'csrf|numeric:assignment_id|numeric:user_id', 'uses' => 'StudentGroupController@removeUserFromStudentGroup' ) );
		Route::post('assignment/{assignment_id}/edit/groups/ajax/dissolve/{user_id}', array('as' => 'dissolveStudentGroupOfUser', 'before' => 'csrf|numeric:assignment_id|numeric:user_id', 'uses' => 'StudentGroupController@dissolveStudentGroupOfUser' ) );
		
		// StudentGroupSuggestionController
		Route::get('assignment/{assignment_id}/groups', array('as' => 'showGroupForStudent', 'before' => 'numeric:assignment_id', 'uses' => 'StudentGroupSuggestionController@showForStudent' ) );
		Route::post('assignment/{assignment_id}/groups/suggest', array('as' => 'suggestGroupAsStudent', 'before' => 'csrf|numeric:assignment_id', 'uses' => 'StudentGroupSuggestionController@suggestGroupPost' ) );
		Route::post('assignment/{assignment_id}/groups/accept/{suggestion_id}', array('as' => 'acceptGroupSuggestion', 'before' => 'csrf|numeric:assignment_id|numeric:suggestion_id', 'uses' => 'StudentGroupSuggestionController@acceptGroupSuggestionPost' ) );
		Route::post('assignment/{assignment_id}/groups/reject/{suggestion_id}', array('as' => 'rejectGroupSuggestion', 'before' => 'csrf|numeric:assignment_id|numeric:suggestion_id', 'uses' => 'StudentGroupSuggestionController@rejectGroupSuggestionPost' ) );
		
		
		// TextBlockController
		Route::get('textblock/ajax/{textblock_id}/{role}/showText', array('as' => 'showTextBlockTextAjax', 'before' => 'numeric:textblock_id|numeric:role', 'uses' => 'TextBlockController@showTextAjax' ) );
		Route::get('textblock/ajax/{textblock_id}/{role}/showText/{showeditlink}', array('as' => 'showTextBlockTextAjax2', 'before' => 'numeric:textblock_id|numeric:role|numeric:showeditlink', 'uses' => 'TextBlockController@showTextAjax' ) );
		Route::get('textblock/ajax/{textblock_id}/{role}/editText', array('as' => 'editTextBlockTextAjax', 'before' => 'numeric:textblock_id|numeric:role', 'uses' => 'TextBlockController@editTextAjax' ) );
		Route::post('textblock/ajax/{textblock_id}/{role}/editText', array('as' => 'editTextBlockTextAjax', 'before' => 'csrf|numeric:textblock_id|numeric:role', 'uses' => 'TextBlockController@editTextAjaxPost' ) );
		Route::post('textblock/ajax/{textblock_id}/{role}/save/inline', array('as' => 'saveTextBlockInlineAjaxPost', 'before' => 'csrf|numeric:textblock_id|numeric:role', 'uses' => 'TextBlockController@saveTextInlineAjaxPost' ) );
		Route::post('textblock/ajax/{textblock_id}/{role}/autosave/save', array('as' => 'autosaveTextBlockTextAjax', 'before' => 'csrf|numeric:textblock_id|numeric:role', 'uses' => 'TextBlockController@autosaveTextAjaxPost' ) );
		Route::post('textblock/ajax/{textblock_id}/{role}/autosave/discard', array('as' => 'editTextBlockTextDiscardAutosaveAjax', 'before' => 'csrf|numeric:textblock_id|numeric:role', 'uses' => 'TextBlockController@editTextDiscardAutosaveAjax' ) );
		Route::post('textblock/ajax/{textblock_id}/{role}/autosave/restore', array('as' => 'editTextBlockTextRestoreAutosaveAjax', 'before' => 'csrf|numeric:textblock_id|numeric:role', 'uses' => 'TextBlockController@editTextRestoreAutosaveAjax' ) );
		
		// TextBlockAttachmentsController
		Route::get('attachment/{textblock_id}/{role}/{attachment_id}/download/{file_name}', array('as' => 'downloadTextBlockAttachment', 'before' => 'numeric:textblock_id|numeric:role|numeric:attachment_id', 'uses' => 'TextBlockAttachmentsController@downloadAttachment' ) );
		Route::get('attachment/{textblock_id}/{role}/{attachment_id}/view/{file_name}', array('as' => 'viewTextBlockAttachment', 'before' => 'numeric:textblock_id|numeric:role|numeric:attachment_id', 'uses' => 'TextBlockAttachmentsController@viewAttachment' ) );
		Route::post('attachment/{textblock_id}/{role}/{attachment_id}/delete', array('as' => 'deleteTextBlockAttachment', 'before' => 'csrf|numeric:textblock_id|numeric:role|numeric:attachment_id', 'uses' => 'TextBlockAttachmentsController@deleteAttachmentPostAjax' ) );
		Route::post('attachment/{textblock_id}/{role}/{attachment_id}/move/{direction}', array('as' => 'moveTextBlockAttachment', 'before' => 'csrf|numeric:textblock_id|numeric:role|numeric:attachment_id|numeric:direction', 'uses' => 'TextBlockAttachmentsController@moveAttachmentPostAjax' ) );
		Route::post('textblock/ajax/{textblock_id}/{role}/attachment/upload', array('as' => 'uploadTextBlockAttachmentAjaxPost', 'before' => 'csrf|numeric:textblock_id|numeric:role', 'uses' => 'TextBlockAttachmentsController@uploadAttachmentAjaxPost' ) );
		Route::post('textblock/ajax/{textblock_id}/{role}/attachment/upload/ckeditor', array('as' => 'uploadTextBlockAttachmentCkEditorAjaxPost', 'before' => 'csrf|numeric:textblock_id|numeric:role', 'uses' => 'TextBlockAttachmentsController@uploadAttachmentCkEditorAjaxPost' ) );
		Route::get('attachment/{textblock_id}/{role}/ajax/attachment/ckeditorselect', array('as' => 'showCkEditorAttachmentSelection', 'before' => 'numeric:textblock_id|numeric:role', 'uses' => 'TextBlockAttachmentsController@showCkEditorAttachmentSelection' ) );
		
		
		
	});


}); // end of route group 'databaseok'


	
Route::get('setup', array('as' => 'setup', 'uses' => 'SetupController@setup' ) );
Route::post('setup', array('as' => 'setup', 'uses' => 'SetupController@setupPost', 'before' => 'csrf' ) );


Route::get('debug/switch/return', array('as' => 'debugSwitchReturn', 'before' => 'databaseok|auth', 'uses' => 'DebugController@returnUser' ) );

Route::group(array('before' => 'databaseok|auth|isDebugger'), function() {
	
	Route::get('debug/switch', array('as' => 'debugSwitchUser', 'uses' => 'DebugController@switchUser' ) );
	
	Route::post('debug/switch/{id}', array('as' => 'debugSwitchUserPost', 'before' => 'csrf|numericid', 'uses' => 'DebugController@switchUserPost' ) );
	Route::get('debug/info', array('as' => 'phpInfo', 'uses' => 'DebugController@phpInfo', 'before' => 'databaseok|auth' ) );
	
	//Route::get('debug/deleteAllStudents', array('as' => 'debugDeleteAllStudents', 'uses' => 'DebugController@deleteAllStudents' ) );
	//Route::get('debug/assignment/{assignment_id}/answer80', array('as' => 'debugAnswerEightyPercent', 'before' => 'numeric:assignment_id', 'uses' => 'DebugController@answerEightyPercent' ) );
		
});

