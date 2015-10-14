@if($review->mayEditTextAndMark(Auth::user()))
<div class="dropdown">
	<button class="btn {{{ is_null($review->mark) ? ("btn-primary noMarkForReview_".$review->id) : "btn-default" }}} btn-block dropdown-toggle" data-toggle="dropdown">
		@if(is_null($review->mark))
			Set mark
		@else
			<strong>Mark: {{{ $review->mark }}}%</strong>
		@endif
		<span class="caret"></span>
	</button>
	
	<div class="dropdown-menu">

      	<div class="row add-margins">
      	<div class="col-md-12 add-bottom-margin">
      	
		{{ Form::open(array('route' => array('setReviewMarkAjax', 'review_id' => $review->id),'autocomplete' => 'off') ) }}

    	<label class="control-label" for="mark">Mark in %</label>
    	<input class="form-control" type="mark" value="" name="mark"></input>
				
		
		<input class="btn btn-primary" type="submit" value="Save"></input>
		
		{{ Form::close() }}
		
		</div>

		</div>
		
	</div>
</div>
@else
	<div>
		@if(is_null($review->mark))
			---
		@else
			<h1><small>Review mark:</small><br>{{{ $review->mark }}}%</h1>
		@endif
	</div>
@endif
			
