@extends('archive.archive_master_template') 

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

@stop
