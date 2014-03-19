<?php
/*
Plugin Name: Boros Elements
Plugin URI: http://alexkoti.com
Description: Funções para o admin do WordPress, páginas personalizadas de administração(options) e campos de post_types(meta_boxes), widgets e form_elements
Version: 1.0.0
Author: Alex Koti
Author URI: http://alexkoti.com
License: GPL2
*/

/** 
 * ==================================================
 * CONSTANTS ========================================
 * ==================================================
 * Em localhost, configurar as seguintes constantes no arquivo /wp-config.php: PLUGINDIR, WP_PLUGIN_DIR, WP_PLUGIN_URL e LOCAL_BOROS_CONFIG
 * 
 * PLUGINDIR			Compatibilidade com plugins antigos
 * WP_PLUGIN_DIR		Caminho de servidor da pasta de plugins
 * WP_PLUGIN_URL		URL do plugins, para CSS, JS e imagens
 * 
 * @link http://wpengineer.com/2374/easier-plugin-development-by-moving-plugin-directory/
 */

// CAMINHOS ABSOLUTOS - para includes
define( 'BOROS', dirname(__FILE__) );
define( 'BOROS_FUNCTIONS', 	BOROS . '/functions/' );
define( 'BOROS_ELEMENTS', 	BOROS_FUNCTIONS . 'form_elements/' );
define( 'BOROS_LIBS', 		BOROS_FUNCTIONS . 'libs/' );

// URLS
define( 'BOROS_URL', 	plugins_url( '/', __FILE__ ) );
define( 'BOROS_CSS', 	plugins_url( 'functions/form_elements/css/', __FILE__ ) );
define( 'BOROS_IMG', 	plugins_url( 'functions/form_elements/css/img/', __FILE__ ) );
define( 'BOROS_JS', 	plugins_url( 'functions/form_elements/js/', __FILE__ ) );

/**
 * DEBUG CONSTANTS
 */
/**
print_r(BOROS);echo "\n";
print_r(BOROS_FUNCTIONS);echo "\n";
print_r(BOROS_ELEMENTS);echo "\n";
print_r(BOROS_CONFIG);echo "\n";
print_r(BOROS_URL);echo "\n";
print_r(BOROS_CSS);echo "\n";
print_r(BOROS_IMG);echo "\n";
print_r(BOROS_JS);echo "\n";
$const = get_defined_constants(true);  
print_r($const['user']);  
/**/


/**
 * Constante para versão de CSS/JS
 * A constante VERSION deverá indicar a versão desejada para o site final. Ao pedir a versão do script/css a ser utilizada, será retornado este valor, mas
 * caso o WP_DEBUG esteja habilitado, será retornado a versão temporária, que poderá ser valor hardcoded ou time(), que removerá o cache.
 *
 * @todo: rever este reço e passar para o plugin do job ou tema, para definir o cache de enqueue conforme a necessidade
 * 
 * @link http://wpengineer.com/2292/force-reload-of-scripts-and-stylesheets-in-your-plugin-or-theme/
 */
define ('VERSION', '1.0');
function version_id(){
	if( WP_DEBUG ){
		//return '1';
		return time(); //para remover totalmente o cache;
	}
	return VERSION;
}



/**
 * ==================================================
 * INCLUDES =========================================
 * ==================================================
 * 
 * 
 */

/**
 * INCLUDES FUNCTIONS GERAIS
 * Válidos para admin e frontend
 * Alguns includes, como admin_pages, metaboxes, frontend_form precisam de include global, pois necessitam estar acessíveis no admin e frontend
 * 
 * @TODO deixar o include de thirdparty(facebook e afins) a cargo de uma função que verificará a real necessidade de chamá-lo
 * 
 */
if( defined('LOCALHOST') and LOCALHOST === true ){
	include_once( BOROS_FUNCTIONS . 'localhost.php' );              // functions restritas ao desenvolvimento localhost
}
include_once( BOROS_FUNCTIONS . 'debug.php' );						// functions de debug(pre, pal, prex)
include_once( BOROS_FUNCTIONS . 'extend_php.php' );					// functions extras de PHP
include_once( BOROS_FUNCTIONS . 'extend_array.php' );				// functions extras para manipulação de arrays
include_once( BOROS_FUNCTIONS . 'extend_wp.php' );					// functions extras para o WordPress
include_once( BOROS_FUNCTIONS . 'walker.php' );						// extensões da classe walker - listagem de terms, categories, pages hierárquicos
include_once( BOROS_FUNCTIONS . 'form_elements.php');				// core do form elements
include_once( BOROS_FUNCTIONS . 'media_uploader.php');				// functions para upload de mídia
include_once( BOROS_FUNCTIONS . 'validation.php');					// classe de validação
include_once( BOROS_FUNCTIONS . 'admin_media.php' );				// [REVER TODOS AS FUNCTIONS AQUI]
include_once( BOROS_FUNCTIONS . 'meta_boxes.php' );					// funções dos metaboxes
include_once( BOROS_FUNCTIONS . 'admin_pages.php');					// funções para adicionar e renderizar as páginas do admin
include_once( BOROS_FUNCTIONS . 'post_types.php');					// funções para post_types
include_once( BOROS_FUNCTIONS . 'page.php' );						// functions extendidas para páginas
include_once( BOROS_FUNCTIONS . 'taxonomy.php');					// functions extendidas para taxonomias e termos
include_once( BOROS_FUNCTIONS . 'taxonomy_meta.php' );				// functions para ediçao das taxonomias - registra aqui a tabela 'termmeta'
include_once( BOROS_FUNCTIONS . 'user.php');						// functions extendidas para manipulação de usuários
include_once( BOROS_FUNCTIONS . 'qtranslate.php');					// functions auxiliares para o plugin qTranslate(multilingua)
include_once( BOROS_FUNCTIONS . 'widgets.php' );					// widgets, fazer includes dos widgets conforme array de config
include_once( BOROS_FUNCTIONS . 'frontend_form.php');				// class de postagem no frontend, ele precisa ter acesso geral para os controles de admin.
include_once( BOROS_FUNCTIONS . 'email.php');						// function para todos os emails - as configs deverão ser feitas no plgin do trabalho
include_once( BOROS_FUNCTIONS . 'tests.php');						// function auxiliares para testes
include_once( BOROS_FUNCTIONS . 'third_party_facebook.php');		// integração com facebook
include_once( BOROS_FUNCTIONS . 'multisite.php');					// functions extras para multisite

/**
 * INCLUDES FUNCTIONS SOMENTE FRONTEND
 * 
 * 
 */
if( !is_admin() ){
	include_once( BOROS_FUNCTIONS . 'frontend_static.php' );		// actions e filters fixas para frontend
	include_once( BOROS_FUNCTIONS . 'frontend_head.php' );			// functions para o <head> do frontend - scripts, css
	include_once( BOROS_FUNCTIONS . 'frontend_media.php');			// functions extendidas para manipulação de midias para frontend apenas
}

/**
 * INCLUDES FUNCTIONS SOMENTE ADMIN
 * 
 * 
 */
if( is_admin() ){
	include_once( BOROS_FUNCTIONS . 'admin.php');					// 
	include_once( BOROS_FUNCTIONS . 'admin_dashboard.php');			// auxiliar do dashboard
	include_once( BOROS_FUNCTIONS . 'admin_functions.php');			//
	include_once( BOROS_FUNCTIONS . 'admin_nav_menus.php');			// personalização do controle de menus
}
