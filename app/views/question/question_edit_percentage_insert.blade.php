@if($assignment->mayEditQuestionPercentages(Auth::user()))
<div class="dropdown">
	<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
		<strong><span class="updatable" data-resourceid="question_{{{ $question->id }}}_percentage">{{{ $question->mark_percentage }}}</span>%</strong>
		(<span class="updatable" data-resourceid="question_{{{ $question->id }}}_percentage_mode">{{{ $question->mark_percentage_mode }}}</span>)
		<span class="caret"></span>
	</button>
	
	<div class="dropdown-menu">

      	<div class="row add-margins">
      	<div class="col-md-12 add-bottom-margin">
      	
		{{ Form::open(array('route' => array('editAssignmentQuestionMarkPercentageAjax','assignment_id' => $assignment->id, 'question_id' => $question->id), 'autocomplete' => 'off' ) ) }}

    	<label class="control-label" for="mark_percentage">New percentage</label>
    	<input class="form-control" type="mark_percentage" value="" name="mark_percentage"></input>
				
		
		<input class="btn btn-primary" type="submit" value="Save"></input>
		
		{{ Form::close() }}
		
		</div>

      	<div class="col-md-12 add-bottom-margin">
		{{ Form::post_button_primary(
			array('route' => array('editAssignmentQuestionMarkPercentageAjax','assignment_id' => $assignment->id, 'question_id' => $question->id)),
    		'Calculate percentage automatically' 
		) }}
		</div>
		</div>
		
	</div>
</div>
@else
	<button class="btn btn-default" disabled>
		<strong><span class="updatable" data-resourceid="question_{{{ $question->id }}}_percentage">{{{ $question->mark_percentage }}}</span>%</strong>
		(<span class="updatable" data-resourceid="question_{{{ $question->id }}}_percentage_mode">{{{ $question->mark_percentage_mode }}}</span>)
	</button>
@endif
			
@if($question->isSubquestion())
	<div>(<span class="updatable" data-resourceid="question_{{{ $question->id }}}_percentage_global">{{{ $question->mark_percentage_global }}}</span>% of assignment)</div>
@endif	


