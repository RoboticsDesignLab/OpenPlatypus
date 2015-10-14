<?php

class TextBlockRestrictionsController extends BaseController {
    public function getAll() {
        $restrictions = TextBlockRestriction::all();
        return(View::make('textblock.textblock_text_block_restriction_editor')->withRestrictions($restrictions));
    }
}
