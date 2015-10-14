<?php

// This file gets always loaded. So we can put helpers in here that we can't put into autoloadable class files.


// Tries to convert the given string into a carbon date. Treating it as a start date goes towards the start of the day if only a date is given.
// returns the given value if conversion fails.
function parseStarttime($value) {
	$result = $value;
	try {
		$result = Carbon::createFromFormat('d/m/Y (H#i#s)', $value);
		return $result;
	} catch ( Exception $e ) {
	}
	try {
		$result = Carbon::createFromFormat('d/m/Y H#i#s', $value);
		return $result;
	} catch ( Exception $e ) {
	}
	try {
		$result = Carbon::createFromFormat('d/m/Y H#i', $value);
		return $result;
	} catch ( Exception $e ) {
	}
	try {
		$result = Carbon::createFromFormat('d#m#Y', $value)->startOfday();
		return $result;
	} catch ( Exception $e ) {
	}
	return $result; 	
}

// Tries to convert the given string into a carbon date. Treating it as a deadline goes towards the end of the day if only a date is given.
// returns the given value if conversion fails.
function parseDeadline($value) {
	$result = $value;
	try {
		$result = Carbon::createFromFormat('d/m/Y (H#i#s)', $value);
		return $result;
	} catch ( Exception $e ) {
	}
	try {
		$result = Carbon::createFromFormat('d/m/Y H#i#s', $value);
		return $result;
	} catch ( Exception $e ) {
	}
	try {
		$result = Carbon::createFromFormat('d/m/Y H#i', $value);
		return $result;
	} catch ( Exception $e ) {
	}
	try {
		$result = Carbon::createFromFormat('d#m#Y', $value)->endOfday();
		return $result;
	} catch ( Exception $e ) {
	}
	return $result; 	
}

function getEnumChoices($enumName, $explainer, $values = NULL) {
	if (is_null($values)) {
		$values = $enumName::getValues();
	}
	
	$result = array();
	foreach ($values as $key) {
		$result[$key] = call_user_func($explainer, array(0 => $arguments));
	}
	return $result;
}

function roundPercentage($value) {
	return round($value, 1);
}



function makeSortableLink($paginator, $name, $text) {

	// we need to access the query property, but it is protected. So let's be evil to make some magic happen.
	$params = getProperty_Evil($paginator, 'query');
	$url = getProperty_Evil($paginator, 'factory')->getCurrentUrl();
	
	$order = $params['order'];
	$sort = $params['sort'];
	
	$params['sort'] = $name;
	if ( ($sort==$name) && ($order=='asc') ) {
		$params['order'] = 'desc';
	} else {
		$params['order'] = 'asc';
	}

	$result = "";
	$result .= '<a href="';
	$result .= $url.'?'.http_build_query($params);
	$result .= '">';
	$result .= $text;
	$result .= '</a>';
	if ($sort==$name) {
		$result .= ' <span class="glyphicon glyphicon-chevron-'. ( ($order=='desc') ? 'down' : 'up' ) .'"></span>';
	}

	return $result;
}


function makePerPageLinks($paginator, $choices = array(10,20,50,100,500,1000)) {

	// we need to access the query property, but it is protected. So let's be evil to make some magic happen.
	$params = getProperty_Evil($paginator, 'query');
	$url = getProperty_Evil($paginator, 'factory')->getCurrentUrl();
	
	$perpage = $paginator->getPerPage();	
	
	
	$result = "";
	
	$result .= '<p class="text-right">';
	$result .= 'Show ';
	
	$first = true;
	foreach($choices as $newperpage) {
		if ($first) {
			$first = false;
		} else {
			$result .= " | ";
		}
		
		$params ['perpage'] = $newperpage;

		if ($perpage != $newperpage) {
		$result .= '<a href="';
			$result .= $url.'?'.http_build_query($params);
			$result .= '">';
		}
		
		$result .= $newperpage;
		
		if ($perpage != $newperpage) {
			$result .= '</a>';
		}
	}
	$result .= ' per page.';
	$result .= '</p>';
	
	return $result;
}

