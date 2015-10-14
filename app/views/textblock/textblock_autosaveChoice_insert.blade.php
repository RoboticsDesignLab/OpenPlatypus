
@if(empty($textBlock->text))
<span style="display:none;" class="empty_text_textblock_{{{$textBlock->id }}}"></span>
@endif

	
<div class="well noAjax userContent add-bottom-margin">
	{{ $autosave->presenter()->text }}
</div>
		
<div class="add-bottom-margin">
	Previously you left without saving your work. Would you like to continue editing your old version as shown here?
</div>
<div>	
	
	{{ Form::post_button_primary(
		array('route' => array('editTextBlockTextRestoreAutosaveAjax', 'textblock_id' => $textBlock->id, 'role' => $role)),
		'Restore unsaved version'
	) }}

	{{ Form::post_button(
		array('route' => array('editTextBlockTextDiscardAutosaveAjax', 'textblock_id' => $textBlock->id, 'role' => $role)),
		'Discard unsaved changes'
	) }}

</div>
