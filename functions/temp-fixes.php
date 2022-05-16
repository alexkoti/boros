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
 * By default, cURL sends the "Expect" header all the time which severely impacts
 * performance. Instead, we'll send it if the body is larger than 1 mb like
 * Guzzle does.
 * 
 * @link https://gist.github.com/carlalexander/c779b473f62dcd1a4ca26fcaa637ec59
 */
add_filter('http_request_args', 'add_expect_header');
function add_expect_header(array $arguments){
    $arguments['headers']['expect'] = '';
    
    if (is_array($arguments['body'])) {
        $bytesize = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($arguments['body']));

        foreach ($iterator as $datum) {
            $bytesize += strlen((string) $datum);

            if ($bytesize >= 1048576) {
                $arguments['headers']['expect'] = '100-Continue';
                break;
            }
        }
    } elseif (!empty($arguments['body']) && strlen((string) $arguments['body']) > 1048576) {
        $arguments['headers']['expect'] = '100-Continue';
    }

    return $arguments;
}


