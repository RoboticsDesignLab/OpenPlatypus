<?php namespace Platypus\Helpers;

use Request;
use Caouecs\Bootstrap3\Helpers;

class Form extends \Caouecs\Bootstrap3\Form {
	
	
	static public function post_button($params, $content, $buttonAttributes = array(), $buttonClass = "btn btn-default", $hiddenData = array()) {
		
		if (is_null($buttonAttributes)) {
			$buttonAttributes = array();
		} else if (!is_array($buttonAttributes)) {
			$buttonAttributes = array('data-confirmationdialog' => $buttonAttributes);
		}
		
		$buttonAttributes = Helpers::addClass($buttonAttributes, $buttonClass);
		
		$result = "";
		
		$result .= Form::open(Helpers::addClass($params, "form-inline post_button"));
		
		foreach($hiddenData as $key => $value) {
			$result .= Form::hidden($key, $value);
		}
		
		$result .= '<button type="submit"';
		foreach($buttonAttributes as $attribute => $value) {
			$result .= " $attribute=\"".htmlentities($value)."\"";
		}
		$result .= '>';
		$result .= $content;
		$result .= '</button>';
		$result .= Form::close();		
		
		return $result;
	}
	
	static public function post_button_primary($params, $content, $confirmation = null, $hiddenData = array()) {
		return static::post_button($params, $content, $confirmation, "btn btn-primary", $hiddenData);
	}

	static public function post_button_sm($params, $content, $confirmation = null, $hiddenData = array()) {
		return static::post_button($params, $content, $confirmation, "btn btn-default btn-sm", $hiddenData);
	}
	
	
	
	static public function open_inline($params=false)
	{
		return self::open(Helpers::addClass($params, "form-inline"));
	}	
	
	
    /**
     * Display checkbox for form-group
     *
     * @access public
     * @param string $name Name of checkbox
     * @param string $title Title of checkbox
     * @param mixed $value Value if checked
     * @param mixed $input Value by input
     * @param ExceptionError $errors
     * @param array $attributes
     * @param string $help Help message
     * @return string
     */
    static public function checkbox_group($name, $title, $value = 1, $input = 0, $errors = null, $attributes = array(), $help = null, $label=true) {
		$result = static::hidden($name, '0', array('autocomplete' => "off", 'data-true-value' => '0'));
		$result.= parent::checkbox_group($name, $title, $value, $input, $errors, $attributes, $help, $label);
		return $result;
	}
	
	
	
	/**
	 * Display input radio for form-group
	 *
	 * @access public
	 * @param string $name Name of radio
	 * @param string $title Title of radio
	 * @param array $choices Choices
	 * @param mixed $value Value if checked
	 * @param ExceptionError $errors
	 * @param array $attributes
	 * @param string $help Help message
	 * @return string
	 */
	static public function radio_group_vertical($name, $title, $choices, $value = 1, $errors = null, $attributes = array(), $help = null, $label=true, $fieldAttributes = array())
	{
		if (!is_array($choices) || empty($choices)) {
			return null;
		}
	
		$txt = '<div class="form-group">';
		if($label){
			$txt .= '<div class="col-md-offset-2 col-md-10">';
		} else {
			$txt .= '<div class="col-md-12">';
		}
	
		foreach ($choices as $key => $_value) {
			$currentAttributes = $attributes;
			if(isset($fieldAttributes[$key])) {
				$currentAttributes = array_merge($currentAttributes,$fieldAttributes[$key]);
			}
			
        	$txt .= '<div class="radio"><label>';
            $txt .= self::radio($name, $key, ($key == $value), $currentAttributes).' '.$_value.' ';
        	$txt .= '</label></div>';
        	$txt .= "\n";		}
	
        // Always include the help block in vertical mode. It makes the spacing more consistent in long forms.
		//if (!empty($help))
			$txt .= '<span class="help-block">'.$help.'</span>';
	
		if (!is_null($errors) && $errors->has($name))
			$txt .= '<span class="text-danger">'.$errors->first($name).'</span>';
	
		$txt .= '</div></div>';
	
		return $txt;
	}	
	
	
	/**
	 * Display submit with cancel for form-group
	 *
	 * @access public
	 * @param array $options
	 * @param array $attributes
	 * @return string
	 */
	static public function submit_group($options = array(), $attributes = array(), $label=true)
	{
		$txt = '<div class="form-group">';
		if($label){
			$txt .= '<div class="col-md-offset-2 col-md-10">';
		} else {
			$txt .= '<div class="col-md-12">';
		}
	
		$attributes = Helpers::addClass($attributes, "btn btn-primary");
	
		$options['submit_title'] = isset($options['submit_title']) ? $options['submit_title'] : trans('form.submit');
	
		$txt .= self::submit($options['submit_title'], $attributes);
	
		/**
		 * Url for cancel
		*/
		if (isset($options['cancel_url'])) {
			$txt .= ' <a href="'.$options['cancel_url'].'">'.( isset($options['cancel_title']) ? $options['cancel_title'] :trans('form.cancel') ).'</a>';
		}
	
		/**
		 * Reset
		 */
		if (isset($options['reset']) && $options['reset'] === true) {
			$txt .= ' '.self::reset("Reset", array("class" => "btn btn-default"));
		}
	
		$txt .= '</div>
        </div>';
	
		return $txt;
	}	
	
	static public function file_group($name, $title, $errors = null, $attributes = array(), $help = null, $label=true, $iconpre=false, $iconpost=false, $buttonpost=false)
	{
		$txt = '<div class="form-group';
		if (!is_null($errors) && $errors->has($name))
			$txt .= ' has-error';
		$txt .= '">';
	
		if($label){
				
			$txt .= '<label for="'.$name.'" class="col-md-2 control-label">'.$title.'</label>';
				
			$txt .= '<div class="col-md-10">';
	
		} else {
	
			$txt .= '<div class="col-md-12">';
			$txt .= '<label for="'.$name.'" class="sr-only">'.$title.'</label>';
		}
	
		if($iconpost || $iconpre || $buttonpost) $txt .= '<div class="input-group">';
	
		if($iconpre) $txt .= '<span class="input-group-addon"><span class="'.$iconpre.'"></span></span>';
	
		//$attributes = Helpers::addClass($attributes, "form-control");
	
		$txt .= self::file($name, $attributes);
	
		if($iconpost) $txt .= '<span class="input-group-addon"><span class="'.$iconpost.'"></span></span>';
		if($buttonpost) $txt .='<span class="input-group-btn">'. $buttonpost.'</span>';
	
		if($iconpost || $iconpre || $buttonpost) $txt .= '</div>';
	
	
		if (!empty($help))
			$txt .= '<span class="help-block">'.$help.'</span>';
	
		if (!is_null($errors) && $errors->has($name))
			$txt .= '<span class="text-danger">'.$errors->first($name).'</span>';
	
		$txt .= '</div></div>';
	
		return $txt;
	}
	
	
}
