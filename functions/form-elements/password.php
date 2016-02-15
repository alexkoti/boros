<?php
/**
 * PASSWORD
 * 
 * 
 * 
 */

class BFE_password extends BorosFormElement {
	/**
	 * Lista de atributos aceitos pelo elemento, e seus respectivos valores padrão.
	 * Caso seja definido qualquer outro atributo no array de configuração ele será ignorado.
	 * Definir qualquer valor padrão ou string vazia(''), irá obrigatoriamente renderizar o atributo, independente do valor. Valor padrão 'false' só irá renderizar o atributo caso ele
	 * seja definido no array de configuração.
	 * 
	 * ATenção: NÃO INCLUIR dataset - este atributo será adicionado em set_elements(), que irá separar os diversos datasets necessários
	 */
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
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
		$attrs = $this->make_attributes($this->data['attr']);
		$input = "<input type='password' value='{$value}'{$attrs} />{$this->input_helper}";
		return $input;
	}
}