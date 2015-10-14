@if($assignment->markingHasStarted())

<?php 

	if(!isset($showRatings)) {
		$showRatings = false;
	}

	if($showRatings) {
		$reviews = $assignment->ratedReviewsOrdered();
	} else {
		$reviews = $assignment->flaggedReviewsOrdered();
	}
	
	$reviews = $reviews->with('answer.user')->with('user')->with('answer.question')->get();

?>
	
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">
    	@if($showRatings)
    		Rated reviews
    	@else
    		Flagging reviews
    	@endif
    	
    </h3>
  </div>
  
	@if(count($reviews) == 0)
		<div class="panel-body">
			<strong>No reviews have been flags set.</strong>
		</div>
	@else    
	<div class="largeScrollBox">
		<table class="table table-condensed">
			<tr>
				<th>#</th>
				<th>Question</th>
				<th>Flag</th>
				<th>Rating</th>
				<th>Mark</th>
				<th>Reviewee</th>
				<th></th>
				<th>Reviewer</th>
			</tr>
		
			@foreach($reviews as $review)
				<?php $review = $review->presenter(); ?>
				<?php
				  $colourClass = "";
				  
				  if ($showRatings) {
					if($review->resource->review_rating > 0) $colourClass = "success";
					if($review->resource->review_rating < 0) $colourClass = "warning";
					if($review->isHiddenFromReviewee()) $colourClass = "danger";
				  } else {
				  	if($review->resource->flag == ReviewFlag::plagiarised) $colourClass = "danger";
				  	if($review->resource->flag == ReviewFlag::attention) $colourClass = "warning";
				  	if($review->resource->flag == ReviewFlag::excellent) $colourClass = "success";
				  	if($review->resource->flag == ReviewFlag::poor) $colourClass = "info";
				  }
				  
				  $url = URL::route('assignmentBrowseStudentShow', array('assignment_id' => $assignment->id, 'user_id' => $review->answer->user_id)).'#target_review_'.$review->id;
				?>
				<tr class="clickableRow {{{ $colourClass }}}" data-url="{{ $url }}">
					<td class="text-nowrap"><a href="{{{ $url }}}">
						#{{{ $review->id }}}
					</a></td>
					<td>{{{	$review->answer->question->presenter()->full_position }}}</td>
					<td>{{{	$review->flag }}}</td>
					<td>{{	$review->review_rating_glyph }}</td>					
					<td>{{{	$review->mark }}}%</td>
					<td>{{{	$review->answer->user->presenter()->name }}}</td>
					<td><span class="glyphicon glyphicon-arrow-right"></span></td>
					<td>{{{	$review->user->presenter()->name }}}</td>
				</tr>
			@endforeach
		</table>
	</div>
	@endif
  
  
</div>

@endif