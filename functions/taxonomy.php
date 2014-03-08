<?php
/**
 * TAXONOMY FUNCTIONS
 * Funções para expandir funcionalidades das taxonomias e termos
 * As primeiras funções são para uso com $post, estão relacionadas a um conteúdo(post, page, post_type)
 * As outras são relacionadas apenas aos termos, considerando o contexto geral.
 * 
 * 
 * @TODO: revisar e separar as functions antigas para deprecated(setor no final do arquivo
 */


/**
 * GET SINGLE TERM ==================================
 * Buscar o termo de nível mais baixo(caso seja hierárquical) atribuido a um post.
 * Se o post estiver classificado em mais de um termo no mesmo nível, serão retornados todos os termos.
 * 
 * @params string $taxonomy_name - nome da taxonomia para buscar
 * @return object
 */
function get_single_term( $taxonomy_name = 'category' ){
	global $post;
	$terms = get_the_terms( $post->ID, $taxonomy_name );
	if( $terms ){
		$args = array(
			'orderby' => 'name',
			'order' => 'ASC',
			'hide_empty' => 0,
			'depth' => 0,
		);
		// organizar os termos em ordem hierárquica, do nível mais baixo para o mais alto
		$ordered_terms = array_reverse( walk_simple_taxonomy( $terms, $args['depth'], $args ) );
		return $ordered_terms[0];
	}
}
function single_post_term( $post_id, $taxonomy = 'category' ){
	$terms = wp_get_object_terms( $post_id, $taxonomy );
	if( $terms )
		return $terms[0];
	else
		return false;
}
function single_post_term_link( $post_id, $taxonomy = 'category' ){
	$terms = wp_get_object_terms( $post_id, $taxonomy );
	$link = get_term_link( (int)$terms[0]->term_id, $taxonomy );
	return "<a href='{$link}'>{$terms[0]->name}</a>";
}
function get_single_post_term_link( $post_id, $taxonomy = 'category' ){
	$terms = wp_get_object_terms( $post_id, $taxonomy );
	$link = get_term_link( (int)$terms[0]->term_id, $taxonomy );
	return array(
		'link' => $link,
		'name' => $terms[0]->name,
	);
}

/**
 * ==================================================
 * FLAT TERMS =======================================
 * ==================================================
 * Lista simples de todos os termos em determinada taxonomia.
 * 
 * @param int $post_id - id do post
 * @param int $taxonomy_name - name da taxonomia
 * @params string $before, $sep, $after - formataÃ§Ã£o de retorno
 */
function flat_terms( $post_id, $taxonomy_name, $before = '', $sep = ', ', $after = '' ){
	$terms = get_the_terms( $post_id, $taxonomy_name );
	if( $terms ){
		foreach( $terms as $term ){
			$t[] = "<span id='{$taxonomy_name}_{$term->term_id}'>{$term->name}</span>";
		}
		return $before . join( $sep, $t ) . $after;
	}
}

/**
 * ==================================================
 * IN TERM ==========================================
 * ==================================================
 * Verificar se o post/page/custom pertence à um termo de determinada taxonomia. Semelhante aos in_category() e has_tags()
 * 
 * @param	string	$term		termo a ser pesquisado
 * @param	string	$taxonomy	taxonomia que o termo pertence
 * @param	string	$_post		post a ser verificado
 * 
 * referencias:
 * 		/wp-includes/category-template.php -> line 270
 * 		/wp-includes/category-template.php -> line 1003
 */
function _in_term( $term, $taxonomy, $_post = null ) {
	if ( empty( $term ) )
		return false;

	if ( $_post ) {
		$_post = get_post( $_post );
	} else {
		$_post =& $GLOBALS['post'];
	}

	if ( !$_post )
		return false;

	$r = is_object_in_term( $_post->ID, $taxonomy, $term );
	if ( is_wp_error( $r ) )
		return false;
	return $r;
}

