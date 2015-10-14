<?php

use Platypus\Helpers\PlatypusEnum;
use Carbon\Carbon;


// This indicates the possible roles a text block can have.
// The roles are not stored in the database. They are purely used as a hint for faster lookup and to
// tweak behaviour in the controller. Basically the roles are passed around in the links and that's it.
class TextBlockRole extends PlatypusEnum {
	const assignmentintroduction = 1;
	const question = 2;
	const questionsolution = 3;
	const markingscheme = 4;
	const studentanswer = 5;
	const questionrevision = 6;
	const review = 7;
	const reviewfeedback = 8;
}

// The content type a text block can have.
// Most likely Platypus will only support creating one type of text block in the end.
// But it should be able to display all the ones that exist. (via the presenter).
class TextBlockContentType extends PlatypusEnum {
	const plain = 1; // plain text. To be displayed using htmlentities() for special characters and replacing of line brakes with <br>.
	const html = 2;  // html code. To be displayed as it is. It would be good practice to apply the html-sanitising filter before displaying. Not yet implemented.
}



class TextBlock extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'text_blocks';
	
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
	
	public static $automaticGarbageCollectionRelations = array('assignments', 'questionsAsText', 'questionsAsSolution', 'questionsAsMarkingScheme', 'answers', 'reviews', 'reviewsAsFeedback');
	
	// define the relationships to other models
	// Note: there are a lot of possible relationships depending on the role. They should all be added here for convenience.
	public static $relationsData = array (
			'assignments'  => array(self::HAS_MANY, 'Assignment', 'foreignKey' => 'introduction_id'),
			'questionsAsText'  => array(self::HAS_MANY, 'Question', 'foreignKey' => 'text_id'), // use as questions_as_text
			'questionsAsSolution'  => array(self::HAS_MANY, 'Question', 'foreignKey' => 'solution_id'), // use as questions_as_solution
			'questionsAsMarkingScheme'  => array(self::HAS_MANY, 'Question', 'foreignKey' => 'marking_scheme_id'), // use as questions_as_marking_scheme
			'answers'  => array(self::HAS_MANY, 'Answer', 'foreignKey' => 'answer_text_id'), // use as student answer
			'reviews'  => array(self::HAS_MANY, 'Review', 'foreignKey' => 'text_id'), // use as review
			'reviewsAsFeedback'  => array(self::HAS_MANY, 'Review', 'foreignKey' => 'review_feedback_id'), // use as review feedback
			
			'restriction'  => array(self::BELONGS_TO, 'TextBlockRestriction', 'foreignKey' => 'restriction_id'), // the restriction definitions that are in effect (if any).
			'attachments'  => array(self::HAS_MANY, 'TextBlockAttachment', 'foreignKey' => 'text_block_id'),
			
			'autosaveTexts'  => array(self::HAS_MANY, 'AutosaveText', 'foreignKey' => 'text_block_id'),
	);
	
	// pseudo relationships
	
	public function attachmentsOrdered() {
		return $this->attachments()->orderBy('position');
	}
	
	
	public static $MaxTextSize = '10485760'; // limit the size to 10MB we allow that much because of possible embedded pictures within the html code.

	// set the validation rules that apply to this model.
	// the rules are defined below to overcome limitations of inline definitions.
	public static $rules;
	
	// point out that this model uses a presenter to tweak fields whe n showing them in views.
	public static $presenterClass='TextBlockPresenter';
	
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
	
	
	public function mayView(User $user, $role) {
		if (!TextBlockRole::isValid($role)) return false;
		
		switch ($role) {
			case TextBlockRole::assignmentintroduction:
				foreach ($this->assignments as $assignment) {
					if ($assignment->mayViewAssignment($user)) return true;
				} 
				break;
			case TextBlockRole::question:
				foreach ($this->questions_as_text as $question) {
					if ($question->mayViewQuestion($user)) return true;
				} 
				break;
			case TextBlockRole::questionsolution:
				foreach ($this->questions_as_solution as $question) {
					if ($question->mayViewSolution($user)) return true;
				} 
				break;
			case TextBlockRole::markingscheme:
				foreach ($this->questions_as_marking_scheme as $question) {
					if ($question->mayViewMarkingScheme($user)) return true;
				} 
				break;
			case TextBlockRole::studentanswer:
				foreach ($this->answers as $answer) {
					if ($answer->mayView($user)) return true;
				} 
				break;
			case TextBlockRole::review:
				foreach ($this->reviews as $review) {
					if ($review->mayViewTextAndMark($user)) return true;
				}
				break;
		}
		
		return false;
	}
	
	public function mayEdit(User $user, $role) {
		if (!TextBlockRole::isValid($role)) return false;
	
		switch ($role) {
			case TextBlockRole::assignmentintroduction:
				foreach ($this->assignments as $assignment) {
					if ($assignment->mayEditIntroduction($user)) return true;
				}
				break;
			case TextBlockRole::question:
				foreach ($this->questions_as_text as $question) {
					
					if ($question->mayEditQuestion($user)) return true;
				} 
				break;
			case TextBlockRole::questionsolution:
				foreach ($this->questions_as_solution as $question) {
					if ($question->mayEditSolution($user)) return true;
				} 
				break;
			case TextBlockRole::markingscheme:
				foreach ($this->questions_as_marking_scheme as $question) {
					if ($question->mayEditMarkingScheme($user)) return true;
				} 
				break;
			case TextBlockRole::studentanswer:
				foreach ($this->answers as $answer) {
					if ($answer->mayEdit($user)) return true;
				} 
				break;
			case TextBlockRole::review:
				foreach ($this->reviews as $review) {
					if ($review->mayEditTextAndMark($user)) return true;
				}
				break;
		}
	
		return false;
	}

	public function mayAddAttachment(User $user, $role) {
		return $this->mayEdit($user, $role);	
	}
	
	public function hasAutosave(User $user) {
		return $this->autosaveTexts()->where('user_id', $user->id)->exists();
	}

	public function getOrCreateAutosave(User $user) {
		$result = $this->autosaveTexts()->where('user_id', $user->id)->first();
		if(!isset($result)) {
			$result = new AutosaveText();
			$result->user_id = $user->id;
			$result->text_block_id = $this->id;
			$result->save();
		}
		
		return $result;
	}
	
	public function getAutosave(User $user) {
		return $this->autosaveTexts()->where('user_id', $user->id)->first();
	}
	
	public function clearAutosave(User $user = null) {
		if(isset($user)) {
			$this->autosaveTexts()->where('user_id', $user->id)->delete();
		} else {
			$this->autosaveTexts()->delete();
		}
	}
	
	public function isEmpty() {
		if (!empty($this->text)) return false;
		
		// check if the attachments are loaded already. If so, we don't need to query the db.
		if(array_key_exists('attachments', $this->relations)) {
			if (count($this->attachments) > 0) return false; 
		} else {
			if ($this->attachments()->count() > 0) return false;
		}
		
		return true;
	}
	
	public function setPlainText($text) {
		$this->content_type = TextBlockContentType::plain;
		$this->text = $text;
	}
	
	public function setHtmlText($html) {
		$this->content_type = TextBlockContentType::html;
		$this->text = $html;
	}
	
	public function getNextUnusedAttachmentPosition() {
		$maxUsed = $this->attachments()->max('position');
		if (is_null($maxUsed)) {
			return 1;
		} else {
			return $maxUsed + 1;
		}
	}
	
	static public function createNewTextblocks($count) {
		
		if ($count == 0) return array();
		
		$max_id = static::max('id');
		
		$time = new Carbon();
		
		$rows = array();
		$ids = array();
		for($i = 0; $i<$count; $i++) {
			$id = $max_id + $i + 1;
			$ids[] = $id;
			$rows[] = array(
					'id' => $id,
					static::CREATED_AT => $time,
					static::UPDATED_AT => $time,
			);
		}
		
		DB::table(static::getTableStatic())->insert($rows);
		return $ids;
	}
	
	
	static public function createTable() {
		Schema::create('text_blocks', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
				
			$table->increments('id');
			$table->tinyInteger('content_type')->default(TextBlockContentType::plain);
			$table->longText('text');
			$table->integer('restriction_id')->unsigned()->nullable();
			$table->foreign('restriction_id')->references('id')->on('text_block_restrictions')->onDelete('restrict');
		});
	}
	
		
}


TextBlock::$rules = array (
	'text' => 'max:'. TextBlock::$MaxTextSize, 
);

//TextBlock::saved(function($textBlock) {
//	$textBlock->clearAutosave();
//});



// A presenter that formats the text block content according to the content type.
class TextBlockPresenter extends PlatypusBasePresenter {


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



