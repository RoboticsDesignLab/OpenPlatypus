<?php
 
if(!isset($linkReviewNumber)) {
	$linkReviewNumber = false;
}

if(!isset($showFinalMarks)) {
	$showFinalMarks = false;
}

if(!isset($showReviewer)) {
	$showReviewer = false;
}

if(!isset($showPendingReviews)) {
	$showPendingReviews = false;
}

if(!isset($attachmentPathPrefix)) {
	$attachmentPathPrefix = '';
}

if(!isset($user)) {
	$user = Auth::user();
}

if(!isset($hideReviewsThatAreHiddenFromReviewee)) {
	$hideReviewsThatAreHiddenFromReviewee = false;
}



?>



<div class="row add-large-bottom-margin">

	@foreach($assignment->getQuestionsOrderedWithSubquestions() as $question)
	<div class="{{{ $question->isSubquestion() ? 'col-md-offset-1 col-md-11' : 'col-md-12' }}}">
		<?php $question = $question->presenter(); ?>
		<div class="panel panel-default">
		
   			<div class="panel-heading">
      			<h4 class="panel-title">
      				@if(!$question->isMaster())
        			<a data-toggle="collapse" href="#browse_panel_{{{$user->id}}}_{{{$question->id}}}">
        			@endif
          				Question {{{ $question->full_position }}}
          			@if(!$question->isMaster())
        			</a>
        			@endif
      			</h4>
    		</div>
    
    		@if(!$question->isMaster())
    			<div id="browse_panel_{{{$user->id}}}_{{{$question->id}}}" class="panel-collapse collapse in">
      				<div class="panel-body">
      					<div class="row">

							<?php 
									$answer = $question->getSubmittedAnswer($user->resource);
									$isGroupAnswer = false;
									
									$groupAnswer = $question->getSubmittedGroupAnswer($user->resource);

									if(isset($groupAnswer)) {
										$answer = $groupAnswer;
										$isGroupAnswer = true;
									}
							?>      					
      					
      						
      						<div class="col-md-2">
      							@if($showFinalMarks)
      								<?php $mark = $question->getUserMark($user->resource); ?>
      								@if(isset($mark))
										<h1><small>Mark:</small><br>{{{ roundPercentage($mark) }}}%</h1>
									@endif
								@endif

								
	      						@if(isset($answer) && $answer->isLate())
									<div class="alert alert-danger add-bottom-margin">
										<strong>Submitted <br>{{{ $answer->presenter()->late_by }}} late</strong>
									</div>
								@endif
							</div>


							
							
							<div class="col-md-10 add-bottom-margin">	

								@if(isset($answer))
  									@include('textblock.textblock_show_insert', array(
  											'textBlock' => $answer->text, 
  											'showEditLink' => false, 
  											'role' => TextBlockRole::studentanswer,
  											'attachmentPathPrefix' => $attachmentPathPrefix.'q'.$question->full_position.'_',
  										))
  									
  									@if($isGroupAnswer && $assignment->mayBrowseStudents(Auth::user()))
										@include('user.user_showData_insert', array('user' => $answer->user, 'title' => 'Submitted by'))
  									@endif
  									
    								<div class="row">
    									@if($answer->hasGuessedMark())
    										<div class="col-md-7 text-left">
    												@if($user->id == Auth::user()->id)
    													<strong>You estimated your mark to be {{{ $answer->guessed_mark }}}%.</strong>
    												@else
    													<strong>The student estimated {{{ rand(0,1) ? 'his/her' : 'her/his' }}} mark to be {{{ $answer->guessed_mark }}}%.</strong>
    												@endif    												
    										</div>
    									@endif
    											
    									<div class="col-md-{{{ $answer->hasGuessedMark() ? '5' : '12' }}} text-right">
    										<small>
    											Submitted on {{{ $answer->presenter()->time_submitted }}}
    											@if($isGroupAnswer)
    												by {{{ $answer->user->presenter()->name }}}
    											@endif
    										</small>
    									</div>
    											
    								</div>
							
    							@else
  									<strong>No answer for this question has been submitted.</strong>
  								@endif
  							</div>
  							
  							@if(isset($answer))
  							<div class="col-md-offset-2 col-md-10">
  								<div class="row">
  									<?php
  										if($showPendingReviews) {
											$reviews = $answer->all_reviews_ordered;
										} else {
											$reviews = $answer->submitted_reviews_ordered;
										}
										
										if($hideReviewsThatAreHiddenFromReviewee) {
											$reviews = $reviews->filter(function($item) {
												if($item->isHiddenFromReviewee()) {
													return false;
												} else {
													return true;
												}
											});
										}
  									?>
  									@include('review.review_showSeveralReviews_insert', array('reviews' => $reviews, 'expandPending' => true, 'showReviewer' => $showReviewer, 'linkReviewNumber'=> $linkReviewNumber))
  								</div>
  							</div>
  							@endif
      						
      					</div>
	  				</div>
    			</div>
    		@endif
    		
		</div>
	</div>
	@endforeach
</div>