function getPaginationQueryData($paginator = null) {
	if (isset($paginator)) {
		$params = getProperty_Evil($paginator, 'query');
		$params['perpage'] = getProperty_Evil($paginator, 'perPage');
		$params['page'] = getProperty_Evil($paginator, 'currentPage');
		return $params;
	} else {
		$params = array();
		if (Input::has('perpage')) $params['perpage'] = Input::get('perpage') + 0;
		if (Input::has('page')) $params['page'] = Input::get('page') + 0;
		if (Input::has('completed')) $params['completed'] = Input::get('completed') + 0;
		return $params;
	}
}

function getPaginationQueryString($paginator = null) {
	return http_build_query(getPaginationQueryData($paginator));
}


function validateSimple($value, $rules, &$errors = null) {
	$validator = Validator::make(
			array('value' => $value),
			array('value' => $rules)
	);
	
	$result = $validator->passes();
	if ($result) {
		$errors = array();
	} else {
		$errors = $validator->getMessageBag()->getMessages()['value'];
	}
	
	return $result;
}

function validateOrAbortSimple($value, $rules) {
	if (!validateSimple($value, $rules)) {
		App::abort(404);
	}
}

function validateMark(&$mark, $allowEmpty = true, $allowDecimals = false) {
	if ($mark == "") {
		if ($allowEmpty) {
			$mark = null;
			return true;
		} else {
			return false;
		}
	}

	if (!is_numeric($mark)) return false;

	$mark = $mark+0; // convert to number

	if (!$allowDecimals) {
		if (!is_int($mark)) return false;
	}

	if ($mark < 0) return false;
	if ($mark > 100) return false;

	return true;
}

/**
 * Retrieves inaccessible properties from a class or object. See @ref read_properties
 * @param	mixed		$object		the name of the class or the initialized object
 * @param	string		$propertyName	the name of the property
 * @return	the value of the property if found.
 */
function getProperty_Evil( $object , $propertyName )
{
	if ( !$object ){ return null; }
	if ( is_string( $object ) )	// static property
	{
		if ( !class_exists( $object ) ){ return null; }
		$reflection = new \ReflectionProperty( $object , $propertyName );
		if ( !$reflection ){ return null; }
		$reflection->setAccessible( true );
		return $reflection->getValue( );
	}
	$class = new \ReflectionClass( $object );
	if ( !$class ){ return null; }
	if( !$class->hasProperty( $propertyName ) ) // check if property exists
	{
		trigger_error( 'Property "' .
				$propertyName . '" not found in class "' .
				get_class( $object ) . '"!' , E_USER_WARNING );
				return null;
	}
	$property = $class->getProperty( $propertyName );
	$property->setAccessible( true );
	return $property->getValue( $object );
}

function is_array_or_alike($var) {
	return is_array($var) ||
	($var instanceof ArrayAccess  &&
			$var instanceof Traversable  &&
			$var instanceof Serializable &&
			$var instanceof Countable);
}

function array_median($array) {
	// perhaps all non numeric values should filtered out of $array here?
	$iCount = count($array);
	if ($iCount == 0) {
		throw new DomainException('Median of an empty array is undefined');
	}
	// if we're down here it must mean $array
	// has at least 1 item in the array.
	$middle_index = floor($iCount / 2);
	sort($array, SORT_NUMERIC);
	$median = $array[$middle_index]; // assume an odd # of items
	// Handle the even case by averaging the middle 2 items
	if ($iCount % 2 == 0) {
		$median = ($median + $array[$middle_index - 1]) / 2;
	}
	return $median;
}

function array_mean($array) {
	// perhaps all non numeric values should filtered out of $array here?
	$iCount = count($array);
	if ($iCount == 0) {
		throw new DomainException('Median of an empty array is undefined');
	}
	
	return array_sum($array) / $iCount;
}


function formatFileSize($bytes) {

	if ($bytes >= 1073741824) {
		$bytes = number_format($bytes / 1073741824, 2) . ' GB';
	} elseif ($bytes >= 1048576) {
		$bytes = number_format($bytes / 1048576, 2) . ' MB';
	} elseif ($bytes >= 1024) {
		$bytes = number_format($bytes / 1024, 2) . ' KB';
	} elseif ($bytes > 1) {
		$bytes = $bytes . ' bytes';
	} elseif ($bytes == 1) {
		$bytes = $bytes . ' byte';
	} else {
		$bytes = '0 bytes';
	}

	return $bytes;

}