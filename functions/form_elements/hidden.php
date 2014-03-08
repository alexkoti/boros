<?php
/**
 * HIDDEN
 * 
 * 
 * 
 */

class BFE_hidden extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'size' => false,
		'disabled' => false,
		'readonly' => false,
		'maxlength' => false,
	);
	
	function set_input( $value = null ){
		$attrs = make_attributes($this->data['attr']);
		$input = "<input type='hidden' value='{$value}'{$attrs} />";
		return $input;
	}
}