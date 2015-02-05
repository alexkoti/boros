<?php
/**
 * TAXONOMY_SELECT
 * 
 * 
 */

class BFE_taxonomy_select extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
	);
	
	function add_defaults(){
		$this->defaults['options']['type'] = 'meta_box'; // post_meta, option, term_meta
		$this->defaults['options']['taxonomy'] = 'category';
		$this->defaults['options']['show_option_all'] = ' — ';
		$this->defaults['options']['hide_empty'] = false;
	}
	
	function set_input( $value = null ){
		global $post;
		
		/**
		 * Caso seja uma chamada ajax, $post não estará disponível. Todas as variáveis vindo do ajax
		 * 
		 */
		if( isset($_GET['ajax_post_id']) )
			$post = get_post( intval($_GET['ajax_post_id']) );
		
		// sempre esperar um array de termos
		foreach( (array)$value as $v ){
			$selected_terms = $v;
		}
		
		
		/**
		 * Essa verificação que busca o valor gravado em banco em vez do reload enviado em $value, deverá ser usado na edição de post/term/user em frontend_forms
		 * 
		 */
		/**
		// termo selecionado - verifica se está buscando post_meta ou taxonomy_meta
		$selected_terms = false;
		if( isset($_GET['taxonomy']) ){
			if( isset($_GET['tag_ID']) ){
				$selected_terms = get_metadata( 'term', intval($_GET['tag_ID']), $this->data['options']['taxonomy'], true );
			}
		}
		elseif( isset($this->data['options']['object_type']) and $this->data['options']['object_type'] == 'admin_page' ){
			$selected_terms = get_option( $this->data['name'] );
		}
		else{
			$selecteds = wp_get_object_terms( $post->ID, $this->data['options']['taxonomy'] );
			if( !empty($selecteds) ){
				foreach( $selecteds as $tt ){
					$selected_terms = absint( $tt->term_id );
				}
			}
		}
		/**/
		
		// caso esteja vazio e possua um default, aplicar
		if( empty( $selected_terms ) and !empty( $this->data['std'] ) ){
			$default_term = get_term_by( 'name', $this->data['std'], $this->data['options']['taxonomy'] );
			$selected_terms = $default_term->term_id;
		}
		
		$args = array(
			'taxonomy'        => $this->data['options']['taxonomy'],
			'selected'        => $selected_terms, 
			'id'              => $this->data['attr']['id'],
			'class'           => $this->data['attr']['class'] . "taxonomy_select taxonomy_{$this->data['options']['taxonomy']}",
			'show_option_all' => $this->data['options']['show_option_all'],
			'hide_empty'      => $this->data['options']['hide_empty'],
			'echo'            => false,
		);
		
		// definir o name conforme o contexto
		// @todo melhorar para tentar deixar essa parte automática, mas avaliar se existirão casos onde
		//       um meta_box poderá precisar de um name diferente de tax_input[]
		if( $this->defaults['options']['type'] == 'post_meta' ){
			$args['name'] = "tax_input[{$this->data['options']['taxonomy']}]";
		}
		elseif( $this->defaults['options']['type'] == 'option' ){
			$args['name'] = $this->data['name'];
		}
		elseif( $this->defaults['options']['type'] == 'term_meta' ){
			$args['name'] = $this->data['name'];
		}
		
		// Remover os atributos que já definidos em args. Eles serão definidos em wp_dropdown_categories()
		unset($this->data['attr']['name']);
		unset($this->data['attr']['id']);
		unset($this->data['attr']['class']);
		// Criar os atributos auxiliares. Alguns como data-name são importantes para o controle duplicável
		$attrs = make_attributes($this->data['attr']);
		
		// Criar o dropdown
		$tdp = wp_dropdown_categories( $args );
		// adicionar os atributos auxiliares
		$input = str_replace( '<select ', "<select {$attrs} ", $tdp );
		
		return $input;
	}
}




