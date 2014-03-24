<?php
/**
 * FUNÇÔES DE ADMIN: WALKER EXTEND
 * Funções auxiliares para extender o Walker. Criar saída de listagem de pages e categorias a aprtir de consulta ao banco, com HTML formatado em:
 * 		{pages|categories}_nested_list() - elementos anunhados e 
 * 		{pages|categories}_flat_list() - elementos sem aninhamento.
 * 
 * TODO:	Ampliar para utilizar cutom post_types e *_meta
 * TODO: adicionar nas classes extendidas o prefixo {boros}, para facilitar a busca quando estiver usando uma function com um desses callbacks
 */



/**
 * ==================================================
 * BOOTSTRAP COLUMN MENU ============================
 * ==================================================
 * Filtro para menu colunado
 * 
 */
class _bootstrap_column_menu_walker extends Walker_Nav_Menu {
	
	function __construct(){
		pal(1);
	}
	
	function start_lvl( &$output, $depth = 0, $args = array() ){
		
		$output .= "\n" . $indent . '<ul class="' . $class_names . '">' . "\n";
	}
	
	function start_el( &$output, $item, $depth = 0, $args = array(), $current_object_id = 0 ){
		//pre($item, 'item');
		//pre($args, 'args');
		
		$output .= apply_filters( 'walker_nav_menu_start_el', $output, $item, $depth, $args );
	}
}



/**
 * ==================================================
 * FORM ELEMENT :: CONTENT ORDER :: TERMS ===========
 * ==================================================
 * Ordenação para termos de taxonomias
 * 
 * 
 */
class ContenOrderTermsOrder extends Walker_Category {
	var $terms = array();
	
	function start_lvl( &$output, $depth = 0, $args = array() ){
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul>\n";
	}
	function end_lvl( &$output, $depth = 0, $args = array() ){
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}
	function start_el( &$output, $term, $depth = 0, $args = array(), $current_object_id = 0 ){
		extract($args, EXTR_SKIP);
		
		// guardar terms
		$this->terms[$term->term_id] = $term;
		// pad
		if( $term->parent != 0 ){
			$parent = get_category( $term->parent );
			$parent_name = $parent->name;
			$pad = " &nbsp; <small><em>{$parent_name}</em></small> - ";
		}
		else{
			$pad = '';
		}
		
		$output .= "\t <li id='content_order_{$term->term_id}' class='sort_item level_{$depth}' rel='{$term->term_id}'>{$pad}{$term->name}\n";
	}
	function end_el( &$output, $object, $depth = 0, $args = array() ){
		$output .= "</li>\n";
	}
}






/**
 * Chamada do walker para ordenar um array de termos|categorias em nível hierárquico
 * 
 */
function walk_simple_taxonomy(){
	$args = func_get_args();
	$walker = new Walker_Simple_Taxonomy;
	return call_user_func_array(array( &$walker, 'walk' ), $args );
}

/**
 * Child class para fazer a ordenação hierárquica de termos|categorias
 * 
 */
class Walker_Simple_Taxonomy extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');
	var $active_level = 0;
	
	function start_lvl( &$output, $depth = 0, $args = array() ){
		$this->active_level = $depth + 1;
		if( !is_array($output) )
			$output = array();
		$output[$this->active_level] = array();
	}
	
	function end_lvl( &$output, $depth = 0, $args = array() ){
		$this->active_level = $depth;
	}
	
	function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ){
		$output[$this->active_level][] = $object;
	}
}

/* ========================================================================== */
/* TAXONOMY TERMS WALKER ==================================================== */
/* ========================================================================== */
/**
 * Clone da class 'Walker_Category_Checklist' presente em wp-admin/includes/template.php
 * Gera uma lista de checkboxes com os termos da taxonomia escolhida. A única diferença entre a esta e a class original são os names dos inputs e classes de label/input, adequados para a edição de taxonomy_meta
 * 
 */
class Walker_Taxonomy_Terms_Checklist extends Walker {
	var $tree_type = 'category';
	
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this
	
	var $input_name;
	
	/**
	 * Filtrar o input 'name' dos checkboxes, para que possibilite gravar categoria, terms, post_meta ou option
	 * 
	 */
	function __construct( $input_name = false ){
		/**
		 * @deprecated - não é mais necessário o filtro, pois é possível passar o argumento pelo construct
		 * @link http://wordpress.stackexchange.com/a/31267
		 * 
		 */
		if( $input_name === false ){
			$this->input_name = apply_filters( 'wttc_input_name',  'category' );
		}
		else{
			$this->input_name = $input_name;
		}
	}

