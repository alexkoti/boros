<?php

class BFE_newsletter_source extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
	);
	
	function set_input( $value = null ){
		global $post;
		$response = wp_remote_get( get_permalink($post->ID) );
		$output_raw = wp_remote_retrieve_body( $response );
		
		$input = '<textarea style="width:100%;height:400px;overflow:auto;border:1px solid #DFDFDF;background:#fff;">';
		$input .= esc_html($output_raw);
		$input .= '</textarea>';
		return $input;
	}
}


