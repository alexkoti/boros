<?php
/**
 * HTML
 * Bloco de HTML simples, sem input de controle
 * 
 * 
 */

class BFE_html extends BorosFormElement {
	var $valid_attrs = array(
		'id' => '',
		'class' => 'ipt_textarea',
		'rel' => '',
	);
	
	function set_input( $value = null ){
		return $this->data['html'];
	}
}