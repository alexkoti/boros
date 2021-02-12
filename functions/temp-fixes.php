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
