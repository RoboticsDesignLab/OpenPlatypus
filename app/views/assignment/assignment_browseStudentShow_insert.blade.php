<?php
 
if(!isset($linkReviewNumber)) {
	$linkReviewNumber = true;
}

if(!isset($renderForArchive)) {
	$renderForArchive = false;
}

if($renderForArchive) {
	$linkReviewNumber = false;
}

?>


<div class="panel panel-default add-large-bottom-margin">
		
   			<div class="panel-heading">
      			<h4 class="panel-title">
       				<a data-toggle="collapse" href="#browse_user_data">
        					{{{ $user->name }}}
       				</a>
      			</h4>
    		</div>
    
    		<div id="browse_user_data" class="panel-collapse collapse in">
      			<div class="panel-body">
    				<dl class="dl-horizontal">
  						<dt>Name: </dt><dd>{{{ $user->presenter()->name }}}</dd>
  						<dt>Email: </dt><dd>{{{ $user->presenter()->email }}}</dd>
  						<dt>Student ID: </dt><dd>{{{ $user->presenter()->student_id }}}</dd>
  					</dl>
	  			</div>
    		</div>
    		
</div>



@include('assignment.assignment_showStudentResultsByQuestion_insert', array(
	'user' => $user, 
	'assignment'=> $assignment, 
	'showFinalMarks' => true, 
	'linkReviewNumber' => $linkReviewNumber,
	'showReviewer' => true,
	'showPendingReviews' => true,
))


<div class="row">

	@foreach($assignment->getQuestionsOrderedWithSubquestions() as $question)
	<?php
		if($question->isMaster()) continue;
		 
		$reviews = $question->getAllReviewsFromUser($user->resource);

		if(count($reviews)==0) continue;
		
		$question = $question->presenter();
	?>
	<div class="col-md-12">
		<div class="panel panel-default">
		
   			<div class="panel-heading">
      			<h4 class="panel-title">
       				<a data-toggle="collapse" href="#browse_reviews_panel_{{{$user->id}}}_{{{$question->id}}}">
        					Reviews written by {{{ $user->name }}} for question {{{ $question->full_position }}}
       				</a>
      			</h4>
    		</div>
    
    			<div id="browse_reviews_panel_{{{$user->id}}}_{{{$question->id}}}" class="panel-collapse collapse in">
      				<div class="panel-body">
      					<div class="row">
      							@include('review.review_showSeveralReviews_insert', array('reviews' => $reviews, 'expandPending' => true, 'showReviewer' => false, 'showReviewee' => true, 'linkReviewNumber'=> $linkReviewNumber))
      					</div>
	  				</div>
    			</div>
    		
		</div>
	</div>
	@endforeach
</div>

