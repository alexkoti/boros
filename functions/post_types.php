<?php


/**
 * ==================================================
 * FORMATTED LINK ===================================
 * ==================================================
 * 
 * 
 * @todo resolver como fica a diferenciação de classes com list(<li>) e sem, já que na versão com lista as classes se repetem no <a>
 */
function formatted_post_type_link( $args ){
	global $post, $wp_query;
	//pre($post, '$post');
	//pre($wp_query, '$wp_query');
	$defaults = array(
		'post_type' => 'post',
		'id'        => false,
		'class'     => false,
		'text'      => false,
		'format'    => '%s',
		'title'     => false,
		'list'      => false,
		'echo'      => true,
		'append'    => null,
		'attr'      => false,
		'detect'    => null,
	);
	
	// se for string single, ou seja, foi pedido apenas o nome do post_type, e não é um string query
	if ( is_string($args) and strpos($args, '=') === false ){
		$post_type = $args;
		$args = array();
		$args['post_type'] = $post_type;
	}
	
	// processar em array os dados enviados pela chamada da função em $args
	$vars = wp_parse_args( $args, $defaults );
	extract( $vars, EXTR_SKIP );
	
	/* 
	 * Verificar se o post_type pedido é válido e/ou existe
	 * Encerra a função caso não exista
	 */
	if( !post_type_exists($post_type) ){
		return 'post_type inexistente';
	}
	
	// pegar labels e configs
	$post_type_obj = get_post_type_object($post_type);
	
	// url do link
	$link = array();
	// caso seja 'post', devolver a página definida para leitura de posts(home.php)
	if( $post_type == 'post' ){
		$blog_url = get_option('page_for_posts');
		if( !empty($blog_url) ){
			$link['url'] = get_permalink($blog_url);
		}
		else{
			$link['url'] = home_url('/');
		}
	}
	else{
		$link['url'] = get_post_type_archive_link( $post_type );
	}
	$link['echo'] = $echo;
	
	/* 
	 * Definir a class do link
	 */
	$link['class'] = active_post_type_class( $post_type, $detect );
	// adicionar custom class, se houver
	if( !empty($class) ){ $link['class'] .= " {$class}"; }
	
	// Se for declarado, usar custom id
	if( !empty($id) ){ $link['id'] = $id; }
	
	/* 
	 * Definir texto e title do link
	 */
	$link['text'] 	= ( empty($text) ) 	? $post_type_obj->labels->singular_name : $text;
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
	
	//pre($link, '$link');
	return formatted_link( $link );
}



