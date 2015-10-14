<?php

use Platypus\Helpers\PlatypusEnum;
use Carbon\Carbon;




class AutosaveText extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'autosave_texts';
	
	// define the types that are taken as enum. These will automatically be validated against the proper range.
	public static $enumColumns = array (
			'content_type' => 'TextBlockContentType',
	);	
	
	// fields we can fill directly from user input.
	protected $fillable = array (
				'text',
		);
	
	// fields we set with default values
	public static $defaultValues = array(
			'content_type' => TextBlockContentType::plain, 
			'text' => '',
	);
	
	
	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array (
				'created_at',
				'updated_at',
		);
	}
	
	public static $automaticGarbageCollectionRelations = array();
	
	// define the relationships to other models
	// Note: there are a lot of possible relationships depending on the role. They should all be added here for convenience.
	public static $relationsData = array (
			'user'  => array(self::BELONGS_TO, 'User', 'foreignKey' => 'user_id'),
			'textBlock'  => array(self::BELONGS_TO, 'TextBlock', 'foreignKey' => 'text_block_id'),
	);
	
	// pseudo relationships
	
	public static $expireAfterDays = 30;
	
	
	public static $MaxTextSize; // value set below to match TextBlock

	// set the validation rules that apply to this model.
	// the rules are defined below to overcome limitations of inline definitions.
	public static $rules;
	
	// point out that this model uses a presenter to tweak fields whe n showing them in views.
	public static $presenterClass='AutosaveTextPresenter';
	
	// stores the result of the last purification. This allows us to avoid purifying twice if we're validating twice.
	private $htmlpurified_text = "";
	
	public function beforeValidate() {
		// if there is new html content, sanitise it.
		if (validateSimple($this->text, self::$rules['text'])) {
			if (($this->content_type == TextBlockContentType::html) && ($this->isDirty('content_type') || $this->isDirty('text')) && ($this->htmlpurified_text != $this->text)) {
				$this->htmlpurified_text = Platypus::sanitiseHtml($this->text);
				$this->text = $this->htmlpurified_text;
			}
		}
	
		return true;
	}
	
	public function afterValidate() {
		
		// final check that the html has been sanitised. (In case something funny happened with the rules and beforeValidate didn't catch it. 
		// This would only occur in case of a bug. But we want to be 100% sure about this.
		if (($this->content_type == TextBlockContentType::html) && ($this->isDirty('content_type') || $this->isDirty('text')) && ($this->htmlpurified_text != $this->text)) {
			return false;
		}
	
		return true;
	}
	
	
	public function mayView(User $user) {
		if ($user->id != $this->user_id) return false;
		return true;
	}
	
	public function mayEdit(User $user, $role) {
		return $this->mayView($user);
	}

	public function isEmpty() {
		if (!empty(trim($this->text))) return false;
		return true;
	}
	
	public function isRelevant() {
		if ($this->updated_at < Carbon::now()->subDays(static::$expireAfterDays) ) return false;
		if ($this->isEmpty()) return false;
		if ($this->content_type != $this->text_block->content_type) return true;
		if (trim($this->text) != trim($this->text_block->text)) return true;
		return false;
	}
	
	public function setPlainText($text) {
		$this->content_type = TextBlockContentType::plain;
		$this->text = $text;
	}
	
	public function setHtmlText($html) {
		$this->content_type = TextBlockContentType::html;
		$this->text = $html;
	}
	
	// garbage collection is purely on date here.
	public static function collectGarbage() {
		$cutOffDate = Carbon::now()->subDays(static::$expireAfterDays);
		$deletedCount = static::where('updated_at','<', $cutOffDate)->delete();
		
		if ($deletedCount > 0) {
			echo "$deletedCount ". get_called_class(). " objects deleted.\n";
		}
		
	}
	
	static public function createTable() {
		Schema::create('autosave_texts', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
			
			$table->integer('text_block_id')->unsigned();
			$table->foreign('text_block_id')->references('id')->on('text_blocks')->onDelete('cascade');
			
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			
			$table->tinyInteger('content_type')->default(TextBlockContentType::plain);
			
			$table->longText('text');
			
			$table->unique(array (
					'user_id',
					'text_block_id' 
			));
			
			$table->index(array (
					'updated_at',
			));
			
		});
	}
	
		
}


AutosaveText::$MaxTextSize = TextBlock::$MaxTextSize;

AutosaveText::$rules = array (
	'text' => 'max:'. TextBlock::$MaxTextSize, 
);



// A presenter that formats the text block content according to the content type.
class AutosaveTextPresenter extends PlatypusBasePresenter {


	public function text() {
		
		switch ($this->resource->content_type) {
			case TextBlockContentType::plain:
				$text = $this->resource->text;
				$text = htmlentities($text);
				$text = str_replace("\n", '<br>', $text);
				return $text;
				break;
			case TextBlockContentType::html:
				$text = $this->resource->text;
				return $text;
				break;
		}
		
		return "ERROR: Unsupported content type: ".$this->resource->contentType;
	}
	
	public function text_edit_plain() {
		switch ($this->resource->content_type) {
			case TextBlockContentType::plain:
				$text = $this->resource->text;
				$text = htmlentities($text);
				return $text;
				break;
			case TextBlockContentType::html:
				$text = $this->resource->text;
				$text = strip_tags($text);
				$text = htmlentities($text);
				return $text;
				break;
		}
		
		return "ERROR: Unsupported content type: ".$this->resource->contentType;
	}
	
	public function text_edit_html() {
		switch ($this->resource->content_type) {
			case TextBlockContentType::plain:
			case TextBlockContentType::html:
				$text = $this->resource->text;
				$text = htmlentities($text);
				return $text;
				break;
		}
	
		return "ERROR: Unsupported content type: ".$this->resource->contentType;
	}
	
	
}



