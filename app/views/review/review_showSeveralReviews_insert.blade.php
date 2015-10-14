<?php
	if(!isset($expandPending)) {
		$expandPending = false;
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
	
	if(!isset($offerRating)) {
		$offerRating = false;
	}
	
	if(!isset($attachmentPathPrefix)) {
		$attachmentPathPrefix = '';
	}
	
	
?>
				
				@foreach($reviews as $related)
					<div class="col-md-12 reviewDisplayPanel" data-review-id="{{{ $related->id }}}">
						<div class="panel panel-default">
							<div class="panel-heading {{{ $related->isCompleted() ? 'success' : 'warning' }}}">
								<h4 class="panel-title">
									<a id="target_review_{{{ $related->id }}}" data-toggle="collapse" href="#related_review_panel_{{{ $related->id }}}">
										@if($related->isCompleted())
											{{{ ucfirst($related->presenter()->reviewer_role) }}} review
										@else
											Pending {{{ $related->presenter()->reviewer_role }}} review
										@endif
									</a>
								</h4>
							</div>
  			
  							<div class="panel-collapse collapse {{{ ($expandPending || $related->isCompleted()) ? 'in' : '' }}}" id="related_review_panel_{{{ $related->id }}}">
  								<div class="panel-body ">

  									@if($related->isCompleted())
  										@include('review.review_showContent_insert', array(
  												'review' => $related, 
  												'showReviewer' => $showReviewer, 
  												'showReviewee' => $showReviewee, 
  												'offerRating' => $offerRating,
  												'attachmentPathPrefix' => $attachmentPathPrefix.'review_'.$related->id.'_',
  											))
  									@else
  										@if($showReviewee)
  											@include('user.user_showData_insert', array('user' => $related->answer->user, 'title' => 'Answer by'))
  										@endif
  										
  										@if($showReviewer)
  											@include('user.user_showData_insert', array('user' => $related->user, 'title' => 'Reviewer'))
  										@endif
  										
										<div class="text-right">
											@if($linkReviewNumber)
												<a href="{{{ route('assignmentBrowseStudentShow', array('assignment_id' => $related->assignment->id, 'user_id' => $related->answer->user_id)) }}}#target_review_{{{ $related->id }}}">
											@endif
												<small>review #{{{ $related->id }}}</small>
											@if($linkReviewNumber)
												</a>
											@endif
										</div>
  									@endif
  			
	  							</div>
  							</div>
						</div>
					</div>
				@endforeach	  	