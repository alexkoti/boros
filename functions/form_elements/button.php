<?php
/**
 * BUTTON
 * 
 * Modelo de configuração:
 * array(
 *     'name' => 'submit',
 *     'type' => 'button',
 *     'attr' => array(
 *         'elem_class' => 'col-md-6',
 *         'class' => 'btn btn-lg',
 *         'type' => 'submit',
 *     ),
 *     'options' => array(
 *         'html' => 'ENVIAR <span class="glyphicon glyphicon-repeat right-spinner"></span>',
 *     ),
 * );
 * 
 * 
 */

class BFE_button extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'type' => 'button',
		'id' => '',
		'class' => '',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
	);
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
		$attrs = make_attributes($this->data['attr']);
		$input = "{$this->input_helper_pre}<button {$attrs}>{$this->data['options']['html']}</button>{$this->input_helper}";
		return $input;
	}
}