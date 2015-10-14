<?php

use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Platypus\Helpers\PlatypusBool;
use Platypus\Helpers;
use Illuminate\Support\MessageBag;


class PlatypusBaseModel extends PlatypusBaseModelFrameworkImprovements implements PresenterInterface {


	public static $defaultValues = array ();
	
	public static $enumColumns = array ();
	
	// the namespaces we search when we try to resolve the enum classes. (e.g. in magic translation of boolean assignments)
	public static $enumSearchNamespaces = array (
			'Platypus\Helpers' 
	);


	public static $rules = array (
			'id' => 'dummyforemptyruleworkaround' 
	);

	public static $automaticGarbageCollectionRelations = false; // set to an array to make the garbage collection work.
	public static $automaticGarbageCollectionChunkSize = 100; // the maximum number of rows that are checked in any one query.
	
	
	public static $presenterClass = 'PlatypusBasePresenter';
	
	public function getPresenter() {
		return static::getPresenterClass();
	}
	
	public static function getPresenterClass() {
		return static::$presenterClass;
	}
	
	
	public function presenter() {
		$className = static::getPresenterClass();
		return new $className($this);
	}
	
	
    public function __construct(array $attributes = array()) {
    	$this->attributes = static::$defaultValues;
        parent::__construct($attributes);
                
    }

    public static function getTableStatic() {
    	return (new static())->getTable();
    }
    
    protected function getRelationshipFromMethod($key, $camelKey) {
    	// we want to be able to access model queries like relations. Thus, special-handle them here.
    	
    	$relations = $this->$camelKey();
    
    	
    	if ( $relations instanceof \Illuminate\Database\Eloquent\Builder){
    		// "fake" relationship
    		return $this->relations[$key] = $relations->get();
    	}
    	
    	// this is the normal laravel behaviour from here.
    	if ( ! $relations instanceof Illuminate\Database\Eloquent\Relations\Relation) {
    		throw new LogicException('Relationship method must return an object of type '
    				. 'Illuminate\Database\Eloquent\Relations\Relation' . ' ' . get_class($relations) .' given.');
    	}
    
    	return $this->relations[$key] = $relations->getResults();
    }    
    
    public static function __callStatic ($name , $arguments ) {
    	// implement the explain-magic to route it to the presenter when necessary
        if (substr($name, 0, 7) == "explain") {
    		$className = static::getPresenterClass();
    		// check if it is explained via a static method.
    	    if (method_exists($className, $name)) {
    			return call_user_func($className .'::'. $name, $arguments);
    		}
    		// assume it is an array.
    		if (property_exists($className, $name)) {
    			$explanations = $className::$$name;
    			if (!is_array($explanations)) App::abort(500, 'The explanations are not an array.');
    			if (isset($arguments[0])) {
		   			if (isset($explanations[$arguments[0]])) {
    					return $explanations[$arguments[0]];
    				} else {
    					return $arguments[0];
    				}
    			} else {
    				return $explanations;
    			}
    		}
    	}
    	
        if (substr($name, 0, 7) == "present") {
        	$className = static::getPresenterClass();
    		// check if it is explained via a static method.
    	    if (method_exists($className, $name)) {
    			return call_user_func($className .'::'. $name, $arguments);
    		}
    		// assume it is an array.
    		if (property_exists($className, $name)) {
    			$explanations = $className::$$name;
    			if (!is_array($explanations)) App::abort(500, 'The explanations are not an array.');
    			if (isset($arguments[0])) {
		   			if (isset($explanations[$arguments[0]])) {
    					return $explanations[$arguments[0]];
    				} else {
    					return $arguments[0];
    				}
    			} else {
    				return $explanations;
    			}
    		}
    	}
    	 
    	return parent::__callStatic($name, $arguments);
    }
    
