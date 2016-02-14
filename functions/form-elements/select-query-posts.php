<?php
/**
 * SELECT_QUERY_POSTS 
 * select usando valores resultantes de uma consulta WP_Query
 * 
 * $options para este controle:
 <code>
	'options' => array(
		'query' => array(
			'post_type' => 'artigo',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
		),
		'show_option_none' => false,
		'option_none_value' => 0,
		'messages' => array(
			'no_results' => 'Mensagem de nenhum resultado',
			'no_query' => 'Mensagem de query não definida',
		),
	),
 </code>
 *
 *
 *
 */

class BFE_select_query_posts extends BFE_select {
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => '',
	);
	
	function add_defaults(){
		$this->defaults['options']['show_option_none'] = false;
		$this->defaults['options']['option_none_value'] = 0;
		$this->defaults['options']['prepend'] = array();
		$this->defaults['options']['messages'] = array(
			'no_results' => 'Nenhum resultado encontrado',
			'no_query' => 'Erro: query de busca não definida',
		);
	}
	
	function set_input( $value = null ){
		// Verificar valor inicial, caso não haja nenhum valor gravado
		if( empty($this->data_value) )
			$this->data_value = $this->data['std'];
		
		// Objetos wp a serem buscados: posts, pages, post_type - qualquer wp_query válido
		$query = $this->data['options']['query'];
		$input = '';
		
		if( $query ){
			$contents = new WP_Query();
			$contents->query($query);
			
			// $args é exigido por walk_page_dropdown_tree(), que é a query original + o valor selecionado(ID do objeto)
			$args = $query;
			$args['selected'] = $this->data_value;
			
			
			// walk_page_dropdown_tree() exige a lista de objetos retornados pela query, a profundidade(aqui marcada como zero, pois os devidos filtros devem ser feitos na query) e $args, já definidos anteriormente.
			if( $contents->posts ){
				$attrs = make_attributes($this->data['attr']);
				$input = "<select {$attrs}>\n";
				if( $this->data['options']['show_option_none'] ){
					$input .= "\t<option value='{$this->data['options']['option_none_value']}'>{$this->data['options']['show_option_none']}</option>\n";
				}
				if( !empty($this->data['options']['prepend']) ){
					foreach( $this->data['options']['prepend'] as $k => $v ){
						$selected = selected( $this->data_value, $k, false );
						$input .= "\t<option value='{$k}'{$selected}>{$v}</option>\n";
					}
				}
				$input .= walk_page_dropdown_tree($contents->posts, 0, $args);
				$input .= "</select>\n{$this->input_helper}";
			}
			else{
				$input = $this->data['options']['messages']['no_results'];
			}
		}
		else {
			$input = $this->data['options']['messages']['no_query'];
		}
		return $input;
	}
}