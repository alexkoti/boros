<?php
/**
 * FORM ELEMENT: DUPLICABLE_GROUP
 * 
 * @todo adicionar botão(checkbox?) de collapse, exibindo apenas o primeiro elemento do grupo
 * 
 * 
 * WARNING: SUPER BUG DA MORTE [here be dragons, this is sparta, deixem a esperança do lado fora ] >>>>>>>>>>>>>>>>>>>>>>>>>> duplicação de elementos em option_page:
 * 		Ao duplicar os elementos em uma option page(em meta_box funciona normalmente) os inputs perdem o 'registro' no DOM, e não enviam informações ao $_POST, inclusive 
 *		perdendo a capacidade de envio pela tecla ENTER.
 * 		Isso se aplica a qualquer navegador.
 * 
 */

class BFE_duplicate_group extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
	);
	
	var $enqueues = array(
		'js' => 'duplicate_group',
		'css' => 'duplicate_group',
	);
	
	var $compact_buttom = '<div class="duplicate_compact"><label><input type="checkbox" /> visualização compacta</label> <span class="btn_help" title="Exibe apenas um item de cada grupo, facilitando a tarefa de arrastar para organizar os itens."></span></div>';
	
	function set_label(){
		if( isset($this->data['options']['compact_button']) and ($this->data['options']['compact_button'] == false) )
			$this->compact_buttom = '';
		
		if( !empty($this->data['label']) )
			$this->label = "<div class='duplicate_group_header'><div class='duplicate_group_label'>{$this->data['label']}{$this->label_helper}</div>{$this->compact_buttom}</div>";
		elseif( $this->data['layout'] == 'table' )
			$this->label = "<div class='duplicate_group_header'>{$this->compact_buttom}</div>";
	}
	
	function set_input( $value = null ){
		/**
		 * Definir o parent dos subelementos
		 * 
		 */
		$this->context['parent'] = $this->data['name'];
		
		// começar a guardar o html do input em buffer
		ob_start();
		
		// criar controle de visualização compacta
		if( $this->label == '' and $this->data['layout'] == 'block' ){
			echo "<div class='duplicate_group_header'>{$this->compact_buttom}</div>";
		}
		?>
		<div class="duplicate_box">
			<ul class="duplicate_group">
			<?php
				$data_length = count($this->data['group_itens']);
				if( $this->data_value == null ){
					foreach( $this->data['group_itens'] as $item ){
						$group_values[$item['name']] = isset($item['std']) ? $item['std'] : '';
					}
					$this->data_value[] = $group_values;
				}
				
				for( $u = 0; $u < count($this->data_value); $u++ ){
					/**
					 * IMPORTANTE:
					 * A <li> possui os seguintes datasets:
					 *  - data-name : com o valor do subinput a ser gravado
					 *  - data-group : com o grupo do input parent
					 *  - data-type : type da página atual(option, post_meta, termmeta, usermeta, widget)
					 *  - data-parent : key name do option que será gravado
					 * Essas informações serão usados pelo javascript de duplicate.
					 */
					$context_dataset = '';
					foreach( $this->context as $data => $value ){
						$context_dataset .= " data-{$data}='{$value}'";
					}
				?>
				<li class="boros_form_element duplicate_element" id="<?php echo $this->data['name'] . '_' . $u; ?>" data-name="<?php echo $this->data['name']; ?>" <?php echo $context_dataset; ?>>
					<div class="btn_remove" title="Remover"><div class="btn"></div></div>
					<div class="btn_move" title="Arraste para organizar"><div class="grip"></div></div>
					
					<table class="form-table boros_options_block">
						<?php
						foreach( $this->data['group_itens'] as $element ){
							if( isset($this->data_value[$u][$element['name']]) ){
								$value = $this->data_value[$u][$element['name']]; // atribuir o valor antes de mudar o name!!!!!!!
							}
							else{
								$value = '';
							}
							
							// armazenar o 'name' original em 'data-name'
							$element['attr']['dataset']['name'] = $element['name'];
							
							// modificar o 'id' e 'name' para o formato aninhado
							$element['attr']['id'] = "{$this->data['name']}_{$u}_{$element['name']}";
							$element['name'] = "{$this->data['name']}[{$u}][{$element['name']}]";
							
							// sinalizar que é um duplicate
							$element['in_duplicate_group'] = true;
							
							$element['index'] = $u;
							
							create_form_elements( $this->context, $element, $value );
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
					
					<div class="duplicate_index"><?php echo $u + 1; ?></div>
				</li>
				<?php
				} // .foreach
			?>
			</ul>
			<p class="dup_btn"><span>Adicionar novo item</span></p>
		</div>
		<?php
		// guardar o output em variável
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}