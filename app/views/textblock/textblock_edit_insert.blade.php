
<span style="display:none;" class="ifExistsSelector" data-if-exists-all=".empty_text_textblock_{{{$textBlock->id }}} .empty_files_textblock_{{{$textBlock->id }}}" data-if-exists-classes="empty_textblock_{{{ $textBlock->id }}}"></span>

<div class="ajaxFormWrapper">
@include('textblock.textblock_editDefault_insert', array('textBlock' => $textBlock, 'role' => $role))
</div>

<hr>
<div class="ajaxFormWrapper">
	@include('textblock.textblock_editAttachments_insert', array('textBlock' => $textBlock, 'showEditLink' => true, 'role' => $role))
</div>
