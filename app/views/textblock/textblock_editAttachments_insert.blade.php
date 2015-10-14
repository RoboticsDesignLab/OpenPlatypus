@if(count($textBlock->attachments) > 0)
 	<ul class="list-group noAjax">
		@foreach($textBlock->attachments_ordered as $attachment)
 			<li class="list-group-item containsFloating">
 				{{ Form::post_button_sm(array('route' => array('deleteTextBlockAttachment','textblock_id' => $textBlock->id, 'role' => $role, 'attachment_id' => $attachment->id), 'class' => 'doAjax pull-right') , '<span class="glyphicon glyphicon-trash"></span>', 'Do you really want to delete this file?') }}
 				{{ Form::post_button_sm(array('route' => array('moveTextBlockAttachment','textblock_id' => $textBlock->id, 'role' => $role, 'attachment_id' => $attachment->id, 'direction' => 1), 'class' => 'doAjax pull-right') , '<span class="glyphicon glyphicon-chevron-down"></span>') }}
 			 	{{ Form::post_button_sm(array('route' => array('moveTextBlockAttachment','textblock_id' => $textBlock->id, 'role' => $role, 'attachment_id' => $attachment->id, 'direction' => -1), 'class' => 'doAjax pull-right') , '<span class="glyphicon glyphicon-chevron-up"></span>') }}
 			 	<span class="pull-right">&nbsp;</span>
 			 	<a class="pull-right" href="{{{ route('downloadTextBlockAttachment',array('textblock_id' => $textBlock->id, 'role' => $role, 'attachment_id' => $attachment->id, 'file_name' => $attachment->file_name)) }}}"><button class="btn btn-default btn-sm"><span class="glyphicon glyphicon-download-alt"></span></button></a>
 				{{--<a class="pull-right" href="{{{ route('viewTextBlockAttachment',array('textblock_id' => $textBlock->id, 'role' => $role, 'attachment_id' => $attachment->id, 'file_name' => $attachment->file_name)) }}}" target="_blank"><button class="btn btn-default btn-sm"><span class="glyphicon glyphicon-eye-open"></span></button></a>--}}
 				<a href="{{{ route('viewTextBlockAttachment',array('textblock_id' => $textBlock->id, 'role' => $role, 'attachment_id' => $attachment->id, 'file_name' => $attachment->file_name)) }}}" target="_blank">{{{ $attachment->file_name }}}</a>
 				({{{ $attachment->presenter()->size }}})
 			</li>	
		@endforeach
	</ul>
@else
	<span style="display:none;" class="empty_files_textblock_{{{$textBlock->id }}}"></span>
@endif

<div class="unhide-group">

<div class="unhide-item">

	{{ Form::open_horizontal(array('route' => array('uploadTextBlockAttachmentAjaxPost', $textBlock->id, $role), 'files' => true )) }}

	{{ Form::file_group('upload_file', 'File:', $errors, array(), null, false) }}

	{{ Form::submit_group(array('submit_title' => 'Upload now'), array(), false) }}

	<div>Note: the maximum file size is {{{ ini_get('upload_max_filesize') }}}B.</div>

	{{ Form::close() }}

</div>

<div class="unhide-control">
	<button class="btn btn-default unhide-trigger"><span class="glyphicon glyphicon-paperclip"></span> Add file</button>
</div>


</div>