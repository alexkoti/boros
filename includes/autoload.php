<?php

/**
 * Autoload classes com namespace \Boros
 * 
 */
spl_autoload_register(function( $class ){

    if( false !== strpos( $class, 'Boros\\' ) ){
        $path = str_replace('Boros\\', '', $class);
        $class_file = strtolower( str_replace( array('\\', 'Boros', '_'), array('/', 'class', '-'), $path ) ) . '.php';
        require_once( BOROS_INCLUDES . '/' . $class_file );
    }
});


