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
add_action('wp_default_scripts', function(){
    global $wp_scripts;

    // não precisa verificar a declaração de 'query-monitor', pois o método WP_Dependencies->add_data() já faz essa verificação
    $wp_scripts->add_data( 'query-monitor', 'group', 1 );
    
    // forçar a ordem de carregamento do query-monitor para ser o último da lista, pois acontece conflito com woocommerce e outros scripts
    foreach( $wp_scripts->queue as $index =>$script ) {
        if( $script == 'query-monitor' ){
            unset($wp_scripts->queue[$index]);
            $wp_scripts->queue[99] = 'query-monitor';
        }
    }
}, 101);


