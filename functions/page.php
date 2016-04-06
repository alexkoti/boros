<?php
/**
 * PAGE FUNCTIONS
 * Funções para expandir funcionalidades de pages e post_types hierarquicos
 * 
 * 
 * 
 */



/**
 * ==================================================
 * PAGE ID BY TITLE|NAME ============================
 * ==================================================
 * Pegar a ID da página a partir do título(post_title) ou slug(post_name), similar ao core get_page_by_title();
 * É armazenado em cache a string 'null' em caso de erro na query, pois é preciso um valor não nulo(null, 0, empty, false) para criar o cache.
 * 
 * @param	string		$page_name	título ou slug da página desejada
 * @param	string		$post_type	Default 'page', aceita outros post_types
 * @return	int|null		ID da página requisitada ou 'null' string
 */
function get_page_ID_by_name( $page_name, $post_type = 'page' ){
	global $wpdb;
	
	// definir a query
	$query = "
		SELECT ID FROM $wpdb->posts 
		WHERE (post_title = '$page_name' OR post_name = '$page_name') 
		AND post_type = '$post_type'
		AND post_status = 'publish'
		";
	/*
	 * verificar o cache
	 */
	$cache_key = 'page_'.$page_name;
	$page_id = wp_cache_get( $cache_key, 'page_names' );
		// se não estiver no cache
	if ( false == $page_id ) {
		// fazer a query
		$page_id = $wpdb->get_var( $query );
		
		// deixar um valor não nulo
		$page_id = ( is_null($page_id) ) ? 'null' : $page_id;
		// armazenar no cache
		wp_cache_set( $cache_key, $page_id, 'page_names' );
	}
	
	return $page_id;
}



/**
 * ==================================================
 * IS SUB PAGE ======================================
 * ==================================================
 * Verificar se a página atual é uma subpage
 * 
 * @param	integer		$parent_id	Optional - Se for declarado, verifica se o post atual é child(qualquer nível) de $parent_id, retornando true|false
 * @return	int|false	se for true, retorna a ID do parent, se não, retorna false;
 */
function is_subpage( $parent_id = 0 ){
	global $post;
	if ( is_page() && $post->post_parent ) {
		if( $parent_id == 0 ){
			return $post->post_parent;
		}
		else{
			$ancestors = get_post_ancestors( $post );
			if( in_array( $parent_id, $ancestors ) ){
				return true;
			}
		}
	} else {
		   return false;
	}
}



/**
 * ==================================================
 * Versão para custom post_types ====================
 * ==================================================
 * 
 * @param	object	$post		objeto do post a ser verificado
 * @param	int		$parent_id	id do post a ser verificado se é parent do post
 * @return	bool
 */
function has_parent( $post, $parent_id ) {
	if ($post->ID == $parent_id)
		return true;
	elseif ($post->post_parent == 0)
		return false;
	else
		return has_parent( get_post($post->post_parent), $parent_id );
}



/**
 * ==================================================
 * PAGE PERMALINK BY NAME ===========================
 * ==================================================
 * Pegar a url(permalink) da página pelo titulo ou slug
 * 
 * @param	string	$page_name	título ou slug da página desejada
 * @return 	string	url absoluta da página
 * @uses		get_page_ID_by_name()
 */
function page_permalink_by_name( $page_name, $echo = true, $post_type = 'page' ){
	$page_id = get_page_ID_by_name($page_name, $post_type);
	if( $page_id == 'null' ){
		$link = 'link inválido';
	}
	else{
		$link = get_permalink( $page_id );
	}
	if( $echo == true )
		echo $link;
	return $link;
}



/**
 * ==================================================
 * NEXT PAGE LINK ===================================
 * ==================================================
 * Similiar ao next_post_link() do core, porém aplicado a post_type hierárquico
 * 
 */
