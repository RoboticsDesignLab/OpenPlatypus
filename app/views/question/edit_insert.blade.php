
@if($question->isSubquestion())
	<div class="panel panel-default col-md-offset-1" data-resourceid="{{{ $question->id }}}">
@else
	<div class="panel panel-default" data-resourceid="{{{ $question->id }}}">
@endif

    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#editQuestion_{{{ $question->id }}}">
          @if($question->isMaster())
          	Question <span class="updatable" data-resourceid="question_{{{ $question->id }}}_position">{{{ $question->position }}}</span>: (with sub-questions)
          @else
          	@if($question->isSubquestion())
          		Sub-question <span class="updatable" data-resourceid="question_{{{ $question->id }}}_position">{{{ $question->position }}}</span>)
          	@else 
            	Question <span class="updatable" data-resourceid="question_{{{ $question->id }}}_position">{{{ $question->position }}}</span>:
          	@endif
          @endif
        </a>
      </h4>
    </div>
    <div id="editQuestion_{{{ $question->id }}}" class="panel-collapse collapse in">
      <div class="panel-body">
      	<div class="row">
      	<div class="col-md-3 col-lg-2">

      	
      		<div class="btn-group add-bottom-margin" role="group">
      		@if($assignment->mayMoveQuestions(Auth::user()))
      		{{ Form::post_button(
      			array(
      				'route' => array('editAssignmentMoveQuestionAjax','assignment_id' => $assignment->id, 'question_id' => $question->id, 'direction' => -1),
      				'class' => 'pull-left', 
      			),
      			'<span class="glyphicon glyphicon-chevron-up"></span>' 
      		) }}{{ Form::post_button(
      			array(
      				'route' => array('editAssignmentMoveQuestionAjax','assignment_id' => $assignment->id, 'question_id' => $question->id, 'direction' => 1), 
      				'class' => 'pull-left', 
      			),
      			'<span class="glyphicon glyphicon-chevron-down"></span>' 
      		) }}
      		@endif
      		@if($assignment->mayDeleteQuestions(Auth::user()))
      		{{ Form::post_button(
				array(
					'route' => array('editAssignmentDeleteQuestionAjax','assignment_id' => $assignment->id, 'question_id' => $question->id),
					'class' => 'pull-left', 
					'data-confirmationdialog' => $question->isMaster() ? 'Are you sure you want to delete this question and all its sub-questions?' : 'Are you sure you want to delete this question?'),
      			'<span class="glyphicon glyphicon-trash"></span>'
      		) }}
      		@endif
   			</div>
   			
   			<div class="add-bottom-margin">
				<div class="ajaxFormWrapper">
					@include('question.question_edit_percentage_insert', array('question' => $question))
				</div>
			</div>
			
			@if(!$question->isMaster())
				<div class="add-bottom-margin">
					<div class="ajaxFormWrapper">
						@include('question.question_edit_solutionEditor_insert', array('question' => $question))
					</div>
				</div>
			@endif
			
			@if(!$question->isMaster() && $assignment->mayDeleteQuestionAnswers(Auth::user()))
			<div class="ajaxFormWrapper">
	   			<div class="btn-group add-bottom-margin">
   					{{ Form::post_button( array('route' => array('editAssignmentDeleteQuestionAnswersAjax','assignment_id' => $assignment->id, 'question_id' => $question->id ) ),'Delete answers','This will delete all student answers and correspondings marks, etc. for this question. Are you sure you want to delete these irreversibly?') }}
   				</div>
   			</div>
   			@endif
			
			@if($question->isMaster() && $assignment->mayAddQuestions(Auth::user()))
   			<div class="btn-group add-bottom-margin">
    		{{ Form::post_button_primary(
				array('route' => array('editAssignmentAddQuestionAjax','assignment_id' => $assignment->id, 'type' => QuestionType::subquestion, 'master_question_id' => $question->id ) ), 
				'Add sub-question') 
			}}
			</div>
			@endif
			
		</div>
      	
		
		<div class="col-md-9 col-lg-10" role="tabpanel">

	  		<!-- Nav tabs -->
  			<ul class="nav nav-tabs" role="tablist">
    			@if($question->isMaster())
	  			<li role="presentation" class="active"><a data-target="#question_{{{ $question->id }}}_question" role="tab" data-toggle="tab">Introduction</a></li>
	  			@else
	  			<li role="presentation" class="active"><a data-target="#question_{{{ $question->id }}}_question" role="tab" data-toggle="tab">Question text</a></li>
    			{{-- <li role="presentation"><a data-target="#question_{{{ $question->id }}}_restrictions" role="tab" data-toggle="tab">Answer restrictions</a></li> --}}
	  			<li role="presentation"><a data-target="#question_{{{ $question->id }}}_solution" role="tab" data-toggle="tab">Solution</a></li>
	  			<li role="presentation"><a data-target="#question_{{{ $question->id }}}_marking_scheme" role="tab" data-toggle="tab">Marking scheme</a></li>
	    		@endif
  			</ul>

	  		<!-- Tab panes -->
  			<div class="tab-content">
    			<div role="tabpanel" class="tab-pane fade in active" id="question_{{{ $question->id }}}_question">
    				@include('textblock.textblock_show_insert', array('textBlock' => $question->text, 'showEditLink' => $assignment->mayEditQuestions(Auth::user()), 'role' => TextBlockRole::question))
	    		</div>
    			@if(!$question->isMaster())
	    		{{--<div role="tabpanel" class="tab-pane fade" id="question_{{{ $question->id }}}_restrictions">
    				<strong>Not implemented yet.</strong>
    			</div>--}}
    			<div role="tabpanel" class="tab-pane fade" id="question_{{{ $question->id }}}_solution">
    				@include('textblock.textblock_show_insert', array('textBlock' => $question->solution, 'showEditLink' => $question->mayEditSolution(Auth::user()), 'role' => TextBlockRole::questionsolution))
    			</div>
    			<div role="tabpanel" class="tab-pane fade" id="question_{{{ $question->id }}}_marking_scheme">
    				@include('textblock.textblock_show_insert', array('textBlock' => $question->marking_scheme, 'showEditLink' => $assignment->mayEditMarkingSchemes(Auth::user()), 'role' => TextBlockRole::markingscheme))
    			</div>
	    		@endif
    		</div>

		</div>		
	  	</div>
	  	
	  </div>
    </div>
</div>

