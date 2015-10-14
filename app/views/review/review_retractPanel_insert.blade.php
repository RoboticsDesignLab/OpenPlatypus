@if($review->mayRetractReview(Auth::user()))
	<div class="panel panel-default ReviewRetractionPanel">
  		<div class="panel-body">
  		
	  		<div class="row">
				<div class="col-md-2">
				
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
				
				<div class="col-md-10">
					@include('review.review_showContent_insert',array('review',$review))
				</div>
		
			</div>
		</div>
	</div>
@endif