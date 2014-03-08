<?php
/**
 * FORM ELEMENT: IMAGE_URL
 * 
 * 
 * 
 * 
 */

function form_element_image_url( $data, $data_value, $parent ){
	global $post;
	$force_size = $link_to = $remove_fields = '';
	
	if( isset($data['force_size']) )
		$force_size = ($data['force_size']) ? '&force_size='.$data['force_size'] : 'medium' ;
	if( isset($data['link_to']) )
		$link_to = ($data['link_to']) ? '&link_to='.$data['link_to'] : '' ;
	if( isset($data['remove_fields']) )
		$remove_fields = ($data['remove_fields']) ? '&remove_fields='.$data['remove_fields'] : '' ;
	
	if( $post ){
		$thickbox_link = "media-upload.php?type=image&post_id={$post->ID}&send_back=true{$link_to}{$force_size}{$remove_fields}&TB_iframe=true";
	}
	/* Definir o post_id = 0, caso contrário dará erro em /wp-admin/includes/media.php media_upload_header().
	 * 
	 */
	else{
		$thickbox_link = "media-upload.php?type=image&post_id=0&send_back=true{$link_to}{$force_size}{$remove_fields}&TB_iframe=true";
	}
	
	// começar a guardar o html do input em buffer
	ob_start();
	?>
		<input id="<?php echo $data['name']; ?>" name="<?php echo $data['name']; ?>" rel="<?php echo $data['name']; ?>" type="text" class="form_element ipt_size_<?php echo $data['size']; ?> image_url_text" value="<?php echo $data_value; ?>" /> 
		<a href="<?php echo $thickbox_link; ?>" class="thickbox button upload_button upload_button_image" rel="<?php echo $data['name']; ?>">adicionar imagem</a><br />
		<img src="<?php echo $data_value; ?>" class="uploaded_image" alt="preview" <?php if($data_value == '') echo 'style="display:none;"' ?> />
	<?php
	// guardar o output em variável
	$input = ob_get_contents();
	ob_end_clean();
	
	// verificar o tipo de layout
	if( !isset($data['layout']) )
		$data['layout'] = 'table';
	
	// exibir conforme o layout
	switch( $data['layout'] ){
		case 'simple':
			echo $input;
			break;
		
		case 'block':
			?>
			<tr>
				<td class="boros_form_element boros_element_image_url" colspan="2">
					<label for="<?php echo $data['name']; ?>"><?php echo $data['label']; ?></label><br />
					<?php echo $input; ?>
				</td>
			</tr>
			<?php
			break;
		
		
		case 'table':
		default:
			?>
			<tr>
				<th class="boros_form_element boros_element_image_url boros_form_element_th"><label for="<?php echo $data['name']; ?>"><?php echo $data['label']; ?></label></th>
				<td class="boros_form_element boros_element_image_url"><?php echo $input; ?></td>
			</tr>
			<?php
			break;
	}
}
?>