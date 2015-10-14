@extends('assignment.assignment_template')

@section('title') 
Set final marks
@stop 

@section('sub_title') 
{{{ $assignment->title }}}
@stop

@section('content')
@parent

{{ Form::open(array('route' => array('autoMarkAssignment', $assignment->id), 'autocomplete' => 'off' )) }}
<input type="text" style="display:none">

<div class="row">

<div class="col-md-12">
	<div class="panel panel-default">
		<div class="panel-heading">
    		<h3 class="panel-title">Automatically assign final marks</h3>
  		</div>
  		<div class="panel-body">
  			<p>
  				On this page you can bulk-assign the students' final marks according to different filter criteria. 
  				Please be careful in selecting your criteria as careless selections easily result in unfair or 
  				unjustified marks.
  			</p>
  			<p>
  				All filters in the different panels are applied and only marks that pass all filters will be assigned.
  				Under no circumstances are marks changed that are already assigned. Only missing marks are created. 
  			</p>  		 
  		</div>
	</div>
</div>

<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading">
    		<h3 class="panel-title">Number of reviews</h3>
  		</div>
  		<div class="panel-body">
  			<p class="add-bottom-margin">
  				Please select the minimum and maximum number of submitted reviews an answer is required to have.
  			</p>

  			<div class="form-horizontal">
  			
  			{{ Form::input_group('text', 'min_any', 'Min total', 1, $errors, null, 
				'The minimum total number of reviews an answer is required to have') }}	

  			{{ Form::input_group('text', 'max_any', 'Max total', 99, $errors, null, 
				'The maximum total number of reviews an answer is allowed to have') }}	
  			
  			
  			{{ Form::input_group('text', 'min_peer', 'Min peers', $assignment->number_of_peers, $errors, null, 
				'The minimum number of peer reviews an answer is required to have') }}	

  			{{ Form::input_group('text', 'max_peer', 'Max peers', $assignment->number_of_peers, $errors, null, 
				'The maximum number of peer reviews an answer is allowed to have') }}	
				
							
  			{{ Form::input_group('text', 'min_tutor', 'Min tutors', 1, $errors, null, 
				'The minimum number of tutor reviews an answer is required to have') }}	

  			{{ Form::input_group('text', 'max_tutor', 'Max tutors', 1, $errors, null, 
				'The maximum number of tutor reviews an answer is allowed to have') }}	
				
							
  			{{ Form::input_group('text', 'min_lecturer', 'Min lecturer', 0, $errors, null, 
				'The minimum number of lecturer reviews an answer is required to have') }}	

  			{{ Form::input_group('text', 'max_lecturer', 'Max lecturer', 0, $errors, null, 
				'The maximum number of lecturer reviews an answer is allowed to have') }}	
				
				
			</div>
  		</div>
	</div>
</div>

<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading">
    		<h3 class="panel-title">Limits on mark differences</h3>
  		</div>
  		<div class="panel-body">
  			<p>
  				Here you can set limits of how much the final mark that is to be assigned is allowed to differ
  				from the mark a reviewer gave to an answer.
  			</p>
  			<p class="add-bottom-margin">
  				These limits are only applied if such a review is present. So make sure you set the minimum
  				required number of reviews in the panel above.
  			</p>

  			<div class="form-horizontal">
  			{{ Form::input_group('text', 'worsethan_any', 'Worse than any', 20, $errors, null, 
				'The maximum number of points the final mark is allowed to be worse than any reviewer\'s mark') }}	

  			{{ Form::input_group('text', 'betterthan_any', 'Better than any', 20, $errors, null, 
				'The maximum number of points the final mark is allowed to be better than any reviewer\'s mark') }}
  			
  			
  			{{ Form::input_group('text', 'worsethan_peer', 'Worse than peer', 20, $errors, null, 
				'The maximum number of points the final mark is allowed to be worse than any student\'s mark') }}	

  			{{ Form::input_group('text', 'betterthan_peer', 'Better than peer', 20, $errors, null, 
				'The maximum number of points the final mark is allowed to be better than any student\'s mark') }}

				
  			{{ Form::input_group('text', 'worsethan_tutor', 'Worse than tutor', 10, $errors, null, 
				'The maximum number of points the final mark is allowed to be worse than any tutor\'s mark') }}	

  			{{ Form::input_group('text', 'betterthan_tutor', 'Better than tutor', 10, $errors, null, 
				'The maximum number of points the final mark is allowed to be better than any tutor\'s mark') }}
  			
  			
  			{{ Form::input_group('text', 'worsethan_lecturer', 'Worse than lecturer', 0, $errors, null, 
				'The maximum number of points the final mark is allowed to be worse than any lecturer\'s mark') }}	

  			{{ Form::input_group('text', 'betterthan_lecturer', 'Better than lecturer', 0, $errors, null, 
				'The maximum number of points the final mark is allowed to be better than any lecturer\'s mark') }}
  			</div>
  			
  		</div>
	</div>
