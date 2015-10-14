
<div class="btn-group" role="group">
	<a class="ajaxPost btn {{{ ( $review->isRated() && ($review->resource->review_rating == -1000)) ? 'btn-success' : 'btn-default' }}}" data-url="{{{ route('rateReviewAjax', $review->id) }}}" data-_token="{{{ csrf_token() }}}" data-rating="-1000"><span class=" glyphicon glyphicon-ban-circle"></span></a>
	<a class="ajaxPost btn {{{ ( $review->isRated() && ($review->resource->review_rating ==  -100)) ? 'btn-success' : 'btn-default' }}}" data-url="{{{ route('rateReviewAjax', $review->id) }}}" data-_token="{{{ csrf_token() }}}" data-rating="-100"><span class="glyphicon glyphicon-thumbs-down"></span> <span class="glyphicon glyphicon-thumbs-down"></span></a>
	<a class="ajaxPost btn {{{ ( $review->isRated() && ($review->resource->review_rating ==   -50)) ? 'btn-success' : 'btn-default' }}}" data-url="{{{ route('rateReviewAjax', $review->id) }}}" data-_token="{{{ csrf_token() }}}" data-rating="-50"><span class="glyphicon glyphicon-thumbs-down"></span></a>
	<a class="ajaxPost btn {{{ ( $review->isRated() && ($review->resource->review_rating ==     0)) ? 'btn-success' : 'btn-default' }}}" data-url="{{{ route('rateReviewAjax', $review->id) }}}" data-_token="{{{ csrf_token() }}}" data-rating="0"><span class="glyphicon glyphicon-record"></span></a>
	<a class="ajaxPost btn {{{ ( $review->isRated() && ($review->resource->review_rating ==    50)) ? 'btn-success' : 'btn-default' }}}" data-url="{{{ route('rateReviewAjax', $review->id) }}}" data-_token="{{{ csrf_token() }}}" data-rating="50"><span class="glyphicon glyphicon-thumbs-up"></span></a>
	<a class="ajaxPost btn {{{ ( $review->isRated() && ($review->resource->review_rating ==   100)) ? 'btn-success' : 'btn-default' }}}" data-url="{{{ route('rateReviewAjax', $review->id) }}}" data-_token="{{{ csrf_token() }}}" data-rating="100"><span class="glyphicon glyphicon-thumbs-up"></span> <span class="glyphicon glyphicon-thumbs-up"></span></a>
</div>

@if($review->isRated())
	<a class="ajaxPost btn btn-default" data-url="{{{ route('rateReviewAjax', $review->id) }}}" data-_token="{{{ csrf_token() }}}" data-rating="unrated"><span class="glyphicon glyphicon-remove"></span></a>
@endif


{{--
@if($review->isRated())
	(This review is rated to be {{{ $review->review_rating }}})
@else
	(This review is unrated)
@endif
--}}

