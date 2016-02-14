<?php
/**
 * TEXTAREA
 * textarea simples
 * 
 * 
 * 
 */

class BFE_textarea extends BorosFormElement {
	/**
	 * ATENÇÃO: foi removido o 'value' da lista, pois ele não possui esse atributo.
	 * 
	 */
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => 'ipt_textarea',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
		'cols' => 60,
		'rows' => 20,
	);
	
	function set_input( $value = null ){
		$attrs = make_attributes($this->data['attr']);
		return "<textarea {$attrs}>{$this->data_value}</textarea>{$this->input_helper}";
	}
}