function boros_adjacent_page_link( $format, $link, $previous = true ){
	global $wpdb, $post;
	// pegar todas as páginas
	$args = "sort_column=menu_order&sort_order=asc&post_type={$post->post_type}&post_parent={$post->post_parent}";
	$page_list = get_pages( $args );
	$page_order = array();
	// ordenar as IDs
	foreach( $page_list as $page ){
		$page_order[] = $page->ID;
	}
	// pegar a página corrente
	$current = array_search( $post->ID, $page_order );
	$adjacent = ($previous == true) ? $current - 1 : $current + 1;
	// caso não exista, retornar
	if( !isset($page_order[$adjacent]) ){
		return;
	}
	// recuperar o post completo
	foreach( $page_list as $page ){
		if( $page->ID == $page_order[$adjacent] ){
			$adjacent_page = $page;
			break;
		}
	}
	$permalink = get_permalink( $adjacent_page->ID );
	$title = apply_filters( 'the_title', $adjacent_page->post_title );
	$text = str_replace( '%title', $title, $link );
	$anchor = "<a href='{$permalink}'>{$text}</a>";
	$output = str_replace( '%link', $anchor, $format );
	return $output;
}
function boros_next_page_link( $format = '&larr; %link', $link = '%title' ){
	echo boros_adjacent_page_link( $format, $link, true );
}
function boros_prev_page_link( $format = '%link &rarr;', $link = '%title' ){
	echo boros_adjacent_page_link( $format, $link, false );
}



/**
 * ==================================================
 * FORMATTED PAGE LINK ==============================
 * ==================================================
 * Retornar um link html formatado da página pedida pelo título ou slug.
 * Pode ser requerido em string simples, apenas o nome da página ou em query string, com os atributos desejados
 * 
 * 
 * @param 	string	$page_name 	required - título ou slug da página desejada
 * @param 	string	$post_type 	post_type caso seja diferente de page
 * @param 	string	$id 			id para ser exibida no html
 * @param 	string	$class 		class para ser adicionada a mais no html
 * @param 	string	$text 		texto para ser exibido no lugar da página
 * @param 	string	$title 		title do elemento
 * @param 	bool		$list 		definir a saída em <a> ou <li>
 * @param 	bool		$echo 		definir se dará saida direto para o navegador
 * @param	array	$append		adicionar query string no href do link. Usar array associativo
 * @param	array	$detect 		array que define se é preciso tentar detectar outras condições além das normais para 'ativar' o link, 
 * 								o modelo é array('tipo-de-condicao' => 'valor-para-verificar'). Os tipos e valores são:
 * 								'by_post_type'	string		verifica o post_type do conteudo corrente
 * 								'by_id'			int|array	verifica o ID do conteúdo corrente
 * 								'conditional'		mixed		verifica se a condição declarada é true, exemplo: is_category(), is_front_page(), '23 > 12', 'azul' == 'azul', etc
 *
 * @uses 	get_page_ID_by_name()
 * @uses 	formatted_link()
 *
 * @return 	string 	link html formatado da página
 * 
 * @todo resolver como fica a diferenciação de classes com list(<li>) e sem, já que na versão com lista as classes se repetem no <a>
 */
function formatted_page_link( $args = array() ){
	global $post, $wp_query;
	$defaults = array(
		'page_name' => null,
		'page_id'   => null,
		'post_type' => 'page',
		'id'        => false,
		'class'     => false,
		'text'      => false,
		'format'    => '%s',
		'list'      => false,
		'echo'      => true,
		'detect'    => null,
		'append'    => null,
		'attr'      => false,
	);
	
	// se for string single, ou seja, foi pedido apenas o nome da categoria sem query, definir o $pagename e usar os defaults
	if ( is_string($args) and strpos($args, '=') === false ){
		$page_name = $args;
		extract( $defaults, EXTR_SKIP );
	}
	// processar em array os dados enviados pela chamada da função em $args
	else {
		$vars = wp_parse_args( $args, $defaults );
		extract( $vars, EXTR_SKIP );
	}
	
	/* 
	 * Verificar se a página pedida é válida e/ou existe
	 * Encerra a função caso não exista
	 */
	if( is_null($page_id) ){
		global $wpdb;
		$page_id = get_page_ID_by_name( $page_name, $post_type );
		if( empty($page_name) or empty($page_id) ){
			return 'link inválido';
		}
	}
	
	// url do link | echo
	$link = array();
	$link['url'] = get_permalink( $page_id );
	$link['echo'] = $echo;
	
	/* 
	 * Definir a class do link
	 * Adiciona classes específicas em caso de subpages e ancestors
	 */
		// link comum
	$link['class'] = active_page_class( (int)$page_id, $post_type, $detect );
	
		// adicionar custom class, se houver
	if( !empty($class) ){ $link['class'] .= " $class"; }
	
	/* 
	 * Definir o id do link
	 * Sempre retornará um id(custom|permalink|post_id)
	 */
		// se for declarado, usar custom id
	if( !empty($id) ){ $link['id'] = $id; }
		// id do link, baseado no slug/permalink
		// @obs : só funciona com os pretty permalinks ativados
	elseif( get_option('permalink_structure') != '' ){ $link['id'] = "{$post_type}_" . basename(get_permalink($page_id)); }
		// id numérico
	else{ $link['id'] = "{$post_type}-item-" . $page_id; }
	
	/* 
	 * Definir texto e title do link
	 */
	$post_title = get_the_title($page_id);
	$link['text'] 	= ( empty($text) ) 	? $post_title : sprintf($text, $post_title);
	$link['title'] 	= ( empty($title) ) ? $post_title : $title;
	
	/* 
	 * Adicionar attrs
	 */
	$link['attr'] = $attr;
	
	/* 
	 * Definir formato(<a>|<li>) do link
	 */
	$link['list'] = isset($list) ? $list : false;
	
	/* 
	 * Adicionar o append se houver
	 */
	$link['append'] = $append;
	
	/* 
	 * Adicionar format
	 */
	$link['format'] = $format;
	
	return formatted_link( $link );
}

