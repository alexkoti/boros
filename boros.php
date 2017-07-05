<?php
/*
Plugin Name: Boros Elements
Plugin URI: https://github.com/alexkoti/boros
Description: Funções para o admin do WordPress, páginas personalizadas de administração(options) e campos de post_types(meta_boxes), widgets e form_elements
Version: 1.5.0
Author: Alex Koti
Author URI: http://alexkoti.com
License: GPL2
*/

/** 
 * =====================================================================================================================
 * CONSTANTS ===========================================================================================================
 * =====================================================================================================================
 * 
 */

// CAMINHOS ABSOLUTOS - para includes
define( 'BOROS', dirname(__FILE__) );
define( 'BOROS_FUNCTIONS',    BOROS . DIRECTORY_SEPARATOR . 'functions' );
define( 'BOROS_ELEMENTS',     BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'form_elements' );
define( 'BOROS_LIBS',         BOROS . DIRECTORY_SEPARATOR . 'vendors' );

// URLS
define( 'BOROS_URL',          plugins_url( '/', __FILE__ ) );
define( 'BOROS_CSS',          plugins_url( 'functions/form_elements/css/', __FILE__ ) );
define( 'BOROS_IMG',          plugins_url( 'functions/form_elements/css/img/', __FILE__ ) );
define( 'BOROS_JS',           plugins_url( 'functions/form_elements/js/', __FILE__ ) );

/**
 * DEBUG CONSTANTS
 * 
 */
//print_r(BOROS);echo "\n";
//print_r(BOROS_FUNCTIONS);echo "\n";
//print_r(BOROS_ELEMENTS);echo "\n";
//print_r(BOROS_LIBS);echo "\n";
//print_r(BOROS_URL);echo "\n";
//print_r(BOROS_CSS);echo "\n";
//print_r(BOROS_IMG);echo "\n";
//print_r(BOROS_JS);echo "\n";
//$const = get_defined_constants(true);  
//print_r($const['user']);  


/**
 * Constante para versão de CSS/JS
 * A constante BOROS_VERSION_ID deverá indicar a versão desejada para o site final. Ao pedir a versão do script/css a ser utilizada, será retornado este valor, mas
 * caso o BOROS_NO_SCRIPT_CACHE esteja habilitado, será retornado a versão temporária, que poderá ser valor hardcoded ou time(), que removerá o cache.
 *
 * @todo: rever este trecho e passar para o plugin do job ou tema, para definir o cache de enqueue conforme a necessidade
 * 
 * @link http://wpengineer.com/2292/force-reload-of-scripts-and-stylesheets-in-your-plugin-or-theme/
 */
function version_id(){
    if( defined('BOROS_NO_SCRIPT_CACHE')and BOROS_NO_SCRIPT_CACHE == true ){
        return time(); //para remover totalmente o cache;
    }
    if( defined('BOROS_VERSION_ID') ){
        return BOROS_VERSION_ID;
    }
    return apply_filters('boros_version_id', '1.0');
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
    include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'localhost.php' );          // functions restritas ao desenvolvimento localhost
}
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'debug.php' );                  // functions de debug(pre, pal, prex)
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
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'third_party_facebook.php');    // integração com facebook
include_once( BOROS_FUNCTIONS . DIRECTORY_SEPARATOR . 'security.php' );               // configurações e filtros de segurança
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
    $className = PucFactory::getLatestClassVersion('PucGitHubChecker');
    $myUpdateChecker = new $className(
        'https://github.com/alexkoti/boros',
        __FILE__,
        'master'
    );
}