</div>


<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading">
    		<h3 class="panel-title">Pending revies</h3>
  		</div>
  		<div class="panel-body">
  			<p class="add-bottom-margin">
  				If an answer still has pending review requests of a type that is NOT selected here, it will be ignored.
  			</p>
  			
  			<div class="form-horizontal">
  			
				{{ Form::checkbox_group("pending_peer", 'Student review', 1, false, $errors) }}
				{{ Form::checkbox_group("pending_tutor", 'Tutor review', 1, false, $errors) }}
				{{ Form::checkbox_group("pending_lecturer", 'Lecturer review', 1, true, $errors) }}
  			
  			</div>
  			
  		</div>
	</div>
</div>


<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading">
    		<h3 class="panel-title">Flags</h3>
  		</div>
  		<div class="panel-body">
  			<p class="add-bottom-margin">
  				Please select the flags that may be present in an answer's review.
  				If any review of the answer has a flag that doesn't comply with this
  				selection, no mark is assigned.
  			</p>
  			
  			<div class="form-horizontal">
  			
  			@foreach(ReviewFlag::getValues() as $flag)
  				<?php 
  					 
  					if($flag == ReviewFlag::none) {
						continue;
					} else {
						$caption = ReviewPresenter::$presentFlag[$flag];
						$checked = false;
					}
  				?>
				{{ Form::checkbox_group("flag_$flag", $caption, 1, $checked, $errors) }}
  			@endforeach
  			
  			</div>
  			
  		</div>
	</div>
</div>

<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading">
    		<h3 class="panel-title">Ignore reviews based on rating</h3>
  		</div>
  		<div class="panel-body">
  			<p class="add-bottom-margin">
  				Here you can select to ignore reviews based on their rating.
  				Reviews that have a rating that is not selected here will be ignored. <strong>Note:</strong> a mark may
  				still get assigned if the remaining reviews satisfy all filter criterions. 
  			</p>
  			
  			<div class="form-horizontal">
  			
  			{{ Form::checkbox_group("rating_unrated", 'unrated', 1, true, $errors) }}
  			
  			@foreach(ReviewRating::getValues() as $rating)
  				<?php 
  					$caption = ReviewPresenter::review_rating_static($rating);
					$checked = ($rating >= 0);
  				?>
				{{ Form::checkbox_group("rating_$rating", $caption, 1, $checked, $errors) }}
  			@endforeach
  			
  			</div>
  			
  		</div>
	</div>
</div>

<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading">
    		<h3 class="panel-title">Questions</h3>
  		</div>
  		<div class="panel-body">
  			<p class="add-bottom-margin">
  				Here you can select which questions are processed.
  			</p>
  			
  			<div class="form-horizontal">
  			
  			@foreach($assignment->questions_with_subquestions_without_masters_ordered as $question)
				{{ Form::checkbox_group("question_".$question->id, 'Question '.$question->presenter()->full_position, 1, true, $errors) }}
  			@endforeach
  			
  			</div>
  			
  		</div>
	</div>