	function start_lvl( &$output, $depth = 0, $args = array() ){
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	function end_lvl( &$output, $depth = 0, $args = array() ){
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ){
		extract($args);
		
		$class = in_array( $object->term_id, $popular_cats ) ? " class='popular-category terms_level_{$depth}'" : "class='terms_level_{$depth}'";
		$output .= "\n<li id='{$taxonomy}-{$object->term_id}'$class>" . '<label class="label_checkbox"><input value="' . $object->term_id . '" type="checkbox" name="'.$this->input_name.'[]" class="boros_form_input input_checkbox" id="in-'.$taxonomy.'-' . $object->term_id . '"' . checked( in_array( $object->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $object->name )) . '</label>';
	}

	function end_el( &$output, $object, $depth = 0, $args = array() ){
		$output .= "</li>\n";
	}
}



/* ========================================================================== */
/* CUSTOM WALKER MENU NAV =================================================== */
/* ========================================================================== */
/**
 * Personalizar a saída dos menus
 * @link http://www.kriesi.at/archives/improve-your-wordpress-navigation-menu-output
 * @link http://wordpress.stackexchange.com/questions/14037/menu-items-description
 * 
<code>
wp_nav_menu(
	array (
		'menu'            => 'main-menu',
		'container'       => FALSE,
		'container_id'    => FALSE,
		'menu_class'      => '',
		'menu_id'         => FALSE,
		'depth'           => 1,
		'walker'          => new Description_Walker
	)
);
</code>
 */
class description_walker extends Walker_Nav_Menu{
	function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ){
		global $wp_query;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );
		$class_names = ' class="'. esc_attr( $class_names ) . '"';

		$output .= $indent . '<li id="menu-item-'. $item->ID . '"' . $value . $class_names .'>';

		$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
		$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
		$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
		$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';

		$prepend = '<strong>';
		$append = '</strong>';
		$description  = ! empty( $item->description ) ? '<span>'.esc_attr( $item->description ).'</span>' : '';

		if($depth != 0){
			$description = $append = $prepend = "";
		}
		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		$item_output .= $args->link_before .$prepend.apply_filters( 'the_title', $item->title, $item->ID ).$append;
		$item_output .= $description.$args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
}




/* ========================================================================== */
/* LISTAGEM CATEGORIAS ====================================================== */
/* ========================================================================== */
/**
 * Cria HTML da lista de categorias com elementos aninhados - NESTED
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class categories_nested_list extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');
	
	function start_lvl( &$output, $depth = 0, $args = array() ){
		if ( 'list' != $args['style'] )
			return;

		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}
	function end_lvl( &$output, $depth = 0, $args = array() ){
		if ( 'list' != $args['style'] )
			return;

		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}
	function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ){
		extract($args);

		$cat_name = esc_attr( $object->name);
		$cat_id = esc_attr( $object->term_id);
		if ( 'list' == $args['style'] ) {
			$output .= "\t" . '<li id="cat_' . $cat_id . '" class="lineitem_1 sortline seta_' . $depth . '"><span class="handle">&nbsp;</span> ' . $cat_name . "\n";
		}
		else {
			$output .= "\t$cat_name";
		}
	}
	function end_el( &$output, $object, $depth = 0, $args = array() ){
		if ( 'list' != $args['style'] )
			return;

		$output .= "</li>\n";
	}
}


/**
 * Cria HTML da lista de categorias com elementos não-aninhados - FLAT
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class categories_flat_list extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');
	
	function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ){
		extract($args);

		$cat_name = esc_attr( $object->name);
		$cat_id = esc_attr( $object->term_id);
		if ( 'list' == $args['style'] ) {
			$output .= "\t" . '<li id="cat_' . $cat_id . '" class="lineitem_1 sortline seta_' . $depth . '"><span class="handle">&nbsp;</span> ' . $cat_name . '</li>' . "\n";
		}
		else {
			$output .= "\t$cat_name";
		}
	}
}


/**
 * Configurar a chamada da classe 'categories_nested_list' ou 'categories_flat_list', conforme $type
 *
 * @param string $type Tipo de lista retornada: aninhada('nested'), ou não-aninhada('flat'), default 'nested'
 */
