

<?php
	if(isset($reviews) &&  (count($reviews)>0) ) {
		$panelId = "review_group_panel_".$reviews[0]->id;
	} else {
		$panelId = "answer_group_panel_".$answers[0]->id;
	}
?>
<div class="panel panel-default add-large-bottom-margin" data-resourceid="{{{ $panelId }}}">

@if(isset($reviews))
	@foreach($reviews as $review)
   		@include('review.review_edit_insert', array(
   			'review' => $review, 
   			'options' => $options,
		))
   	@endforeach
@endif

@if(isset($answers))
	@foreach($answers as $answer)
   		@include('review.review_edit_insert', array(
   			'answer' => $answer, 
   			'options' => $options,
		))
   	@endforeach
@endif

</div>	
	
