<?php
/**
 * Ferramenta para includes
 * 
 * 
 * @link http://www.smashingmagazine.com/2015/05/how-to-use-autoloading-and-a-plugin-container-in-wordpress-plugins/
 */



/**
 * Carregar classes 'Boros_'
 * 
 */
spl_autoload_register( 'boros_autoload_class' );
function boros_autoload_class( $class_name ){
	if( false !== strpos( $class_name, 'Boros_' ) ){
		$classes_dir = BOROS_FUNCTIONS . DIRECTORY_SEPARATOR;
		$class_file = strtolower( str_replace( array('Boros', '_'), array('class', '-'), $class_name ) ) . '.php';
		require_once( $classes_dir . $class_file );
	}
}



/**
 * Carregar Form Elements
 * 
 */
spl_autoload_register( 'boros_autoload_form_elements' );
function boros_autoload_form_elements( $class_name ){
    if( false !== strpos( $class_name, 'BFE_' ) ){
        $class = str_replace( array('BFE_', '_'), array('', '-'), $class_name );
        
        // core elements
        $filename = BOROS_ELEMENTS . DIRECTORY_SEPARATOR . $class . '.php';
        if( is_readable($filename) ){
            require_once $filename;
            return;
        }
        
        // extra elements
        $extra_folders = apply_filters( 'boros_extra_form_elements_folder', array() );
        if( !empty($extra_folders) ){
            foreach( $extra_folders as $folder ){
                $filename = $folder . DIRECTORY_SEPARATOR . $class . '.php';
                if( is_readable($filename) ){
                    require_once $filename;
                    return;
                }
            }
        }
    }
}