/**
 * ==================================================
 * ACTIVE PAGE CLASS ================================
 * ==================================================
 * Definir as classes de uma determinada página(page|hierarchical post_type) para ser usada em links, indicando seu status em relação à página corrente.
 * Sempre compara a página página requisitada com o local atual da url, verificando as condições para aplicar as classes adequadas.
 * 
 * @param	int|string	$page		se for integer será usado puro, caso seja string será feita uma busca ATENÇÃO!!! caso envia um integer, certificar com typecasting (int)
 * @param	string		$post_type
 * @param	array		$detect		definições de detecção, para que possa identificar a page atual como ativa, mesmo estando em outra, 
 * 									por ex. marcar como 'current_item' a page 'Posts', quando exibido uma single de post_type 'post'.
 * 
 * @return 	string		lista de classes do link
 */
function active_page_class( $page, $post_type = 'page', $detect = null, $append = '' ){
	global $wp_query, $post;
	
	// Pegar page ID caso seja passado um nome(string) em $page
	$page_id = ( is_numeric($page) ) ? $page : get_page_ID_by_name( $page, $post_type );
	
	// Class padrão mínima
	$class = "{$post_type}_item";
	
	// Está na exata página do link
	// É utilizado get_queried_object(), pois é possível que $post já tenha sido modificado, por query_posts, custom queries, filtros, etc
	$queried = $wp_query->get_queried_object();
	if( isset($queried->ID) AND ($queried->ID == $page_id) ){
		$class = "{$post_type}_item current_{$post_type}_item current-menu-item current-item current active {$append}";
	}
	
	// Subpage ou page_parent
	$parent = is_subpage( $page_id );
	if( $parent ){
		// subpage
		$class = "{$post_type}_item current_{$post_type}_subitem current-menu-item current-item current active {$append}";
		// page_parent
		if($parent == $page_id){
			$class = "{$post_type}_item current-{$post_type}-ancestor current_{$post_type}_parent current-menu-item current-item current active {$append}";
		}
	}
	
	// Detectar outras condições customizadas
	if( !is_null($detect) ){
		$detected_class = '';
		foreach( $detect as $key => $value ){
			switch( $key ){
				case 'by_post_type':
					if( isset($queried->post_type) ){
						if( is_array($value) ){
							if( in_array($queried->post_type, $value) ){
								$detected_class .= ' detected_by_post_type';
							}
						}
						elseif( $queried->post_type == $value ){
							$detected_class .= ' detected_by_post_type';
						}
					}
					break;
				
				case 'by_id':
					if( isset($queried->ID) ){
						if( is_array($value) ){
							if( in_array($queried->ID, $value) )
								$detected_class .= ' detected_by_id';
						}
						elseif( $queried->ID == $value ){
							$detected_class .= ' detected_by_id';
						}
					}
					break;
				
				case 'conditional':
					if( call_user_func($value) == true ){
						$detected_class .= ' detected_by_conditional';
					}
					break;
			}
		}
		if( !empty($detected_class) ){
			$class = "{$post_type}_item current_{$post_type}_item current-menu-item current-item current active {$append}{$detected_class}";
		}
	}
	
	return $class;
}