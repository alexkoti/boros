<?php
/**
 * FORM ELEMENT: SELECT TAXONOMY
 * Elemento input:select com a lista de taxonomias - padrão category
 * Caso seja preciso maiores configurações na requisição dos termos, é melhor criar um elemento derivado
 * 
 * 
 */

function form_element_select_terms( $data, $data_value, $parent ){
	$args = array(
		'hide_empty' => false,
	);
	$taxonomy = ( isset($data['taxonomy']) ) ? $data['taxonomy'] : 'category';
	$terms = get_terms( $taxonomy, $args );
	
	// definir o input
	$input = "<select name='{$data['name']}' id='{$data['name']}'>";
	$input .= "<option value='0'>Selecione a editoria</option>";
	foreach( $terms as $term ){
		$selected = selected($term->term_id, $data_value, false);
		$input .= "<option value='{$term->term_id}' {$selected}>{$term->name}</option>";
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