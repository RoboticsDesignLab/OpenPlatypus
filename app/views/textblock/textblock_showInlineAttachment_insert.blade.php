<?php

$mimeType = $attachment->getMimeType();


?>

@if($mimeType == 'application/pdf')
	<iframe frameborder="0" style="height: 600px; width: 100%" src="{{{ route('viewTextBlockAttachment',array('textblock_id' => $textBlock->id, 'role' => $role, 'attachment_id' => $attachment->id, 'file_name' => $attachment->file_name)) }}}"></iframe>
@endif

