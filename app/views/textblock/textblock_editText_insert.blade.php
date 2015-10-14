@if(empty($textBlock->text))
<span style="display:none;" class="empty_text_textblock_{{{$textBlock->id }}}"></span>
@endif

{{ Form::open_horizontal(array('route' => array('editTextBlockTextAjax','textblock_id' => $textBlock->id, 'role' => $role ) ) ) }}

{{ Form::hidden('content_type', TextBlockContentType::plain) }}

{{ Form::textarea_group('text', '', $textBlock->text_edit_plain, $errors, array(
		'class' => ($showAsChanged ? ('preventPageLeave changed changed_textblock_'.$textBlock->id) : null), 
		'data-on-change-add-classes' => 'preventPageLeave changed changed_textblock_'.$textBlock->id 
	), null, false) }}

{{ Form::submit_group(array('submit_title' => 'Save changes', 'cancel_url' => route('showTextBlockTextAjax', array('textblock_id' => $textBlock->id, 'role' => $role)), 'cancel_title' => 'Cancel' ), null, false) }}
				

{{ Form::close() }}



