

<?php 

$makeRoleChangingLink = function ($text, $role, $suspended  = false, $confirmation = null) use($subject, $user) {

	$suspendedValue = $suspended;
	if ($suspendedValue === true) $suspendedValue = 1;
	if ($suspendedValue === false) $suspendedValue = 0;
	
	$attributes = array();
	$attributes['data-url'] = route('changeSubjectMembershipAjax', array('id' => $subject->id, 'userid' => $user->id, 'role' => $role, 'suspended' => $suspendedValue ));
	$attributes['data-_token'] = csrf_token();
	$attributes['class'] = 'ajaxPost';
	if (!is_null($confirmation)) {
		$attributes['data-confirmationdialog'] = $confirmation;
	}
	return link_to('#', $text, $attributes );
}

?>

<div class="dropdown">
	<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
		{{{ $subject->isMember($user) ? $subject->getMembership($user)->presenter()->role_complete : "---" }}} <span class="caret"></span>
	</button>

	<ul class="dropdown-menu" role="menu">
		@if($subject->isLecturer($user->resource))
			@if($user->id == Auth::user()->id)
				@if(Auth::user()->isAdmin())
					<li>{{ $makeRoleChangingLink('Remove yourself as lecturer', -1, false, 'Are you sure you want to remove yourself as lecturer?') }}</li>
				@else
					<li>{{ $makeRoleChangingLink('Remove yourself as lecturer', -1, false, 'Are you sure you want to remove yourself as lecturer? The effect will be immediate and you cannot undo it yourself.') }}</li>
				@endif
			@else
				<li>{{ $makeRoleChangingLink('Remove lecturer', -1) }}</li>
			@endif
		@endif
		
		@if($subject->isStudent($user->resource))
			@if($subject->isSuspended($user->resource))
				<li>{{ $makeRoleChangingLink('Resume student', SubjectMemberRoles::student) }}</li>
			@else
				<li>{{ $makeRoleChangingLink('Suspend student', SubjectMemberRoles::student, true) }}</li>
			@endif
			@if(!$subject->studentHasNonEmptyData($user->resource))
				<li>{{ $makeRoleChangingLink('Remove student', -1) }}</li>
			@endif			
		@endif
		
		@if($subject->isTutor($user->resource))
			@if($subject->isSuspended($user->resource))
				<li>{{ $makeRoleChangingLink('Resume tutor', SubjectMemberRoles::tutor) }}</li>
			@else
				<li>{{ $makeRoleChangingLink('Suspend tutor', SubjectMemberRoles::tutor, true) }}</li>
			@endif
			@if(!$subject->tutorHasNonEmptyData($user->resource))
				<li>{{ $makeRoleChangingLink('Remove tutor', -1) }}</li>
			@endif			
		@endif
		
		@if($subject->isQuestionObserver($user->resource))
			<li>{{ $makeRoleChangingLink('Remove observer', -1) }}</li>
		@endif
		
		@if($subject->isFullObserver($user->resource))
			<li>{{ $makeRoleChangingLink('Remove observer', -1) }}</li>
		@endif
		
		@if(!$subject->isMember($user->resource))
			<li>{{ $makeRoleChangingLink('Add as student', SubjectMemberRoles::student) }}</li>
			<li>{{ $makeRoleChangingLink('Add as tutor', SubjectMemberRoles::tutor) }}</li>
			<li>{{ $makeRoleChangingLink('Grant read access to questions and solutions of this class', SubjectMemberRoles::questionobserver) }}</li>
			<li>{{ $makeRoleChangingLink('Grant full read access to this class', SubjectMemberRoles::fullobserver) }}</li>
			<li>{{ $makeRoleChangingLink('Add as lecturer', SubjectMemberRoles::lecturer) }}</li>
		@endif
	</ul>
</div>