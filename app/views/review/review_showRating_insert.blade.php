		
@if($review->isRated())
	<div class="add-bottom-margin">
		<span style="font-size: 300%;">{{ $review->review_rating_glyph }}</span><br>
		<small>This review is {{{$review->review_rating}}}.</small>
	</div>
@endif
