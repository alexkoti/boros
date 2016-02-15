<?php
/**
 * CONTENT_ORDER
 * Ordenar conteúdos conforme a query da configuração. Pode ser post, page ou post_type
 * Para categorias e custom taxonomies, usar 'term_order'
 * 
 * @todo arrumar TYPO em ContenOrderTermsOrder
 * @todo revisar para os casos de falta de confiração requerida
 * @todo em terms, possibilitar multi-level
 * @todo SEPARAR EM DUAS CLASSES: BFE_content_order e BFE_taxonomy_order, para poder setar um callback automático. >>>> tentar fazer um callback autoregistrável nos includes, exemplo:
 */

class BFE_content_order extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
	);
	
	var $enqueues = array(
		'js'  => 'content-order',
		'css' => 'content-order',
	);
	
	static function set_callback_functions(){
		return array('taxonomy_term_order');
	}
	
	function set_input( $value = null ){
		$class = empty($this->data['size']) ? 'content_order_list iptw_medium' : "content_order_list iptw_{$this->data['size']}";
		$input = '';
		
		// $query definida, então é para buscar na tabela posts
		if( isset( $this->data['options']['query'] ) ){
			$contents = new WP_Query();
			$contents->query($this->data['options']['query']);
			if( $contents->posts ){
				$input .= "<input type='hidden' class='boros_form_input content_order_values iptw_large' name='{$this->data['name']}' value='{$this->data_value}' />";
				$input .= "<ul class='{$class}'>";
				foreach($contents->posts as $post){
					setup_postdata($post);
					$title = apply_filters('the_title',$post->post_title);
					$input .= "<li class='sort_item' id='content_order_{$post->ID}' rel='{$post->ID}'>{$title}</li>";
				}
				$input .= "</ul>{$this->input_helper}";
			}
		}
		// buscar termos da taxonomia
		elseif( isset( $this->data['options']['taxonomy'] ) ){
			$tax = (array)$this->data['options']['taxonomy'];
			$name = isset( $tax['taxonomy_name']) ? $tax['taxonomy_name'] : 'category';
			
			$input .= "<input type='hidden' class='boros_form_input content_order_values iptw_large' name='{$this->data['name']}' value='{$this->data_value}' />";
			
			$walker = new ContenOrderTermsOrder();
			$args = array(
				'hide_empty' => false,
				'title_li' => '',
				'taxonomy' => $name,
				'walker' => $walker,
				'echo' => false,
				'hierarchical' => false,
				'orderby' => 'term_order',
			);
			$input .= "<ul class='{$class}'>";
			$input .= wp_list_categories($args);
			$input .= "</ul>{$this->input_helper}";
			/**
			$terms = get_terms( $name, array('hide_empty' => false) );
			if( $terms ){
				$input .= "<input type='hidden' class='boros_form_input content_order_values iptw_large' name='{$this->data['name']}' value='{$this->data_value}' />";
				$input .= "<ul class='{$class}'>";
				foreach($terms as $term){
					$input .= "<li class='sort_item' id='content_order_{$term->term_id}' rel='{$term->term_id}'>{$term->name}</li>";
				}
				$input .= "</ul>{$this->input_helper}";
			}
			/**/
		}
		else{
			$input = "<p>Não foi definido uma configuração para este controle. É preciso requisitar uma <code>query</code>(post|page|post_type) ou <code>taxonomy</code>(category|tag|custom_taxonomy).</p>{$this->input_helper}";
		}
		
		// adicionar input_help
		if( isset($this->input_helper) )
			$input .= $this->input_helper;
		
		return $input;
	}
}












