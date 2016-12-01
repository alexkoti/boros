<?php
/**
 * DASHBOARD
 * 
 * As functions boros_add_dashboard_notification() e boros_remove_dashboard_notification() estão em extend_wp.php pois 
 * precisam ser carregadas no frontend e admin
 * 
 */


/**
 * ==================================================
 * UPDATES CHECKS ===================================
 * ==================================================
 * Verificar itens que necessitam checagem manual em caso de atualizações.
 * 
 */
add_action( 'admin_init', 'boros_update_checks' );
function boros_update_checks(){

    /**
     * Verificar o plugin code do tinymce
     * 
     */
    global $pagenow;
    if( $pagenow == 'index.php' ){
        $alerts = get_option('boros_dashboard_notifications');
        
        // verificar plugin "CODE" de tinymce
        if( !file_exists( ABSPATH . '/wp-includes/js/tinymce/plugins/code/plugin.min.js' ) ){
            boros_add_dashboard_notification('need_tinymce_code_plugin', 'É preciso atualizar os plugins do tinymce, adicionando o plugin "<code>code</code>"');
        }
        else{
            boros_remove_dashboard_notification('need_tinymce_code_plugin');
        }
        
        // verificar se o plugin wp-email-login está ativo
        if( function_exists('dr_email_login_authenticate') ){
            boros_add_dashboard_notification('plugin_wp_email_login_active', 'O plugin Wp Email Login está ativo, porém ele não é mais necessário, pois as funcionalidades deste foram incorporadas no plugin base.');
        }
        else{
            boros_remove_dashboard_notification('plugin_wp_email_login_active');
        }
    }
}

/**
 * ==================================================
 * DASHBOARD NOTIFICATIONS ==========================
 * ==================================================
 * Mostrar mensagens importantes de aviso de desenvolvimento.
 * 
 */
add_action( 'wp_dashboard_setup', 'boros_dashboard_notifications_widget' );
function boros_dashboard_notifications_widget(){
    $alerts = get_option('boros_dashboard_notifications'); //pre($alerts);
    if( empty($alerts) ){
        return;
    }
    wp_add_dashboard_widget(
        'boros_dashboard_notifications_widget',       // Widget slug.
        'Mensagens e alertas',                        // Title.
        'boros_dashboard_notifications_widget_output' // Display function.
    );
}

function boros_dashboard_notifications_widget_output(){
	$alerts = get_option('boros_dashboard_notifications'); //pre($alerts);
	if( !empty($alerts) ){
		echo '<ol>';
		foreach( $alerts as $name => $alert ){
			$nonce = wp_create_nonce($name);
			echo "<li data-alert-name='{$name}' data-alert-nonce='{$nonce}'>{$alert} <span class='dashicons dashicons-dismiss'></span></li>";
		}
		echo '</ol>';
	}
	else{
		echo 'Sem mensagens';
	}
}

add_action('admin_enqueue_scripts', 'boros_dashboard_admin_enqueue_scripts');
function boros_dashboard_admin_enqueue_scripts( $hook ){
	if( $hook == 'index.php' ){
		wp_enqueue_script( 'boros-dashboard-script', BOROS_JS . 'boros-dashboard-script.js', 'jquery', null, true );
	}
}

add_action('wp_ajax_boros_dashboard_notifications_widget_remove_item', 'boros_dashboard_notifications_widget_remove_item');
function boros_dashboard_notifications_widget_remove_item(){
    check_ajax_referer( $_POST['alert'], 'nonce', true );
    boros_remove_dashboard_notification( $_POST['alert'] );
    $alerts = get_option('boros_dashboard_notifications'); //print_r($alerts); echo 'aaaa';
    die();
}



/**
 * ==================================================
 * AT A GLANCE ======================================
 * ==================================================
 * Substituto do 'Right Now' a partir da versão 3.8 do WordPress, mostrando todos os posts types e taxonomies
 * 
 * 
 */
