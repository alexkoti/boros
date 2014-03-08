<?php
/**
 * FORM ELEMENT: 
 * 
 * 
 * 
 */

/**
 * Para este elemento, $data_value poderá ser o valor de term_meta, no caso de edição de série(taxonomy) ou então $post, no caso de produto(posts)
 * 
 */
function form_element_excs_taxonomy_radio( $data, $data_value, $parent ){
	global $post;
	
	// começar a guardar o output do script js em buffer
	ob_start();
	
	
	//options
	$defaults = array(
		'object_type' => 'post',
		'show_popular' => true,
		'show_recent' => true,
		'show_favs' => true,
		'show_adder' => false,
	);
	$options = wp_parse_args($data['options'], $defaults);
	//pre($options);
	
	$taxonomy = $data['name'];
	$tax = get_taxonomy($taxonomy);
	$input_name = "tax_input[{$taxonomy}]";
	?>
	<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
		<div id="<?php echo $taxonomy; ?>-all" class="">
			<div id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
				<p><label for="<?php echo $taxonomy; ?>_filter_list_input">Filtrar</label> <input type="text" id="<?php echo $taxonomy; ?>_filter_list_input" class="filter_list_input" /></p>
				<ul class="filter_list_itens">
					<?php
					// verificar qual é o checkado
					$selected_terms = wp_get_object_terms( $post->ID, $taxonomy );
					if($selected_terms)
						$selected_term = $selected_terms[0]->term_id;
					else
						$selected_term = 0;
					
					// vazio
					$checked = checked( 0, $selected_term, false );
					$empty_radio = "<li><label for='{$taxonomy}_radio_0'><input type='radio' name='{$input_name}' value='0' id='{$taxonomy}_radio_0' {$checked} /> <span>Sem série</span></label></li>";
					
					// todos os termos
					$terms_list = array();
					$terms = get_terms( $taxonomy, array('hide_empty' => false) );
					foreach( $terms as $term ){
						$checked = checked( $term->term_id, $selected_term, false );
						$item = "<li><label for='{$taxonomy}_radio_{$term->term_id}'><input type='radio' name='{$input_name}' value='{$term->term_id}' id='{$taxonomy}_radio_{$term->term_id}'{$checked} /> <span>{$term->name}</span></label></li>";
						
						// colocar o selecionado no começo da lista
						if( $term->term_id == $selected_term )
							array_unshift( $terms_list, $item );
						else
							$terms_list[] = $item;
						
					}
					// adicionar o vazio no começo
					array_unshift( $terms_list, $empty_radio );
					// exibir itens
					echo implode( "\n", $terms_list );
					?>
				</ul>
			</div>
		</div>
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
		case 'simple':
			echo $input;
			break;
		
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