/**
 * Verificar se o post esta em alguma das subcategorias
 * 
 * @param	string	$post_id		post a ser verificado
 * @param	string	$taxonomy	taxonomia que o termo pertence
 * @param	string	$terms		termos para verificar
 */
function post_is_in_descendant_term( $post_id, $taxonomy, $terms ){
	foreach ( (array) $terms as $term ) {
		// get_term_children() accepts integer ID only
		$descendants = get_term_children( (int) $term, $taxonomy);
		if ( $descendants && is_object_in_term( $post_id, $taxonomy, $descendants ) )
			return true;
	}
	return false;
}

/**
 * ==================================================
 * BOROS TERMS ======================================
 * ==================================================
 * Lista simples de todos os termos na ordem hierárquica, em determinada taxonomia. Semelhante ao the_category(), mas hierárquico.
 * Caso esteja classificado em mais de um term do mesmo nível, esses serão separados por $same_level_sep.
 * $before e $after estão dentro da função para que a saída HTML seja completamente opcional, caso não sejam encontrados termos para o post.
 * 
 * @param	int		$post_id			id do post
 * @param	string	$taxonomy_name	name da taxonomia
 * @param	bool		$linked			linkar as termos
 * @param	string	$before			HTML antes da lista
 * @param	string	$sep				separador entre níveis
 * @param	string	$same_level_sep	separador no mesmo nível
 * @param	string	$after			HTML depois da lista
 * @return	string	lista dos termos formatados em HTML.
 * @uses		walk_simple_taxonomy()
 */
function boros_terms( $post_id = null, $taxonomy_name = 'category', $linked = true, $before = '', $sep = '&gt; ', $same_level_sep = ', ', $after = '' ){
	if( is_null($post_id) ){
		global $post;
		$post_id = $post->ID;
	}
	
	$terms = get_the_terms( $post_id, $taxonomy_name );
	if( $terms ){
		$t = '';
		$args = array(
			'orderby' => 'name',
			'order' => 'ASC',
			'hide_empty' => 0,
			'depth' => 0,
		);
		// organizar os termos em ordem hierárquica, do nível mais alto para o mais baixo
		$ordered_terms = walk_simple_taxonomy( $terms, $args['depth'], $args );
		$last_level = end($ordered_terms);
		
		foreach( $ordered_terms as $level ){
			$last_item = end($level);
			foreach( $level as $term ){
				// caso tenha mais de um item no mesmo nível, separar por $same_level_sep
				if( count($level) > 1 )
					$separator = $same_level_sep;
				else
					$separator = $sep;
				
				// se for último item do último level, não adicionar $sep
				if( $last_item == $term )
					$separator = ( $last_level == $level ) ? '' : $sep;
				
				if( $linked == true ){
					$taxonomy_link = get_term_link( $term, $taxonomy_name );
					$t .= "<a href='{$taxonomy_link}' id='{$taxonomy_name}_{$term->term_id}'>{$term->name}</a> {$separator}";
				}
				else{
					$t .= "<span id='{$taxonomy_name}_{$term->term_id}'>{$term->name}</span> {$separator}";
				}
			}
		}
		return $before . $t . $after;
	}
}

/**
 * ==================================================
 * TERM/PARENT RELATIONSHIPS ========================
 * ==================================================
 * Essa sequência de functions é relacionada aos termos em contexto geral, insto é, não relacionados
 * a nenhum post|conteúdo específico.
 */

/**
 * TERM ID BY SLUG ==================================
 *
 * @param	string	$term_name	optional, o padrão é 'uncategorized'
 * @param	string	$taxonomy	taxonomia que termo pertence
 * @return	int		retorna 0, caso falhe, ou a ID do termo em caso de sucesso.
 */
function get_term_ID( $term_name = 'uncategorized', $taxonomy = 'category' ) {
	$term = get_term_by( 'slug', $term_name, $taxonomy );
	if ( $term )
		return $term->term_id;
	return 0;
}

