<?php

use Platypus\Helpers\PlatypusEnum;
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;


class StoredValue extends PlatypusEnum {
	const lastGarbageCollection = 1;
	const lastAutostartMarking = 2;
}

// This is a simple class to store values in the database permanently.
// for each id we can store different data types independently.
// as last resort there is the 'any' field which serializes the data into a string.

class ValueStore {

	// The database table used by the model.
	static protected $table = 'stored_values';

	
	private static function ensureExists($id) {
		$name = StoredValue::getName($id);
		
		$record = DB::table(static::$table)->where('id', $id)->select('id', 'name')->first();
		if(!isset($record)) {
			DB::table(static::$table)->insert(array('id' => $id, 'name' => $name));
		} else {
			if($record->name != $name) {
				DB::table(static::$table)->where('id', $id)->update(array('name' => $name));
			}
		}
	}
	
	static function getInteger($id) {
		return DB::table(static::$table)->where('id', $id)->pluck('integer');
	} 
	
	static function setInteger($id, $value) {
		static::ensureExists($id);
		DB::table(static::$table)->where('id', $id)->update(array('integer' => $value));
	}

	static function getFloat($id) {
		return DB::table(static::$table)->where('id', $id)->pluck('float');
	}

	static function setFloat($id, $value) {
		static::ensureExists($id);
		DB::table(static::$table)->where('id', $id)->update(array('integer' => $float));
	}
	
	static function getTime($id) {
		$result = DB::table(static::$table)->where('id', $id)->pluck('time');
		
		if(isset($result)) {
			$format = DB::table(static::$table)->getConnection()->getQueryGrammar()->getDateFormat();
			$result = Carbon::createFromFormat($format, $result);
		}
		
		return $result;
	}
	
	static function setTime($id, $value) {
		static::ensureExists($id);
		$format = DB::table(static::$table)->getConnection()->getQueryGrammar()->getDateFormat();
		DB::table(static::$table)->where('id', $id)->update(array('time' => $value->format($format)));
	}

	static function getText($id) {
		return DB::table(static::$table)->where('id', $id)->pluck('text');
	}
	
	static function setText($id, $value) {
		static::ensureExists($id);
		DB::table(static::$table)->where('id', $id)->update(array('text' => $value));
	}
	
	static function getAny($id) {
		$result = DB::table(static::$table)->where('id', $id)->pluck('any');
		if(isset($result)) {
			$result = unserialize($result);
		}
		return $result;
		
	}
	
	static function setAny($id, $value) {
		static::ensureExists($id);
		DB::table(static::$table)->where('id', $id)->update(array('any' => serialize($value)));
	}	
	
	
	static function createTable() {
		Schema::create('stored_values', function ($table) {
			$table->engine = 'InnoDB';

			$table->integer('id')->unique();
			$table->text('name')->nullable(); // the name of the field for convenience when looking at the database.
		
			$table->integer('integer')->nullable();
			$table->double('float')->nullable();
			$table->timestamp('time')->nullable();
			$table->longText('text')->nullable();
			$table->longText('any')->nullable();
		});
	}	
	
}