</div>


<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading">
    		<h3 class="panel-title">Mark calculation</h3>
  		</div>
  		<div class="panel-body">
  			<p class="add-bottom-margin">
  				Please select how the final mark should be calculated.
  			</p>
  			
  			<div class="form-horizontal">
  			
  			<?php
  				$options = array();
  				$options[1] = 'Use the mean of all reviews.'; 
  				$options[2] = 'Use the median of all reviews.';
  				$options[3] = 'Use the tutor\'s mark. (Calculates mean if several are present)';
  				$options[4] = 'Use the lecturer\'s mark. (Calculates mean if several are present)';
  			?>
  			
  			{{ Form::radio_group_vertical('mark_mode', 'mark_mode', $options, null, $errors) }}

			</div>
  			  			
  		</div>
	</div>
</div>

<div class="col-md-12">
	<div class="panel panel-default">
		<div class="panel-heading">
    		<h3 class="panel-title">Calculate</h3>
  		</div>
  		<div class="panel-body">
  			<p class="add-bottom-margin">
  				Pressing this button will calculate the marks using the settings you provided. 
  			</p>
  			
  			<div class="form-horizontal">
  			
  			{{ Form::submit_group(array('submit_title' => 'Calculate marks')) }}
  			
  			</div>
  			
  		</div>
	</div>
</div>



</div>



{{ Form::close() }}
	
	
@if(isset($marksToSet))
	{{ Form::open(array('route' => array('autoMarkSetAssignment', $assignment->id), 'autocomplete' => 'off' )) }}

<div class="">
	<div class="panel panel-default">
	<div class="panel-heading">
    	<h3 class="panel-title">Calculated marks</h3>
  	</div>	
	
	<table class="table">
	<tr>
		<th>Select</th>
		<th>Question</th>
		<th>Student</th>
		<th>Proposed mark</th>
		<th>Reviews</th>
	</tr>
	
	@foreach($marksToSet as $item)
		<tr>
			<?php
				$answer = $item['answer']; 
				$mark = $item['mark'];
			?>
		
			<td>
				<input type="checkbox" name="set_mark_for_answer_{{{ $answer->id }}}" value="{{{ $mark }}}" checked>
			</td>
			
			<td>
				Question {{{ $answer->question->presenter()->full_position }}}
			</td>
			
			<td>
				<a href="{{{ route('assignmentBrowseStudentShow', array('assignment_id' => $assignment->id, 'user_id' => $answer->user->id)) }}}">{{{ $answer->user->presenter()->name }}}</a>
			</td>
			
			<td style="font-size: 200%;">
				{{{ $mark }}}%
			</td>
			
			<td>
				<ul class="list-group compact">
 					@foreach($answer->reviews as $review)
 						<?php $review = $review->presenter(); ?>
 						<li class="list-group-item">
 							{{{ $review->reviewer_role }}} : 
 							@if($review->isCompleted())
 								{{{ $review->mark }}}%
 								{{{ $review->flag }}}
 								{{ $review->review_rating_glyph }}
 							@else
 								pending
 							@endif
 						</li>	
 					@endforeach
 				</ul>
			</td>
			
			
		</tr>
	@endforeach
	
	
	
	</table>
	
	<div class="panel-body">
	
			<hr>
	
  			<p class="add-bottom-margin">
  				Pressing this button will set all the calculated marks that have their checkbox checked. 
  			</p>
  			
  			
  			
  			<div class="form-horizontal">
  			
  			{{ Form::submit_group(array('submit_title' => 'Set proposed marks now')) }}
  			
  			</div>
  			
  	</div>	
	
	</div>
</div>
	
	
	{{ Form::close() }}
	
@endif






@stop
