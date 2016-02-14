<?php
/**
 * FORM ELEMENT: FILE_URL
 * 
 * 
 * 
 * 
 */

function form_element_file_url( $data, $data_value, $parent ){
	global $post;
	$link_to = ( isset($data['link_to']) ) ? '&link_to='.$data['link_to'] : '' ;
	$remove_fields = ( isset($data['remove_fields']) ) ? '&remove_fields='.$data['remove_fields'] : '' ;
	
	if( $post )
		$thickbox_link = "media-upload.php?post_id={$post->ID}&send_back=true{$link_to}{$remove_fields}&TB_iframe=true";
	else
		$thickbox_link = "media-upload.php?post_id=0&send_back=true{$link_to}{$remove_fields}&TB_iframe=true";
	?>
	
	<div class="form_file_url">
		<p>
			<input id="<?php echo $data['name']; ?>" name="<?php echo $data['name']; ?>" rel="<?php echo $data['name']; ?>" type="text" class="form_element ipt_size_<?php echo $data['size']; ?> file_url_text" value="<?php echo $data_value; ?>" /> 
			<a href="<?php echo $thickbox_link; ?>" class="thickbox button file_url_button" rel="<?php echo $data['name']; ?>">adicionar arquivo</a>
		</p>
	</div>
	
<?php
}
?>