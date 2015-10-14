<?php
	if(!isset($review)) {
		$review = null;
	}

	if (isset($review)) {
		$answer = $review->answer;
	}
	
	if(!isset($options['showEditorIfEmpty'])) {
		$options['showEditorIfEmpty'] = false;
	} 
	
	if(!isset($options['alwaysShowEditor'])) {
		$options['alwaysShowEditor'] = false;
	}
	
	if(!isset($options['ergonomic'])) {
		$options['ergonomic'] = false;
	} 

	
	$offerFinalMark = $answer->assignment->maySetFinalMarks(Auth::user());
	
	if ($options['ergonomic']) {
		if ( (isset($review) && !$review->isEmpty()) || ($offerFinalMark && $answer->hasFinalMark()) ) $options['ergonomic'] = false;
	}
	

	
	
?>


@if($options['ergonomic'])
	@include('review.review_edit_real_ergonomic_insert', array('options' => $options, 'review' => $review, 'answer' => $answer) )
@else


  <?php
	

	
	$mean = null;
	$median = null;
	if ($offerFinalMark) {
		$reviewMarks = array ();
		foreach ( $answer->question->getAllCompletedReviewsForUser($answer->user->resource) as $item ) {
			if ($item->isLecturerReview()) continue; // skip lecturer reviews.
			$reviewMarks [] = $item->mark;
		}
		
		if (! empty($reviewMarks)) {
			$mean = array_mean($reviewMarks);
			$median = array_median($reviewMarks);
		}
	}
		
	
  ?>


		<div class="row {{{ (isset($review) && $review->isCompleted()) ? ('review_complete_'.$review->id) : '' }}}">
			<div class="{{{ $offerFinalMark ? 'col-md-4 col-lg-3' : 'col-md-3 col-lg-2' }}}">
			
				@if(isset($review))
					<div class="add-bottom-margin">
						<div class="ajaxFormWrapper">
							@include('review.review_edit_mark_insert', array('review' => $review))
						</div>
					</div>

					<div class="add-bottom-margin">
						<div class="ajaxFormWrapper">
							@include('review.review_edit_flag_insert', array('review' => $review))
						</div>
					</div>
				@endif
				
				
				@if(isset($review) && $review->mayRetractReview(Auth::user()))
				<div class="add-bottom-margin">
				
					{{ Form::post_button( array(
							'url' => route('retractReviewAjax', array('review_id' => $review->id)). ( isset($options['urlQueryString']) ? '?'.$options['urlQueryString'] : '' ),
							'class' => 'post_button_block',
						),
						"Retract review now",
						array(
							'class' => "btn-block",
							'data-confirmationdialog' => 'Are you sure you want to retract this review?',					
						)
					) }}
					
				</div>
				@endif				
				
				@if(isset($review) && $review->maySubmitReview(Auth::user()))
				
					{{ Form::open(array('url' => route('submitReviewAjax', array('review_id' => $review->id)). ( isset($options['urlQueryString']) ? '?'.$options['urlQueryString'] : '' ))) }}
					<input type="hidden" name="button" value="">
	 				<div class="add-bottom-margin">
				
							
						<button 
							type="submit"
							name="button"
							value="normal-review"
							class="btn {{{ $offerFinalMark ? 'btn-default' : 'btn-primary' }}} btn-block ifExistsSelector"
							data-if-exists=".changed_textblock_{{{$review->text->id}}},.empty_textblock_{{{$review->text->id}}},.noMarkForReview_{{{$review->id}}}"
							data-if-exists-classes="disabled"
							@if($review->isStudentReview())
								data-confirmationdialog="Do you want to submit the review now? You cannot make changes once the review is submitted."
							@endif
						>
							{{{ $offerFinalMark ? 'Submit as normal review' : 'Submit review now' }}}
						</button>
						
					</div>
					
					@if($review->isStudentReview())
						<div 
							class="ifExistsSelector"
							data-if-exists=".noMarkForReview_{{{$review->id}}},.changed_textblock_{{{$review->text->id}}}"
							data-if-exists-classes="hidden"
						>
							<div 
								class="add-bottom-margin ifExistsSelector"
								data-if-exists=".empty_textblock_{{{$review->text->id}}}"
								data-if-exists-classes="hidden"
							>
								<div class="alert alert-danger" role="alert">
  									<strong>Important:</strong> you must press the submit button in order to finalise your peer review.
								</div>
							</div>
		
							<div 
								class="add-bottom-margin ifExistsSelector"
								data-if-exists=".empty_textblock_{{{$review->text->id}}}"
								data-if-not-exists-classes="hidden"
							>
								<div class="alert alert-warning" role="alert">
  									<strong>Important:</strong> you must enter text to complete the review.
								</div>
							</div>				
						</div>		
					@endif
					
					@if($offerFinalMark)
					
					<?php $mark = $answer->question->getUserMarkModel($answer->user->resource); ?>
					<div class="add-bottom-margin">
						@if(isset($mark))
							<span class="marking_complete_{{{ $answer->id }}}" style="display: none;"></span>
							<h1><small>Current final mark:</small><br>{{{ $mark->presenter()->mark }}}%</h1>
						@endif
					</div>						
				
					<div class="add-bottom-margin">	
						<button 
							type="submit"
							name="button"
							value="submit-final-mark"
							class="btn btn-primary btn-block ifExistsSelector"
							data-if-exists=".changed_textblock_{{{$review->text->id}}},.noMarkForReview_{{{$review->id}}}"
							data-if-exists-classes="disabled"
						>
							Submit review as final mark
						</button>
					</div>
					
					@if(isset($mean))
					<div class="add-bottom-margin">
						<input type="hidden" name="mean" value="{{{ $mean }}}">	
						<button 
							type="submit"
							name="button"
							value="submit-final-mark-mean"
							class="btn btn-primary btn-block ifExistsSelector"
							data-if-exists=".changed_textblock_{{{$review->text->id}}},.noMarkForReview_{{{$review->id}}}"
							data-if-exists-classes="disabled"
						>
							Final mark {{{ roundPercentage($mean) }}}% (mean)
						</button>
					</div>			
					@endif		
					
					@if(isset($median))
					<div class="add-bottom-margin">
						<input type="hidden" name="median" value="{{{ $median }}}">	
						<button 
							type="submit"
							name="button"
							value="submit-final-mark-median"
							class="btn btn-primary btn-block ifExistsSelector"
							data-if-exists=".changed_textblock_{{{$review->text->id}}},.noMarkForReview_{{{$review->id}}}"
							data-if-exists-classes="disabled"
						>
							Final mark {{{ roundPercentage($median) }}}% (median)
						</button>
					</div>			
					@endif		
										
					@endif
					
					{{ Form::close() }}
					
				@else
				
					@if($offerFinalMark)
					
					@if(isset($review))
						<hr>
					@endif
					
					<?php $mark = $answer->question->getUserMarkModel($answer->user->resource); ?>
					<div class="add-bottom-margin">
						@if(isset($mark))
							<span class="marking_complete_{{{ $answer->id }}}" style="display: none;"></span>
							<h1><small>Final mark:</small><br>{{{ $mark->presenter()->mark }}}%</h1>
						@else
							<strong>No final mark has been set yet.</strong>
						@endif
					</div>					
					
					{{ Form::open(array('url' => route('setFinalQuestionMarkAjax', array('question_id' => $answer->question_id, 'user_id' => $answer->user_id)). ( isset($options['urlQueryString']) ? '?'.$options['urlQueryString'] : '' ))) }}
	 					<div class="add-bottom-margin">
				
							<div class="add-bottom-margin">
								<label class="control-label" for="mark">Final mark in %</label>
   								<input class="form-control" type="mark" value="" name="mark"></input>
   							</div>
						
						</div>
					
						<div class="add-bottom-margin">	
							<button	type="submit" class="btn btn-primary btn-block">Set final mark</button>
						</div>
					{{ Form::close() }}	
					
					@if(isset($review) && $review->isCompleted())
						{{ Form::open(array('url' => route('setFinalQuestionMarkAjax', array('question_id' => $answer->question_id, 'user_id' => $answer->user_id)). ( isset($options['urlQueryString']) ? '?'.$options['urlQueryString'] : '' ))) }}
							<div class="add-bottom-margin">
								<input type="hidden" name="mark" value="{{{ $review->resource->mark }}}">	
								<button	type="submit" class="btn btn-primary btn-block">Final mark {{{ roundPercentage($review->resource->mark) }}}%</button>
							</div>		
						{{ Form::close() }}	
					@endif		
					
					@if(isset($mean))
						{{ Form::open(array('url' => route('setFinalQuestionMarkAjax', array('question_id' => $answer->question_id, 'user_id' => $answer->user_id)). ( isset($options['urlQueryString']) ? '?'.$options['urlQueryString'] : '' ))) }}
							<div class="add-bottom-margin">
								<input type="hidden" name="mark" value="{{{ $mean }}}">	
								<button	type="submit" class="btn btn-primary btn-block">Final mark {{{ roundPercentage($mean) }}}% (mean)</button>
							</div>		
						{{ Form::close() }}	
					@endif		
					
					@if(isset($median))
						{{ Form::open(array('url' => route('setFinalQuestionMarkAjax', array('question_id' => $answer->question_id, 'user_id' => $answer->user_id)). ( isset($options['urlQueryString']) ? '?'.$options['urlQueryString'] : '' ))) }}
							<div class="add-bottom-margin">
								<input type="hidden" name="mark" value="{{{ $median }}}">	
								<button	type="submit" class="btn btn-primary btn-block">Final mark {{{ roundPercentage($median) }}}% (median)</button>
							</div>		
						{{ Form::close() }}	
					@endif		
					
					
								
					@endif
					
				@endif
				
				
			</div>
			
			<div class="{{{ $offerFinalMark ? 'col-md-8 col-lg-9' : 'col-md-9 col-lg-10' }}}">
				@if(isset($review))
					@if($options['alwaysShowEditor'] || ($options['showEditorIfEmpty'] && $review->mayEditTextAndMark(Auth::user()) && $review->text->isEmpty()))
						@include('textblock.textblock_edit_insert', array('textBlock' => $review->text, 'role' => TextBlockRole::review))
					@else
						@include('textblock.textblock_show_insert', array('textBlock' => $review->text, 'showEditLink' => $review->mayEditTextAndMark(Auth::user()), 'role' => TextBlockRole::review))
					@endif
				@else
				
					{{ Form::post_button( array(
							'url' => route('addAdHocReviewAjax', array('question_id' => $answer->question->id, 'user_id' => $answer->user_id)). ( isset($options['urlQueryString']) ? '?'.$options['urlQueryString'] : '' ),
						),
						"Write a review"
					) }}
					
				@endif
			
			</div>
			
			@if(isset($review))
				<div class="col-md-12 text-right">
					<small>review #{{{ $review->id }}}</small>
				</div>
			@endif
			
	  	</div>
	  	
@endif