/**
 * GET TOP TERM =====================================
 * Pegar o termo de nível mais alto, em caso de hierarchical taxonomy
 * 
 * @param	object	$term
 * @param	string	$taxonomy_name
 * @return	object	$term
 */
function get_top_term( $term, $taxonomy_name ){
	if( $term->parent != 0 ){
		$parent = get_term( $term->parent, $taxonomy_name );
		return get_top_term( $parent, $taxonomy_name );
	}
	else{
		return $term;
	}
}

/**
 * GET TOP CATEGORY =================================
 * Versão direta para categorias
 */
function get_top_category( $category_name ){
	return get_top_term( $category_name, 'category' );
}

/**
 * GET TERM ANCESTORS ===============================
 * Pegar as ID de todos os parents do termo
 * 
 * @param	object	$term
 * @param	string	$taxonomy
 * @param	string	$ancestors	not necessary, é usado apenas para a recursão
 * @return	array	$ancestors	array com as ids dos termos parents
 * 
 * 
 * @modify_date 2013.02.02 - removido "Call-time pass-by-reference" --> em &$_config foi removido o "&" por conta das mudanças do PHP 5.4
 * 
 */
function get_term_ancestors( $term, $taxonomy, $ancestors = array() ){
	if( $term->parent != 0 ){
		$ancestors[] = $term->parent;
		$parent = get_term( $term->parent, $taxonomy );
		//return get_term_ancestors( $parent, $taxonomy, &$ancestors );
		return get_term_ancestors( $parent, $taxonomy, $ancestors );
	}
	else{
		return $ancestors;
	}
}

/**
 * TERM IS CHILD OF =================================
 * Verificar se o termo é child de outro
 * 
 * @param	object	$term
 * @param	string	$term_parent
 * @param	string	$taxonomy
 * @return	bool
 */
function term_is_child_of( $term, $term_parent, $taxonomy ){
	if( $term->parent != 0 ){
		if( $term->parent == $term_parent )
			return true;
		$parent = get_term( $term->parent, $taxonomy );
		return term_is_child_of( $parent, $term_parent, $taxonomy );
	}
	else{
		return false;
	}
}

/**
 * TERM IS ANCESTOR OF ==============================
 * Verificar se um termo é parent de outro.
 * Copiado de cat_is_ancestor_of(), localizado em wp-includes/category.php
 * 
 * @param	object|int	$term1		termo a ser verificado como child do $term2
 * @param	object|int	$term2		termo a ser verificado como parent do $term1
 * @param	string		$taxonomy
 * @return	bool
 */
function boros_term_is_ancestor_of( $term1, $term2, $taxonomy ){
	if ( ! isset($term1->term_id) )
		$term1 = &get_term( $term1, $taxonomy );
	if ( ! isset($term2->parent) )
		$term2 = &get_term( $term2, $taxonomy );
	
	if ( empty($term1->term_id) || empty($term2->parent) )
		return false;
	if ( $term2->parent == $term1->term_id )
		return true;
	
	return boros_term_is_ancestor_of( $term1, get_term( $term2->parent, $taxonomy ), $taxonomy );
}

/**
 * GET TERM PARENTS =================================
 * Listagem sequencial de termos, em contexto geral, não atribuidos a posts.
 * Como é feito o caminho entre parent>child em uma só direção, não é preciso separador de mesmo nível.
 * Para exibir termos de um post|conteúdo, usar boros_terms()
 * 
 * @param	int		$term_id
 * @param	string	$taxonomy
 * @param	bool		$link		linkar termo
 * @param	string	$separator	separadore entre termos
 * @param	array	$visited		not necessary, é usado apenas para a recursão
 * @return	bool
 */
