<?php
/**
 * TEXT
 * input text comum
 * 
 * 
 * 
 */

class BFE_factory_options extends BorosFormElement {
	
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'size' => false,
	);
	
	/**
	 * 
	 * 
	 */
	function set_input( $value = null ){
		$attrs = make_attributes($this->data['attr']);
		
		ob_start();
		
		if( isset($this->data['options']) ){
			$provider_value = array_key_search_r( 'provider_value', $this->data['options'] );
			if( $provider_value === false )
				$provider_value = 'animais';
		}
		else{
			$provider_value = 'animais';
			//$provider_value = false;
		}
		
		if( $provider_value != false ){
			$query = array(
				'post_type' => 'post',
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'category_name' => $provider_value
			);
			$pages = new WP_Query();
			$pages->query($query);
			if( $pages->posts ){
				echo "<select {$attrs}>";
				echo "<option>Nenhuma opção</option>";
				foreach($pages->posts as $post){
					setup_postdata($post);
					$selected = selected( $this->data_value, $post->post_name, false );
					echo "<option value='{$post->post_name}' {$selected}>{$post->post_title}</option>";
				}
				echo '</select>';
			}
			wp_reset_query();
		}
		else{
			echo "<div {$attrs}>Não definido.</div>";
		}
		
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}