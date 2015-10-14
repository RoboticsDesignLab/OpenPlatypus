<?php namespace Platypus\Helpers;


class PlatypusEnum {
	
	static public function getConstants($include_default = false) {
		$reflection = new \ReflectionClass(get_called_class());
		$constants = $reflection->getConstants();
		if (! $include_default) {
			unset($constants["__default"]);
		}
		return $constants;
	}

	static public function getNames() {
		return array_keys(self::getConstants(false));
	}

	static public function getValues() {
		return array_values(self::getConstants(false));
	}
	
	static public function isValid($value) {
		if (!is_numeric( $value )) return false;
		$numericValue = $value + 0;		
		return ( array_search($numericValue, self::getConstants(false), true) !== false );
	}
	
	static public function getName($value) {
		if (!is_numeric( $value )) return null;
		$numericValue = $value + 0;
		$constants = self::getConstants(false);
		$key = array_search($numericValue, $constants, true);
		if ( $key === false ) return null;
		return $key;
	}
}


class PlatypusBool extends PlatypusEnum {
	const false = 0;
	const true = 1;	
}