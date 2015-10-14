
<?php
	if(isset($review)) {
		$answer = $review->answer;
	} else {
		$review = $answer->getReview(Auth::user());
	}
	
	$question = $answer->question; 
		
	if(!isset($options['showEditorIfEmpty'])) {
		$options['showEditorIfEmpty'] = false;
	}
	
	
	$useWide = $options['useWide'];
	
	$relatedReviews = array();
	
	if(isset($review)) {
		if ($options['showRelatedStudentReviews']) {
			foreach($review->related_student_reviews as $item) {
				$relatedReviews[] = $item;
			}
		}
	
		if ($options['showRelatedTutorReviews']) {
			foreach($review->related_tutor_reviews as $item) {
				$relatedReviews[] = $item;
			}
		}
		
	} else {
		
			if ($options['showRelatedStudentReviews']) {
			foreach($answer->reviews as $item) {
				if($item->isStudentReview()) {
					$relatedReviews[] = $item;
				}
			}
		}

		if ($options['showRelatedTutorReviews']) {
			foreach($answer->reviews as $item) {
				if($item->isTutorReview()) {
					$relatedReviews[] = $item;
				}
			}
		}

	}
	
	if(isset($review)) {
		$domUid = "v1_".$review->id;
	} else {
		$domUid = "v2_".$answer->id;
	}
	
	
?>

