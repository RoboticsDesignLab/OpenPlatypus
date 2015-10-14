<?php 
if(!isset($showDate)) {
	$showDate = false;
}

if(!isset($showReviewer)) {
	$showReviewer = false;
}

if(!isset($showReviewee)) {
	$showReviewee = false;
}

if(!isset($linkReviewNumber)) {
	$linkReviewNumber = false;
}

$review = $review->presenter();

if (!isset($offerRating)) {
	$offerRating = false;
}

$offerRating = $offerRating && $review->isStudentReview() && $review->assignment->mayRateReviews(Auth::user());

?>

@if($review->isCompleted())
	<div class="row">
		<div class="col-md-2">
		
			<h1><small>Mark:</small><br>{{{ $review->mark }}}%</h1>

			@if($review->hasFlag() && $review->maySeeFlag(Auth::user()))
				<div class="alert alert-danger add-bottom-margin">
					<strong>{{{ $review->presenter()->flag }}}</strong>
				</div>
			@endif
			
			<div class="updatable" data-resourceid="review_rating_{{{ $review->id }}}">
				@include('review.review_showRating_insert', array('review' => $review))
			</div>
		</div>
		<div class="col-md-10">	
			<div class="add-bottom-margin">
  				@include('textblock.textblock_show_insert', array('textBlock' => $review->text, 'showEditLink' => false, 'role' => TextBlockRole::review))
  			</div>
  			
  			@if($showReviewer)
  				@include('user.user_showData_insert', array('user' => $review->user, 'title' => 'Reviewer'))
	  		@endif
	  		
  			@if($showReviewee)
  				@include('user.user_showData_insert', array('user' => $review->answer->user, 'title' => 'Answer by'))
	  		@endif	  		
  			
  		</div>
  		
  		
  	</div>
  	<div class="row">
  		@if($offerRating)
  			<div class="col-md-8">
				<div class="ajaxFormWrapper">
					@include('review.review_ratingControls_insert', array('review' => $review))
				</div>
			</div>
		@endif
  		
  		
		<div class="{{{ $offerRating ? 'col-md-4' : 'col-md-12' }}} text-right">
		
			@if($linkReviewNumber)
				<a href="{{{ route('assignmentBrowseStudentShow', array('assignment_id' => $review->assignment->id, 'user_id' => $review->answer->user_id)) }}}#target_review_{{{ $review->id }}}">
			@endif
			<small>review #{{{ $review->id }}}</small>
			@if($linkReviewNumber)
				</a>
			@endif

  			@if($showDate)
  				<br><small>written {{{ $review->time_submitted }}}</small>
  			@endif
		</div>
  		
  		
  	</div>
@else
	Review pending.
@endif
