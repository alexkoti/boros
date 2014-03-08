<?php
/**
 * FUNÇÕES ADICIONAIS PARA qTRANSLATE
 * Basicamente é o acesso filtrado de options/post_meta filtrados no idioma correto
 * 
 * 
 * 
 * 
 */


/* ========================================================================== */
/* QTRANSLATE GET_OPTIONS =================================================== */
/* ========================================================================== */
function qt_option( $option_name ){
	if( function_exists( 'qtrans_init' ) ){
		global $q_config;
		$sufix = ( $q_config['language'] == 'pt' ) ? '' : "_{$q_config['language']}";
		return get_option( "{$option_name}{$sufix}" );
	}
	else{
		'qTranslate não instalado';
	}
}

function qt_opt_option( $option, $wrapper = '%s' ){
	if( function_exists( 'qtrans_init' ) ){
		$meta = qt_option( $option );
		if( $meta != '' ){
			if( $wrapper != '' )
				printf( $wrapper . "\n", $meta );
			else
				echo $meta;
		}
	}
	else{
		'qTranslate não instalado';
	}
}

/* ========================================================================== */
/* QTRANSLATE GET_POST_META ================================================= */
/* ========================================================================== */
function qt_post_meta( $post_id, $meta_name, $unique = true ){
	if( function_exists( 'qtrans_init' ) ){
		global $q_config;
		$sufix = ( $q_config['language'] == 'pt' ) ? '' : "_{$q_config['language']}";
		return get_post_meta( $post_id, "{$meta_name}{$sufix}", $unique );
	}
	else{
		'qTranslate não instalado';
	}
}

function qt_opt_post_meta( $post_id, $post_meta, $wrapper = '%s' ){
	if( function_exists( 'qtrans_init' ) ){
		$meta = qt_post_meta( $post_id, $post_meta, true );
		if( $meta != '' ){
			if( $wrapper != '' )
				printf( $wrapper . "\n", $meta );
			else
				echo $meta;
		}
	}
	else{
		'qTranslate não instalado';
	}
}