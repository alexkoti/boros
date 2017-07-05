<?php
/**
 * FUNÇÔES DE ADMIN: STATIC
 * Apenas funções fixas e que precisem rodar apenas no admin, sem acesso no frontend. No caso de arquivos de admin pages, 
 * que podem conter functions que precisem dar a saída de dados no frontend, deixar em includes globais.
 * 
 * 
 */

/* ========================================================================== */
/* ADMIN BODY CLASS ========================================================= */
/* ========================================================================== */
/**
 * Adicionar classes ao <body> do admin
 * Filtro localizado em "wp-admin/admin-header.php"
 */
add_action('admin_body_class', 'custom_admin_body_class');
function custom_admin_body_class( $a ){
	global $post_type;
	return "post-type-{$post_type}";
}



/**
 * Corrigir https nas imagens do admin, listagem de thumbs
 * 
 * @link https://core.trac.wordpress.org/ticket/20996
 * @link https://developer.wordpress.org/reference/functions/set_url_scheme/
 * 
 */
add_filter( 'wp_get_attachment_url', 'set_url_scheme', 10, 2 );



/**
 * ==================================================
 * FORÇAR LOGIN DIÁRIO ==============================
 * ==================================================
 * 
 * 
 */
add_action('wp', 'force_daily_login_activation');
function force_daily_login_activation() {
	if ( !wp_next_scheduled( 'force_daily_login_hook' ) ) {
		wp_schedule_event( time(), 'daily', 'force_daily_login_hook');
	}
}

add_action('force_daily_login_hook', 'force_daily_login');
function force_daily_login(){
	wp_clear_auth_cookie();
}


