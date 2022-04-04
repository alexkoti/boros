<?php

/**    
 * ==================================================
 * TEMPORARY FIXES ==================================
 * ==================================================
 * Correções temporárias relacionadas a funcionalidade do core do WordPress, WooCommerce e outros plugins de terceiros.
 * 
 * Sempre adicionar o ticket do trac e post do suporte relacionados ao problema e acompanhar a resolução, para que o 
 * código seja removido.
 * 
 * 
 */



/**
 * Corrigir erro de query monitor no frontend.
 * O query monitor espera que jquery seja carregado no header, o que causa erro na exibição do painel.
 * 
 */
add_action( 'wp_default_scripts', 'fix_query_monitor_frontend', 101 );
function fix_query_monitor_frontend($wp_scripts){
    $wp_scripts->add_data( 'query-monitor', 'group', 1 );
}



/**
 * ALERTA DE JAVASCRIPT AO SAIR DE QUALQUER PÁGINA ou CPT
 * O navegador exibe mensagem "tem  certeza que deseja sair, modificações foram feitas", mesmo sem o 
 * conteúdo ter sido editado.
 * 
 * WordPress 5.6.1: Window Unload Error Final Fix
 * 
 * @link https://core.trac.wordpress.org/ticket/52440#comment:28
 * @link https://wordpress.org/support/topic/save-popup-seen-even-in-page-with-no-changes-made/
 * 
 */
if( !function_exists('wp_561_window_unload_error_final_fix') ){
    add_action('admin_print_footer_scripts', 'wp_561_window_unload_error_final_fix');
    function wp_561_window_unload_error_final_fix(){
        ?>
        <script>
            jQuery(document).ready(function($){
    
                // Check screen
                if(typeof window.wp.autosave === 'undefined')
                    return;
    
                // Data Hack
                var initialCompareData = {
                    post_title: $( '#title' ).val() || '',
                    content: $( '#content' ).val() || '',
                    excerpt: $( '#excerpt' ).val() || ''
                };
    
                var initialCompareString = window.wp.autosave.getCompareString(initialCompareData);
    
                // Fixed postChanged()
                window.wp.autosave.server.postChanged = function(){
    
                    var changed = false;
    
                    // If there are TinyMCE instances, loop through them.
                    if ( window.tinymce ) {
                        window.tinymce.each( [ 'content', 'excerpt' ], function( field ) {
                            var editor = window.tinymce.get( field );
    
                            if ( ( editor && editor.isDirty() ) || ( $( '#' + field ).val() || '' ) !== initialCompareData[ field ] ) {
                                changed = true;
                                return false;
                            }
    
                        } );
    
                        if ( ( $( '#title' ).val() || '' ) !== initialCompareData.post_title ) {
                            changed = true;
                        }
    
                        return changed;
                    }
    
                    return window.wp.autosave.getCompareString() !== initialCompareString;
    
                }
            });
        </script>
        <?php
    }
}



/**
 * By default, cURL sends the "Expect" header all the time which severely impacts
 * performance. Instead, we'll send it if the body is larger than 1 mb like
 * Guzzle does.
 * 
 * @link https://gist.github.com/carlalexander/c779b473f62dcd1a4ca26fcaa637ec59
 */
add_filter('http_request_args', 'add_expect_header');
function add_expect_header(array $arguments){
    if( is_array($arguments['headers']) ){
        $arguments['headers']['expect'] = !empty($arguments['body']) && (!is_array($arguments['body']) && strlen($arguments['body']) > 1048576) ? '100-Continue' : '';
    }
    return $arguments;
}


