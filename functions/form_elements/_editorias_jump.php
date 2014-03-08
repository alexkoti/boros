<?php
/**
 * FORM ELEMENT: TEXT
 * Elemento input:text comum
 * 
 * 
 * 
 */

function form_element_editorias_jump( $data, $data_value, $parent ){
	if( isset( $_GET['editoria'] ) ){
		$terms = get_terms( 'editoria', array('hide_empty' => false, 'fields' => 'ids') );
		if( in_array( $_GET['editoria'], $terms) )
			$editoria = $_GET['editoria'];
		else
			$editoria = 'home';
	}
	else{
		$editoria = 'home';
	}
	
	$terms = get_terms( 'editoria', array( 'hide_empty' => false ) );
	
	// definir o input
	$input = "<select name='{$data['name']}' id='{$data['name']}' class='editorias_jump'>";
	$selected_home = ( $editoria == 'home' ) ? ' selected="selected"' : '';
	$link_home = remove_query_arg( 'editoria' );
	$input .= "<option value='{$link_home}' {$selected_home}>Home</option>";
	foreach( $terms as $term ){
		$link_editoria = add_query_arg( array('editoria' => $term->term_id) );
		$selected = selected($term->term_id, $editoria, false);
		$input .= "<option value='{$link_editoria}' {$selected}>{$term->name}</option>";
	}
	$input .= "</select>";
	
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