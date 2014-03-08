<?php
/**
 * TAXONOMY_CHECKBOX
 * 
 * 
 * @ATENÇÃO: como este controle usa wp_terms_checklist() do core, não é possível aplicar a class 'boros_form_input'
 * @todo revisar e testar
 * @todo revisar para usar melhor o 'object_type'
 * @todo adicionar opção de deixar os termos já selecionados no topo
 */

class BFE_taxonomy_checkbox extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
	);
	
	function add_defaults(){
		$this->defaults['options']['taxonomy'] = 'category';
		$this->defaults['options']['force_hierachical'] = true;
		$this->defaults['options']['force_compact'] = false;
	}
	
	// Checkboxgroups não possuem label inicial, possuindo cada opção seu próprio label.
	function set_label(){
		if( !empty($this->data['label']) )
			$this->label = apply_filters( "BFE_{$this->data['type']}_label", "<span class='non_click_label'>{$this->data['label']}{$this->label_helper}</span>" );
	}
	
	function set_input( $value = null ){
		global $post;
		
		// $object_id será usado por wp_terms_checklist, e someente no caso de 'post' irá procurar as taxonomias relativas diretamente ao objeto
		// caso seja 'taxonomy' ou 'user' será '0', para que faça a busca geral em todos os termos da taxonomia
		$object_id = 0;
		
		/**
		 * Caso seja uma chamada ajax, $post não estará disponível. Todas as variáveis vindo do ajax
		 * 
		 */
		if( isset($_GET['ajax_post_id']) )
			$post = get_post( intval($_GET['ajax_post_id']) );
		
		// termo selecionado - verifica se está buscando post_meta ou taxonomy_meta
		$selected_terms = array();
		if( isset($_GET['taxonomy']) ){
			if( isset($_GET['tag_ID']) ){
				$selected_terms = get_metadata( 'term', intval($_GET['tag_ID']), $this->data['options']['taxonomy'], true );
			}
		}
		elseif( isset($this->data['options']['object_type']) and $this->data['options']['object_type'] == 'admin_page' ){
			$selected_terms = get_option( $this->data['name'] );
		}
		else{
			$object_id = $post->ID;
			$selecteds = wp_get_object_terms( $post->ID, $this->data['options']['taxonomy'] );
			foreach( $selecteds as $tt ){
				$selected_terms[] = absint( $tt->term_id );
			}
		}
		
		
		// caso esteja vazio e possua um default, aplicar
		if( empty( $selected_terms ) and !empty( $this->data['std'] ) ){
			$default_term = get_term_by( 'name', $this->data['std'], $this->data['options']['taxonomy'] );
			$selected_terms = array( $default_term->term_id );
		}
		
		// adicionar filtro para o input name dos checkboxes
		//$input_name = ( $this->data['options']['taxonomy'] == 'category' ) ? 'category' : 'tax_input';
		//add_filter( 'wttc_input_name', create_function('', "return '{$input_name}';") );
		//pre( $this->context );
		
		$args = array(
			'taxonomy' => $this->data['options']['taxonomy'],
			'checked_ontop' => false,
			'selected_cats' => $selected_terms, 
			'walker' => new Walker_Taxonomy_Terms_Checklist($this->data['name']), 
		);
		
		// force hierachical
		if( $this->data['options']['force_hierachical'] == true )
			$class = "taxonomy_checkbox_list force_hierachical";
		else
			$class = "taxonomy_checkbox_list ";
		
		// force compact view
		if( $this->data['options']['force_compact'] == true )
			$divclass = "taxonomy_checkbox_div force_compact";
		else
			$divclass = "taxonomy_checkbox_div ";
		
		// começar a guardar o output do script js em buffer
		ob_start();
		
		echo "<div class='{$divclass}'>";
		echo "<ul class='{$class}'>";
		wp_terms_checklist( $object_id, $args );
		echo '</ul>';
		echo '</div>';
		
		// guardar o output em variável
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}




