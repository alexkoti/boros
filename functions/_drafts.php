<?php
/**
 * ATENÇÂO: o functions.php sempre será o do tema que está definido em wp_options
 * 
 */


add_filter( 'stylesheet', 'my_stylesheet' );
add_filter( 'template',   'my_template' );

function my_stylesheet( $stylesheet ) {

	switch ( $_SERVER['SERVER_NAME'] ) {
		case 'mydevelopmentsite.com':
			return 'mydevelopmenttheme';
			break;
		case 'mylivesite.com':
		default:
			return 'mylivetheme';
			break;
	}

}

function my_template( $stylesheet ) {

	switch ( $_SERVER['SERVER_NAME'] ) {
		case 'mydevelopmentsite.com':
			return 'mydevelopmenttheme';
			break;
		case 'mylivesite.com':
		default:
			return 'mylivetheme';
			break;
	}

}