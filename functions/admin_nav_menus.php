<?php
/**
 * ==================================================
 * CUSTOMIZAÇÃO DO PAINEL DE MENUS ==================
 * ==================================================
 * 
 * 
 */


// FIXO: Adicionar item 'Home' às páginas dsponíveis para criar menus
add_filter( 'wp_page_menu_args', 'boros_home_page_menu_args' );
function boros_home_page_menu_args( $args ){
	$args['show_home'] = true;
	return $args;
}

/**
 * ==================================================
 * ADICIONAR CPT ARCHIVE LINK =======================
 * ==================================================
 * Clone do plugin "Custom Post Type Archives in Nav Menus"
 * 
 */
class BorosCptArchiveNavMenu {
	public function __construct() {
		add_action( 'admin_head-nav-menus.php', array( $this, 'add_filters' ) );
	}

	public function add_filters() {
		$post_type_args = array(
			'show_in_nav_menus' => true
		);

		$post_types = get_post_types( $post_type_args, 'object' );

		foreach ( $post_types as $post_type ) {
			if ( $post_type->has_archive ) {
				add_filter( 'nav_menu_items_' . $post_type->name, array( $this, 'add_archive_checkbox' ), null, 3 );
			}
		}
	}

    public function add_archive_checkbox( $posts, $args, $post_type ) {
        global $_nav_menu_placeholder, $wp_rewrite;
        $_nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? intval($_nav_menu_placeholder) - 1 : -1;

        //dump( $post_type, '$post_type', 'htmlcomment' );

        $archive_slug = $post_type->has_archive === true ? $post_type->rewrite['slug'] : $post_type->has_archive;
        if ( $post_type->rewrite['with_front'] ){
            $archive_slug = substr( $wp_rewrite->front, 1 ) . $archive_slug;
        } else {
            $archive_slug = $wp_rewrite->root . $archive_slug;
        }

        array_unshift( $posts, (object) array(
            'ID' => 0,
            'object_id' => $_nav_menu_placeholder,
            'post_content' => '',
            'post_excerpt' => '',
            'post_title' => $post_type->labels->all_items,
            'post_type' => 'nav_menu_item',
            'post_parent' => 0,
            'type' => 'custom',
            'url' => site_url( $archive_slug ),
        ) );

        return $posts;
    }
}
//$BorosCptArchiveNavMenu = new BorosCptArchiveNavMenu();