function _get_term_parents( $term_id, $taxonomy, $link = false, $separator = ' &gt; ', $visited = array() ) {
	$chain = '';
	$parent = &get_term( $term_id, $taxonomy );
	if ( is_wp_error( $parent ) ){
		return $parent;
	}
	
	$name = $parent->name;
	
	if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) ) {
		$visited[] = $parent->parent;
		$chain .= _get_term_parents( $parent->parent, $taxonomy, $link, $separator, $visited );
	}

	if ( $link )
		$chain .= '<a href="' . get_term_link( $parent->slug, $taxonomy ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $parent->name ) ) . '">'.$name.'</a>' . $separator;
	else
		$chain .= $name.$separator;

	return $chain;
}

/**
 * ==================================================
 * FORMATTED TERM LINK ==============================
 * ==================================================
 * Retornar um link html formatado do termo pedido pelo slug(preferido) ou título.
 * 
 * @param	string	$term_name 	slug termo desejado.
 * @param	string	$taxonomy 	Default 'category' - nome da taxonomia a qual pertence o termo.
 * @param	string	$id 			custom id para ser exibida no html
 * @param	string	$class 		custom class, será adicionada junto às classes criadas pela função.
 * @param	string	$text 		texto personalizado para exibir, pode ser usado sintaxe printf
 * @param	string	$title 		texto personalizado o atributo 'title' do link
 * @param	string	$list 		definir a saída em <a> ou <li>
 * @param	bool		$echo 		Default true - definir se é para exibir pu retornar o link
 * @param	array	$append		adicionar query string no href do link. Usar array associativo
 * @param	array	$detect 		array que define se é preciso tentar detectar outras condições além das normais para 'ativar' o link, 
 * 								o modelo é array('tipo-de-condicao' => 'valor-para-verificar'). Os tipos e valores são:
 * 								by_post_type	string		verifica o post_type do conteudo corrente
 * 								by_id		int|array	verifica o ID do conteúdo corrente
 * 								by_term		string		verifica o slug do termo corrent
 * 								conditional	mixed		verifica se a condição declarada é true, exemplo: is_category(), is_front_page(), '23 > 12', 'azul' == 'azul', etc
 * 
 * @uses		term_is_child_of()
 * @uses		term_is_ancestor_of()
 * @uses		post_is_in_descendant_term()
 * 
 * @return	string	link html formatado da categoria|termo
 * 
 * @todo resolver como fica a diferenciação de classes com list(<li>) e sem, já que na versão com lista as classes se repetem no <a>
 */
