<?php
/**
 * RADIO_GROUP
 * radio group simples
 * 
 * @TODO: fazer o recurso opcional de 'checked_ontop'
 * @TODO: permitir determinados itens com disabled
 * @todo campo outros - consultar o select.php onde já está implementado
 */

class BFE_radio extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => '',
		'value' => '',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
	);
	
	// Radiogroups não possuem label inicial, possuindo cada opção seu próprio label.
	function set_label(){
		if( !isset($this->data['label']) ){
			$this->data['label'] = '';
		}
		
		if( !empty($this->data['label']) ){
			// separar os layouts :: bootstrap
			if( $this->data['layout'] == 'bootstrap' ){
				$for = "{$this->data['attr']['id']}_";
				$for .= key($this->data['options']['values']);
				$this->label = "<label class='control-label' for='{$for}'>{$this->data['label']}{$this->label_helper}</label>";
			}
			else{
				$this->label = apply_filters( "BFE_{$this->data['type']}_label", "<span class='non_click_label'>{$this->data['label']}{$this->label_helper}</span>" );
			}
		}
	}
	
	function set_input( $value = null ){
		if( isset($this->data['options']['values']) ){
			
			// separador, o parão é <br />
			$separator = isset($this->data['options']['separator']) ? $this->data['options']['separator'] : '<br />';
			
			// verificar valor inicial, caso não haja nenhum valor gravado
			if( empty($this->data_value) )
				$this->data_value = $this->data['std'];
			
			$radios = array();
			foreach( $this->data['options']['values'] as $option_value => $option_label ){
				// verificar defaults/checked, é comparado o option_value, que é a informação gravada
				$checked = checked( $option_value, $this->data_value, false );
				$dataset = isset($this->data['attr']['dataset']['name']) ? "data-name='{$this->data['attr']['dataset']['name']}'" : '';
				
				// separar os layouts :: bootstrap
				if( $this->data['layout'] == 'bootstrap' ){
					$radios[] = "<label for='{$this->data['attr']['id']}_{$option_value}' class='radio'><input type='radio' name='{$this->data['attr']['name']}' value='{$option_value}'{$checked} id='{$this->data['attr']['id']}_{$option_value}' {$dataset} class='input_radio' /> {$option_label}</label>";
				}
				if( $this->data['layout'] == 'bootstrap3' ){
					$radios[] = "<label for='{$this->data['attr']['id']}_{$option_value}' class='radio-inline'><input type='radio' name='{$this->data['attr']['name']}' value='{$option_value}'{$checked} id='{$this->data['attr']['id']}_{$option_value}' {$dataset} class='radio-inline' /> {$option_label}</label>";
				}
				// layout normal
				else{
					$radios[] = "<span class='item_radio'><input type='radio' name='{$this->data['attr']['name']}' value='{$option_value}'{$checked} id='{$this->data['attr']['id']}_{$option_value}' {$dataset} class='boros_form_input input_radio' /><label for='{$this->data['attr']['id']}_{$option_value}' class='label_radio iptw_{$this->data['size']}'>{$option_label}</label></span>";
				}
			}
			$input = implode( $separator, $radios ) . $this->input_helper;
			return $input;
		}
		else{
			return '<div class="form_element_error">As opções dos radios não foram definidas</div>';
		}
	}
}