<?php
/**
 * TEXT
 * input text comum
 * 
 * 
 * 
 */

class BFE_file extends BorosFormElement {
	/**
	 * Lista de atributos aceitos pelo elemento, e seus respectivos valores padrão.
	 * Caso seja definido qualquer outro atributo no array de configuração ele será ignorado.
	 * Definir qualquer valor padrão ou string vazia(''), irá obrigatoriamente renderizar o atributo, independente do valor. Valor padrão 'false' só irá renderizar o atributo caso ele
	 * seja definido no array de configuração.
	 * 
	 * Atenção: NÃO INCLUIR dataset - este atributo será adicionado em set_elements(), que irá separar os diversos datasets necessários
	 */
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
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
		if( is_array($value) ){
			$value = '';
		}
		$attrs = make_attributes($this->data['attr']);
		$input = "<input type='file' value='{$value}'{$attrs} />{$this->input_helper}";
		
		return apply_filters( 'boros_form_elemento_file_input', $input, $this );
	}
}