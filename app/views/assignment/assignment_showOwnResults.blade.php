@extends('assignment.assignment_template')

@section('title') 
{{{ $assignment->title }}}
@stop 

@section('sub_title') 
Due {{{ $assignment->answers_due }}}
@stop

@section('content')
@parent

<?php 
	$user = Auth::user();
?>

@if($assignment->isBlockedDueToRapidRelease($user))
	<div class="well">
		You are only allowed to see your results once you finished your <a href="{{{ route('showReviewTasks', $assignment->id) }}}">marking tasks</a>. 
		Once you have completed your peer reviews, reviews and marks you received will be shown here.   
	</div>
@else

@if($assignment->maySeeReceivedFinalMark($user))
	<?php $mark = $assignment->getUserAssignmentMark(Auth::user()); ?>
	@if(isset($mark))
		<div class="panel panel-default add-large-bottom-margin">
			<div class="panel-body text-center bg-success">
				<span class="font-huge">{{{$mark->presenter()->mark}}}%</span><br>
				<strong>(This is your final mark for this assignment)</strong>
			</div>
		</div>
	@else
		<div class="panel panel-default add-large-bottom-margin">
			<div class="panel-body text-center bg-warning">
				<strong>The lecturer has not assigned a final mark for you yet</strong>
			</div>
		</div>
	@endif		
@endif




@if($assignment->maySeeReceivedReviews($user))
	@include('assignment.assignment_showStudentResultsByQuestion_insert', array(
		'user' => $user, 
		'assignment'=> $assignment, 
		'showFinalMarks' => $assignment->maySeeReceivedQuestionMarks($user),
		'showPendingReviews' => true,
		'hideReviewsThatAreHiddenFromReviewee' => true,
	))

<div class="add-large-bottom-margin"></div>

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
        					The reviews you wrote for question {{{ $question->full_position }}}
       				</a>
      			</h4>
    		</div>
    
    			<div id="browse_reviews_panel_{{{$user->id}}}_{{{$question->id}}}" class="panel-collapse collapse in">
      				<div class="panel-body">
      					<div class="row">
      							@include('review.review_showSeveralReviews_insert', array('reviews' => $reviews, 'expandPending' => false))
      					</div>
	  				</div>
    			</div>
    		
		</div>
	</div>
	@endforeach
</div>


@endif


@endif
@stop
