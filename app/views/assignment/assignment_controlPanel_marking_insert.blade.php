

<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">Submissions</h3>
  </div>
  <div class="panel-body">
    
    	<?php 
    		$answerCount = $assignment->submittedAnswers()->count();
    		
    		
    		$answerStudentCount =  count($assignment->submittedAnswers()->distinct()->lists('user_id'));
    		$totalStudentCount = $assignment->activeStudents->count();
    		
    		if(($answerCount > 0) && ($totalStudentCount > 0)) {
    		        $showPercentage = true;
    		
            		$answerPercentage = 100 * $answerCount / ($totalStudentCount * $assignment->questionsWithSubquestionsWithoutMasters()->count());
    	        	$answerStudentPercentage = 100 * $answerStudentCount / $totalStudentCount;
                } else {
                        $showPercentage = false;
                }
    		
    	?>
    	<p><strong>
    		There is a total of {{{ $answerCount }}} submitted answers by {{{ $answerStudentCount }}} students.
    		@if($showPercentage)
    			This equates to {{{ roundPercentage($answerPercentage) }}}% of the possible answers submitted by {{{ roundPercentage($answerStudentPercentage) }}}% of the students.
    		@endif
    	</strong></p>
    	
    	@if($answerCount > 0)
    		<dl class="dl-horizontal">
    			@foreach($assignment->getQuestionsOrderedWithSubquestionsWithoutMasters() as $question)
    			<?php $count = $question->submittedAnswers()->count(); ?>
	    			<dt>{{{ $question->presenter()->full_position }}}</dt>
  					<dd>{{{ $count }}} answers</dd>
  				@endforeach
    		</dl>
  		@endif
    	
  </div>    
  <div class="panel-heading">
    <h3 class="panel-title">Marking process</h3>
  </div>
  <div class="panel-body">


	@if($assignment->markingHasStarted())
		<p><strong>The marking process is on its way.</strong></p>
		
		<p>{{{ $assignment->allCompletedReviews(ReviewReviewerRole::student)->count() }}} of {{{ $assignment->allReviews(ReviewReviewerRole::student)->count() }}} peer reviews completed. Due: {{ $assignment->peers_due }}</p>
		<p>{{{ $assignment->allCompletedReviews(ReviewReviewerRole::tutor)->count() }}} of {{{ $assignment->allReviews(ReviewReviewerRole::tutor)->count() }}} tutor reviews completed. Due: {{ $assignment->tutors_due }}</p>
		<p>{{{ $assignment->allCompletedReviews(ReviewReviewerRole::lecturer)->count() }}} of {{{ $assignment->allReviews(ReviewReviewerRole::lecturer)->count() }}} lecturer reviews completed.</p>

		
		@if($assignment->mayManageAssignment(Auth::user()))
		
		<p>{{ Form::post_button(
			array('route' => array('deletePendingTurorReviewTasksPost', 'id' => $assignment->id)), 
			'Delete pending tutor reviews', 
			array(
				'data-confirmationdialog' => 'Do you really want all pending tutor review tasks?', 
				'data-confirmationbutton' => 'Delete review tasks now', 
			)) }}
		</p>
		
		<p>{{ Form::post_button(
			array('route' => array('ensureEachAnswerHasTutorReviewTaskPost', 'id' => $assignment->id)), 
			'Create missing tutor review tasks', 
			array(
				'data-confirmationdialog' => 'Do you really want to create tutor review tasks for all answers that do not have a tutor assigned for review?', 
				'data-confirmationbutton' => 'Create review tasks now', 
			)) }}
		</p>
		
		<p>{{ Form::post_button(
			array('route' => array('ensureEachAnswerHasLecturerReviewTask', 'id' => $assignment->id)), 
			'Create lecturer review tasks', 
			array(
				'data-confirmationdialog' => 'Do you really want to create an explicit lecturer review task for all student answers? As lecturer you can mark student answers directly without having explicit tasks created.', 
				'data-confirmationbutton' => 'Create review tasks now', 
			)) }}
		</p>
		@endif
		
		
		@if($assignment->mayCancelMarking(Auth::user()))
		<?php 
			$cancelMessage = '
				Do you really want to cancel the marking process? This will delete:
				<ul>
					<li>All review requests.</li>
					<li>All written reviews.</li>
					<li>All marks that have been given for this assignment.</li>
				</ul>'; 
		
		?>
		<p>{{ Form::post_button(
			array('route' => array('cancelAssignmentMarking', 'id' => $assignment->id)), 
			'Cancel marking process', 
			array(
				'data-confirmationdialog' => $cancelMessage, 
				'data-confirmationbutton' => 'Cancel marking process now', 
				'data-confirmationcheckbox' => 'Yes, I really want to cancel the marking process now.'
			)) }}
		</p>
		@endif
		
	@else
		
		@if($assignment->mayManageMarkingProcess(Auth::user()))
			@if($assignment->assignmentIsreadyToStartMarking())
				{{ Form::post_button_primary(array('route' => array('startAssignmentMarking', 'assignment_id'=> $assignment->id)) ,"Start marking process now", "Do you really want to start the marking phase now?") }}
			@else	
				Here is where you can start the marking process. However, this assignment is not due yet. Please wait till after the due date to start the marking process. The due date is {{{$assignment->answers_due}}}.
			@endif
		@else
			The marking process hasn't started yet.
		@endif
	@endif
  
  
  </div>
</div>


