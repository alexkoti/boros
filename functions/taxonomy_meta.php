<?php
/**
 * TAXONOMY META FUNCTIONS
 * Functions para o controle de informações adicionais para taxonomias, assim como exibição de dados destas no admin.
 * 
 */



/**
 * Applicar term_order caso seja requerido.
 * Inspirado no plugin my-category-order
 * 
 */
//add_filter( 'get_terms_orderby', 'custom_terms_orderby', 10, 2 );
function custom_terms_orderby( $orderby, $args ){
	if($args['orderby'] == 'term_order')
		return 't.term_order';
	else
		return $orderby;
}

/**
 * ==================================================
 * TERM META CLASS ==================================
 * ==================================================
 * Classe que controla todos os processos de adição/edição de term_metas
 * 
 */
class BorosTermMeta {
	var $config = array();
	
	var $context = array(
		'type' => 'termmeta',
		'taxonomy' => 'category',
		'term_id' => 1,
	);
	
	function __construct( $config ){
		$this->config = $config;
		$this->add_term_metas();
	}
	
	function add_term_metas(){
		remove_filter( 'pre_term_description', 'wp_filter_kses' ); //remover filtro de limpeza de tags html na descrição
		
		add_action( 'edit_category_form_pre', array($this, 'pre_formatt_term_core_data'), 10, 1 ); // prepara dados para exibir no form
		add_action( 'edit_link_category_form_pre', array($this, 'pre_formatt_term_core_data'), 10, 1 ); // prepara dados para exibir no form
		add_action( 'edit_tag_form_pre', array($this, 'pre_formatt_term_core_data'), 10, 1 ); // prepara dados para exibir no form
		
		foreach( $this->config as $taxonomy => $itens ){
			add_action( "{$taxonomy}_pre_edit_form", array($this, 'pre_formatt_term_core_data'), 10, 2 ); // prepara dados para exibir no form
			add_action( "{$taxonomy}_add_form_fields", array($this, 'add_term_meta_fields'), 10, 1 ); // campos extras na listagem de termos
			add_action( "{$taxonomy}_edit_form_fields", array($this, 'edit_term_meta_fields'), 10, 2 ); // campos extras na edição do termo[single]
			add_action( "edit_{$taxonomy}", array($this, 'save_taxonomy_meta'), 10, 2 ); // salvar termmeta ao editar um term
			add_action( "created_{$taxonomy}", array($this, 'save_taxonomy_meta'), 10, 2 ); // salvar termmeta ao criar um term
			add_action( 'delete_term', array($this, 'delete_taxonomy_meta'), 10, 3 ); // apagar termmeta ao remover um term
		}
		
		// adicionar custom actions
		do_action('boros_term_meta_actions', $this->config);
	}
	
	/**
	 * Form para adicionar nova taxonomia, aquele que é em ajax, na listagem geral de termos (wp-admin/edit-tags.php?taxonomy=serie&post_type=produto)
	 * 
	 * @TODO: modificar para permitir vários blocos de elements, assim como em admin pages
	 */
	function add_term_meta_fields( $taxonomy ){
		$data_block = $this->config[$taxonomy];
		foreach( $data_block['itens'] as $data  ){
			?>
			<div class="form-field boros_taxonomy_block" id="<?php echo "{$taxonomy}-{$data['name']}"; ?>">
				<?php create_form_elements( $this->context, $data, '', '' ); ?>
			</div>
			<?php
		}
	}
	
	/**
	 * Form para editar taxonomia já existente, em página própria (/wp-admin/edit-tags.php?action=edit&taxonomy=serie&tag_ID=5&post_type=produto)
	 * 
	 * @TODO: configurar o form elements para deixar o campo label opcional, para que possa ser usado na coluna <th>
	 * @TODO: modificar para permitir vários blocos de elements, assim como em admin pages
	 */
	function edit_term_meta_fields( $tag, $taxonomy ){
		$data_block = $this->config[$taxonomy];
		//pre( $data_block );
		
		echo "</tr></table><h3>{$data_block['title']}</h3><table class='form-field boros_taxonomy_block'><tr>";
		foreach( $data_block['itens'] as $data  ){
			//pre($data['name']);
			// chamar o valor gravado para o input
			$data_value = get_metadata('term', $tag->term_id, $data['name'], true);
			//pre($data_value);
			
			// se estiver vazio, usar o valor padrão
			if( empty( $data_value ) and isset($data['std']) ) $data_value = $data['std'];
			$parent = '';
			create_form_elements( $this->context, $data, $data_value, $parent );
		}
		echo '</tr></table>';
	}
	
