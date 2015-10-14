<?php
use Carbon\Carbon;
use Platypus\Helpers;
use Platypus\Helpers\PlatypusBool;


class PlatypusValidators extends Illuminate\Validation\Validator {

	public function __construct() {
		$args = func_get_args();
		$this->implicitRules[] = 'RequiredIfNot';
		call_user_func_array("parent::__construct", $args);
	}
	
	
	public function validateCarbon($attribute, $value, $parameters) {
		if ($value instanceof Carbon) {
			return true;
		}
		
		// if we haven't touched the value, it is a string from the database. Thus, we have to allow database-string-format as well.
		try {
			Carbon::createFromFormat('Y-m-d H#i#s', $value);
			return true;
		} catch ( Exception $e ) {
		}
		return false;
	}
	
	public function validateHarmlessFilename($attribute, $value, $parameters) {
		return ( preg_match("#^[a-zA-Z0-9_ \.\-]+$#", $value) >0);
	}

	public function validateAfterfield($attribute, $value, $parameters) {
		$second = strtotime($value);
		if ($second === false)
			return false;

		// we do not require existence.
		if (is_null($this->getValue($parameters[0]))) return true;
		
		$first = strtotime($this->getValue($parameters [0]));
		if ($first === false)
			return false;
		
		if ($second <= $first) {
			return false;
		}
		return true;
	}

	protected function replaceAfterfield($message, $attribute, $rule, $parameters) {
		return str_replace(':other', $this->getAttribute($parameters [0]), $message);
	}
	
	public function validateAfterequalfield($attribute, $value, $parameters) {
		$second = strtotime($value);
		if ($second === false)
			return false;
	
		// we do not require existence.
		if (is_null($this->getValue($parameters[0]))) return true;
	
		$first = strtotime($this->getValue($parameters [0]));
		if ($first === false)
			return false;
	
		if ($second < $first) {
			return false;
		}
		return true;
	}
	
	protected function replaceAfterequalfield($message, $attribute, $rule, $parameters) {
		return str_replace(':other', $this->getAttribute($parameters [0]), $message);
	}	

	public function validateBeforefield($attribute, $value, $parameters) {
		$second = strtotime($value);
		if ($second === false)
			return false;

		// we do not require existence.
		if (is_null($this->getValue($parameters[0]))) return true;
		
		$first = strtotime($this->getValue($parameters [0]));
		if ($first === false)
			return false;
	
		if ($second >= $first) {
			return false;
		}
		return true;
	}
	
	protected function replaceBeforefield($message, $attribute, $rule, $parameters) {
		return str_replace(':other', $this->getAttribute($parameters [0]), $message);
	}
	
	public function validateEquallarger($attribute, $value, $parameters) {
		$second = floatval($value);
		$first = floatval($this->getValue($parameters[0]));
		if ($second < $first)
			return false;
		return true;
	}
	
	protected function replaceEquallarger($message, $attribute, $rule, $parameters) {
		return str_replace(':other', $this->getAttribute($parameters [0]), $message);
	}

	protected function validateRequiredIfNot($attribute, $value, $parameters) {
		$this->requireParameterCount(2, $parameters, 'required_if_not');
		
		$data = array_get($this->data, $parameters [0]);
		
		$values = array_slice($parameters, 1);
				
		if (! in_array($data, $values)) {
			return $this->validateRequired($attribute, $value);
		}
		
		return true;
	}

	protected function replaceRequiredIfNot($message, $attribute, $rule, $parameters) {
		$result = $message;
		$result = str_replace(':other', $this->getAttribute($parameters [0]), $result);
		$result = str_replace(':value', $this->getAttribute($parameters [1]), $result);
		return $result;
	}

	public function validateEnumrange($attribute, $value, $parameters) {
		$enumClassName = $parameters [0];
		return call_user_func($enumClassName . '::isValid', $value);
	}

	public function validateDummyforemptyruleworkaround($attribute, $value, $parameters) {
		return true;
	}
	
	public function validateEmailorstudentid($attribute, $value, $parameters) {
		return strlen($value) > 0 && strlen($value) < 100;		
	}
}



Validator::resolver(function ($translator, $data, $rules, $messages) {
	return new PlatypusValidators($translator, $data, $rules, $messages);
});

