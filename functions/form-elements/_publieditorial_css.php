<?php
/**
 * FORM ELEMENT: TEXT
 * Elemento input:text comum
 * 
 * 
 * 
 */

function form_element_publieditorial_css( $data, $data_value, $parent ){
	global $post;
	$url = get_bloginfo('template_url');
	$filename = "hotsite_{$post->ID}.css";
	
	$localfile = get_theme_root() . '/' . get_template() . '/css/' . $filename;
	$status = ( file_exists( $localfile ) ) ? '<span style="color:green;">arquivo presente na pasta do tema</span>' : '<span style="color:red;">arquivo <strong style="text-decoration:underline;">não</strong> encontrado na pasta do tema</span>';
	echo "<p>Arquivo CSS para este hotsite: '<strong>{$url}/css/<span style='color:red;'>{$filename}</span>'</strong>: {$status}</p>";
}
?>