{{--<div class="panel panel-default">--}}

    <div class="panel-heading ifExistsSelector" data-if-exists="{{{ (isset($review) && !$answer->assignment->maySetFinalMarks(Auth::user())) ? '.review_complete_'.$review->id : '.marking_complete_'.$answer->id }}}" data-if-exists-classes="success">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#showedit_panel_{{{ $domUid }}}">
           	Question {{{ $answer->question->presenter()->full_position }}}
        </a>
      </h4>
    </div>
    
    <?php
    	if($options['showCompleted']) {
			$showPanel = true;
		} else {
			if(isset($review)) {
				if($answer->assignment->maySetFinalMarks(Auth::user())) {
					$showPanel = !$review->isCompleted() || !$answer->hasFinalMark();
				} else {
					$showPanel = !$review->isCompleted();
				}
			} else {
				$showPanel = !$answer->hasFinalMark();
			}
		}
    ?>
    <div id="showedit_panel_{{{ $domUid }}}" class="panel-collapse collapse {{{ $showPanel ? 'in' : ''}}}">
      <div class="panel-body">

      
      	<div class="row">
      	
      		@if($useWide)
      			<div class="col-md-12 col-xl-6"><div class="row">
      		@endif
      	
      			<div class="col-md-12 add-bottom-margin">
      				<div class="row">
      	
      					<div class="col-md-3 col-lg-2">
					  		<!-- Nav tabs -->
  							<ul class="nav nav-tabs tabs-left" role="tablist">
	  							<li role="presentation" class="active"><a data-target="#showAnswer_{{{ $domUid }}}_answer" role="tab" data-toggle="tab">Student answer</a></li>
    							<li role="presentation"><a data-target="#showAnswer_{{{ $domUid }}}_question" role="tab" data-toggle="tab">Question</a></li>
    							@if(!$question->solution->isEmpty())
    								<li role="presentation"><a data-target="#showAnswer_{{{ $domUid }}}_solution" role="tab" data-toggle="tab">Solution</a></li>
    							@endif
    							@if(!$question->marking_scheme->isEmpty())
	  								<li role="presentation"><a data-target="#showAnswer_{{{ $domUid }}}_marking_scheme" role="tab" data-toggle="tab">Marking scheme</a></li>
	  							@endif
  							</ul>
						</div>
			
      	
						<div class="col-md-9 col-lg-10" role="tabpanel">
					  		<!-- Tab panes -->
  							<div class="tab-content">
  							
    							<div role="tabpanel" class="tab-pane fade in active" id="showAnswer_{{{ $domUid }}}_answer">
    								<div class="add-bottom-margin">
    									@include('textblock.textblock_show_insert', array('textBlock' => $answer->text, 'showEditLink' => false, 'role' => TextBlockRole::studentanswer))
    								</div>
    								@if($answer->assignment->maySetFinalMarks(Auth::user()))
    									@if($answer->hasGuessedMark())
    										<div class="row">
    											<div class="col-md-8 text-left">
    												<strong>The student estimated the mark as {{{ $answer->guessed_mark }}}%.</strong>
    											</div>
    											<div class="col-md-4 text-right">
    												<small>Submitted: {{{ $answer->time_submitted }}}</small>
    											</div>
    										</div>
    									@else
    										<div class="text-right">
    											<small>Submitted: {{{ $answer->time_submitted }}}</small>
    										</div>
    									@endif
    								@endif
    								@if($answer->assignment->mayBrowseStudents(Auth::user()))
    									<div class="text-right">
    										<a class="noAjax" href="{{{ route('assignmentBrowseStudentShow', array('assignment_id' => $answer->assignment->id, 'user_id' => $answer->user_id)) }}}">
    											<small>Browse this student</small>
    										</a>
    									</div>
    								@endif
	    						</div>
	    						
	    						<div role="tabpanel" class="tab-pane fade" id="showAnswer_{{{ $domUid }}}_question">
    								@include('textblock.textblock_show_insert', array('textBlock' => $question->text, 'showEditLink' => false, 'role' => TextBlockRole::question))
    							</div>
    							
    							@if(!$question->solution->isEmpty())
    								<div role="tabpanel" class="tab-pane fade" id="showAnswer_{{{ $domUid }}}_solution">
    									@include('textblock.textblock_show_insert', array('textBlock' => $question->solution, 'showEditLink' => false, 'role' => TextBlockRole::questionsolution))
    								</div>
    							@endif
    							
    							@if(!$question->marking_scheme->isEmpty())
    								<div role="tabpanel" class="tab-pane fade" id="showAnswer_{{{ $domUid }}}_marking_scheme">
    									@include('textblock.textblock_show_insert', array('textBlock' => $question->marking_scheme, 'showEditLink' => false, 'role' => TextBlockRole::markingscheme))
    								</div>
	    						@endif
	    						
    						</div>
						</div>
		
					</div>
				</div>
		
				@if($answer->assignment->maySetFinalMarks(Auth::user()))
					@if($answer->isLate())
						<div class="col-md-12">
							<div class="panel panel-danger">
								<div class="panel-body bg-danger text-center">
									<strong style="font-size: 175%;">
										This answer was submitted {{{ $answer->late_by }}} late.
									</strong>
		  						</div>
	  						</div>
	  					</div>
	  				@endif

	  				@foreach($relatedReviews as $related)
	  					@if($related->isCompleted() && $related->hasFlag())
							<div class="col-md-12">
								<div class="panel panel-danger">
									<div class="panel-body bg-danger text-center">
										<strong style="font-size: 175%;">
											A reviewer set a flag: {{{ $related->presenter()->flag }}}
										</strong>
		  							</div>
	  							</div>
	  						</div>
	  					@endif
	  				@endforeach
				@endif

				<div class="col-md-12">
					<div class="panel panel-default">
						<div class="panel-body">
							<div class="ajaxFormWrapper">
								@include('review.review_edit_real_insert', array('review' => $review, 'answer' => $answer, 'options' => $options))
							</div>
		  				</div>
	  				</div>
	  			</div>
			

      		@if($useWide)
      			</div></div>
      			<div class="col-md-12 col-xl-6"><div class="row">
      		@endif
	  			
  			@include('review.review_showSeveralReviews_insert', array('reviews' => $relatedReviews, 'options' => $options, 'offerRating' => true))

      		@if($useWide)
      			</div></div>
      		@endif
	  	</div>
	  	
	  </div>
    </div>
{{--</div>--}}

