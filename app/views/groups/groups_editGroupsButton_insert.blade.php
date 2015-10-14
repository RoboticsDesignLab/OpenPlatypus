

<?php 
$group = StudentGroup::findGroup($assignment->resource, $user->resource);
?>


<div class="dropdown inline">
	<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
		<span class="glyphicon glyphicon glyphicon-plus"></span> <span class="caret"></span>
	</button>	
	<div class="dropdown-menu">

		<div class="row add-bottom-margin">
			<div class="col-md-12">
				Enter the student ID or email address of another student. This student will then be added to the group.
			</div>
		</div>
	
		<div class="row">
			<div class="col-md-12">	
	
				{{ Form::open(array('route' => array('addUserToOtherUsersStudentGroup', 'assignment_id' => $assignment->id, 'user_id' => $user->id) ) ) }}


    			<label class="control-label" for="other_user">ID or email</label>
    			<input class="form-control" type="text" value="" name="other_user"></input>
				
				<input class="btn btn-primary" type="submit" value="Add now"></input>
				{{ Form::close() }}
				
			</div>
		</div>

</div>



@if(!is_null($group))
<div class="dropdown inline">
	<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
		<span class="glyphicon glyphicon glyphicon-trash"></span> <span class="caret"></span>
	</button>
	<ul class="dropdown-menu" role="menu">
		
		<li><a href="#"	class="ajaxPost" 
			data-url="{{{ route('removeUserFromStudentGroup', array('assignment_id' => $assignment->id, 'user_id' => $user->id )) }}}" 
			data-_token="{{{ csrf_token() }}}">Remove student from group</a></li>		
		<li><a href="#"	class="ajaxPost" 
			data-url="{{{ route('dissolveStudentGroupOfUser', array('assignment_id' => $assignment->id, 'user_id' => $user->id )) }}}" 
			data-_token="{{{ csrf_token() }}}">Dissolve group</a></li>		
	</ul>
</div>
@endif