	/**
	 * Salvar metadatas do termo
	 * 
	 * @TODO: sanitize, validation
	 */
	function save_taxonomy_meta( $term_id, $tt_id ){
		//pre($term_id);
		//pre($tt_id);
		//pre($_POST);
		
		/**
		 * Caso seja edição rápida ou adição de termo, a constante DOING_AJAX estará presente, portanto é ncessário diferenciar os dois casos.
		 * Em caso de adição de termo, os term_metas estarão presentes, portanto deve-se salvar os campos adicionais.
		 * 
		 */
		if( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) and isset($_POST['_inline_edit']) ){
			return false;
		}
        
        if( !isset($_POST['taxonomy']) ){
            return false;
        }
		
		$data_block = $this->config[ $_POST['taxonomy'] ];
		
		/**
		 * TAXONOMY METAS
		 * Salvar ou apagar term_metas
		 * 
		 */
		foreach( $data_block['itens'] as $element ){
			//pre($data);
			
			$data_name = $element['name'];
			
			/**
			 * Tentar atribuir o valor postado em $value, ou definí-lo como false.
			 * É verificado em $_POST e $_FILES para que possa incluir envios de arquivos
			 * 
			 */
			$value = false;
			if( isset($_POST[$element['name']]) )
				$value = $_POST[$element['name']];
			
			/**
			 * CALLBACKs
			 * Usar função de callback caso tenha sido declarado.
			 * Caso seja definido um callback, este deverá obrigatoriamente retornar um valor, ou então retornar false.
			 */
			if( isset( $element['callback']) ){
				$value = call_user_func( $element['callback'], $term_id, $element, $value );
			}
			if( isset( $element['callbacks']) ){
				foreach( $element['callbacks'] as $callback ){
					if( function_exists($callback) ){
						$value = call_user_func( $element['callback'], $term_id, $element, $value );
					}
				}
			}
				
			/**
			 * SKIP SAVE
			 * Pular o salvamento caso seja configurado, por exemplo, caso o meta box sirva apenas para ativar algum callback.
			 * 
			 */
			if( isset($element['skip_save']) and $element['skip_save'] == true ){
				continue;
			}
			
			if( isset( $_POST[$data_name] ) ){
				/**
				 * @todo - rever a necessidade dos filtros
				 * 
				 */
				switch( $element['type'] ){
					case 'duplicate_group':
						$new_data = array_values($value);
						break;
					
					//case 'textarea':
					//	$new_data = wpautop( stripslashes( $value ));
					//	break;
					
					//case 'simple_textarea':
					//	$new_data = stripslashes( $value );
					//	break;
					
					// TODO: fazer stripslahses|tags nos arrays
					default:
						$new_data = $value;
						//if( !is_array($value) ){
						//	$new_data = strip_tags( stripslashes( $value ) );
						//}
						//else{
						//	$new_data = $value;
						//}
						break;
				}
				update_metadata( 'term', $term_id, $data_name, $new_data );
			}
			else{
				delete_metadata( 'term', $term_id, $data_name );
			}
		}
	}

	function delete_taxonomy_meta( $term, $tt_id, $taxonomy ){
		if( isset($this->config[$taxonomy]['itens']) ){
			foreach( $this->config[$taxonomy]['itens'] as $option ){
				delete_metadata( 'term', $term, $option['name'] );
			}
		}
	}
	
	/**
	 * Filtrar dados antes de exibir nos inputs de controle.
	 * 
	 */
	function pre_formatt_term_core_data( $tag, $taxonomy = array() ){
		$tag->description = wpautop( $tag->description ); // ao habilitar o tinymce para este campo, ativar esse filtro
		
		return $tag;
	}
}

class BorosTaxonomyColumns {
	/**
	 * Configuração geral das colunas
	 * 
	 */
	var $config = array();
	
	/**
	 * Equivalente a $config, porém com as chaves das filtros correspondentes
	 * 
	 */
	var $manage = array();
	
	/**
	 * Array de colunas no padrão $filter => $taxonomy
	 * 
	 */
	var $render = array();
	
	function __construct( $config ){
		$this->config = $config;
		foreach( $config as $tax_name => $columns ){
			$this->manage["manage_edit-{$tax_name}_columns"] = $columns;
			add_filter( "manage_edit-{$tax_name}_columns", array($this, 'manage_columns'), 10, 1 );
			
			$this->render["manage_{$tax_name}_custom_column"] = $tax_name;
			add_filter( "manage_{$tax_name}_custom_column", array($this, 'render_columns'), 10, 3);
		}
	}
	
