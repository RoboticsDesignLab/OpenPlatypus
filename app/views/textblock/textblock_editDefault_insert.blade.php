<?php 
if(!isset($showAsChanged)) {
	$showAsChanged = false;
}
?>

@include('textblock.textblock_editHtml_insert', array('textBlock' => $textBlock, 'role' => $role, 'showAsChanged' => $showAsChanged))


