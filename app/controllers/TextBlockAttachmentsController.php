<?php


class TextBlockAttachmentsController extends BaseController {
	
	private static function makeUpdateAttachmentsResponse($textBlock, $role) {
		$json = array ();
		$json ['success'] = true;
		
		$json ['html'] = View::make('textblock.textblock_editAttachments_insert')->with(array (
				'textBlock' => $textBlock,
				'role' => $role,
				'showEditLink' => true,
		))->render();
		
		$json['update'] = array();
		if(count($textBlock->attachments_ordered) > 0) {
			$json['update']['textblock_'. $textBlock->id.'_first_attachment'] = View::make('textblock.textblock_showInlineAttachment_insert')
				->withAttachment($textBlock->attachments_ordered[0])
				->with('textBlock',$textBlock)
				->withRole($role)
				->render();
		} else {
			$json['update']['textblock_'. $textBlock->id.'_first_attachment'] = '';
		}
		
		return $json;
	}
	
	public function showCkEditorAttachmentSelection($textblock_id, $role) {
		return Platypus::transaction(function () use($textblock_id, $role) {
			if (! TextBlockRole::isValid($role)) {
				App::abort(404);
			}
				
			$textBlock = TextBlock::findOrFail($textblock_id);
				
			if (! $textBlock->mayView(Auth::user(), $role)) {
				App::abort(403);
			}

			$json = array ();
			$json ['success'] = true;
			
			$json ['html'] = View::make('textblock.textblock_selectAttachmentsCkEditor_insert')->with(array (
					'textBlock' => $textBlock,
					'role' => $role,
					'showEditLink' => false,
			))->render();
			return $json;			
			
		});
	}
	
	public function viewAttachment($textblock_id, $role, $attachment_id) {
		return $this->downloadOrViewAttachment($textblock_id, $role, $attachment_id, false);
	}
	
	public function downloadAttachment($textblock_id, $role, $attachment_id, $download) {
		return $this->downloadOrViewAttachment($textblock_id, $role, $attachment_id, true);
	}
	
	public function downloadOrViewAttachment($textblock_id, $role, $attachment_id, $download) {
		return Platypus::transaction(function () use($textblock_id, $role, $attachment_id, $download) {
			if (! TextBlockRole::isValid($role)) {
				App::abort(404);
			}
			
			$textBlock = TextBlock::findOrFail($textblock_id);
			
			if (! $textBlock->mayView(Auth::user(), $role)) {
				App::abort(403);
			}

			$attachment = $textBlock->attachments()->where('id', $attachment_id)->first();
			
			if(!isset($attachment)) {
				return (new HomeController())->showErrorPage(404);
			}
		
			return $attachment->getDowloadResponse($download);
		});		
	}
	
	public function deleteAttachmentPostAjax($textblock_id, $role, $attachment_id) {
		return Platypus::transaction(function () use($textblock_id, $role, $attachment_id) {
			if (! TextBlockRole::isValid($role)) {
				App::abort(404);
			}
				
			$textBlock = TextBlock::findOrFail($textblock_id);
				
			if (! $textBlock->mayEdit(Auth::user(), $role)) {
				App::abort(403);
			}
			
			$textBlock->attachments()->where('id',$attachment_id)->delete();

			return Response::json(static::makeUpdateAttachmentsResponse($textBlock, $role));
		});	
	}
	
	public function moveAttachmentPostAjax($textblock_id, $role, $attachment_id, $direction) {
		return Platypus::transaction(function () use($textblock_id, $role, $attachment_id, $direction) {
				
			if (($direction != '1') && ($direction != '-1')) {
				App::abort(404);
			}
				
			if (! TextBlockRole::isValid($role)) {
				App::abort(404);
			}
			
			$textBlock = TextBlock::findOrFail($textblock_id);
			
			if (! $textBlock->mayEdit(Auth::user(), $role)) {
				App::abort(403);
			}

			$attachment = $textBlock->attachments()->where('id', $attachment_id)->firstOrFail();
			
			$ordered = $textBlock->attachments_ordered;
							
			$changeMade = false;
			for($i = 0; $i < count($ordered); $i ++) {
				if ($ordered[$i]->id == $attachment->id) {
					if (($i + $direction >= 0) && ($i + $direction < count($ordered))) {
						$tmp = $ordered[$i];
						$ordered[$i] = $ordered[$i + $direction];
						$ordered[$i + $direction] = $tmp;
						$changeMade = true;
						break;
					}
				}
			}
				
			if ($changeMade) {
				for($i = 0; $i < count($ordered); $i ++) {
					$ordered[$i]->position = $i + 1;
					$ordered[$i]->save();
				}
			}
	
			return Response::json(static::makeUpdateAttachmentsResponse($textBlock, $role));
		});
	}
	
