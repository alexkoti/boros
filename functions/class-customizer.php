<?php

/**
 * Disponibilizar os controles adicionais ao Customizer
 * 
 * 
 * Exemplo de adição ao plugin:
 * ```php
 * add_action('customize_register', 'add_customizer_controls', 10);
 * function add_customizer_controls(){
 *     $controls = new Boros_Customizer(array('wp-editor'));
 * }
 * ```
 * 
 * Exemplo de adição de controle:
 * <code>
 * $wp_customize->add_setting(
 *     'slide_banner_text',
 *     array(
 *         'default'        => '',
 *         'transport'      => 'refresh',
 *     )
 * );
 * $wp_customize->add_control(
 *     new Boros_Customizer_WP_Editor(
 *         $wp_customize,
 *         'slide_banner_text',
 *         array(
 *             'label'      => 'Texto',
 *             'section'    => 'slide_banner',
 *             'settings'   => 'slide_banner_text',
 *         )
 *     )
 * );
 * </code>
 * 
 * 
 */
class Boros_Customizer {

    function __construct( $controls ){
        foreach( $controls as $control ){
            include_once( BOROS_CUSTOMIZER . "/{$control}.php" );
        }
    }
}


