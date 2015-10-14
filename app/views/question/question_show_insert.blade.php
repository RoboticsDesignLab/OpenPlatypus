
<div class="row add-bottom-margin">
@if($question->isSubquestion())
<div class="col-md-offset-1 col-md-1">
<strong>{{{ $question->position }}})</strong>
<br>
{{{ $question->mark_percentage_global }}}%
</div>
@else
<div class="col-md-12">
<h4><strong>Question {{{ $question->position }}}:</strong></h4>
</div>
<div class="col-md-1">
{{{ $question->mark_percentage_global }}}%
</div>
@endif

<div class="{{ $question->isSubquestion() ? 'col-md-10' : 'col-md-11' }}">
	@include('textblock.textblock_show_insert', array('textBlock' => $question->text, 'showEditLink' => false, 'role' => TextBlockRole::question))
</div>

</div>