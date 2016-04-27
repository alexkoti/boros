<?php
/**
 * SELECT
 * select simples baseado em valores passado no array de configuração
 * 
 * Other field:
<code>
	'other_field' => array(
		'attr' => array(
			'placeholder' => 'Outras',
			'class' => 'input-small',
		),
	),
</code>
 * 
 */

class BFE_select extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
	);
	
	function add_defaults(){
		$this->defaults['options']['other_field'] = false;
		//$this->defaults['options']['other_field']['attr'] = array();
		//$this->defaults['options']['other_field']['other_index'] = 'other';
	}
	
	function set_input( $value = null ){
		if( isset($this->data['options']['values']) ){
			
			// verificar valor inicial, caso não haja nenhum valor gravado
			if( empty($value) ){
				$data_value = $this->data['std'];
			}
			else{
				$data_value = $value;
			}
            
            $values = apply_filters( "BFE_select_values", $this->data['options']['values'], $this );
            $values = apply_filters( "BFE_{$this->data['attr']['name']}_values", $values, $this );
			
			/**
			 * Adicionar o campo 'outros': será adicionado um input:text e a valor será dividido em um array, sendo o valor do select o índice [0] e o other o índice [1]
			 * 
			 */
			$input_other = '';
			if( !empty($this->data['options']['other_field']) ){
				if( !is_array($this->data_value) ){
					$this->data_value = array($this->data_value);
				}
				$data_value = issetor($this->data_value[0], '');
				$this->data['attr']['name'] .= '[]';
				$this->data['attr']['value'] = issetor($this->data_value[1], '');
				$custom_attr = issetor($this->data['options']['other_field']['attr'], array());
				$custom_attr['id'] = isset($this->data['attr']['id']) ? $this->data['attr']['id'] . '_other' : $this->data['attr']['name'] . '_other';
				$other_attr = $this->make_attributes( boros_parse_args( $this->data['attr'], $custom_attr ) );
				$input_other = " <input type='text'{$other_attr} />";
			}
			$attrs = $this->make_attributes($this->data['attr']);
			$input = "<select {$attrs}>";
			// adicionar o 'option_none', caso setado
			if( isset($this->data['options']['option_none']) and $this->data['options']['option_none'] !== false ){
				$input .= "<option value=''>{$this->data['options']['option_none']}</option>\n";
			}
			foreach( $this->data['options']['values'] as $option_value => $option_label ){
				// verificar defaults/selected, é comparado o option_value, que é a informação gravada
				$selected = selected( $option_value, $data_value, false );
				
				$input .= "<option value='{$option_value}'{$selected}>{$option_label}</option>\n";
			}
			$input .= "</select>";
			
			$input .= $input_other;
			$input .= $this->input_helper;
			return $input;
		}
		else{
			return '<div class="form_element_error">As opções dos select não foram definidas</div>';
		}
	}
}


