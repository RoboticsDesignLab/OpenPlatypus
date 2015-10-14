<?php 
	if(!isset($border)) {
		$border = true;
	}

	if(!isset($attachmentPathPrefix)) {
		$attachmentPathPrefix = '';
	}
	
	if(isset($showSolution) && $showSolution) {
		$fieldname = 'solution';
		$hasFunction = 'hasSolution';
		$role = TextBlockRole::questionsolution;
		$attachmentPathPrefix .= 'solution';
	} else if (isset($showMarkingScheme) && $showMarkingScheme) {
		$fieldname = 'marking_scheme';
		$hasFunction = 'hasMarkingScheme';
		$role = TextBlockRole::markingscheme;
		$attachmentPathPrefix .= 'markingscheme';
	} else {
		App::abort(500,'we should never reach this point.');
	}

?>


@if($border)
<div class="panel panel-default">
<div class="panel-body">
@endif


    	@foreach($assignment->getQuestionsOrderedWithSubquestions() as $question)
    		@if($question->$hasFunction())
    			<div class="row add-bottom-margin">
					@if($question->isSubquestion())
						<div class="col-md-offset-1 col-md-1">
							<strong>{{{ $question->position }}})</strong>
						</div>
					@else
						<div class="col-md-12">
							<h4><strong>Question {{{ $question->position }}}:</strong></h4>
						</div>
					@endif

					@if(!$question->$fieldname->isEmpty())
						<div class="{{ $question->isSubquestion() ? 'col-md-10' : 'col-md-11 col-md-offset-1' }}">
							@include('textblock.textblock_show_insert', array('textBlock' => $question->$fieldname, 'showEditLink' => false, 'role' => $role, 'attachmentPathPrefix' => $attachmentPathPrefix.$question->presenter()->full_position.'_'))
						</div>
					@endif

				</div>
    		@endif
    	@endforeach
    	
    	
    	
@if($border)
</div>
</div>
@endif
