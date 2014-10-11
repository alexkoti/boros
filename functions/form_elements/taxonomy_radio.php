<?php
/**
 * TAXONOMY_RADIO
 * radio group com os termos da taxonomia pedida
 * 
 * Permite escolher o field do termo a ser gravado, que pode ser 'term_id'(padrão), 'name' ou 'slug'
 * 
 * @todo: aplicar walker para organizar taxonomias hierárquicas
 * 
 * $options para este controle:
 <code>
	'options' => array(
		'taxonomy' => 'taxonomy_name',
		'show_option_none' => false,
		'option_none_value' => false,
		'save_field' => 'term_id',
	),
 </code>
 * 
 * 
 * 
 */

class BFE_taxonomy_radio extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => '',
		'value' => '',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
	);
	
	function add_defaults(){
		$this->defaults['options']['show_option_none'] = false;
		$this->defaults['options']['option_none_value'] = 0;
		$this->defaults['options']['checked_ontop'] = false;
	}
	
	// Radiogroups não possuem label inicial, possuindo cada opção seu próprio label.
	function set_label(){
		if( !empty($this->data['label']) )
			$this->label = apply_filters( "BFE_{$this->data['type']}_label", "<p style='margin:0;'>{$this->data['label']}{$this->label_helper}</p>" );
	}
	
	function set_input( $value = null ){
		global $post;
		
		// separador, o parão é <br />
		$separator = isset($this->data['options']['separator']) ? $this->data['options']['separator'] : '<br />';
		// wrapper, opcional
		$wrapper = isset($this->data['options']['wrapper']) ? $this->data['options']['wrapper'] : '';
		// caso o layout tenha sido definido como list
		$layout = isset($this->data['options']['layout']) ? $this->data['options']['layout'] : 'simple';
		$input = '';
		
		/**
		 * Caso queira usar este controle como substituto da taxonomia, não preencha o name na declaração do metabox. Caso contrário, 
		 * a informação será gravado como post_meta.
		 * 
		 */
		$terms = get_terms( $this->data['options']['taxonomy'], 'hide_empty=0' );
		$selected_term = false;
		
		/**
		 * Separar os contextos:
		 * - recuperar terms gravados
		 * - definir o name do campo, em post_meta será o tax_input substituindo o metabox padrão
		 * 
		 * @todo verificar no contexto de frontend, como deverá ser quando se está editando ou adicionando um novo 'post'
		 */
		switch( $this->context['type'] ){
			// recuperar o term_id gravado
			case 'option':
			case 'user_meta':
			case 'termmeta':
			case 'frontend':
				$selected_term = $value;
				$name = $this->data['name'];
				break;
			
			// taxonomy terms associado ao post. Por ser um rádio, espera-se que possua apenas um term
			case 'post_meta':
				$selected_terms = wp_get_object_terms( $post->ID, $this->data['options']['taxonomy'] );
				if( !empty($selected_terms) ){
					$selected_term = $selected_terms[0]->term_id;
				}
				else{
					$selected_term = false;
				}
				$name = "tax_input[{$this->data['options']['taxonomy']}][]";
				break;
		}
		
		if( !empty($terms) ){
			$radios = array();
			$valid_fields = array('term_id', 'name', 'slug');
			$option_none_checked = ( count($selected_term) == 0 or $selected_term == false ) ? ' checked="checked"' : '';
			
			if ( $this->data['options']['show_option_none'] == true )
				$radios[] =  "<input type='radio' name='{$name}' value='{$this->data['options']['option_none_value']}'{$option_none_checked} id='{$this->data['options']['taxonomy']}_0' rel='{$this->data['options']['taxonomy']}_0' class='boros_form_input input_radio' /><label for='{$this->data['options']['taxonomy']}_0' class='label_radio iptw_{$this->data['size']}'>{$this->data['options']['show_option_none']}</label><br />";
			
			$checked_ontop = '';
			foreach( $terms as $term ){
				// Definir o atributo id
				if( !empty($this->data['name']) )
					$id = "{$this->data['name']}_{$term->term_id}";
				else
					$id = "{$this->data['options']['taxonomy']}_{$term->term_id}";
				
				// Definir o atributo value, que normalmente é o term_id
				$field = 'term_id';
				if( isset($this->data['options']['save_field']) and in_array($this->data['options']['save_field'], $valid_fields) ){
					$field = $this->data['options']['save_field'];
					$save_field = $term->$field;
				}
				$save_field =  $term->$field;
				
				// verificar defaults/checked
				$checked = '';
				if( empty($this->data['name']) ){
					if( count($selected_term) == 0 or $selected_term == false ){
						if( $term->$field == $this->data['std'] ){
							$checked = ' checked="checked"';
						}
					}
					// obs: usado index 0(zero) por considerar apenas um termo para ser escolhido
					else{
						if( $term->$field == $selected_term and !is_wp_error($term) ){
							$checked = ' checked="checked"';
						}
					}
				}
				else{
					$checked = checked( $selected_term, $term->$field, false );
				}
				
				// criar input + label
				$element = "<input type='radio' name='{$name}' value='{$save_field}'{$checked} id='{$id}' rel='{$this->data['options']['taxonomy']}_{$term->term_id}' class='boros_form_input input_radio' /><label for='{$id}' class='label_radio iptw_{$this->data['size']}'>{$term->name}</label>";
				
				// aplicar wrapper, caso tenha sido definido
				if( $wrapper != '' )
					$element = sprintf( $wrapper, $element );
				
				// aplicar wrapper de listagem
				if( $layout == 'list' ){
					$element = sprintf( '<li>%s</li>', $element );
				}
				
				// guardar elemento caso 'checked_ontop' seja true
				if( ($this->data['options']['checked_ontop'] == true) and ($term->term_id == $selected_term) ){
					$checked_ontop = $element;
				}
				else{
					$radios[] = $element;
				}
			}
			
			// adicionar o $checked_ontop ao começo da lista
			if( $this->data['options']['checked_ontop'] == true ){
				array_unshift( $radios, $checked_ontop );
			}
			
			// fechar em item de lista, caso o layout seja list
			if( $layout == 'list' ){
				$input = sprintf( '<ul class="taxonomy_radio_list">%s</ul>', implode( '', $radios ) );
			}
			else{
				$input = implode( $separator, $radios );
			}
		}
		else{
			$input = '<div class="form_element_error">Não existem opções para esta classificação.</div>';
		}
		
		return $input;
	}
}



