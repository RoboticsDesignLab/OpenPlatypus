@if(empty($textBlock->text))
<span style="display:none;" class="empty_text_textblock_{{{$textBlock->id }}}"></span>
@endif

{{ Form::open_horizontal(array('route' => array('editTextBlockTextAjax','textblock_id' => $textBlock->id, 'role' => $role ) ) ) }}

{{ Form::hidden('content_type', TextBlockContentType::html) }}

{{ Form::textarea_group('text', '', $textBlock->text_edit_html, $errors, array(
		'class'=>'ckeditor_manual autosaveText'. ($showAsChanged ? (' preventPageLeave changed changed_textblock_'.$textBlock->id) : ''), 
		'data-autosave-url' => route('autosaveTextBlockTextAjax',array('textblock_id' => $textBlock->id, 'role' => $role )),
		'data-autosave-message-id' => '#savestatus_message_textblock_'.$textBlock->id,
		'data-inlinesave-url' => route('saveTextBlockInlineAjaxPost',array('textblock_id' => $textBlock->id, 'role' => $role )),
		'data-inlinesave-message-id' => '#savestatus_message_textblock_'.$textBlock->id,
		'data-ckeditor-file-selection-url' => route('showCkEditorAttachmentSelection',array('textblock_id' => $textBlock->id, 'role' => $role)), 
		'data-on-change-add-classes' => 'preventPageLeave changed changed_textblock_'.$textBlock->id
	 ), null, false) }}
	 
<div class="text-right">
<small id="savestatus_message_textblock_{{{$textBlock->id }}}">&nbsp;</small>
</div>

{{ Form::submit_group(array('submit_title' => 'Save changes', 'cancel_url' => route('showTextBlockTextAjax', array('textblock_id' => $textBlock->id, 'role' => $role)), 'cancel_title' => 'Cancel' ), null, false) }}
				

{{ Form::close() }}

