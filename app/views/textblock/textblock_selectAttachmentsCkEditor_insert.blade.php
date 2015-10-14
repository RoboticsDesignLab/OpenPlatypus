@if(count($textBlock->attachments) > 0)
 	<ul class="list-group noAjax">
		@foreach($textBlock->attachments_ordered as $attachment)
 			<li class="list-group-item">
 				<a class="ckeditorFileChoice" href="{{{ route('viewTextBlockAttachment',array('textblock_id' => $textBlock->id, 'role' => $role, 'attachment_id' => $attachment->id, 'file_name' => $attachment->file_name)) }}}" target="_blank" data-ckeditorattachmentlinkid="{{{$attachment->id}}}">{{{ $attachment->file_name }}}</a>
 			</li>	
		@endforeach
	</ul>
@else
	@if($textBlock->mayAddAttachment(Auth::user(), $role))
		<p><strong>No files have been uploaded yet.</strong></p>
		
	@else
		<strong>File upload has been deactivated.</strong>
	@endif
@endif

@if($textBlock->mayAddAttachment(Auth::user(), $role))
	<div style="margin-top: 25px;">
	{{ Form::open(array('route' => array('uploadTextBlockAttachmentCkEditorAjaxPost', $textBlock->id, $role), 'files' => true)) }}
	
	{{ Form::file('upload_file') }}

	<a href="javascript:void(0)" onclick="$(this).closest('form').submit();" class="noAjax cke_dialog_ui_button" style="margin-top: 10px;"><span class="cke_dialog_ui_button">Upload now</span></a>

	<div style="margin-top: 10px;">Note: the maximum file size is {{{ ini_get('upload_max_filesize') }}}B.</div>
	
	{{ Form::close() }}
	</div>
@endif

