<?php
/**
 * TEXT
 * input text comum
 * 
 * 
 * 
 */

class BFE_color_picker extends BorosFormElement {
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
	
	var $enqueues = array(
		'js' => array('color-picker'),
	);
	
	function init(){
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');
	}
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
		$attrs = make_attributes($this->data['attr']);
		$input = "{$this->input_helper_pre}<input type='text' value='{$value}'{$attrs} />{$this->input_helper}";
		return $input;
	}
}