	function manage_columns( $columns ){
		$key = current_filter();
		if( array_key_exists( $key, $this->manage ) ){
			return $this->manage[$key];
		}
		return $columns;
	}
	
	function render_columns( $output, $column_name, $term_id ){
		$key = current_filter();
		$taxonomy = $this->render[$key];
		
		/**
		 * Exibir o valor simples caso esteja no formato 'termmeta_{meta_name}'
		 * 
		 */
		preg_match( '/^termmeta_(.*)/', $column_name, $meta_name );
		if( isset($meta_name[1]) ){
			$termmeta = get_metadata('term', $term_id, $meta_name[1], true);
			echo "<span class='taxonomy_{$taxonomy} termid_{$term_id}'>{$termmeta}</span>";
			return;
		}
		
		/**
		 * Executar a function correspondente caso seteja no formato 'function_{function_name}'
		 * É útil para adicionar functions de renderização sem interferir no switch com as opções padrão.
		 * 
		 * Essa function irá receber os seguintes parâmetros:
		 * @param $taxonomy - nome da taxonomia
		 * @param $term_id - id do termo
		 */
		preg_match( '/^function_(.*)/', $column_name, $function );
		if( isset($function[1]) ){
			if( function_exists($function[1]) ){
				call_user_func($function[1], $taxonomy, $term_id);
			}
			else{
				echo "<span class='form_element_error'>A function {$function[1]}() não existe.</span>";
			}
			return;
		}
		
		switch( $column_name ){
			/**
			 * Por padrão deixar o formato 'termmeta_{meta_name}', que irá buscar o termmeta relacionado
			 * 
			 */
			default:
				do_action( 'boros_custom_taxonomy_column', $taxonomy, $term_id, $column_name );
				break;
		}
		return $output;
	}
}


/**
 * Apenas options page, não válido para metabox
 * 
 * @todo revisar a necessidade ou se é deprecated
 */
function taxonomy_term_order( $option, $value ){
	global $wpdb;
	
	$order = explode( ',', $value );
	$total = count($order);
	//pre($order, '$order');
	//pre($total, '$total');
	
	for( $i = 0; $i < $total; $i++ ){
		$table = $wpdb->terms;
		$data = array('term_order' => ($i + 1));
		$where = array('term_id' => $order[$i]);
		$wpdb->update( $table, $data, $where );
	}
	
	return $value;
}



function post_type_content_order( $config, $value ){
	global $wpdb;
	
	$order = explode( ',', $value );
	$total = count($order);
	
	for( $i = 0; $i < $total; $i++ ){
		$table = $wpdb->posts;
		$data = array('menu_order' => ($i + 1));
		$where = array('ID' => $order[$i]);
		$wpdb->update( $table, $data, $where );
	}
	
	return $value;
}


















/**
 * ==================================================
 * DEPRECATED? ======================================
 * ==================================================
 * 
 * 
 */

/**
 * Adicionar scripts à página de adição de categoria
 * 
 * TODO: REVER SE É AINDA PRECISO DESSE CÓDIGO
 */
//add_action('admin_footer-edit-tags.php', 'add_edit_terms_inline_scripts');
function add_edit_terms_inline_scripts(){
	global $taxonomy_meta, $taxonomy;
	if( !in_array($taxonomy, $taxonomy_meta) ){
		return;
	}
	
	$fields_to_reset = array();
	$editors_to_reset = array();
	foreach( $taxonomy_meta[$taxonomy]['itens'] as $option ){
		$fields_to_reset[] = "#{$option['name']}";
		if( $option['type'] == 'textarea' )
			$editors_to_reset[] = "#{$option['name']}";
	}
	$fields = join(', ', $fields_to_reset);
	$editors = join(', ', $editors_to_reset);
?>
<script type="text/javascript">
jQuery(document).ready(function($){
	/**
	 * Atualizar o textarea escondido com o conteúdo do tinymce, para que seja corretamente enviado via ajax.
	 */
	$('#submit').mousedown(function(){
		tinyMCE.triggerSave();
	});
	
	/**
	 * Limpar os campos personalizados depois do envio por ajax.
	 * A variável global 'fields_to_reset' está declarada em add_edit_terms_inline_scripts()
	 */
	$('#submit').click(function(){
		$("<?php echo $fields; ?>").val('');
		$("<?php echo $editors; ?>").add('#tag-description').each(function(){
			var id = $(this).attr('id');
			tinyMCE.get(id).setContent('');
		});
	});
});
</script>
<?php
}



