<?php
/**
 * FORM ELEMENT: DUPLICABLE_GROUP
 * 
 * WARNING: duplicação de elementos em option_page:
 *          Ao duplicar os elementos em uma option page(em meta_box funciona normalmente) os inputs perdem o 'registro' no DOM, 
 *          e não enviam informações ao $_POST, inclusive perdendo a capacidade de envio pela tecla ENTER.
 *          Isso se aplica a qualquer navegador.
 * 
 * @button_prepend e $button_append - botão de adicionar item no começo e fim da lista
 * 
 * @compact_button - Mostrar checkbox para esconder elementos nos itens e deixar mais fácil o drag-drop
 * Por padrão o controle irá manter o primeiro campo visível e esconder os demais
 * Para escolher um mais campos para sempre serem visíveis no modo compacto, utilizar a class 'compact-show' no [attr][elem_class]
 * 
 * Para mostrar campos condicionais dependentes de um select, utilizar a class 'conditional-type' em [attr][class] do select e 
 * as classes em [attr][elem_class] nos campos dependentes:
 *   'conditional-row'
 *   'row-{SELECT-FIELD-VALUE}'
 * Assim no onchange do select, será exibido todos os campos com a class com o mesmo valor do select
 * 
 * Exemplo de configuração de elemento, com opção de visibilidade condicional. Neste exemplo, quando o select 'type' é 
 * modificado, será exibido/escondido os campos dependentes de cada um. No modo compacto, os campos 'title' e 'question'
 * sempre serão exibidos, mas respeitando a visibilidade defeinida pelo select:
 * <code>
 * array(
 *     'name'   => 'jlpt_faq',
 *     'type'   => 'duplicate_group',
 *     'label'  => 'Peguntas',
 *     'layout' => 'block',
 *     'attr'   => array(
 *         'elem_class' => 'duplicate-conditional'
 *     ),
 *     'options' => array(
 *         'compact_button' => true,
 *     ),
 *     'group_itens' => array(
 *         array(
 *             'name'  => 'type',
 *             'type'  => 'select',
 *             'label' => 'Tipo',
 *             'std'   => 'content',
 *             'attr'  => array('class' => 'conditional-type'),
 *             'options' => array(
 *                 'values' => array(
 *                     'faq-question' => 'Pergunta',
 *                     'faq-title'    => 'Titulo',
 *                 ),
 *             ),
 *         ),
 *         array(
 *             'name'  => 'title',
 *             'type'  => 'text',
 *             'label' => 'Título do setor',
 *             'size'  => 'full',
 *             'attr'  => array('elem_class' => 'conditional-row row-faq-title compact-show'),
 *         ),
 *         array(
 *             'name'  => 'question',
 *             'type'  => 'text',
 *             'label' => 'Pergunta',
 *             'size'  => 'full',
 *             'attr'  => array('elem_class' => 'conditional-row row-faq-question compact-show'),
 *         ),
 *         array(
 *             'name'  => 'slug',
 *             'type'  => 'text',
 *             'label' => 'Âncora',
 *             'size'  => 'small',
 *             'attr'  => array('elem_class' => 'conditional-row row-faq-question'),
 *         ),
 *         array(
 *             'name'  => 'answer',
 *             'type'  => 'textarea_editor',
 *             'label' => 'Resposta',
 *             'attr'  => array('elem_class' => 'conditional-row row-faq-question'),
 *             'options' => array(
 *                 'editor' => 'full'
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 * 
 * 
 */

class BFE_duplicate_group extends BorosFormElement {

	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
	);

    var $first_element_index = '';

    function add_defaults(){
        $this->defaults['options']['compact_button'] = true;
        $this->defaults['options']['button_prepend'] = false;
        $this->defaults['options']['button_append']  = true;
    }
	
	var $enqueues = array(
		'js' => 'duplicate_group',
		'css' => 'duplicate_group',
	);
	
	var $compact_buttom = '<div class="duplicate_compact"><label><input type="checkbox" /> visualização compacta</label> <span class="btn_help" title="Exibe apenas um item de cada grupo, facilitando a tarefa de arrastar para organizar os itens."></span></div>';
	
    function set_label(){
        if( $this->data['options']['compact_button'] == false ){
            $this->compact_buttom = '';
        }
        
        if( !empty($this->data['label']) ){
            $this->label = "<div class='duplicate_group_header'><div class='duplicate_group_label'>{$this->data['label']}{$this->label_helper}</div>{$this->compact_buttom}</div>";
        }
        elseif( $this->data['layout'] == 'table' ){
            $this->label = "<div class='duplicate_group_header'>{$this->compact_buttom}</div>";
        }
    }

    function element_class( $element ){

    }
	
	function set_input( $value = null ){
		/**
		 * Definir o parent dos subelementos
		 * 
		 */
		$this->context['parent'] = $this->data['name'];

        /**
         * Aplicar class padrão .compact-show ao primeiro elemento, caso nenhum outro possua essa class, para
         * que pelo menos 1 item fique visível no modo compacto
         * 
         */
        $compact_elem = false;
        $u = 1;
        foreach( $this->data['group_itens'] as $index => $item ){
            if( $u == 1 ){
                $this->first_element_index = $index;
            }

            if( isset($item['attr']['elem_class']) ){
                $classes = explode(' ', $item['attr']['elem_class']);
                if( in_array('compact-show', $classes) ){
                    $compact_elem = true;
                    break;
                }
            }
            $u++;
        }
        if( $compact_elem == false ){
            $classes = $this->data['group_itens'][ $this->first_element_index ]['attr']['elem_class'] ?? '';
            $this->data['group_itens'][ $this->first_element_index ]['attr']['elem_class'] = "{$classes} compact-show";
        }

		// começar a guardar o html do input em buffer
		ob_start();
		
		// criar controle de visualização compacta
		if( $this->label == '' and $this->data['layout'] == 'block' ){
			echo "<div class='duplicate_group_header'>{$this->compact_buttom}</div>";
		}
		?>
		<div class="duplicate_box">
            <?php if( $this->data['options']['button_prepend'] == true ){ ?>
            <p class="dup_btn"><span data-pos="prepend">Adicionar novo item</span></p>
            <?php } ?>
			<ul class="duplicate_group">
			<?php
				$data_length = count($this->data['group_itens']);
				if( $this->data_value == null ){
					$this->data_value = array();
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
					<div class="btn_remove" title="Remover grupo"><div class="btn"></div></div>
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
            <?php if( $this->data['options']['button_append'] == true ){ ?>
            <p class="dup_btn"><span data-pos="append">Adicionar novo item</span></p>
            <?php } ?>
		</div>
		<?php
		// guardar o output em variável
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}