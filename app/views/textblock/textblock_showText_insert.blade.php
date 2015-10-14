<?php 
if(!isset($lazyMode)) {
	$lazyMode = TextBlockLazyLoadMode::smart; 
}

if($lazyMode == TextBlockLazyLoadMode::smart) {
	if (strlen($textBlock->resource->text) > 20480) {
		$lazyMode = TextBlockLazyLoadMode::defer;
	} else {
		$lazyMode = TextBlockLazyLoadMode::show;
	}
}

?>

@if(empty($textBlock->text))
<span style="display:none;" class="empty_text_textblock_{{{$textBlock->id }}}"></span>
@endif

@if($lazyMode == TextBlockLazyLoadMode::defer)
	<div class="ajaxContentLoader" data-url="{{{ route('showTextBlockTextAjax2', array("textblock_id" => $textBlock->id, "role" => $role, "showeditlink" => (int)$showEditLink) ) }}}">
		<strong>Loading...</strong>
	</div>
@else
	
	@if(!empty(trim($textBlock->resource->text)))
		<div class="noAjax userContent {{{ $showEditLink ? 'add-bottom-margin' : '' }}}">
			{{ $textBlock->text }}
		</div>
	@else
		<div class="updatable add-bottom-margin hidden-xs hidden-sm" data-resourceid="textblock_{{{$textBlock->id }}}_first_attachment">
			@if(count($textBlock->attachments_ordered) > 0)
				@include('textblock.textblock_showInlineAttachment_insert', array('textBlock' => $textBlock, 'role' => $role, 'attachment' => $textBlock->attachments_ordered[0]))
			@endif
		</div>
	@endif
		
	@if($showEditLink)
		<?php
		
			if($textBlock->isEmpty()) {
				if ($role == TextBlockRole::review) {
					$buttonText = 'Write review now';
				} else {
					$buttonText = 'Write text';
				}
			} else {
				$buttonText = 'Edit';
			}		
			
		?>
		<p>
			<a href="{{ route('editTextBlockTextAjax', array('textblock_id' => $textBlock->id, 'role' => $role)) }}" class="textBlockEditButton {{{ empty($textBlock->text) ? 'emptyTextBlockEditButton' : '' }}}" data-textblock-id="{{{ $textBlock->id }}}"><button class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span> {{{ $buttonText }}}</button></a>
		</p>
	@endif
@endif

