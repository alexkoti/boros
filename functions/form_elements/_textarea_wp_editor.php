<?php
/**
 * FORM ELEMENT: TEXTAREA
 * 
 * 
 * 
 */

function form_element_textarea_wp_editor( $data, $data_value, $parent ){
	global $post;
	// Usar $post->post_content no lugar do post_meta > $data['name']
	if( isset($data['options']['use_post_content']) )
		$data_value = $post->post_content;
	
	// Editor profiles
	$editor_profiles = array(
		'minimal' => array(
			'editor_type' => 'minimal',
			'buttons' => 'bold,italic,|,undo,redo,|,code',
			'buttons2' => '',
			'buttons3' => '',
			'height' => 150,
			'css' => get_bloginfo('template_url') . '/css/site.css',
		),
		'simple' => array(
			'editor_type' => 'simple',
			'buttons' => 'bold,italic,link,bullist,numlist,|,undo,redo,|,code',
			'buttons2' => '',
			'buttons3' => '',
			'height' => 150,
			'css' => get_bloginfo('template_url') . '/css/site.css',
		),
		'full' => array(
			'editor_type' => 'full',
			'buttons' => 'bold,italic,link,bullist,numlist,image,|,justifyleft,justifycenter,justifyright,|,undo,redo,|,code',
			'buttons2' => '',
			'buttons3' => '',
			'height' => 150,
			'css' => get_bloginfo('template_url') . '/css/site.css',
		),
	);
	
	$editor_defs = array(
		'editor_type' => 'simple',
		'buttons' => 'bold,italic,link,bullist,numlist,|,code',
		'buttons2' => '',
		'buttons3' => '',
		'height' => 150,
		'css' => get_bloginfo('template_url') . '/css/site.css',
	);
	
	/**
	 * $editor_attr['editor_type'] - define um profile pré-determinado. A menos que seja definido como 'custom', os demais valores enviados serão ignorados e
	 * usados os valores defaults
	 */
	if( !isset($data['options']['editor']) )
		$data['options']['editor'] = 'minimal';
	
	if( is_array($data['options']['editor']) ){
		$data['options']['editor']['editor_type'] = $data['name'];
		$editor_attr = wp_parse_args( $data['options']['editor'], $editor_defs );
	}
	else{
		$editor_attr = $editor_profiles[$data['options']['editor']];
	}
	
	
	// definir o input
	$input_content = format_to_edit( wpautop($data_value) );
	$editor_settings = array(
		'textarea_rows' => 10,
		//'editor_css' => '',
		'editor_class' => 'hentry',
	);
	$input = wp_editor( $data_value, $data['name'], $editor_settings );
	//$input = "<textarea id='{$data['name']}' name='{$data['name']}' class='form_element form_textarea form_text_editor editor_type_{$editor_attr['editor_type']}' rel='{$data['name']}'>{$input_content}</textarea>";
	
	// verificar o tipo de layout
	if( !isset($data['layout']) )
		$data['layout'] = 'table';
	
	// exibir conforme o layout
	switch( $data['layout'] ){
		case 'simple':
			echo $input;
			echo $js;
			break;
		
		case 'block':
			$label = (!empty($data['label'])) ? "<p><label for='{$data['name']}'>{$data['label']}</label></p>" : "";
			?>
			<tr>
				<td class="boros_form_element boros_element_textarea" colspan="2">
					<?php echo $label; ?>
					<?php echo $input; ?>
					<?php echo $js; ?>
				</td>
			</tr>
			<?php
			break;
		
		
		case 'table':
		default:
			?>
			<tr>
				<th class="boros_form_element boros_element_textarea boros_form_element_th"><?php echo $data['label']; ?></th>
				<td class="boros_form_element boros_element_textarea">
					<?php echo $input; ?>
					<?php echo $js; ?>
				</td>
			</tr>
			<?php
			break;
	}
}
?>