    public function __get($key)	{
    
    	if($key == 'resource') {
    		return $this;
    	}
    
    	return parent::__get($key);
    }    
    
    
	// try to add namespace information to a class name. It uses $enumSearchNamespaces.
	protected static function resolveClassName($className, $softFail = false) {

		if (class_exists($className)) {
			return $className;
		}
		
		
		foreach ( static::$enumSearchNamespaces as $namespace ) {
			$fullClassName = $namespace . '\\' . $className;
			if (class_exists($fullClassName)) {
				return $fullClassName;
			}
		}

		if ($softFail) {
			return $className;
		} else { 
		    return NULL;
		}
		
	}

	
	// this function is called before validation starts.
	// can be overridden in derived models.
	protected function prepareForValidation() {
	}
	
	
	// receives a copy of the current rules for self-validation
	// has to return the new rules.
	// can be overridden in derived models.
	protected function mangleRulesForValidation($rules) {
		
		// make sure the rules are all in array notation.
		$newRules = array ();
		foreach ( $rules as $field => $rule ) {
			if (is_string($rule)) {
				$newRules [$field] = explode("|", $rule);
			}
		}
		
		// add rules for the enum fields.
		foreach ( static::$enumColumns as $field => $enumClassName ) {
				
			$ruleName = 'enumrange:' . static::resolveClassName($enumClassName,true);
				
			$newRules [$field] [] = $ruleName;
		}
		
		// special handling of password-fornfirmation fields: if the password hasn't changed, ignore the confirmation.
		foreach (static::$passwordAttributes as $field) {
			if ($this->getOriginal($field) == $this->getAttribute($field)) {
				$newRules[$field] = array();
				if (isset($newRules[$field."_confirmation"])) {
					$newRules[$field."_confirmation"] = array();
				}
			}
		}
		
		return $newRules;
		
	}
	
	
	// is run after the validation rules have been processed.
	// $success indicates whether the rules so far have passed.
	// Change it to false if validation fails.
	// $this->validationErrors contains the validation errors. Add yours there.
	//
	// can be overridden in derived models.
	protected function runCustomValidationRules(&$success) {
	}	
	
	
	// This one is a bit evil. There seems to be no subtle way to link in functional validation rules in ardent.
	// Thus, I just copied the entire validation function to override and enhance it.
	/**
	 * Validate the model instance
	 *
	 * @param array $rules          Validation rules
	 * @param array $customMessages Custom error messages
	 * @return bool
	 * @throws InvalidModelException
	 */
	public function validate(array $rules = array(), array $customMessages = array()) {
		$this->prepareForValidation();
		
		if ($this->fireModelEvent('validating') === false) {
			if ($this->throwOnValidation) {
				throw new InvalidModelException($this);
			} else {
				return false;
			}
		}
	
		// check for overrides, then remove any empty rules
		$rules = (empty($rules))? static::$rules : $rules;
		foreach ($rules as $field => $rls) {
			if ($rls == '') {
				unset($rules[$field]);
			}
		}
	
		if (empty($rules)) {
			$success = true;
		} else {
			$customMessages = (empty($customMessages))? static::$customMessages : $customMessages;
	
			if ($this->forceEntityHydrationFromInput || (empty($this->attributes) && $this->autoHydrateEntityFromInput)) {
				$this->fill(Input::all());
			}
	
			$data = $this->getAttributes(); // the data under validation
	
			// perform validation
			$validator = static::makeValidator($data, $this->mangleRulesForValidation($rules), $customMessages);
			$success   = $validator->passes();
			$this->validationErrors = $validator->messages();
	
			$this->runCustomValidationRules($success);
			
			if ($success) {
				// if the model is valid, unset errors
				if (is_null($this->validationErrors) || $this->validationErrors->count() > 0) {
					$this->validationErrors = new MessageBag;
				}
			} else {
				// stash the input to the current session
				if (!self::$externalValidator && Input::hasSession()) {
					Input::flash();
				}
			}
		}
	
		$this->fireModelEvent('validated', false);
	
		if (!$success && $this->throwOnValidation) {
			throw new InvalidModelException($this);
		}
	
		return $success;
	}	
	

	
	// This little hack allows us to assign native php bool values to fields that are of PlatypusBool type. They will get converted to their integer representation on the fly.
	// Also takes care of date-time assignments so they end up as NULL when empty.
	public function setAttribute($key, $value) {
		if ((is_bool($value)) && (array_key_exists($key, static::$enumColumns))) {
			$className = static::resolveClassName(static::$enumColumns [$key]);
			
			if (!is_null($className)) {
				$testClass = new $className();
				if ($testClass instanceof PlatypusBool) {
					return parent::setAttribute($key, $value ? PlatypusBool::true : PlatypusBool::false);
				}
			}
		}
		
		// 
		if (in_array($key, $this->getDates()) && !$value) {
			return parent::setAttribute($key, NULL);
		}
		
		return parent::setAttribute($key, $value);
	}
	
