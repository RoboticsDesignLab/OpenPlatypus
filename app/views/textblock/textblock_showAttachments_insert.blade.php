<?php
 
if(!isset($renderForArchive)) {
	$renderForArchive  =false;
}

?>
@if(count($textBlock->attachments) > 0)
 	<ul class="list-group noAjax">
 		<?php $textBlock->injectCachedRelation('attachments_ordered', $textBlock->attachmentsOrdered()->with('diskFile')->get()); ?>
		@foreach($textBlock->attachments_ordered as $attachment)
			<?php
				if($renderForArchive) {
					$downloadUrl = $attachment->getArchiveFileName($attachmentPathPrefix);
					$viewUrl = $downloadUrl;
					$addArchiveFile($downloadUrl, $attachment->fullDiskFileName());
				} else {
					$downloadUrl = route('downloadTextBlockAttachment',array('textblock_id' => $textBlock->id, 'role' => $role, 'attachment_id' => $attachment->id, 'file_name' => $attachment->file_name));
					$viewUrl = route('viewTextBlockAttachment',array('textblock_id' => $textBlock->id, 'role' => $role, 'attachment_id' => $attachment->id, 'file_name' => $attachment->file_name));
				}
			
			?>
 			<li class="list-group-item containsFloating">
 				<a class="pull-right" href="{{{ $downloadUrl }}}"><button class="btn btn-default btn-sm"><span class="glyphicon glyphicon-download-alt"></span></button></a>
 				{{-- <a class="pull-right" href="{{{ route('viewTextBlockAttachment',array('textblock_id' => $textBlock->id, 'role' => $role, 'attachment_id' => $attachment->id, 'file_name' => $attachment->file_name)) }}}" {{{ $renderForArchive ? '' : 'target="_blank"' }}}><button class="btn btn-default btn-sm"><span class="glyphicon glyphicon-eye-open"></span></button></a> --}}
 				<a href="{{{ $viewUrl }}}" {{{ $renderForArchive ? '' : 'target="_blank"' }}}>{{{ $attachment->file_name }}}</a>
 				({{{ $attachment->presenter()->size }}})
 			</li>	
		@endforeach
	</ul>
@else
	<span style="display:none;" class="empty_files_textblock_{{{$textBlock->id }}}"></span>
@endif
