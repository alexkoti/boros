<?php
/**
 * DATEPICKER MULTIPLE
 * 
 * 
 */

class BFE_date_picker_multiple extends BorosFormElement {
	
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'placeholder' => '',
		'size' => false,
		'disabled' => false,
		'readonly' => false,
		'maxlength' => false,
	);
	
	static $localized = false;
	
	var $enqueues = array(
		'js' => array('date_picker_multiple'),
		'css' => array('date_picker_multiple'),
	);
	
	/**
	 * Opções
	 * 
	 * 
	 */
	function add_defaults(){
		$this->defaults['options']['num_months'] = 3;
	}
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
		$input = "<div class='date_picker_multiple_box date_picker_multiple_cols_{$this->data['options']['num_months']}'><input type='hidden' style='width:100%' name='{$this->data['attr']['name']}' value='{$value}' class='date_picker_input' id='date_picker_input_{$this->data['attr']['name']}'  /><div class='date_picker_multiple_calendars' id='date_picker_calendars_{$this->data['attr']['name']}' data-num-months='{$this->data['options']['num_months']}'></div></div>";
		return $input;
	}
}


