<?php
/**
 * Apenas faz os includes da pasta /boros/functions/widgets/ cada arquivo deverá conter a class do widget e o registro do mesmo
 * 
 */

/* ========================================================================== */
/* ADD ACTIONS/FILTERS ====================================================== */
/* ========================================================================== */
//add_action( 'widgets_init', 'register_widgets' );

/**
 * ==================================================
 * REGISTER WIDGETS =================================
 * ==================================================
 * Ler todos os arquivos da pasta widgets e fazer loop de todos
 * Desconsidera arquivos com underscore no começo do nome do arquivo
 * 
 * 
 * @TODO: adicionar fallback para glob, assim como foi feito em form_elements
 */
function register_widgets(){
	
	foreach( glob( BOROS_FUNCTIONS . "/widgets/*.php" ) as $filename ){
		if( !preg_match( "/^_.*php$/", basename($filename) ) ){
			$files[] = $filename;
			include_once $filename;
		}
	}
	
	$files = array(
		'custom_tagcloud.php',
	);
	foreach( $files as $file ){
		include_once BOROS_FUNCTIONS . "/widgets/{$file}";
	}
	
	/**
	 * Adicionar Widgets customizados para o projeto.
	 * 
	 */
	$extra_widgets = apply_filters( 'boros_extra_widgets_folder', array() );
	//pre($extra_widgets);
	if( !empty($extra_widgets) ){
		foreach( $extra_widgets as $folder ){
			foreach( glob( $folder . "/*.php" ) as $filename ){
				$path = pathinfo( $filename );
				if( !preg_match( "/^_/", $path['filename'] ) ){
					include_once $filename;
				}
			}
		}
	}
}
