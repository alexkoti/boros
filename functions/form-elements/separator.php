<?php
/**
 * SEPARATOR
 * Apenas um separador comum
 * 
 * @todo repensar a necessidade deste elemento OU estilizar com CSS
 */

class BFE_separator extends BorosFormElement {
	function set_attributes(){} // resetar esse método
	
	function set_label(){
		$this->label = '';
	}
	
	function set_input( $value = null ){
		$this->data['layout'] = 'block';
		return '<hr />';
	}
}