	public function invalidateRelations() {
		$this->relations = array();
	}
	
	public function injectCachedRelation($relation, $data) {
		$this->relations[snake_case($relation)] = $data;
	}
	
	public function dumpRelations() {
		var_dump($this->relations);
	}	
	
	
	public static function collectGarbage() {
		
		$deletedCount = 0;
		
		$relations = static::$automaticGarbageCollectionRelations;
		if (is_array($relations) && !empty($relations)) {
			
			$chunkSize = static::$automaticGarbageCollectionChunkSize;
			if ($chunkSize < 1) $chunkSize = 1;
			
			$lastId = 0;
			while (true) {
				$ids = static::where('id','>',$lastId)->orderBy('id')->limit($chunkSize)->lists('id');
				
				if (empty($ids)) break;
				
				$lastId = max($ids);
				
				$query = static::whereIn('id', $ids);
				
				foreach($relations as $relation) {
					$query->has($relation, '<', 1);
				}
				
				$deletedCount += $query->delete();
			
			}
			
		}
		
		if ($deletedCount > 0) {
			echo "$deletedCount ". get_called_class(). " objects deleted.\n";
		}
				
		
	}
	
	
}


class PlatypusBasePresenter extends BasePresenter {
	
	public function __call ($name , $arguments ) {
		
		// check for our explain interface
		if ( (substr($name, 0, 7) == "explain") && (empty($arguments)) ) {
			$key = snake_case(substr($name, 7));
	
			$className = get_class($this);
			// check if it is explained via a static method.
			if (method_exists($className, $name)) {
				return call_user_func($className .'::'. $name, array(0 => $this->getAttribute($key)));
			}
			// assume it is an array.
			if (property_exists($className, $name)) {
				$explanations = $className::$$name;
				if (!is_array($explanations)) App::abort(500, 'The explanations are not an array.');
				if (isset($explanations[$this->getAttribute($key)])) {
					return $explanations[$this->getAttribute($key)];
				} else {
					return $this->getAttribute($key);
				}
			}
		}
		 
		// check for our present interface
		if ( (substr($name, 0, 7) == "present") && (empty($arguments)) ) {
			$key = snake_case(substr($name, 7));
	
			$className = get_class($this);
			// check if it is explained via a static method.
			if (method_exists($className, $name)) {
				return call_user_func($className .'::'. $name, array(0 => $this->getAttribute($key)));
			}
			// assume it is an array.
			if (property_exists($className, $name)) {
				$explanations = $className::$$name;
				if (!is_array($explanations)) App::abort(500, 'The presentations are not an array.');
				if (isset($explanations[$this->getAttribute($key)])) {
					return $explanations[$this->getAttribute($key)];
				} else {
					return $this->getAttribute($key);
				}
			}
		}
		
		// handle ardent relations
		$resourceClass = get_class($this->resource);
		if (isset($resourceClass::$relationsData[$name])) {
			return call_user_func_array(array($this->resource, $name), $arguments);
		}	

		
		 
		return parent::__call($name, $arguments);
	}
	
	public function __get($key)	{
		
		$presentKey = 'present'.ucfirst ( camel_case($key) );
			if (method_exists($this, $presentKey)) {
			return $this->{$presentKey}();
		}
	
		if (property_exists(get_class($this), $presentKey)) {
			return $this->{$presentKey}();
		}
	
		return parent::__get($key);
	}
	
	
}

