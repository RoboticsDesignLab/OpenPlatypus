@extends('assignment.assignment_template') 

@section('title') 
My student group 
@stop 

@section('sub_title') 
{{{ $assignment->title }}} 
@stop

@section('content')
@parent


@if($assignment->onlyOneAnswerPerGroup())
{{ Alert::warning('
	Note: this is a group work assignment. This means, that you can only hand in one solution for the entire group.
	Only one member of your group can submit an answer. The answer that is submitted will count as the answer
	of the entire group.
')}}
@endif

@if(isset($group))

	<div class="panel panel-default">
 		<div class="panel-heading">
  			<h3 class="panel-title">For this assignment you are in the following group:</h3>
  		</div>
  		
  		<div class="panel-body">
  			@include('user.user_list_insert', array('users' => $group->users, 'hideStudentId' => true))

  			<div class="text-center">
    			<strong>This group is now fixed. If you want to change your group, please contact your lecturer.</strong>
  			</div>  			
  		</div>


  		
  	</div>
	
@else

	
	<div class="panel panel-default">
 		<div class="panel-heading">
  			<h3 class="panel-title">No group assigned</h3>
  		</div>
  		
  		<div class="panel-body">
  			You are not assigned to a student group yet. 
  			@if($assignment->maySuggestStudentGroup($user->resource))
  				You may suggest a student group here. Once all your peers confirm that they want to be in a group with you, the group is formed.
  			@else
  				Please contact your lecturer about forming a group.
  			@endif
  		</div>


  		
  	</div>
	
@if(isset($suggestions))
		@foreach($suggestions as $suggestion)
			<div class="panel panel-default">
 			<div class="panel-heading">
 				<?php $creator = $suggestion->creator->presenter(); ?>
  				<h3 class="panel-title">Suggestion by {{{ $creator->name }}}</h3>
  			</div>
  		
  			<div class="panel-body">
  				<?php
  					$acceptanceColumnGenerator = function ($user) use($suggestion) {
  						$result = '';
  						if ($suggestion->hasAccepted($user->resource)) {
							$result .= 'accepted';
						} else {
							$result .= 'waiting for acceptance';
						}
  						return $result;
  					};
  				
  					$appendColumns = array(array('title' => 'Status', 'generator' => $acceptanceColumnGenerator));
  				
  				?>
  				@include('user.user_list_insert', array('users' => $suggestion->users, 'hideStudentId' => true))
  				
  				@if(!$suggestion->hasAccepted($user->resource))
  				{{ Form::post_button_primary(array('route' => array('acceptGroupSuggestion','assignment_id' => $assignment->id, 'suggestion_id' => $suggestion->id)), '<span class="glyphicon glyphicon glyphicon-ok"></span> Accept') }}
  				@endif
  				{{ Form::post_button(array('route' => array('rejectGroupSuggestion','assignment_id' => $assignment->id, 'suggestion_id' => $suggestion->id)), '<span class="glyphicon glyphicon glyphicon-remove"></span> Reject') }}
  				  				
  			</div>

	
  			</div>		
		@endforeach
@endif	
	
@if($assignment->maySuggestStudentGroup($user->resource))
	

	
	<div class="panel panel-default">
 		<div class="panel-heading">
  			<h3 class="panel-title">Suggest a group</h3>
  		</div>
  		
  		<div class="panel-body">
  			<p>
  				You can suggest multiple groups. If multiple groups are suggested, the first suggestion that has been accepted
  				by all members will become your group. 
  			</p>
  			
  			<p class="add-bottom-margin">
  				For this assignment, each group has to have {{{ $assignment->group_size }}} members. 
  				Please enter the email addresses or student IDs of the people you would like to form a group with. 
  			</p>
  		
  		
  			{{ Form::open_horizontal(array('route' => array('suggestGroupAsStudent', $assignment->id))) }}
  			
  			
  			
  			{{ Form::input_group('text', 'member_1', 'Group member 1', $user->email, $errors, array('readonly' => 'readonly'), 'This is you') }}
  			
  			<div class="unhide-group">
  			
  			<?php 
  				$hasHiddenField = false;
  			
  			 	if(!isset($showLinesCount) && Session::has('showLinesCount')) { 
					$showLinesCount = Session::get('showLinesCount'); 
				}
  			?>
  			
  			@for($count = 2; $count <= $assignment->resource->group_size_max; $count++)
  				<?php
  					$showThisLine = false;
  					if ($count <= $assignment->resource->group_size_min) $showThisLine = true;
  					if (isset($showLinesCount) && ($count <= $showLinesCount)) $showThisLine = true;
  					if ($count <= 2) $showThisLine = true;
  					
  					if (!$showThisLine) $hasHiddenField = true;
  				?>
  				<div class="{{ ($showThisLine ? '' : 'unhide-item') }}">
	  			{{ Form::input_group('text', 'member_'.$count, 'Group member '.$count, '', $errors, NULL, 'Email or student ID') }}
	  			</div>
  			@endfor
  			
  			@if($hasHiddenField)
  			<div class="form-group unhide-control">
  				<div class="col-md-offset-2 col-md-10">
  					<button class="btn btn-default unhide-trigger" type="button"><span class="glyphicon glyphicon glyphicon-plus"></span> Show more lines</button>
  				</div>
			</div>
			@endif
  			
  			</div>
  			
  			{{ Form::submit_group(array('submit_title' => 'Suggest this group now' )) }}
  			
  			{{ Form::close() }}
  			
  		</div>
  	</div>
	
	
	
@endif


@endif

@stop
