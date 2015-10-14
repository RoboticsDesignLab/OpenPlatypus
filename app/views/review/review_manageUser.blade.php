@extends('assignment.assignment_template') 

@section('title') 
Reviews
@stop 

@section('sub_title') 
{{{ $user->name }}}
@stop


@section('content')
@parent

@foreach(array(true => $dispatchedReviews, false => $reviews) as $dispatched => $currentReviews)

<div class="row">
	<div class="col-md-12">
	
		{{ Form::open(array('route' => array('manageReviewsUserPost', $assignment->id, $user->id))) }}
		<?php $showForm = false; ?>
		<div class="panel panel-default">
			<div class="panel-heading"><h3 class="panel-title">{{{ $dispatched ? 'Reviews for answers by' : 'Reviews by' }}} {{{ $user->name }}}</h3></div>
  			<div class="panel-body">
  				@if($dispatched)
  					<p>These reviews are the ones that are assigned to the answers that were submitted by {{{ $user->name }}}.</p>
    			@else
    				<p>This is a list of the review tasks that were assigned to {{{ $user->name }}}.</p>
    			@endif
  			</div>

  			<table class="table table-hover table-border-bottom">
  				<tr>
  					<th>Select</th>
  					<th>Question</th>
  					<th>Answer by</th>
  					<th></th>
  					<th>Reviewer</th>
  					<th>Mark</th>
  				<tr>
  				
				@foreach($currentReviews as $review)
					<tr{{ $review->isCompleted() ? ' class="success clickableRow" data-toggle="collapse" data-target="review_content_'.$review->id.'"' : '' }}>
						<td>
							@if(!$review->isCompleted())
								<?php $showForm = true; ?>
								{{ Form::checkbox('review_'.$review->id, '1') }}
							@endif
						</td>
						<td>{{{ $review->question->presenter()->full_position }}}</td>
						<td>{{{ $review->answer->user->presenter()->name }}}</td>
						<td><span class="glyphicon glyphicon-arrow-right"></span></td>
						<td>
							{{{ $review->user->presenter()->name }}}
							<?php $siblingCount = is_null($review->review_group_id) ? 0 : $review->siblings()->count(); ?>
							@if($siblingCount > 0)
								<?php
									$siblingNames = '';
									foreach($review->siblings()->get() as $sibling) {
										if (!empty($siblingNames)) $siblingNames .= '<br>';
										$siblingNames .= htmlentities($sibling->user->presenter()->name);
									} 
								?>
								<span class="makeTooltip" data-toggle="tooltip" data-placement="top" data-html="true" title="{{ $siblingNames }}">
								+ {{{ $siblingCount }}}
								</span> 
							@endif
						</td>
						<td>
							@if($review->isCompleted())
								{{{ $review->presenter()->mark }}}%
							@else
								{{{ $review->status }}}
							@endif
						</td>
					</tr>
					@if($review->isCompleted())
						<tr class="collapse no-hover" id="review_content_{{{ $review->id }}}">
							<td colspan="6">
								@include('review.review_showContent_insert', array('review' => $review, 'showDate' => true, 'linkReviewNumber' => true))
							</td>
						</tr>
					@endif
				@endforeach  				
  				
  				@if($showForm)
  					<tr>
						<td colspan="6">
							<a href="#" class="formSelectAllCheckboxes">Select all</a>
							|	
							<a href="#" class="formSelectNoCheckboxes">Select none</a>
						</td>
					</tr>
  				@endif
  				
  			</table>
  			
  			@if($showForm)
  				<div class="panel-body">
  					  				
    				{{ Form::input_group('text', 'reassignee', 'Re-assign to', '', $errors, NULL, 'Re-assign selected review tasks to someone else. Enter email address or student ID.') }}
    				
    				<div class="form-group"><div class="col-md-offset-2 col-md-10">
    					<button name="button" type="submit" value="reassign" class="btn btn-primary"><span class="glyphicon glyphicon glyphicon-forward"></span> Re-assign selected</button>
    					<button name="button" type="submit" value="delete" class="btn btn-default" data-confirmationdialog="Are you sure you want to delete the selected review tasks?"><span class="glyphicon glyphicon-trash"></span> Delete selected</button>
    				</div></div>
  				</div>
  			@endif
		</div>
		{{ Form::close() }}
		
	</div>
</div>
@endforeach
		
		

@stop