function show_categories_menu_list( $type = 'nested' ){
	// declarar array $args. Único item obrigatório é o 'style', que configura o retorno em <li>
	$args = array();
	$args['style'] = 'list';
	
	// declarar array $data.
	//	Obrigatórios:
	//	'categories': objeto com categorias a serem parseadas. Precisa ser object, e não lista de IDs;
	//	'depth': tipo de indentação, 0 = indentado, -1 = sem indentação
	//  'args': array de argumentos diversos que podem ser processados pelos metodos personalizados. Item obrigatório: 'style'
	$data = array();
	$data['categories'] = get_categories( 'hide_empty=0' );
	$data['depth'] = 0;
	$data['args'] = $args;
	
	// criar objeto
	if( $type == 'nested' ){
		$walker = new categories_nested_list;
	} else {
		$walker = new categories_flat_list;
	}
	
	/*
		Esta chamada é bem complexa:
		É chamado o objeto recém criado $walker, que herdou do class Walker o método walk(), 
		que é a função que navega por todos os itens verificando o aninhamento e aplicando 
		os métodos personalizados definidos na classe: 'start_lvl', 'end_lvl', 'start_el', 'end_el'.
		Por isso é declarado o objeto criado (&$walker) e o método a ser ativado ('walk').
		
		Depois é enviado para o walk() as variáveis de $args
		
	 */
	return call_user_func_array(array( &$walker, 'walk' ), $data );
}



/* ========================================================================== */
/* LISTAGEM PAGES =========================================================== */
/* ========================================================================== */
/**
 * Cria HTML da lista de páginas com elementos aninhados - NESTED
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class pages_nested_list extends Walker {
	var $tree_type = 'page';
	var $db_fields = array ('parent' => 'post_parent', 'id' => 'ID');
	
	function start_lvl( &$output, $depth = 0, $args = array() ){
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul>\n";
	}
	function end_lvl( &$output, $depth = 0, $args = array() ){
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}
	function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ){
		extract($args, EXTR_SKIP);

		$page_name = $object->post_title;
		$page_id = $object->ID;
		$output .= "\t" . '<li id="page_' . $page_id . '" class="lineitem_2 sortline seta_' . $depth . '">' . $page_name . "\n";
	}
	function end_el( &$output, $object, $depth = 0, $args = array() ){
		$output .= "</li>\n";
	}
}


/**
 * Cria HTML da lista de páginas com elementos não-aninhados - FLAT
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class pages_flat_list extends Walker {
	var $tree_type = 'page';
	var $db_fields = array ('parent' => 'post_parent', 'id' => 'ID');
	
	function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ){
		// extrair os argumentos enviados pela função 'show_pages_menu_list' em array associativo nome:valor
		extract($args, EXTR_SKIP);
		//pre($args);

		$page_name = $object->post_title;
		$page_id = $object->ID;
		
		// filtragem para o selected
		if( is_array($filter) ){
			if( !in_array($page_id, $filter) ){
				$filter_insert = '';
			}
		}
		
		$output .= "\t" . '<li id="page_' . $page_id . '" class="lineitem_2 sortline level_' . $depth . ' ' . $filter_insert . '">' . $page_name . "\n";
	}
}


/**
 * Configurar a chamada da classe 'pages_nested_list' ou 'pages_flat_list', conforme $type
 *
 * @param string $type Tipo de lista retornada: aninhada('nested'), ou não-aninhada('flat'), default 'nested'
 */
function show_pages_menu_list( $type = 'nested', $filter = '', $filter_insert = '', $exclude = array() ){

	// declarar array $data.
	//	Obrigatórios:
	//	'pages': objeto com as pages a serem parseadas. Precisa ser object, e não lista de IDs;
	//	'depth': tipo de indentação, 0 = indentado, -1 = sem indentação
	//  'args': array de argumentos diversos que podem ser processados pelos metodos personalizados. Eles são declarados como variáveis da função(neste caso são $filter e $filter_insert)
	$data = array();
	$data['pages'] = get_pages( array( 'exclude' => $exclude ) );
	$data['depth'] = 0;
	$data['args'] = array('filter' => $filter, 'filter_insert' => $filter_insert);
	
	// criar objeto
	if( $type == 'nested' ){
		$walker = new pages_nested_list;
	} else {
		$walker = new pages_flat_list;
	}
	
	/*
		Esta chamada é bem complexa:
		É chamado o objeto recém criado $walker, que herdou do class Walker o método walk(), 
		que é a função que navega por todos os itens verificando o aninhamento e aplicando 
		os métodos personalizados definidos na classe: 'start_lvl', 'end_lvl', 'start_el', 'end_el'.
		Por isso é declarado o objeto criado (&$walker) e o método a ser ativado ('walk').
		
		Depois é enviado para o walk() as variáveis de $args
		
	 */
	return call_user_func_array(array( &$walker, 'walk' ), $data );
}
