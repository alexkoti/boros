<?php
/**
 * FORM ELEMENT: TEXT
 * Elemento input:text comum
 * 
 * 
 * 
 */

/**
 * Para este elemento, $data_value poderá ser o valor de term_meta, no caso de edição de série(taxonomy) ou então $post, no caso de produto(posts)
 * 
 */
function form_element_excs_taxonomy_terms( $data, $data_value, $parent ){
	// começar a guardar o output do script js em buffer
	ob_start();
	
	
	if( $data['hierachical'] == true ){
		$options = array(
			'object_type' => 'post',
			'show_popular' => true,
			'show_recent' => true,
			'show_favs' => true,
			'show_adder' => true,
		);
		form_element_excs_taxonomy_terms_non_hierachical( $data_value, $data['taxonomy'], $options );
	}
	else{
		$options = array(
			'object_type' => 'post',
			'show_popular' => true,
			'show_recent' => true,
			'show_favs' => true,
		);
		form_element_excs_taxonomy_terms_non_hierachical( $data_value, $data['taxonomy'], $options );
	}
	
	
	
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
					<p class="form_ipt_text">
						<label for="<?php echo $data['name']; ?>"><?php echo $data['label']; ?></label><br />
						<?php echo $input; ?>
					</p>
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
?>