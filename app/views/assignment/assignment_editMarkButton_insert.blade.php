<?php 
	
$user = $user->resource;

if(!isset($offerEdit)) {
	$offerEdit = $assignment->maySetFinalMarks(Auth::user());
}

if(!isset($isLastColumn)) {
	$isLastColumn = false;
}

$offerUnset = false;

if(isset($question)) {
	if(!isset($assignment)) {
		$assignment = $question->assignment;
	}
	
	$display = '';
	$mark = $question->getUserMarkModel($user);
	
	if(!isset($mark)) {
		if($question->hasSubmittedAnswer($user)) {
			$display .= 'pending';
		} else {
			$display .= 'n/a';
		}
	} else {
		$display .= '<strong>'.$mark->presenter()->mark .'%</strong>';
		$offerUnset = true;
	}
	
	if($offerEdit) {
		$formUrl = route('setFinalAssignmentQuestionMarkAjax', array('assignment_id' => $assignment->id, 'question_id' => $question->id, 'user_id' => $user->id));
	}
	
} else {
	
	$display = '';
	$mark = $assignment->getUserAssignmentMark($user);
	
	if(!isset($mark)) {
		if($assignment->getUserAnswersSubmittedQuery($user)->exists()) {
			$display .= 'pending';
		} else {
			$display .= 'n/a';
		}
	} else {
		$display .= '<strong>'.$mark->presenter()->mark .'%</strong>';
		if($mark->isAutomatic()) {
			$display .= ' (auto)';
		} else {
			$offerUnset = true;
		}
	}
	
	if($offerEdit) {
		$formUrl = route('setFinalAssignmentMarkAjax', array('assignment_id' => $assignment->id, 'user_id' => $user->id));
	}
	
}


?>

@if(!$offerEdit) 
	{{ $display }}
@else


<div class="dropdown">
	<button class="btn btn-default btn-block dropdown-toggle" data-toggle="dropdown">
		{{ $display }}
		<span class="caret pull-right"></span>
	</button>
	
	<div class="dropdown-menu wide-dropdown {{{ $isLastColumn ? 'dropdown-menu-right' : ''}}}">

      	<div class="row add-margins">
      	<div class="col-md-12">
      		@if(isset($question))
      			Question {{{ $question->presenter()->full_position }}}<br>
      		@endif
      		{{{ $user->presenter()->name }}}
      	</div>
      	<div class="col-md-12 add-bottom-margin">
      	
		{{ Form::open(array('url' => $formUrl,'autocomplete' => 'off') ) }}

    		<label class="control-label" for="mark">Mark in %</label>
    		<input class="form-control" type="mark" value="" name="mark"></input>
		
			<input class="btn btn-primary" type="submit" value="Save"></input>
		
		{{ Form::close() }}

		</div>
		@if($offerUnset)
		<div class="col-md-12 add-bottom-margin">
			<a class="ajaxPost btn btn-primary" href="{{{ $formUrl }}}" data-_token="{{{ csrf_token() }}}" data-mark="unset">Unset mark</a> 
		</div>
		@endif
		

		</div>
		
	</div>
</div>


@endif
			
