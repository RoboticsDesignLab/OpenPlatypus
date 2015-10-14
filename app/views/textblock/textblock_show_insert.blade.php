
<?php 
if(!isset($lazyMode)) {
	$lazyMode = TextBlockLazyLoadMode::smart; 
}

if(!isset($renderForArchive)) {
	$renderForArchive  =false;
}

if($renderForArchive) {
	$lazyMode = TextBlockLazyLoadMode::show;
	$showEditLink = false;
}

?>
<div class="add-bottom-margin">

@if(!$renderForArchive)
<span style="display:none;" class="ifExistsSelector" data-if-exists-all=".empty_text_textblock_{{{$textBlock->id }}} .empty_files_textblock_{{{$textBlock->id }}}" data-if-exists-classes="empty_textblock_{{{ $textBlock->id }}}"></span>
@endif

<div{{ $showEditLink ? ' class="ajaxFormWrapper"' : '' }}>
	@if($renderForArchive)
		@include('textblock.textblock_showText_archive_insert', array('textBlock' => $textBlock, 'role' => $role))
	@else
		@include('textblock.textblock_showText_insert', array('textBlock' => $textBlock, 'showEditLink' => $showEditLink, 'role' => $role, 'lazyMode' => $lazyMode))
	@endif
</div>

@if( $showEditLink )
	<hr>
	<div class="ajaxFormWrapper updatable" data-resourceid="textblock_attachment_editor_{{{$textBlock->id}}}">
		@include('textblock.textblock_editAttachments_insert', array('textBlock' => $textBlock, 'showEditLink' => $showEditLink, 'role' => $role))
	</div>
@else
	@include('textblock.textblock_showAttachments_insert', array('textBlock' => $textBlock, 'showEditLink' => $showEditLink, 'role', $role))
@endif

</div>