<?php
/**
 * FORM ELEMENT: TEXT
 * Elemento input:text comum
 * 
 * ATENÇÂO: para post_types, em 'meta_boxes_save()' é verificado se existe callback, e para os demias páginas(admin_page, tax) não é feita essa verificação, sendo gravado conteúdo puro dos campos.
 * 
 */

function form_element_excs_tax_box( $data, $data_value, $parent ){
	global $post;
	/**
	 * Caso seja uma chamada ajax, $post não estará disponível. Todas as variáveis vindo do ajax
	 * 
	 */
	if( isset($_GET['ajax_post_id']) )
		$post = get_post( intval($_GET['ajax_post_id']) );
	
	// começar a guardar o output do script js em buffer
	ob_start();
	
	// termo selecionado - verifica se está buscando post_meta ou taxonomy_meta
	$selected_terms = array();
	if( isset($_GET['taxonomy']) ){
		$object_id = 0;
		if( isset($_GET['tag_ID']) ){
			$selected_terms = get_metadata( 'term', intval($_GET['tag_ID']), $data['taxonomy'], true );
		}
	}
	else{
		$object_id = $post->ID;
		$selecteds = wp_get_object_terms( $post->ID, $data['taxonomy'] );
		foreach( $selecteds as $tt ){
			$selected_terms[] = absint( $tt->term_id );
		}
	}
	//pre($data['taxonomy']);
	//pre($selected_terms, 'selected_terms');
	
	// caso esteja vazio e possua um default, aplicar
	if( empty( $selected_terms ) and isset( $data['std'] ) ){
		$default_term = get_term_by( 'name', $data['std'], $data['taxonomy'] );
		$selected_terms = array( $default_term->term_id );
	}
	
	// exibir lista de checkboxes
	$args = array(
		'taxonomy' => $data['taxonomy'],
		'checked_ontop' => false,
		'selected_cats' => $selected_terms, 
		'walker' => new Walker_Taxonomy_Terms_Checklist, 
	);
	echo '<ul class="excs_tax_box_term_list">';
	wp_terms_checklist( $object_id, $args );
	echo '</ul>';
	
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
					<?php if( !empty($data['label']) ) echo "<label for='{$data['name']}'>{$data['label']}</label><br />"; ?>
					<?php echo $input; ?>
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