<?php
/**
 * CHECKBOX
 * 
 * 
 * 
 */

class BFE_checkbox extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
		'checked' => false,
    );
    
	function set_input_helper(){
		if( !empty($this->data['input_helper']) )
			$this->input_helper = apply_filters( "BFE_{$this->data['type']}_input_helper", " {$this->data['input_helper']}" );
    }
    
	function set_input( $value = null ){
		// verificar defaults/checked, é comparado o option_value, que é a informação gravada
		$checked = checked( $this->data_value, true, false );
		$name = $this->data['attr']['name'];
		$attrs = make_attributes($this->data['attr']);
		$input = '';
		$for = $this->data['attr']['id'];
		
		// caso seja layout normal, usando label de texto + input helper ao lado do checkbox
		if( !empty($this->data['input_helper']) ){
			$input = "<span class='checkbox_single_item'><input type='checkbox' {$attrs} value='1'{$checked} /><label for='{$for}' class='label_checkbox iptw_{$this->data['size']}'>{$this->input_helper}</label></span>";
		}
		
		// separar os layouts :: bootstrap
		if( $this->data['layout'] == 'bootstrap' ){
			$input = "<label for='{$name}' class='checkbox'><input type='checkbox' {$attrs} value='1'{$checked} /> {$this->input_helper}</label>";
		}
		elseif( $this->data['layout'] == 'bootstrap3' ){
			$input = "<div class='checkbox'><label><input type='checkbox' {$attrs} value='1'{$checked} /> {$this->input_helper}</label></div>";
		}
		elseif( $this->data['layout'] == 'bootstrap4' ){
			$input = "<div class='form-check'><input type='checkbox' {$attrs} value='1' {$checked} id='{$for}' class='form-check-input' /><label clas='form-check-label' for='{$for}'>{$this->input_helper}</label></div>";
		}
		
		return $input;
	}
}
