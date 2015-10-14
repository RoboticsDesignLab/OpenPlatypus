<?php


use Platypus\Helpers\PlatypusBool;


// This indicates the possible roles a text block restriction can have.
// The roles are not stored in the database. They are purely used as a hint for faster lookup and to
// tweak behaviour in the controller. Basically the roles are passed around in the links and that's it.
class TextBlockRestrictionRole extends PlatypusEnum {
	const answer = 1;
}

// The TextBlockRestriction mechanism isn't properly implemented.
// The infrastructure is there, but so far we don't have any actual data we store.
// 
// The idea is the following: when a TextBlock has a restriction attached to it, the
// restrictions stored in this model are enforced.
//
// currently each Question owns a TextBlockRestriction. Each time an answer is created,
// the answer's textblock gets the question's restriction attached.
// This all works.
//
// To implement the restrictions, this model needs the relevant columns and the 
// TextBlock model needs the logic to enfore the restrictions.
// And of course the question editor needs to show an editor to edit the restrictions.

class TextBlockRestriction extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'text_block_restrictions';

	// fields we can fill directly from user input.
	protected $fillable = array ();
	
	// fields we set with default values
	public static $defaultValues = array();
	
	
	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array (
				'created_at',
				'updated_at',
		);
	}
	
	public static $automaticGarbageCollectionRelations = array('textBlocks', 'questions');
	
	// define the relationships to other models
	// Note: there are a lot of possible relationships depending on the role. They should all be added here for convenience.
	public static $relationsData = array (
			'textBlocks'  => array(self::HAS_MANY, 'TextBlock', 'foreignKey' => 'restriction_id'),
			'questions' => array (self::HAS_MANY, 'Question', 'foreignKey' => 'answer_restriction_id'),
	);
	
	
	// set the validation rules that apply to this model.
	public static $rules = array ();
	
	
	public static function createTable() {
		Schema::create('text_block_restrictions', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
						
			$table->increments('id');
			
		});
	}
}