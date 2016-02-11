

<p><strong>{{{ $assignment->title }}}</strong></p>

<ul>
<li>{{{ $assignment->explainVisibility() }}}</li>


<li>The assignment is due on {{{ $assignment->answers_due }}}.</li>


<li>The policy for late sumbissions is: {{{ $assignment->explainLatePolicy() }}}</li>
	
<li>
@if(empty($assignment->autostart_marking_time))
You have to start the marking process manually.
@else
The marking process will be started on {{{$assignment->autostart_marking_time}}}.
@endif
</li>

<li>{{{ $assignment->explainGuessMarks() }}}</li>

@if($assignment->usesPeerReview())
@if($assignment->review_mode == ReviewLimitMode::minreviewlimit || $assignment->review_mode == ReviewLimitMode::mincombinedlimit)
<li>Every student submission will be given to at least {{{ $assignment->number_of_peers }}} peers for marking.</li>
@endif
@if($assignment->review_mode == ReviewLimitMode::minassignedlimit|| $assignment->review_mode == ReviewLimitMode::mincombinedlimit)
<li>Every student will receive at least {{{ $assignment->min_assigned_reviews }}} assignments for marking.</li>
@endif
<li>{{{ $assignment->explainShuffleMode() }}}</li>

<li>The peer reviews are due on {{{ $assignment->peers_due }}}.</li>
@else
<li>The peer review mode is deactivated.</li>
@endif


	
<li>{{{ $assignment->explainMarkByTutors() }}}</li>
@if($assignment->mark_by_tutors != AssignmentMarkByTutors::none)
<li>The reviews from the tutors are due on {{{ $assignment->tutors_due }}}.</li>
@endif

<li>{{{ $assignment->explainMarksReleased() }}}</li>

<li>{{{ $assignment->explainGroupWorkMode() }}}</li>
@if($assignment->group_work_mode != AssignmentGroupWorkMode::no)
<li>{{{ $assignment->explainGroupSelectionMode() }}}</li>
@if($assignment->group_size_min == $assignment->group_size_max)
	<li>Each group has to have {{{ $assignment->group_size_min }}} members.</li>
@else
	<li>Each group has to have between {{{ $assignment->group_size_min }}} and {{{ $assignment->group_size_max }}} members.</li>	
@endif
	<li>{{{ $assignment->explainGroupMarkMode() }}}</li>
@endif

</ul>

@if($assignment->mayEditAssignment(Auth::user()))
	<a href="{{ route('editAssignmentAjax', $assignment->id) }}"><button class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span> Change settings</button></a>
@endif
