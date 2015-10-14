@extends('assignment.assignment_template') 

@section('title') 
{{{ $assignment->title }}}
@stop 

@section('sub_title') 
{{{ $assignment->subject->presenter()->title }}}
@stop 

@section('content')
@parent



<h2>Assignment questions</h2>
@include('assignment.assignment_show_questions_insert', array('assignment' => $assignment))

@if($assignment->hasQuestionsWithSolution())
	<div class="add-large-bottom-margin"></div>
	<h2>Solutions</h2>
	@include('assignment.assignment_showSolutionsOrMarkingSchemes_insert', array('assignment' => $assignment, 'showSolution' => true, 'border' => true))
@endif

@if($assignment->hasQuestionsWithMarkingScheme())
	<div class="add-large-bottom-margin"></div>
	<h2>Marking scheme</h2>
	@include('assignment.assignment_showSolutionsOrMarkingSchemes_insert', array('assignment' => $assignment, 'showMarkingScheme' => true, 'border' => true))
@endif

<div class="add-large-bottom-margin"></div><hr>



@foreach($assignment->allStudents()->orderBy('last_name')->orderBy('first_name')->get() as $user)
	<?php  $user=$user->presenter(); ?>
	<h2>
		{{{ $user->name }}}
	</h2>
	
	@include('assignment.assignment_browseStudentShow_insert', array('assignment' => $assignment, 'user' => $user, 'linkReviewNumber' => false))
	
	<div class="add-large-bottom-margin"></div><hr>
@endforeach

<?php 
	$time = 0;
	$count = 0;
	
	foreach(DB::getQueryLog() as $item) {
		$count++;
		$time+= $item['time'];
	}

?>

<div class="text-right">
<small>Database load: {{{ $time / 1000 }}} seconds in {{{ $count }}} requests.</small>
</div>

@stop
