@extends('assignment.assignment_template')


@section('title') 
Edit assignment
@stop




@section('content') 
@parent



<div class="row">

<div class="col-md-12">

<?php
  if(!isset($showEditForm)) {
	$showEditForm = false;
  }	 
?>
@include('assignment.assignment_controlPanel_generalSettings_insert',array('assignment' => $assignment, 'showEditForm' => $showEditForm))


@if($assignment->mayEditAssignment(Auth::user()))
	<div class="ajaxFormWrapper autoupdate" data-url="{{ route('editAssignmentInfopanelAjax', $assignment->id) }}">
		@include('assignment.assignment_infopanel_insert', array('assignment' => $assignment))
	</div>
@endif

<div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#editIntroduction">
          Introduction
        </a>
      </h4>
    </div>
    <div id="editIntroduction" class="panel-collapse collapse in">
      <div class="panel-body">
		@include('...textblock.textblock_show_insert', array('textBlock' => $assignment->introduction, 'showEditLink' => $assignment->mayEditIntroduction(Auth::user()), 'role' => TextBlockRole::assignmentintroduction))
	  </div>
    </div>
</div>



<div class="ajaxFormWrapper">

@foreach ($questions as $question)
	@include('...question.edit_insert', array('question' => $question, 'showEditLink' => true, 'assignment' => $assignment))
@endforeach

@if($assignment->mayAddQuestions(Auth::user()))
	<div data-resourceid="foot">
		{{ Form::post_button_primary(
			array('route' => array('editAssignmentAddQuestionAjax','assignment_id' => $assignment->id, 'type' => QuestionType::simple, 'master_question_id' => 0 ) ), 
			'<span class="glyphicon  glyphicon-plus"></span> Add a new question') 
		}}
		{{ Form::post_button_primary(
			array('route' => array('editAssignmentAddQuestionAjax','assignment_id' => $assignment->id, 'type' => QuestionType::master, 'master_question_id' => 0 ) ), 
			'<span class="glyphicon  glyphicon-plus"></span> Add a new question with sub-questions') 
		}}
	</div>
@endif

</div>


</div>

</div>


@stop