	public function uploadAttachmentCkEditorAjaxPost($textblock_id, $role) {
		return $this->uploadAttachmentAjaxPost($textblock_id, $role, true);		
	}
	
	public function uploadAttachmentAjaxPost($textblock_id, $role, $forCkEditor = false) {
		return Platypus::transaction(function () use($textblock_id, $role, $forCkEditor) {
			if (! TextBlockRole::isValid($role)) {
				App::abort(404);
			}
				
			$textBlock = TextBlock::findOrFail($textblock_id);
				
			if (! $textBlock->mayAddAttachment(Auth::user(), $role)) {
				App::abort(403);
			}

			
			
			// render the view how it was before we did anything so we can return it in case of an error.
			$failJson = array ();
			$failJson ['success'] = true;	
			if($forCkEditor) {
				$json ['html'] = View::make('textblock.textblock_selectAttachmentsCkEditor_insert')->with(array (
					'textBlock' => $textBlock,
					'role' => $role,
					'showEditLink' => true,
				))->render();			
			} else {
				$failJson['html'] = View::make('textblock.textblock_editAttachments_insert')->with(array (
					'textBlock' => $textBlock,
					'role' => $role,
					'showEditLink' => true,
				))->render();
			}
			
			
			$ok = false;
			do{
				if (!Input::hasFile('upload_file')) {
					$failJson ['alert'] = 'No file was uploaded or your file was too large.';
					break;
				}
				
				if (!Input::file('upload_file')->isValid())	{
					$failJson ['alert'] = 'The file is invalid. Please try again.';
					break;
				}
				
				$filename = Input::file('upload_file')->getRealPath();
				if (!is_uploaded_file($filename)) {
					// this is something naughty. Don't be graceful.
					App::abort(500);
				}
				
				$diskFile = DiskFile::createOrGetRecordForFile_noMove_save($filename);
				
				// create the attachment record.
				$attachment = new TextBlockAttachment();
				$attachment->position = $textBlock->getNextUnusedAttachmentPosition();
				$attachment->file_name = Input::file('upload_file')->getClientOriginalName();
				$attachment->file_id = $diskFile->id;
				if (!$attachment->validate()) {
					$failJson ['alert'] = 'The file name is invalid. Please rename your file and try again. Letters, numbers and dashes are allowed.';
					break;
				}
				$textBlock->attachments()->save($attachment);
				$textBlock->invalidateRelations();
				
				// everything seems ok.
				$ok = true;
			} while(false);

			// render the successful view.
			$successResponse = self::makeUpdateAttachmentsResponse($textBlock, $role);
			
			if ($forCkEditor) {
				$successResponse['update']['textblock_attachment_editor_'.$textBlock->id] = $successResponse['html'];
				$successResponse['html'] = View::make('textblock.textblock_selectAttachmentsCkEditor_insert')->with(array (
					'textBlock' => $textBlock,
					'role' => $role,
					'showEditLink' => true,
				))->render();
				
				// ckeditor and modal alerts don't work well together. So we us normal javascript alerts.
				if (!isset($successResponse['script'])) $successResponse['script'] = '';
				if (!isset($failJson['script'])) $failJson['script'] = '';
				
				if (isset($successResponse['alert'])) {
					$successResponse['script'] .= "alert('".$successResponse['alert']."');";
					unset($successResponse['alert']);					
				}
				if (isset($failJson['alert'])) {
					$failJson['script'] .= "alert('".$failJson['alert']."');";
					unset($failJson['alert']);
				}
				
				// trigger the new link for user convenience
				if($ok) {
					$successResponse['script'] .= '$("a[data-ckeditorattachmentlinkid='.$attachment->id.']").click();';
				}
					
				
			}
			
			if ($ok) {
				// everything above worked well. So we can move the file into its place.
				if (!$diskFile->moveFile($filename)) {
					$ok = false;
					$failJson ['alert'] = 'A server error occured and your file could not be saved. Please try again.';
				}
			}
			
			// roll back if there is anything wrong.
			if ($ok) {
				return $successResponse;
			} else {
				DB::rollback();
				return Response::json($failJson);
			}

		});
	}
	
	
	
	
}

