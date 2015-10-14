<?php

	if(isset($review)) {
		$answer = $review->answer;
	}

	$offerFinalMark = $answer->assignment->maySetFinalMarks(Auth::user());

	
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

{{ Form::open(array(
	'url' => route('submitErgonomicReviewAjax', array('question_id' => $answer->question_id, 'user_id' => $answer->user_id)). ( isset($options['urlQueryString']) ? '?'.$options['urlQueryString'] : '' ),
	'autocomplete' => 'off',
)) }}
<input type="hidden" name="button" value="">

		<div class="row">
			<div class="{{{ $offerFinalMark ? 'col-md-4 col-lg-3' : 'col-md-3 col-lg-2' }}}">
			
	 				<div class="add-bottom-margin">
				

						
						<div class="add-bottom-margin">
							<label class="control-label" for="mark">Mark in %</label>
    						<input class="form-control" type="mark" value="" name="mark">
    					</div>
						
							
						<button 
							type="submit"
							name="button"
							value="normal-review"
							class="btn {{{ $offerFinalMark ? 'btn-default' : 'btn-primary' }}} btn-block"
						>
							{{{ $offerFinalMark ? 'Submit as normal review' : 'Submit review now' }}}
						</button>
						
					</div>
					
					@if($offerFinalMark)
					
				
						<div class="add-bottom-margin">	
							<button	type="submit" name="button"	value="submit-final-mark" class="btn btn-primary btn-block">Submit as final mark</button>
						</div>
					
						@if(isset($mean))
							<div class="add-bottom-margin">
								<input type="hidden" name="mean" value="{{{ $mean }}}">	
								<button	type="submit" name="button"	value="submit-final-mark-mean"	class="btn btn-primary btn-block">
									Final mark {{{ roundPercentage($mean) }}}% (mean)
								</button>
							</div>			
						@endif		
					
						@if(isset($median))
							<div class="add-bottom-margin">
								<input type="hidden" name="median" value="{{{ $median }}}">	
								<button	type="submit" name="button"	value="submit-final-mark-median" class="btn btn-primary btn-block">
									Final mark {{{ roundPercentage($median) }}}% (median)
								</button>
							</div>			
						@endif		
										
					@endif
					
				
			</div>
			
			<div class="{{{ $offerFinalMark ? 'col-md-8 col-lg-9' : 'col-md-9 col-lg-10' }}} form-horizontal">
				
				
				{{ Form::textarea_group(
					'text', 
					'', 
					'', 
					$errors, 
					array(
						'class'=>'ckeditor_manual noAjax', 
						'data-on-change-add-classes' => 'changed' ), 
					null, 
					false) 
				}}
				
				
				<div class="form-group"><div class="col-md-12 add-top-margin">
					<button	type="submit" name="button"	value="full-editor" class="btn btn-default">Show fully featured editor</button>
				</div></div>
		
				
				
			</div>
		
			@if(isset($review))	
				<div class="col-md-12 text-right">
					<small>review #{{{ $review->id }}}</small>
				</div>
			@endif
			
	  	</div>

{{ Form::close() }}
	  	
	  	