<?php
/**
 * FORM ELEMENT: DUPLICABLE_GROUP
 * 
 * 
 * WARNING: SUPER BUG DA MORTE [here be dragons, this is sparta, deixem a esperança do lado fora ] >>>>>>>>>>>>>>>>>>>>>>>>>> duplicação de elementos em option_page:
 * 		Ao duplicar os elementos em uma option page(em meta_box funciona normalmente) os inputs perdem o 'registro' no DOM, e não enviam informações ao $_POST, inclusive 
 *		perdendo a capacidade de envio pela tecla ENTER.
 * 		Isso se aplica a qualquer navegador.
 * 
 */

function form_element_duplicable_group( $data, $data_value, $parent ){
	// começar a guardar o html do input em buffer
	ob_start();
	
	//pre($data_value);
	?>
	<ul class="duplicate_group">
	<?php
		//global $post;
		//pre( get_post_meta( $post->ID, 'html_boxes', true ) );
		
		$data_length = count($data['group_itens']);
		if( $data_value == null ){
			foreach( $data['group_itens'] as $item ){
				$group_values[$item['name']] = $item['std'];
			}
			$data_value[] = $group_values;
		}
		
		//pre($data_length, '$data_length');
		//pre($data['group_itens'], '$data[group_itens]');
		//pre($data, '$data');
		//pre($data_value, '$data_value');
		//pre($parent, '$parent');
		
		//pre($data_value);
		//pre($data['group_itens']);
		
		
		for( $u = 0; $u < count($data_value); $u++ ){
		?>
		<li class="form_element_box duplicate_element" id="<?php echo $parent . '_' . $u; ?>" rel="<?php echo $parent; ?>">
			<div class="btn_move" title="Arraste para organizar"></div>
			
			<table class="form-table  boros_options_block">
				<?php
				//pre($item);
				
				$i = 0;
				foreach( $data['group_itens'] as $item ){
					//pre($u);
					//pre($data['name']);
					//pre($data_value[$i], 'valor');
					
					//pre( $data['name'] . '[' . $u . ']' . '[' . $item['name'] . ']' , 'new_name');
					
					if( isset($data_value[$u][$item['name']]) ){
						$value = $data_value[$u][$item['name']]; // atribuir o valor antes de mudar o name!!!!!!!
					}
					else{
						$value = '';
					}
					
					$item['name'] = $data['name'] . '[' . $u . ']' . '[' . $item['name'] . ']';
					//pre($item);
					//pre($value, 'novo');
					
					create_form_elements( $item, $value, $parent );
					$i++;
				}
				
				if( isset($data_block['hints']) ){
				?>
				<td colspan="2" class="block_desc">
					<div>
						<span class="block_desc_ico">Info:</span> 
						<?php echo $data_block['hints']; ?>
					</div>
				</td>
				<?php } ?>
			</table>
			
			<p class="remove"><span class="btn_remove">Remover</span></p>
		</li>
		<?php
		} // .foreach
	?>
	</ul>
	<p class="dup_btn"><span>Adicionar</span></p>
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