add_filter( 'dashboard_glance_items', 'boros_dashboard_right_now' );
function boros_dashboard_right_now( $elements ){
	// post types públicos e privados
	$types = array(true, false);
	foreach( $types as $t ){
		$args = array(
			'public' => $t ,
			'_builtin' => false
		);
		$output = 'object';
		$operator = 'and';
		$post_types = get_post_types( $args , $output , $operator );
		foreach( $post_types as $post_type ) {
			$num_posts = wp_count_posts( $post_type->name );
			$num = number_format_i18n( $num_posts->publish );
			$text = _n( $post_type->labels->singular_name, $post_type->labels->name , intval( $num_posts->publish ) );
			if ( current_user_can( 'edit_posts' ) ) {
				$elements[] = "<a href='edit.php?post_type={$post_type->name}' class='ico-post-type-{$post_type->name} {$post_type->menu_icon}'>{$num} {$text}</a>";
			}
			else{
				$elements[] = "<span class='ico-post-type-{$post_type->name} {$post_type->menu_icon}'>{$num} {$text}</span>";
			}
		}
	}
	
	$taxonomies = get_taxonomies( $args , $output , $operator );
	foreach( $taxonomies as $taxonomy ) {
		$num_terms  = wp_count_terms( $taxonomy->name );
		$num = number_format_i18n( $num_terms );
		$text = _n( $taxonomy->labels->singular_name, $taxonomy->labels->name , intval( $num_terms ) );
		$class = issetor($taxonomy->menu_icon, '');
		if ( current_user_can( 'manage_categories' ) ) {
			$elements[] = "<a href='edit-tags.php?taxonomy={$taxonomy->name}&post_type={$taxonomy->object_type[0]}' class='ico-taxonomy-{$taxonomy->name} {$class}'>{$num} {$text}</a>";
		}
		else{
			$elements[] = "<span class='ico-taxonomy-{$taxonomy->name} {$class}'>{$num} {$text}</span>";
		}
	}
	//pre($elements);
	
	return $elements;
}



/**
 * ==================================================
 * DASHBOARD WIDGETS ================================
 * ==================================================
 * Remover os widgets padrão, deixando apenas o 'At a Glance'
 * 
 * @link http://codex.wordpress.org/Dashboard_Widgets_API
 */
add_action( 'wp_dashboard_setup', 'boros_remove_dashboard_widget', 1 );
function boros_remove_dashboard_widget(){
	global $wp_meta_boxes;
	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
}



/**
 * ==================================================
 * RIGHT NOW :: DEPRECATED ==========================
 * ==================================================
 * 
 * Adicionar os custom post_types ao dashboard widget 'Right Now'
 * Esta action é fixa
 * 
 * @link http://new2wp.com/snippet/add-custom-post-types-to-the-right-now-dashboard-widget/
 */
add_action( 'right_now_content_table_end' , 'right_now_advanced' );
function right_now_advanced() {
	$args = array(
		'public' => true ,
		'_builtin' => false
	);
	$output = 'object';
	$operator = 'and';
	
	$post_types = get_post_types( $args , $output , $operator );
	
	foreach( $post_types as $post_type ) {
		$num_posts = wp_count_posts( $post_type->name );
		$num = number_format_i18n( $num_posts->publish );
		$text = _n( $post_type->labels->singular_name, $post_type->labels->name , intval( $num_posts->publish ) );
		if ( current_user_can( 'edit_posts' ) ) {
			$num = "<a href='edit.php?post_type={$post_type->name}'>{$num}</a>";
			$text = "<a href='edit.php?post_type={$post_type->name}'>{$text}</a>";
		}
		echo "<tr><td class='first b b-{$post_type->name}'>{$num}</td>";
		echo "<td class='t t-{$post_type->name}'>{$text}</td></tr>";
	}
	
	$taxonomies = get_taxonomies( $args , $output , $operator );
	
	foreach( $taxonomies as $taxonomy ) {
		$num_terms  = wp_count_terms( $taxonomy->name );
		$num = number_format_i18n( $num_terms );
		$text = _n( $taxonomy->labels->singular_name, $taxonomy->labels->name , intval( $num_terms ) );
		if ( current_user_can( 'manage_categories' ) ) {
			$num = "<a href='edit-tags.php?taxonomy={$taxonomy->name}'>{$num}</a>";
			$text = "<a href='edit-tags.php?taxonomy={$taxonomy->name}'>{$text}</a>";
		}
		echo "<tr><td class='first b b-{$taxonomy->name}'>{$num}</td>";
		echo "<td class='t t-{$taxonomy->name}'>{$text}</td></tr>";
	}
}