function formatted_category_link( $args ){
	return formatted_term_link( $args );
}
function formatted_term_link( $args ){
	global $post, $wp_query;
	//pre($wp_query);
	$defaults = array(
		'term_name'	=> null,
		'taxonomy'	=> 'category',
		'id' 		=> false,
		'class' 	=> false,
		'text' 		=> false,
		'format' 	=> '%s',
		'title' 	=> false,
		'list' 		=> false,
		'echo' 		=> true,
		'append' 	=> null,
		'detect' 	=> null,
	);
	
	// se for string single, ou seja, foi pedido apenas o nome do termo sem definir taxonomia, e não é um string query
	if ( is_string($args) and strpos($args, '=') === false ){
		$term_name = $args;
		$args = array();
		$args['term_name'] = $term_name;
		$args['taxonomy'] = 'category';
	}
	
	// processar em array os dados enviados pela chamada da função em $args
	$attr = wp_parse_args( $args, $defaults );
	extract( $attr, EXTR_SKIP );
	
	if( empty($term_name) )
		return 'termo não definido';
	
	/* 
	 * Verificar se o term pedido é válido e/ou existe
	 * Encerra a função caso não exista
	 */
	// tenta pegar pelo slug
	$term = get_term_by('slug', $term_name, $taxonomy);
	// se falhar tenta pelo name
	if( $term == false ){
		$term = get_term_by('name', $term_name, $taxonomy);
	}
	if( $term == false ){
		return 'termo não encontrado';
	}
	$term_id = (int)$term->term_id;
	if( empty($term_id) )
		return 'termo não encontrado';
	
	// url do link
	$link = array();
	$link['url'] = get_term_link( $term, $taxonomy );
	$link['echo'] = $echo;
	
	/* 
	 * Definir a class do link
	 * Adiciona classes específicas em caso de subitens e ancestors
	 */
	# class padrão, sempre irá possuir
	$link['class'] = active_taxonomy_class($term, $taxonomy, $detect);
	
	# adicionar custom class, se houver
	if( !empty($class) ){ $link['class'] .= " $class"; }
	
	
	/* 
	 * Definir o id do link
	 * Sempre retornará um id(custom|permalink|post_id)
	 */
	# se for declarado, usar custom id
	if( !empty($id) ){ $link['id'] = $id; }
	# id do link, baseado no slug/permalink
	# @obs : só funciona com os pretty permalinks ativados
	elseif( get_option('permalink_structure') != '' ){ $link['id'] = 'term_' . basename($link['url']); }
	# id numérico
	else{ $link['id'] = 'term_' . $term_id; }
	
	/* 
	 * Definir texto e title do link
	 */
	$link['text'] 	= ( empty($text) ) 	? $term->name : $text;
	$link['title'] 	= ( empty($title) ) ? $link['text'] : $title;
	
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

function active_taxonomy_class( $term, $taxonomy, $detect = null, $append = '' ){
	global $wp_query, $post;
	
	// class mínima
	$class = "term-item term-item-{$term->term_id} taxonomy-{$taxonomy}";
	
	// listagem de categoria
	if( is_category($term->term_id) ){
		$class .= " current-term current-category current-item active {$append}";
	}
	// listagem de tag
	elseif( is_tag($term->slug) ){
		$$class .= " current-term current-tag current-item active {$append}";
	}
	// listagem custom_taxonomy
	elseif( is_tax() ){
		$current = (int)$wp_query->queried_object->term_id;
		if( is_tax($taxonomy, $term->term_id)	)
			$class .= " current-term current-{$taxonomy} current-item active {$append}";
		elseif( term_is_child_of( $term, $current, $taxonomy ) )
			$class .= " current-term-child current-{$taxonomy}-child current-item active {$append}";
		elseif( term_is_ancestor_of( $term->term_id, $current, $taxonomy ) )
			$class .= " current-term-parent current-{$taxonomy}-parent current-item active {$append}";
	}
	// se for um single dentro da categoria
	elseif( is_single() ){
		if( _in_term( $term, $taxonomy, $wp_query->queried_object_id) ){
			$class .= " current-term current-{$taxonomy} current-item active {$append}";
		}
		// faz parte de uma subcategoria
		elseif( post_is_in_descendant_term( $post->ID, $taxonomy, $term->term_id ) and !is_tax($taxonomy, $term->term_id) ){
			//echo "in_taxonomy -> descendant = {$term->name}";
			$class .= " current-term-parent current-{$taxonomy}-parent current-item active {$append}";
		}
	}
	
	// detectar outros elementos, se definido
	if( !is_null($detect) ){
		$queried = $wp_query->queried_object;
		$detected_class = "term-item term-item-{$term->term_id} current-term current-{$taxonomy} current-item active {$append}";
		foreach( $detect as $key => $value ){
			if( $key == 'by_post_type' ){
				if( get_post_type($wp_query->post->ID) == $value ){
					$class = "{$detected_class} detected_by_post_type";
				}
			}
			elseif( $key == 'by_id' ){
				if( is_array($value) ){
					if( in_array( $wp_query->post->ID, $value ) )
						$class = "{$detected_class} detected_by_id";
				}
				elseif( $wp_query->post->ID == $value ){
					$class = "{$detected_class} detected_by_id";
				}
			}
			elseif( $key == 'by_term' ){
				if( isset($queried->term_id) ){
					if( $queried->slug == $value ){
						$class = "{$detected_class} detected_by_term";
					}
				}
			}
			elseif( $key == 'conditional' ){
				if( call_user_func($value) == true ){
					$class = "{$detected_class} detected_by_conditional";
				}
			}
		}
	}
	return $class;
}


