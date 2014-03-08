<?php
/**
 * WP_EDITOR
 * 
 * 
 * 
 */

class BFE_wp_editor extends BorosFormElement {
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
	
	function add_defaults(){
		$this->defaults['options']['raw'] = true;
		$this->defaults['options']['textarea_rows'] = 6;
		$this->defaults['options']['editor_class'] = 'hentry';
		$this->defaults['options']['media_buttons'] = false;
	}
	
	function set_input( $value = null ){
		ob_start();
		wp_editor( $value, $this->data['name'], $this->data['options'] );
		echo "<div>{$this->input_helper}</div>";
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}