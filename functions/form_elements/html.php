<?php
/**
 * HTML
 * Bloco de HTML simples, sem input de controle
 * 
 * 
 */

class BFE_html extends BorosFormElement {
	function set_attributes(){} // resetar esse método
	
	function set_input( $value = null ){
		return $this->data['html'];
	}
}