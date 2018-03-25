<?php
/**
 * Ferramenta para includes
 * 
 * 
 * @link http://www.smashingmagazine.com/2015/05/how-to-use-autoloading-and-a-plugin-container-in-wordpress-plugins/
 */

spl_autoload_register( 'boros_autoload_class' );

function boros_autoload_class( $class_name ){

	// Apenas classes Boros
	if( false !== strpos( $class_name, 'Boros_' ) ){
		$classes_dir = BOROS_FUNCTIONS . DIRECTORY_SEPARATOR;
		$class_file = strtolower( str_replace( array('Boros', '_'), array('class', '-'), $class_name ) ) . '.php';
		require_once( $classes_dir . $class_file );
    }
    
    // Third Party

    // Form Elements
}


