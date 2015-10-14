<?php


class ReviewGroup extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'review_groups';


	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array('created_at', 'updated_at');
	}
	
	public static $automaticGarbageCollectionRelations = array('members');
	
	// define the relationships to other models
	public static $relationsData = array(
			'members'  => array(self::HAS_MANY, 'Review', 'table' => 'reviews', 'foreignKey' => 'review_group_id'),
	);

	// pseudo-relationships
	
	public function count() {
		return $this->members()->count();
	}
	
	public function isEmpty() {
		return $this->members()->count() == 0;
	}
	
	
	static public function createNewReviewGroups($count) {
	
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
	
	static function createTable() {
		
		Schema::create('review_groups', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
			
		});		
	}
}



