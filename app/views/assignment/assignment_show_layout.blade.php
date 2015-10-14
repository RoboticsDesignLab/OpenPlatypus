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

$showSolutions = $assignment->mayViewAllSolutions(Auth::user()) && $assignment->hasQuestionsWithSolution();

$showMarkingSchemes = $assignment->mayViewAllMarkingSchemes(Auth::user()) && $assignment->hasQuestionsWithMarkingScheme();

?>

<div class="row">

<div class="col-md-12">

@if($showSolutions || $showMarkingSchemes)

  <ul class="nav nav-tabs">
    <li class="active"><a href="#questions" data-toggle="tab">Questions</a></li>
    @if($showSolutions)
    	<li><a href="#solutions" data-toggle="tab">Solutions</a></li>
    @endif
    @if($showMarkingSchemes)
    	<li><a href="#markingscheme" data-toggle="tab">Marking scheme</a></li>
    @endif
  </ul>

  <div class="tab-content borderedTabContent">
    <div class="tab-pane active" id="questions">
    	@include('assignment.assignment_show_questions_insert', array('assignment' => $assignment, 'border' => false))
    </div>
    
    @if($showSolutions)
    <div class="tab-pane" id="solutions">
    	@include('assignment.assignment_showSolutionsOrMarkingSchemes_insert', array('assignment' => $assignment, 'showSolution' => true, 'border' => false))
    </div>
    @endif
    
    @if($showMarkingSchemes)
    <div class="tab-pane" id="markingscheme">
    	@include('assignment.assignment_showSolutionsOrMarkingSchemes_insert', array('assignment' => $assignment, 'showMarkingScheme' => true, 'border' => false))
    </div>
    @endif
    
  </div>

</div>


@else
	@include('assignment.assignment_show_questions_insert', array('assignment' => $assignment, 'border' => true))
@endif

</div>


</div>

@stop
