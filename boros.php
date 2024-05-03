<?php
/**
 * Plugin Name: Boros Elements
 * Plugin URI:  https://github.com/alexkoti/boros
 * Description: Funções para o admin do WordPress, páginas personalizadas de administração(options) e campos de post_types(meta_boxes), widgets, form_elements e frontend forms
 * Version:     1.6.51
 * Author:      Alex Koti
 * Author URI:  http://alexkoti.com
 * License:     GPL2
 */

/**
 * ==================================================
 * CONSTANTS ========================================
 * ==================================================
 * 
 * 
 */

// Paths
define( 'BOROS',            dirname(__FILE__) );
define( 'BOROS_FUNCTIONS',  BOROS . DIRECTORY_SEPARATOR . 'functions' );
define( 'BOROS_INCLUDES',   BOROS . DIRECTORY_SEPARATOR . 'includes' );
define( 'BOROS_ELEMENTS',   BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'form_elements' );
define( 'BOROS_CUSTOMIZER', BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'customizer' );
define( 'BOROS_LIBS',       BOROS . DIRECTORY_SEPARATOR . 'vendors' );

// URLs
define( 'BOROS_URL',        plugins_url( '/', __FILE__ ) );
define( 'BOROS_CSS',        plugins_url( 'functions/form_elements/css/', __FILE__ ) );
define( 'BOROS_IMG',        plugins_url( 'functions/form_elements/css/img/', __FILE__ ) );
define( 'BOROS_JS',         plugins_url( 'functions/form_elements/js/', __FILE__ ) );
define( 'BOROS_VERSION',    '2024.05.12.1' );

// debug geral
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'debug.php' );

/**
 * Nova estrutura de arquivos
 * 
 */
include_once( BOROS_INCLUDES . DIRECTORY_SEPARATOR . 'autoload.php' ); // autoload



/**
 * INCLUDES FUNCTIONS GERAIS
 * Válidos para admin e frontend
 * Alguns includes, como admin_pages, metaboxes, frontend_form precisam de include global, pois necessitam estar acessíveis no admin e frontend
 * 
 * 
 */
if( defined('LOCALHOST') and LOCALHOST === true ){
    include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'localhost.php' );          // functions restritas ao desenvolvimento localhost
}
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'autoload.php' );               // autoload
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'extend_php.php' );             // functions extras de PHP
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'extend_array.php' );           // functions extras para manipulação de arrays
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'extend_wp.php' );              // functions extras para o WordPress
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'walker.php' );                 // extensões da classe walker - listagem de terms, categories, pages hierárquicos
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'form_elements.php');           // core do form elements
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'media_uploader.php');          // functions para upload de mídia
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'validation.php');              // classe de validação
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'admin_media.php' );            // [REVER TODOS AS FUNCTIONS AQUI]
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'meta_boxes.php' );             // funções dos metaboxes
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'admin_pages.php');             // funções para adicionar e renderizar as páginas do admin
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'post_types.php');              // funções para post_types
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'page.php' );                   // functions extendidas para páginas
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'taxonomy.php');                // functions extendidas para taxonomias e termos
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'taxonomy_meta.php' );          // functions para ediçao das taxonomias - registra aqui a tabela 'termmeta'
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'user.php');                    // functions extendidas para manipulação de usuários
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'widgets.php' );                // widgets, fazer includes dos widgets conforme array de config
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'frontend_form.php');           // class de postagem no frontend, ele precisa ter acesso geral para os controles de admin.
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'email.php');                   // function para todos os emails - as configs deverão ser feitas no plgin do trabalho
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'tests.php');                   // function auxiliares para testes
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'security.php' );               // configurações e filtros de segurança
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'temp-fixes.php' );             // correções temporárias para o core
if( defined('MULTISITE') and MULTISITE == true ){
    include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'multisite.php');           // functions extras para multisite
}

/**
 * INCLUDES FUNCTIONS SOMENTE FRONTEND
 * 
 * 
 */
if( !is_admin() ){
    include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'frontend_static.php' );    // actions e filters fixas para frontend
    include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'frontend_head.php' );      // functions para o <head> do frontend - scripts, css
    include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'frontend_media.php');      // functions extendidas para manipulação de midias para frontend apenas
}

/**
 * INCLUDES FUNCTIONS SOMENTE ADMIN
 * 
 * 
 */
if( is_admin() ){
    include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'admin.php');               // 
    include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'admin_dashboard.php');     // auxiliar do dashboard
    include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'admin_functions.php');     //
    include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'admin_nav_menus.php');     // personalização do controle de menus
    //include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'admin_tools.php');       // functions para sub-tarefas, como criar conteúdo dummy
    
    /**
     * UPDATE CHECKER
     * Verificar updates
     * 
     * @link https://github.com/YahnisElsts/plugin-update-checker
     * 
     */
    require 'plugin-update-checker/plugin-update-checker.php';
    $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
        'https://github.com/alexkoti/boros',
        __FILE__,
        'master'
    );
}



/**
 * ==================================================
 * FORÇAR LOGIN DIÁRIO ==============================
 * ==================================================
 * É necessário utilizar no hook de ativação
 * 
 * @link https://codex.wordpress.org/Function_Reference/wp_schedule_event
 * 
 */
register_activation_hook( __FILE__, 'force_daily_login_activation' );
function force_daily_login_activation() {
    if( !wp_next_scheduled( 'force_daily_login_hook' ) ){
        wp_schedule_event( time(), 'daily', 'force_daily_login_hook');
    }
}

add_action('force_daily_login_hook', 'force_daily_login');
function force_daily_login(){
    wp_clear_auth_cookie();
}

register_deactivation_hook(__FILE__, 'force_daily_login_deactivation');
function force_daily_login_deactivation() {
    wp_clear_scheduled_hook( 'force_daily_login_hook ');
}
