<?php 

$makeFlagChangingLink = function ($text, $flag, $confirmation = null) use($review) {

	if(!in_array($flag, $review->getAllowedFlags())) return '';
	if($flag == $review->resource->flag) return '';
	
	$attributes = array();
	$attributes['data-url'] = route('setReviewFlagAjax', array('review_id' => $review->id));
	$attributes['data-_token'] = csrf_token();
	$attributes['data-flag'] = $flag;
	$attributes['class'] = 'ajaxPost';
	if (!is_null($confirmation)) {
		$attributes['data-confirmationdialog'] = $confirmation;
	}
	return '<li>' . link_to('#', $text, $attributes ) . '</li>';
};

?>


@if($review->mayEditTextAndMark(Auth::user()))
<div class="dropdown">
	<button class="btn btn-default btn-block dropdown-toggle" data-toggle="dropdown">
		@if($review->hasFlag())
			{{{ $review->presenter()->flag }}}
		@else
			Flag this answer
		@endif
		<span class="caret"></span>
	</button>
	
	<ul class="dropdown-menu">

		{{ $makeFlagChangingLink('Clear flag', ReviewFlag::none) }}
		{{ $makeFlagChangingLink('Flag answer als plagiarised', ReviewFlag::plagiarised) }}
		{{ $makeFlagChangingLink('Flag answer for lecturer\'s attention', ReviewFlag::attention) }}
		{{ $makeFlagChangingLink('Flag answer as excellent', ReviewFlag::excellent) }}
		{{ $makeFlagChangingLink('Flag answer as exceptionally poor', ReviewFlag::poor) }}
		
	</ul>
</div>
@else
	@if($review->hasFlag())
		<div class="alert alert-danger">
			<strong>{{{ $review->presenter()->flag }}}</strong>
		
		</div>
	@endif
@endif
			
