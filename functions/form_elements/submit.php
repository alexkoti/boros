<?php
/**
 * SUBMIT
 * 
 * 
 * 
 */

class BFE_submit extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'value' => 'Enviar',
		'id' => '',
		'class' => '',
		'rel' => '',
		'size' => false,
		'disabled' => false,
		'readonly' => false,
		'maxlength' => false,
	);
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
		$attrs = make_attributes($this->data['attr']);
		$input = "<input type='submit' value='{$value}'{$attrs} />{$this->input_helper}";
		return $input;
	}
}