function active_post_type_class( $post_type, $detect = null, $append = '' ){
	global $wp_query; //pre($wp_query);
	
	// class mínima
	$class = "post-type-item post-type-{$post_type}";
	
	// current post-type
	if( isset($wp_query->query_vars['post_type']) or isset($wp_query->queried_object->post_type) ){
		if( $post_type == $wp_query->query_vars['post_type'] or $post_type == $wp_query->queried_object->post_type ){
			$class = "post-type-item post-type-{$post_type} current-post-type current-item active {$append}";
		}
	}
	
	// é a home de blog?
	if( isset($wp_query->queried_object->ID) and $post_type == 'post' ){
		if( get_option('page_for_posts') == $wp_query->queried_object->ID ){
			$class = "post-type-item post-type-{$post_type} current-post-type current-item active {$append}";
		}
	}
	
	// detectar outros elementos, se definido
	if( !is_null($detect) ){
		$queried = $wp_query->queried_object;
		$detected_class = "post-type-item post-type-{$post_type} current-post-type current-item active {$append}";
		foreach( $detect as $key => $value ){
			if( $key == 'by_post_type' ){
				if( isset($wp_query->query_vars['post_type']) ){
					if( is_array($value) ){
						if( in_array($wp_query->query_vars['post_type'], $value) ){
							$detected_class .= ' detected_by_post_type';
						}
					}
					elseif( $wp_query->query_vars['post_type'] == $value ){
						$detected_class .= ' detected_by_post_type';
					}
				}
			}
			elseif( $key == 'by_id' ){
				if( isset($wp_query->post->ID) ){
					if( is_array($value) ){
						if( in_array( $wp_query->post->ID, $value ) )
							$class = "{$detected_class} detected_by_id";
					}
					elseif( $wp_query->post->ID == $value ){
						$class = "{$detected_class} detected_by_id";
					}
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



function remove_all_post_type_supports( $post_type = 'post', $supports = null ){
	$all = array(
		'editor',
		'author',
		'thumbnail',
		'comments',
		'trackbacks',
		'excerpt',
		'custom-fields',
		'revisions',
	);
	if( is_null($supports) )
		$supports = $all;
	
	foreach( $supports as $support ){
		remove_post_type_support( $post_type, $support );
	}
}



/**
 * ==================================================
 * ADMIN SUBMENU CUSTOM POST TYPE "ADD NEW" =========
 * ==================================================
 * Quando colocamos um post_type como submenu de outro local utilizando o argumento 'show_in_menu', é necessário corrigir
 * adicionando uma nova admin page para o "add new item", caso contrário acontecerá um bloqueio de permissão.
 * Function copiada do core wp-includes/post.php _add_post_type_submenus()
 * 
 * 
 * @todo futura remoção desta action, caso o ticket https://core.trac.wordpress.org/ticket/16808 seja resolvido
 * 
 */
add_action( 'admin_menu', '_boros_add_post_type_submenus', 99 );
function _boros_add_post_type_submenus() {
	foreach ( get_post_types( array( 'show_ui' => true ) ) as $ptype ) {
		$ptype_obj = get_post_type_object( $ptype );
		// Sub-menus only.
		if ( ! $ptype_obj->show_in_menu || $ptype_obj->show_in_menu === true )
			continue;
		add_submenu_page( $ptype_obj->show_in_menu, $ptype_obj->labels->add_new, $ptype_obj->labels->add_new_item, $ptype_obj->cap->edit_posts, "post-new.php?post_type=$ptype" );
	}
}



/**
 * ==================================================
 * POST_TYPE HELPS ==================================
 * ==================================================
 * 
 * Modelo com tabs e sidebar
<code>
$config = array(
	'post_type_name' => array(
		'list' => array(
			'tabs' => array(
				array( 'id1' => '', 'title' => '', 'content' => '' )
				array( 'id2' => '', 'title' => '', 'content' => '' )
			),
			'sidebar' => 'content'
		),
	)
);
</code>
 * 
 * Modelo apenas com as tabs
<code>
$config = array(
	'post_type_name' => array(
		'list' => array(
			array( 'id1' => '', 'title' => '', 'content' => '' )
			array( 'id2' => '', 'title' => '', 'content' => '' )
		),
	)
);
</code>
 */
class BorosPostTypeScreenHelp {
	// tela de listagem de posts
	var $list_screen = array();
	
	// tela de edição de post
	var $edit_screen = array();
	
	// tela de novo post
	var $new_screen = array();
	
	// adicionar helps para um único post_type
	function __construct( $config ){
		$post_type = $config['post_type'];
		if( isset($config['list']) )
			$this->list_screen[$post_type] = $config['list'];
		
		if( isset($config['edit']) )
			$this->edit_screen[$post_type] = $config['edit'];
		
		// usar o mesmo help de edit caso 'new' não tenha sido definido
		if( isset($config['new']) )
			$this->new_screen[$post_type] = $config['new'];
		else
			$this->new_screen[$post_type] = $config['edit'];
		
		add_action( 'load-edit.php', array( $this, 'add_list_helps' ) );		// list
		add_action( 'load-post.php', array( $this, 'add_edit_helps' ) );		// edit
		add_action( 'load-post-new.php', array( $this, 'add_new_helps' ) );		// new
	}
	
	// ajuda da listagem de posts
	function add_list_helps(){
		global $typenow;
		if( isset($this->list_screen[$typenow]) )
			$this->add_helps( $this->list_screen[$typenow] );
	}
	
	// ajuda de edição de post
	function add_edit_helps(){
		global $typenow;
		if( isset($this->edit_screen[$typenow]) )
			$this->add_helps( $this->edit_screen[$typenow] );
	}
	
	// ajuda de novo post
	function add_new_helps(){
		global $typenow;
		if( isset($this->new_screen[$typenow]) )
			$this->add_helps( $this->new_screen[$typenow] );
	}
	
	// adicionar as tabs, com opção de aceitar um array simples de tabs ou um associativo com 'tabs' e 'sidebar'
	function add_helps( $helps ){
		$screen = get_current_screen();
		// verificar se está separado em tabs e sidebar ou é apenas uma lista de tabs
		if( isset($helps['tabs']) ){
			$this->add_help_tabs( $helps['tabs'] );
			if( isset($helps['sidebar']) )
				$screen->set_help_sidebar($helps['sidebar']);
		}
		else{
			$this->add_help_tabs( $helps );
		}
	}
	
	// loop nas tabs
	function add_help_tabs( $tabs ){
		$screen = get_current_screen();
		foreach( $tabs as $tab ){
			$screen->add_help_tab( $tab );
		}
	}
}

/**
 * ==================================================
 * POST_TYPE COLUMNS ================================
 * ==================================================
 * Customizar colunas do admin
 * Para as telas core(posts, pages, attachment, media, links) usar o filtro 'manage_posts_custom_column'
 * ATENÇÃO:		existem dois hooks para as colunas 'manage_{posts}_custom_column' e 'manage_{pages}_custom_column', para post_types não-hierárquicos e hierárquicos.
 * 
 * @link	http://shibashake.com/wordpress-theme/add-admin-columns-in-wordpress
 * @link	http://shibashake.com/wordpress-theme/add-custom-post-type-columns
 * @link	http://nspeaks.com/1015/add-custom-taxonomy-columns-to-edit-posts-page/
 */

/**
 * Ordenar e registrar colunas para cada tipo de conteúdo:
 * 		'manage_posts_columns'
 * 		'manage_pages_columns'
 * 		'manage_media_columns'
 * 		'manage_edit-{post_type}_columns'
 * 
 * @return	array	array ordenado com as colunas de edição, formato 'name' => 'title'
 */
class BorosPostTypeColumns {
	var $post_type_columns = array();
	
	function __construct( $config ){
		$this->post_type_columns = $config['columns'];
		add_filter( "manage_edit-{$config['post_type']}_columns", array($this, 'post_type_columns') );
	}
	
	function post_type_columns( $columns ){
		return $this->post_type_columns;
	}
}

/**
 * Renderizar colunas customizadas.
 * Primeiro os 'names' core(cb, title, author, etc) são processados primeiros, caso não esteja no core é ativado a função render_columns( $column_name ), enviando
 * o 'name' da coluna para ser processada. Por conta do switch pode-se usar a mesma função para as colunas customizadas de todas as telas.
 * 
 */
add_action('manage_posts_custom_column', 'render_columns');
add_action('manage_pages_custom_column', 'render_columns');
function render_columns( $column_name ){
	global $post;
	
	/**
	 * Lista de termos
	 * 
	 */
	preg_match( '/^(terms_list_|terms_)(.*)/', $column_name, $taxonomy );
	if( isset($taxonomy[2]) ){
		$terms = get_the_terms( $post->ID, $taxonomy[2] );
		if( !is_wp_error( $terms ) and $terms !== false ){
			$out = array();
			foreach ( $terms as $c ){
				$out[] = "<a href='edit.php?post_type={$post->post_type}&{$c->taxonomy}={$c->slug}'> " . esc_html(sanitize_term_field('name', $c->name, $c->term_id, $c->taxonomy, 'display')) . "</a>";
			}
			echo join( ', ', $out );
		}
		return;
	}
	
	/**
	 * Executar a function correspondente caso seteja no formato 'function_{function_name}'
	 * É útil para adicionar functions de renderização sem interferir no switch com as opções padrão.
	 * 
	 * Essa function irá receber os seguintes parâmetros:
	 * @param $post
	 */
	preg_match( '/^function_(.*)/', $column_name, $function );
	if( isset($function[1]) ){
		if( function_exists($function[1]) ){
			call_user_func($function[1], $post->post_type, $post);
		}
		else{
			echo "<span class='form_element_error'>A function {$function[1]}() não existe.</span>";
		}
		return;
	}
	
	/**
	 * Thumbnail sizes
	 * 
	 */
	preg_match( '/^thumb_(.*)/', $column_name, $image_size );
	if( isset($image_size[1]) ){
		if( has_post_thumbnail() ){
			the_post_thumbnail($image_size[1]);
		}
		return;
	}
	
	/**
	 * POST_META
	 * 
	 */
	preg_match( '/^meta_(.*)/', $column_name, $meta );
	if( isset($meta[1]) ){
		$post_meta = get_post_meta($post->ID, $meta[1], true);
		if( empty($post_meta) ){
			echo 'não definido';
		}
		else{
			echo $post_meta;
		}
		return;
	}
	
	switch ($column_name) {
		case 'id':
			echo $post->ID;
			break;
		
		case 'ordem':
			echo $post->menu_order;
			break;
			
		case 'order':
			echo $post->menu_order;
			break;
			
		case 'thumb':
			if ( has_post_thumbnail() ) { the_post_thumbnail('thumbnail'); }
			break;
		
		case 'resumo':
			the_excerpt();
			break;
		
		case 'slug':
			echo $post->post_name;
			break;
		
		default:
			do_action( 'boros_custom_column', $post->post_type, $post, $column_name );
			break;
	}
}
