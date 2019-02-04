<?php
/**
 * CHECKBOX_GROUP
 * 
 * @todo campo outros - consultar o select.php onde já está implementado
 * 
 */

class BFE_checkbox_group extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
	);
	
	// Checkboxgroups não possuem label inicial, possuindo cada opção seu próprio label.
	function set_label(){
		if( !empty($this->data['label']) ){
			$this->label = apply_filters( "BFE_{$this->data['type']}_label", "<span class='non_click_label'>{$this->data['label']}{$this->label_helper}</span>" );
		}
		// separar os layouts :: bootstrap
		if( $this->data['layout'] == 'bootstrap' ){
			$for = "{$this->data['attr']['id']}_";
			$for .= key($this->data['options']['values']);
			$this->label = "<label class='control-label' for='{$for}'>{$this->data['label']}{$this->label_helper}</label>";
		}
	}
	
	/**
	 * @todo em bootstrap, permitir configurar se quer o label inline ou normal
	 * 
	 */
	function set_input( $value = null ){
		if( isset($this->data['options']['values']) ){
			
			// separador, o parão é <br />
			$separator = isset($this->data['options']['separator']) ? $this->data['options']['separator'] : '<br />';
			
			$checkboxes = array();
			foreach( $this->data['options']['values'] as $option_value => $option_label ){
				// verificar defaults/checked, é comparado o option_value, que é a informação gravada
				$checked = '';
				if( is_array($this->data_value) ){
					if( in_array( $option_value, $this->data_value) ){
						$checked = ' checked="checked"';
					}
				}
				else{
					$checked = checked( $option_value, $this->data_value, false );
				}
				
				$dataset = isset($this->data['attr']['dataset']['name']) ? "data-name='{$this->data['attr']['dataset']['name']}'" : '';
				
				// separar os layouts :: bootstrap
				if( $this->data['layout'] == 'bootstrap' ){
					$checkboxes[] = "<label class='checkbox'><input type='checkbox' name='{$this->data['attr']['name']}[]' id='{$this->data['attr']['id']}_{$option_value}' value='{$option_value}'{$checked} rel='{$option_value}_checkbox'> {$option_label}</label>";
				}
				if( $this->data['layout'] == 'bootstrap4' ){
					$checkboxes[] = "<div class='form-check'><input type='checkbox' name='{$this->data['attr']['name']}[]' value='{$option_value}'{$checked} id='{$this->data['attr']['id']}_{$option_value}' {$dataset} class='boros_form_input input_checkbox form-check-input' rel='{$option_value}_checkbox' /><label for='{$this->data['attr']['id']}_{$option_value}' class='label_checkbox iptw_{$this->data['size']} form-check-label'>{$option_label}</label></div>";
				}
				else{
					//$checkboxes[] = "<span class='item_checkbox'><input type='checkbox' name='{$this->data['attr']['name']}[{$option_value}]' value='{$option_value}'{$checked} id='{$this->data['attr']['id']}_{$option_value}' class='boros_form_input input_checkbox' rel='{$option_value}_checkbox' /><label for='{$this->data['attr']['id']}_{$option_value}' class='label_checkbox iptw_{$this->data['size']}'>{$option_label}</label></span>";
					$checkboxes[] = "<span class='item_checkbox'><input type='checkbox' name='{$this->data['attr']['name']}[]' value='{$option_value}'{$checked} id='{$this->data['attr']['id']}_{$option_value}' {$dataset} class='boros_form_input input_checkbox' rel='{$option_value}_checkbox' /><label for='{$this->data['attr']['id']}_{$option_value}' class='label_checkbox iptw_{$this->data['size']}'>{$option_label}</label></span>";
				}
			}
			return implode( $separator, $checkboxes );
		}
		else{
			return '<div class="form_element_error">As opções dos checkboxes não foram definidas</div>';
		}
	}
}