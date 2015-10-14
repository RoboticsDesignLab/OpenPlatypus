@extends('assignment.assignment_template') 

@section('title') 
Student groups
@stop 


@section('content')
@parent



<div class="row">
	<div class="col-md-12">

<?php

if ($editable) {
	$buttonGenerator = function ($user) use($assignment) {
		$result = "";
		$result .= '<div class="ajaxFormWrapper updatable" data-resourceid="user_button_'. $user->id .'">';
		$result .= View::make('groups.groups_editGroupsButton_insert')->withAssignment($assignment)->withUser($user)->render();
		$result .= '</div>';
		return $result;
	};
} else {
	$buttonGenerator = null;
}


$groupSummaryGenerator = function ($user) use($assignment) {
	$result = '';
	$result .= '<div class="updatable" data-resourceid="user_group_'. $user->id .'">';
	$group = StudentGroup::findGroup($assignment->resource, $user->resource);
	$result .= View::make('groups.groups_editGroupsSummary_insert')->withGroup($group)->render();
	$result .= '</div>';
	return $result;
};

$appendColumns = array(array('title' => 'Group', 'generator' => $groupSummaryGenerator));

?>

@include('user.user_list_insert', array('users' => $users, 'buttonGenerator' => $buttonGenerator, 'appendColumns' => $appendColumns))

</div>
</div>

@stop
