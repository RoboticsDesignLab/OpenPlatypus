<div class="{{ $question->isSubquestion() ? 'col-md-11 col-md-offset-1' : 'col-md-12' }}">
<?php
$panelClass = 'panel-default';
if (!$question->isMaster()) {
	if($answer->submitted) {
		$panelClass = 'panel-success';
	} else if (!$answer->isEmpty()) {
		$panelClass = 'panel-warning';
	}
}

$groupAnswer=$question->getSubmittedGroupAnswer(Auth::user());

?>
<div class="panel {{{ $panelClass }}}">
	<div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#answerQuestion_{{{ $question->id }}}">
          	@if($question->isSubquestion())
          		Question {{{ $question->master_question->presenter()->position }}}.{{{ $question->position }}})
          	@else 
            	Question {{{ $question->position }}}:
          	@endif
        </a>
      </h4>
    </div>
    <div id="answerQuestion_{{{ $question->id }}}" class="panel-collapse collapse in">
      <div class="panel-body">
      	<div class="row">
      	<div class="col-md-2">

      		<div class="row">
   			<div class="col-md-12 btn-group add-bottom-margin">
      			<div>{{{ $question->mark_percentage }}}%</div>
      		
      		@if($question->isSubquestion())
				<div>({{{ $question->mark_percentage_global }}}% of assignment)</div>
			@endif
			</div>	
			</div>
   			
   			@if(!$question->isMaster())
				<div class="ajaxFormWrapper updatable" data-resourceid="submit_button_{{{ $answer->id }}}">
   					@include('answer.answer_submitButton_insert', array('answer' => $answer))
   				</div>
   			@endif
   			
			
		</div>
      	
		
		<div class="col-md-10" role="tabpanel">

		@if($question->isMaster())
			@include('textblock.textblock_show_insert', array('textBlock' => $question->text, 'showEditLink' => false, 'role' => TextBlockRole::question))
	  	@else
			
	  		<!-- Nav tabs -->
  			<ul class="nav nav-tabs" role="tablist">
	  			<li role="presentation" class="{{{ (!isset($groupAnswer) && $answer->text->isempty()) ? 'active' : '' }}}"><a data-target="#question_{{{ $question->id }}}_question" role="tab" data-toggle="tab">Question</a></li>
    			<li role="presentation" class="{{{ (!isset($groupAnswer) && !$answer->text->isempty()) ? 'active' : '' }}}"><a data-target="#question_{{{ $question->id }}}_answer" role="tab" data-toggle="tab">Your answer {{{ $answer->submitted ? '' : '(not submitted)' }}}</a></li>
    			@if(isset($groupAnswer))
	    			<li role="presentation" class="active"><a data-target="#question_{{{ $question->id }}}_group_answer" role="tab" data-toggle="tab">Group submission</a></li>
	    		@endif
  			</ul>

	  		<!-- Tab panes -->
  			<div class="tab-content">
    			<div role="tabpanel" class="tab-pane fade {{{ (!isset($groupAnswer) && $answer->text->isempty()) ? 'in active' : '' }}}" id="question_{{{ $question->id }}}_question">
    				@include('textblock.textblock_show_insert', array('textBlock' => $question->text, 'showEditLink' => false, 'role' => TextBlockRole::question))
	    		</div>
	    		<div role="tabpanel" class="tab-pane fade {{{ (!isset($groupAnswer) && !$answer->text->isempty()) ? 'in active' : '' }}}" id="question_{{{ $question->id }}}_answer">
    				@include('textblock.textblock_show_insert', array('textBlock' => $answer->text, 'showEditLink' => $answer->mayEdit(Auth::user()), 'role' => TextBlockRole::studentanswer))
    				@if($answer->isSubmitted())
    					<div class="text-right">
    						<small>Submitted on {{{ $answer->presenter()->time_submitted }}}</small>
    					</div>
    				@endif
    			</div>
    			@if(isset($groupAnswer))
    			<div role="tabpanel" class="tab-pane fade in active" id="question_{{{ $question->id }}}_group_answer">
    				@include('textblock.textblock_show_insert', array('textBlock' => $groupAnswer->text, 'showEditLink' => false, 'role' => TextBlockRole::studentanswer))
    				<div class="text-right">
    					<small>Submitted on {{{ $groupAnswer->presenter()->time_submitted }}} by {{{ $groupAnswer->user->presenter()->name }}}</small>
    				</div>
    			</div>
	    		@endif
    			
    			
    			
    		</div>

	    @endif
    		
    		
		</div>		
	  	</div>
	  	
	  </div>
    </div>
</div>
</div>