<?php
/**
 * FORM ELEMENT: RELATED_PRODUCTS
 * 
 * 
 * 
 */

function form_element_related_products( $data, $data_value, $parent ){
	global $post;
	
	// começar a guardar o output do script js em buffer
	ob_start();
	?>
	<div class="excs_related_products_box">
		<?php
		$args = array(
			'post_type' => 'produto',
			'post_status' => 'publish',
			'meta_key' => 'related_history',
			'meta_value' => $post->ID,
			'meta_compare' => '=',
		);
		$products = new WP_Query();
		$products->query($args);
		if( $products->posts ){
			echo '<ul class="related_products_list">';
			foreach( $products->posts as $post ){
				$thumb = get_the_post_thumbnail( $post->ID, 'thumbnail', array('title' => $post->post_title) );
				?>
				<li>
					<?php echo edit_post_link( $thumb, '', '', $post->ID ); ?>
				</li>
				<?php
			}
			echo '</ul>';
		}
		?>
	</div>
	<?php
	// guardar o output em variável
	$input = ob_get_contents();
	ob_end_clean();
	
	// verificar o tipo de layout
	if( !isset($data['layout']) )
		$data['layout'] = 'table';
	
	// exibir conforme o layout
	switch( $data['layout'] ){
		case 'block':
			?>
			<tr>
				<td class="boros_form_element boros_element_text" colspan="2">
					<p class="form_ipt_text"><label for="<?php echo $data['name']; ?>"><?php echo $data['label']; ?></label></p>
					<?php echo $input; ?>
				</td>
			</tr>
			<?php
			break;
		
		
		case 'table':
		default:
			?>
			<tr>
				<th><label for="<?php echo $data['name']; ?>"><?php echo $data['label']; ?></label></th>
				<td><?php echo $input; ?></td>
			</tr>
			<?php
			break;
	}
}