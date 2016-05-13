<?php


class TextBlockLazyLoadMode extends PlatypusEnum {
	const smart = 1; // determines dmartly whether the text is shown immediately or loaded later in a second request
	const show = 2; // always include the whole text in the response
	const defer = 3; // always defer loading of the text content.
}


class ConsiderAutosaveMode extends PlatypusEnum {
	const ask = 1;
	const discard = 2;
	const restore = 3;
}

class TextBlockController extends BaseController {

	public function getTextAjax($textblock_id, $role) {
		return Platypus::transaction(function () use($textblock_id, $role) {

			$textBlock = TextBlock::findOrFail($textblock_id);
			if (! $textBlock->mayView(Auth::user(), $role)) {
				App::abort(403);
			}

			$json = array ();
			$json ['success'] = true;
			$json ['text'] = $textBlock->text;

			return Response::json($json);
		});
	}

	public function showTextAjax($textblock_id, $role, $showEditLink = true, $showCopyLink = false) {
		if ($showEditLink == '0') $showEditLink=false;
		if ($showEditLink == '1') $showEditLink=true;

		if ($showCopyLink == '0') $showCopyLink=false;
		if ($showCopyLink == '1') $showCopyLink=true;
		
		return Platypus::transaction(function () use($textblock_id, $role, $showEditLink, $showCopyLink) {
			
			if (! TextBlockRole::isValid($role)) {
				App::abort(404);
			}
			
			$textBlock = TextBlock::findOrFail($textblock_id);
			
			if (! $textBlock->mayView(Auth::user(), $role)) {
				App::abort(403);
			}
			
			if ($showEditLink && $textBlock->mayEdit(Auth::user(), $role)) {
				$showEditLink = true;
			} else {
				$showEditLink = false;
			}
				
				
			$json = array ();
			$json ['success'] = true;
			$json ['html'] = View::make('textblock.textblock_showText_insert', array (
					'textBlock' => $textBlock,
					'role' => $role,
					'showEditLink' => $showEditLink,
					'showCopyLink' => $showCopyLink,
					'lazyMode' => TextBlockLazyLoadMode::show,
			))->render();
			
			return Response::json($json);
		});	
	}

	public function editTextDiscardAutosaveAjax($textblock_id, $role) {
		return $this->editTextAjax($textblock_id, $role, ConsiderAutosaveMode::discard);
	}

	public function editTextRestoreAutosaveAjax($textblock_id, $role) {
		return $this->editTextAjax($textblock_id, $role, ConsiderAutosaveMode::restore);
	}
	
	
	public function editTextAjax($textblock_id, $role, $considerAutosave = ConsiderAutosaveMode::ask) {
		return Platypus::transaction(function () use($textblock_id, $role, $considerAutosave) {
			
			if (! TextBlockRole::isValid($role)) {
				App::abort(404);
			}
			
			$textBlock = TextBlock::findOrFail($textblock_id);
			
			if (! $textBlock->mayEdit(Auth::user(), $role)) {
				App::abort(403);
			}
			
			$autosave = $textBlock->getAutosave(Auth::user());
			$showAsChanged = false;
			if (isset($autosave)) {
				if ($autosave->isRelevant()) {
					switch ($considerAutosave) {
						case ConsiderAutosaveMode::discard :
							$autosave->delete();
							break;
						case ConsiderAutosaveMode::restore :
							$textBlock->content_type = $autosave->content_type;
							$textBlock->text = $autosave->text;
							$showAsChanged = true;
							break;
						default:
							$json = array ();
							$json ['success'] = true;
							$json ['html'] = View::make('textblock.textblock_autosaveChoice_insert', array (
									'textBlock' => $textBlock,
									'role' => $role,
									'autosave' => $autosave,
							))->render();
							return Response::json($json);
					}
				} else {
					$autosave->delete();
				}
			}
			
			$json = array ();
			$json ['success'] = true;
			$json ['html'] = View::make('textblock.textblock_editDefault_insert', array (
					'textBlock' => $textBlock,
					'role' => $role,
					'showAsChanged' => $showAsChanged,
			))->render();
			return Response::json($json);
		});
	}

	public function saveTextInlineAjaxPost($textblock_id, $role) {
		return $this->editTextAjaxPost($textblock_id, $role, true);
	}
	
	public function editTextAjaxPost($textblock_id, $role, $inlineSave = false) {
		return Platypus::transaction(function () use($textblock_id, $role, $inlineSave) {
			
			if (! TextBlockRole::isValid($role)) {
				App::abort(404);
			}
			
			$textBlock = TextBlock::findOrFail($textblock_id);
			
			if (! $textBlock->mayEdit(Auth::user(), $role)) {
				App::abort(403);
			}
			
			$onlyValues = array (
					'text',
					'content_type',
			);
			$input = Input::only($onlyValues);
			
			$originalTextBlock = clone $textBlock;
			
			$textBlock->content_type = $input ['content_type'];
			$textBlock->text = $input ['text'];
			
			if (! $textBlock->validate()) {
				$json = array ();
				$json ['success'] = false;
				
				if(!$inlineSave) {
					$json ['html'] = View::make('textblock.textblock_editDefault_insert')
						->with('textBlock', $originalTextBlock)->with('role', $role)
						->with('errors', $textBlock->errors())
						->render();
				}
				
				$json ['alert'] = "Your changes could not be saved.";
				return Response::json($json);
			} else {
				
				$textBlock->save();
				$textBlock->clearAutosave(Auth::user());
				
				$json = array ();
				$json ['success'] = true;
				
				if(!$inlineSave) {
					$json ['html'] = View::make('textblock.textblock_showText_insert', array (
							'textBlock' => $textBlock,
							'role' => $role,
							'showEditLink' => true,
							'lazyMode' => TextBlockLazyLoadMode::show,
					))->render();
				}
				
				$json ['growl'] = "Your changes have been saved.";
				return Response::json($json);
			}
			
		});
	}

	public function autosaveTextAjaxPost($textblock_id, $role) {
		return Platypus::transaction(function () use($textblock_id, $role) {
				
			if (! TextBlockRole::isValid($role)) {
				App::abort(404);
			}
				
			$textBlock = TextBlock::findOrFail($textblock_id);
				
			if (! $textBlock->mayEdit(Auth::user(), $role)) {
				App::abort(403);
			}
				
			$onlyValues = array (
					'text',
					'content_type',
			);
			$input = Input::only($onlyValues);

			$autosave = $textBlock->getOrCreateAutosave(Auth::user());

			$autosave->content_type = $input ['content_type'];
			$autosave->text = $input ['text'];
				
			if(!$autosave->isRelevant()) {
				$autosave->delete();
				$json ['success'] = true;
				return Response::json($json);
			}
			
			if (! $autosave->validate()) {
				$json = array ();
				$json ['success'] = false;
				return Response::json($json);
			} else {
				$json = array ();
				$json ['success'] = $autosave->save();
				return Response::json($json);
			}
				
		});
	}
	
}

