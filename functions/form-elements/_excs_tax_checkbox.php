<?php
/**
 * FORM ELEMENT: TEXT
 * Elemento input:text comum
 * 
 * 
 * 
 */

function form_element_excs_tax_checkbox( $data, $data_value, $parent ){
	global $post;
	
	// começar a guardar o output do script js em buffer
	ob_start();
	
	// termos da taxonomia
	$terms = get_terms( $data['taxonomy'], 'hide_empty=0' );
	// termo selecionado - verifica se está buscando post_meta ou taxonomy_meta
	if( isset($_GET['taxonomy']) ){
		if( isset($_GET['tag_ID']) ){
			$selected_terms = get_metadata( 'term', intval($_GET['tag_ID']), $data['taxonomy'], true );
		}
		else{
			$selected_terms = array();
		}
	}
	else{
		$selected_terms = wp_get_object_terms( $post->ID, $data['taxonomy'] );
	}
	//pre($data['taxonomy']);
	//pre($selected_terms);
	
	// criar select
	foreach( $terms as $term ){
		// verificar defaults/checked
		$checked = '';
		if( !is_array($selected_terms) )
			$selected_terms = array($selected_terms);
		if( in_array( $term->term_id, $selected_terms ) and !is_wp_error($term) )
			$checked = 'checked="checked"';
		?>
		<label for="<?php echo "{$data['taxonomy']}_{$term->term_id}"; ?>" id="<?php echo $term->term_id; ?>_checkbox_label" class="label_checkbox">
			<input id="<?php echo "{$data['taxonomy']}_{$term->term_id}"; ?>" rel="<?php echo "{$data['taxonomy']}_{$term->term_id}"; ?>" name="<?php echo $data['name']; ?>[]" type="checkbox" class="form_element ipt_form_checkbox" value="<?php echo $term->term_id; ?>"<?php echo $checked; ?> />
			<?php echo $term->name; ?>
		</label>
		<?php
	}
	
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
			<tr id="<?php echo "tax_input_{$data['taxonomy']}"; ?>" class="unique_tax_input">
				<td class="boros_form_element boros_element_text" colspan="2">
					<p class="form_ipt_text">
						<?php if( !empty($data['label']) ) echo "<label for='{$data['name']}'>{$data['label']}</label><br />"; ?>
						<?php echo $input; ?>
					</p>
				</td>
			</tr>
			<?php
			break;
		
		
		case 'table':
		default:
			?>
			<tr id="<?php echo "tax_input_{$data['taxonomy']}"; ?>" class="unique_tax_input">
				<th><label for="<?php echo $data['name']; ?>"><?php echo $data['label']; ?></label></th>
				<td><?php echo $input; ?></td>
			</tr>
			<?php
			break;
	}
}
?>