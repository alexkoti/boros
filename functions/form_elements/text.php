<?php
/**
 * TEXT
 * input text comum
 * 
 * 
 * 
 */

class BFE_text extends BorosFormElement {
	/**
	 * Lista de atributos aceitos pelo elemento, e seus respectivos valores padrão.
	 * Caso seja definido qualquer outro atributo no array de configuração ele será ignorado.
	 * Definir qualquer valor padrão ou string vazia(''), irá obrigatoriamente renderizar o atributo, independente do valor. 
	 * Valor padrão 'false' só irá renderizar o atributo caso ele seja definido no array de configuração.
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
		$attrs = $this->make_attributes($this->data['attr']);
		
		/**
		 * Possibilitar campos de texto com validação de navegador('date', 'email', 'range', etc)
		 * 
		 * @todo adicionar as opções de range, number, etc
		 */
		if( isset($this->data['options']['type']) ){
			$type = $this->data['options']['type'];
		}
		else{
			$type = 'text';
		}
		
		/**
		 * Separar o input em partes caso necessário, por exemplo em campos de telefone com ddd separado ou data de nascimento
		 * 
		 */
		if( isset($this->data['options']['split']) ){
			$input = array();
			$separator = isset($this->data['options']['separator']) ? $this->data['options']['separator'] : '';
			$index = 0;
			foreach( $this->data['options']['split'] as $name => $attr ){
				$item_value = isset($value[$name]) ? $value[$name] : '';
                $type = isset($attr['type']) ? $attr['type'] : $type;
                
                // caso cada pedaço tenho o attr definido
                if( isset($attr['attr']) ){
                    $item_attr = boros_parse_args($this->data['attr'], $attr['attr']);
                }
                // ou usar padrão
                else{
                    $item_attr = boros_parse_args($this->data['attr'], $attr);
                }

				$item_attr['dataset']['key']  = $name;
				$item_attr['id']              = "{$this->data['name']}_{$name}";
				$item_attr['class']           = "{$this->data['attr']['class']} {$item_attr['class']} splitted split-{$index}";
				
				$input_helper = isset($attr['input_helper']) ? "<span class='description'>{$attr['input_helper']}</span>" : '';
				
				$sattr = $this->make_attributes($item_attr);
				$input[] = "{$input_helper}<input type='{$type}' value='{$item_value}' {$sattr} /> ";
				$index++;
			}
			$input = implode( $separator, $input );
			$input .= $this->input_helper;
		}
		/**
		 * Campo de texto comum
		 * 
		 */
		else{
			$input = "{$this->input_helper_pre}<input type='{$type}' value='{$value}'{$attrs} />{$this->input_helper}";
		}
		
		return $input;
	}
}