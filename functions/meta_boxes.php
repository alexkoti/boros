<?php
/**
 * META_BOXES
 * 
 * 
 * 
 */

class BorosMetaBoxes {
	/**
	 * Todos os meta_boxes declarados
	 * 
	 */
	var $meta_boxes;
	
	var $errors;
	
	var $core_post_fields = array(
		'ID',
		'post_author',
		'post_date',
		'post_date_gmt',
		'post_content',
		'post_title',
		'post_excerpt',
		'post_status',
		'comment_status',
		'ping_status',
		'post_password',
		'post_name',
		'to_ping',
		'pinged',
		'post_modified',
		'post_modified_gmt',
		'post_content_filtered',
		'post_parent',
		'guid',
		'menu_order',
		'post_type',
		'post_mime_type',
		'comment_count',
		'post_category',
		//'tags_input',
		//'tax_input',
		//'page_template',
    );
    
    /**
     * CSS class adicional do metabox
     * 
     */
    var $css_class = '';
	
	/**
	 * Contexto
	 * 
	 */
	var $context = array(
		'type' => 'post_meta',
		'post_type' => 'post',
		'post_id' => 0,
		'parent' => 0,
	);
	
	/**
	 * O __construct() é executado no hook 'admin_init', possibilitando que seja adicionado tanto a action 'add_meta_boxes' como 'save_post'
	 * 
	 */
	function __construct( $config ){
		// gravar $config, corrigindo o formato do array, adaptando os modelos antigos e novos
		$this->meta_boxes = update_element_config($config);
		
		add_action( 'add_meta_boxes', array($this, 'add') );
		add_action( 'save_post', array($this, 'save'), 10, 2 );
		add_action( 'ajax_duplicate_group', array($this, 'duplicate_group'), 10, 3 );
		add_filter( 'load_element_config', array($this, 'load_element_config'), 10, 2 );
	}
	
	function add(){
		global $post;
		$this->context['post_type'] = $post->post_type;
		$this->context['post_id'] = $post->ID;
		
		$post_types = get_post_types();
		foreach( $this->meta_boxes as $box ){
			// argumentos a serem enviados ao callback
			$defaults = array(
				'id' 	=> NULL,
				'desc' 	=> NULL,
				'itens' => NULL,
			);
			$args = wp_parse_args( $box, $defaults );
			
			//criar um array de post_types para aplicar o meta_box
			$apply_to = ( is_array($box['post_type']) ) ? $box['post_type'] : array($box['post_type']);
			
			foreach( $apply_to as $apply ){
				if ( array_key_exists( $apply, $post_types ) ){
					if( isset($box['class']) ){
                        $this->css_class = $box['class'];
                    }
					add_filter( "postbox_classes_{$apply}_{$box['id']}", array($this, 'box_classes') );
					//add_meta_box( "metabox_{$box['id']}", $box['title'], array($this, 'output'), $apply, $box['context'], $box['priority'], $args );
					add_meta_box( $box['id'], $box['title'], array($this, 'output'), $apply, $box['context'], $box['priority'], $args );
				}
			}
		}
	}
	
	/**
	 * @todo não seria melhor usar $post_object? Fazer testes.
	 * 
	 */
	function output( $post_object, $box ){
		global $post;
		//pre($post_object, '$post_object');
		//pre($box, '$box');
		
		// carregar as mensagens de erro, se houver
		$errors = get_transient( "{$post->ID}_meta_errors" );
		
		$parent     = $box['args']['id'];
		$meta_itens = $box['args']['itens'];
		
		echo "<table class='form-table boros_form_block boros_meta_block' id='{$parent}_inner'>";
		
		// descrição
		if( isset($box['args']['desc']) and !empty($box['args']['desc']) ){
			?>
			<tr>
				<td colspan="2" class="boros_form_desc">
					<div><?php echo $box['args']['desc']; ?></div>
				</td>
			</tr>
			<?php
		}
		
		/**
		 * input:hidden para identificar envio de dados personalizados
		 * TODO: mudar para wp_check_referer
		 */
		//echo "<input type='hidden' name='custom_data' value='1' />";
		
		foreach( $meta_itens as $meta_item ){
			//pre($meta_item);
			$data_value = null;
			
			/**
			 * Alguns itens, como taxonomy_radio, que substituem names de inputs core do WordPress, não necessariamente declaram 'name', gerando erro no script.
			 */
			if( isset( $meta_item['name'] ) ){
				// separar entre os valores de coluna de wp_post e wp_postmeta
				// post_content é renderizado no wp_editor com o name 'content' apenas, mas para o elemento customizado, deverá ser usado o 'post_content'
				if( in_array($meta_item['name'], $this->core_post_fields) ){
					$data_value = $post->{$meta_item['name']};
				}
				else{
					$data_value = get_post_meta( $post->ID, $meta_item['name'] ); // chamar o valor gravado para o input
					if( count($data_value) == 1 ){
						$data_value = $data_value[0];
					}
				}
			}
			//pre($data_value, $meta_item['name']);
			
			// se estiver vazio, usar o valor padrão
			if( empty( $data_value ) and isset( $meta_item['std']) ) $data_value = $meta_item['std'];
			
			// adicionar mensagens de erro
			if( isset($meta_item['name']) and isset( $errors[$meta_item['name']] ) ){
				$meta_item['errors'] = $errors[$meta_item['name']];
			}
			
			//pre($data_value);
			// Aplicar filtro de exibição, 'pre_filter'
			if( isset($meta_item['pre_filters']) ){
				foreach( $meta_item['pre_filters'] as $pre_filter ){
					if( function_exists($pre_filter) ){
						$data_value = call_user_func( $pre_filter, $post, $meta_item, $data_value );
					}
				}
			}
			//pre($data_value);
			
			// o parent é a ID do box
			$this->context['group'] = $box['id'];
			create_form_elements( $this->context, $meta_item, $data_value, $this->context['group'] );
		}
		
		// info help de rodapé
		if( isset($box['args']['help']) and !empty($box['args']['help']) ){
			?>
			<tr>
				<td colspan="2" class="boros_form_extra_info">
					<div>
						<span class="ico"></span> 
						<?php echo $box['args']['help']; ?>
					</div>
				</td>
			</tr>
			<?php
		}
		
		echo '</table>';
	}
	
	function save( $post_id, $post ){
		//pre($_POST, '$_POST'); die();
		//pal('DEBUG!!!');
		
		/*
		 * Verificar se é a rotina de autosave ou quick edit. O autosave/quickedit ignora o conteúdo dos meta boxes e acaba 'resetando' os campos
		 * Em caso de autosave/quick o função é interrompida, retorna o ID do post para o restante do processo de autosave.
		 *
		 * O código: if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		   não cobre o quick edit, e a declaração id( DOING_AJAX ), conflita com o autosave. Por isso foi necessário usar 
		   um input:hidden com name="custom_data", declarado em meta_boxes_print() e verificar o $_POST desta variável na action 'save_post'.
		   Adicionado a verificação de 'post_type' == 'revision', pois isso também resetava os meta_boxes
		
		 * @link http://www.mikoder.com.au/2009/07/disappearing-custom-post-data/
		 */
		if( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) ){
			return $post_id;
		}
		if( !isset( $_POST['post_ID'] ) || $post_id != $_POST['post_ID'] ){
			return $post_id;
		}
		if( $post->post_type == 'revision' ){
			return $post_id;
		}
		
		/**
		 * Verificar permissões.
		 * Deixado por último para que as verificações anteriores cuidem de pular revisões, ajax e autosave.
		 * 
		 */
		$post_type = get_post_type_object( $post->post_type );
		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ){
			wp_die( "Sem permissões para editar {$post_type->labels->name}." );
			return $post_id;
		}
		
		/**
		 * Manter esse bloco de reserva caso as verificações de autosave e ajax não cubram todas as situações.
		 * Não esquecer do input:hidden em $this->output()
		 */
		//if ( (!$post_id) or (!isset($_POST['custom_data'])) or ($post->post_type == 'revision') ){
		//	return $post;
		//}
		
		/**
		 * Instanciar um objeto validate
		 * 
		 */
		$context = array(
			'type' => 'post_meta',
			'post_type' => 'post',
			'post_id' => $post_id
		);
		$validation = new BorosValidation( $context );
		
		foreach( $this->meta_boxes as $box ){
			/**
			 * Caso não pertença ao mesmo post_type, pular iteração, assim não tenta processar(e resetar) meta_datas não aplicáveis no momento.
			 * 
			 */
			if( !in_array( $_POST['post_type'], $box['post_type'] ) ){
				continue;
			}
			
			/**
			 * Loop nos form_elements
			 */
			foreach( $box['itens'] as $element ){
				/**
				 * Tentar atribuir o valor postado em $value, ou definí-lo como false.
				 * É verificado em $_POST e $_FILES para que possa incluir envios de arquivos
				 * 
				 */
				$value = false;
				if( isset($element['name']) ){
					if( isset($_POST[$element['name']]) ){
						$value = $_POST[$element['name']];
					}
					elseif( isset($_FILES[$element['name']]) ){
						$value = $_FILES[$element['name']];
					}
				}
				
				//pal($value, '1' . $element['name']);
				//pre($element, 'element ' . $element['name']);
				
				/**
				 * Validate/Sanitize
				 * Validação de type e custom, se houver
				 */
				$validation->add( $element );
				$value = $validation->verify_post_meta( $post_id, $element, $value );
				 
				/**
				 * CALLBACK
				 * Usar função de callback caso tenha sido declarado.
				 * Caso seja definido um callback, este deverá obrigatoriamente retornar um valor, ou então retornar false.
				 * 
				 * IMPORTANTE: DEVE ENVIAR $post completo como parâmetro. Isso evita uma possível chamda de get_post na função de callbak
                 * IMPORTANTE: DEVE RETORNAR O $value caso contrário o meta será apagado
				 */
				if( isset( $element['callback']) ){
					$value = call_user_func( $element['callback'], $post, $element, $value );
				}
				if( isset( $element['callbacks']) ){
					foreach( $element['callbacks'] as $callback ){
						if( function_exists($callback) ){
							$value = call_user_func( $callback, $post, $element, $value );
						}
					}
				}
				
				/**
				 * CONDITIONAL
				 * Função verificadora para determinar se deve ou não salvar o dado enviado pelo formulário
				 * Obrigatoriamente deve retornar true ou false
				 * 
				 */
				$conditional = true;
				if( isset( $element['conditional']) ){
					$conditional = call_user_func( $element['conditional'], $post, $element, $value );
				}
				if( $conditional == false ){
					continue;
				}
				
				/**
				 * SKIP SAVE
				 * Pular o salvamento caso seja configurado, por exemplo, caso o meta box sirva apenas para ativar algum callback.
				 * 
				 */
				if( isset($element['skip_save']) and $element['skip_save'] == true ){
					continue;
				}
				
				//pre($value, $element['name']);
				/**
				 * Salvar dados
				 * Aplicar correçoes dependendo do type do elemento
				 *
				 */
                $can_save    = true;
                $allow_empty = isset($element['allow_empty']) ? $element['allow_empty'] : false;
                $is_empty    = boros_check_empty_var($value) === false;
                if( $is_empty && $allow_empty === false ){
                    $can_save = false;
                }

                //pel($allow_empty, "allow empty: {$element['name']}");
                //pel($is_empty, $element['name'], 50);
                //pel($can_save, 'cansave: ' . $element['name'], 50);

				if( $can_save == true ){
                    //pel($element['name'], 'salvar', 50);
					
					switch( $element['type'] ){
						// duplicate group: reindexar o array numérico, para que seja perfeitamente sequencial
						case 'duplicate_group':
							$data = array_values($value);
							break;
						
						// @todo - verificar se precisa do stripslashes
						// verificar se é simple_textarea - prepara para gravar em plain text
						case 'simple_textarea':
							$data = stripslashes( $value );
							break;
						
						default:
							$data = $value;
					}
					
					/**
					IMPORTANTE >>>>>>> TALVEZ COLOCAR AQUI O DUPLICATE ITEMS (diferente de duplicate group) - add_post_meta multiple
					// Verificar se os dados são um array e remover caso esteja vazio
					if( is_array($data) ){
						foreach( $data as $d ){
							$arr = trim_array($d);
						}
						if( empty($arr)){
							unset($data);
						}
					}
					/**/
					
					//pre($data, $element['name']);
					//pre($element['name']);
					
					if( isset($element['duplicable']) and $element['duplicable'] == true ){
						$this->save_multiple( $post_id, $element['name'], $data );
					}
					else{
						//pal( $element['name'], $data );
						$this->save_single( $post_id, $element['name'], $data );
					}
					
					/**
					if( get_post_meta( $post_id, $element['name'] ) == '' ){
						add_post_meta( $post_id, $element['name'], $data, true );
					}
					elseif( empty($data) or $data == '' ){
						delete_post_meta( $post_id, $element['name'], get_post_meta( $post_id, $element['name'], true ) );
					}
					elseif( $data != get_post_meta( $post_id, $element['name'], true ) ){
						update_post_meta( $post_id, $element['name'], $data );
					}
					/**/
					
				}
				// Remover postmeta, se existir. Manter se a opção 'allow_empty' estiver true
				else{
					if( isset($element['name']) && get_post_meta($post_id, $element['name'], true) ){
                        //pel($element['name'], 'delete');
						delete_post_meta($post_id, $element['name'], get_post_meta($post_id, $element['name'], true));
					}
				}
			}
			
			/**
			 * Gravar mensagens de erro, se houver
			 * @todo mostrar avisos no admin
			 */
			if( !empty($validation->meta_errors) ){
				set_transient( "{$post_id}_meta_errors", $validation->meta_errors, 30 );
			}
		}
		//pre($_POST, '$_POST');
		//pre($post_id, '$post_id');
		//pre($post, '$post');
		//pre($_POST, '$_POST'); die();
		//pal('DEBUG!!!');
	}
    
    /**
     * A verificação de valor vazio já está sendo feita em save(), quando o valor chegar
     * neste método já estará validado.
     * 
     */
	function save_single( $post_id, $name, $value ){
        update_post_meta( $post_id, $name, $value );
		//pal('save_single', $name);
		//$original = get_post_meta( $post_id, $name );
		//$value_status = boros_check_empty_var($value);
		//
		//// valor postado vazio
		//if( $value_status === false ){
		//	//pal("deleted {$name}");
		//	delete_post_meta( $post_id, $name, $original );
		//}
		//// não vazio, salvar
		//else{
		//	//pal("saved {$name}");
		//	update_post_meta( $post_id, $name, $value );
		//}
	}
	
	/**
	 * @TODO IMPORTANTE >>>> REVISAR ESTE MÉTODO - usar o delete_post_meta() apenas quando necessário
	 * 
	 */
	function save_multiple( $post_id, $name, $value ){
		// apagar todos os metas
		delete_post_meta( $post_id, $name );
		foreach( $value as $subdata ){
			//pal($name, $subdata);
			add_post_meta( $post_id, $name, $subdata );
		}
		//pre( get_post_meta( $post_id, $name ) );
		//pre( get_post_custom_values( $name, $post_id ) );
		//pre( get_post_custom( $post_id ) );
	}
	
	/**
	 * Usado para duplicate elements ou atualizar campos dependentes.
	 * 
	 * ATENÇÃO: o $context a ser usado é sempre o parâmetro enviado, e não $this->context
	 */
	function duplicate_group( $context ){
		if( $context['type'] != 'post_meta' )
			return $context;
		
		$item = load_element_config( $context );
		//pre( $item, 'meta_box' );return;
		
		if( !isset($item) or empty($item) )
			return $context;
		
		if( $item['type'] == 'duplicate_group' ){
			foreach( $item['group_itens'] as $element ){
				if( isset($_POST['args']['options']) )
					$element['options'] = array_merge( $element['options'], $_POST['args'] );
				
				// definir o index do elemento
				if( isset($_POST['args']['index']) )
					$element['index'] = $_POST['args']['index'];
				
				$data_value = get_post_meta( $context['post_id'], $element['name'], true );
				$element['options']['post_id'] = $context['post_id'];
				
				// armazenar o 'name' original em 'data-name'
				$element['attr']['dataset']['name'] = $element['name'];
				
				// modificar o 'id' e 'name' para o formato aninhado
				$element['attr']['id'] = "{$item['name']}_{$element['index']}_{$element['name']}";
				$element['name'] = "{$item['name']}[{$element['index']}][{$element['name']}]";
				
				// sinalizar que é um duplicate
				$element['in_duplicate_group'] = true;
				
				//pre($element);
				create_form_elements( $context, $element, false, $context['group'] );
			}
		}
		else{
			pal( 'REVISAR ESTE TRECHO: meta_boxes.php, duplicate_group()' );
			if( isset($item['options']) )
				$item['options'] = array_merge( $item['options'], $_POST['args'] );
			else
				$item['options'] = $_POST['args'];
			
			if( isset($_POST['args']['index']) )
				$item['index'] = $_POST['args']['index'];
			
			// armazenar o 'name' original em 'data-name'
			$item['attr']['dataset']['name'] = $item['name'];
			
			$item['options']['post_id'] = $context['post_id'];
			
			//$data_value = get_post_meta( $context['post_id'], $item['name'], true );
			create_form_elements( $context, $item, false, $context['group'] );
		}
	}
	
	/**
	 * Esse método é executado no hook do_action('load_element_config'), que pode ser requerido por qualquer function que fornceça o $context e o $name. Esse hook irá rodar em
	 * todas as intâncias de BorosMetaBoxes
	 * 
	 * 1 - procura dentro da config desta instância o grupo(neste caso a id do metabox) requerido
	 * 2 - dentro do grupo, o element com o mesmo name
	 * 3 - 
	 */
	function load_element_config( &$config, $context ){
		//pal('load_element_config');
		//pre($context, 'context');
		//pre($config, '&config');
		//sep();
		
		if( $context['type'] != 'post_meta' ){
			return $config;
		}
		
		/**
		 * HERE BE DRAGONS!!!
		 * Bug não identificado:
		 * Caso: site Multitude, duplicate elements, qualquer um.
		 * Descrição: Ao requisitar a duplicação do elemento, o presente método é rodado duas vezes, resultando em erro, onde não é encontrado o index $group em $config.
		 * Não importa quantos elements o grupo possui.
		 * Abordagens: Verificar se no add_filters se existe alguma duplicação nesse momento.
		 * Correção(temporária): verificar se o array $confi já está populado, e devolver em caso positivo. Isso impede o erro.
		 * 
		 */
		if( count($config) > 1 ){
			return $config;
		}
		
		$config = $this->meta_boxes;
		
		//pre($_POST, 'POST');
		//pre($context, 'context');
		//pre($config);
		//pre($config[$context['group']], 'group');
		
		if( isset($context['in_duplicate_group']) and $context['in_duplicate_group'] == true ){
			//pal(1);
			$element_config = $config[$context['group']]['itens'][$context['parent']]['group_itens'][$context['name']];
		}
		else{
			//pal(2);
			$element_config = $config[$context['group']]['itens'][$context['name']];
		}
		return $element_config;
	}
    
    /* Adicionar uma class no meta_box, opcional
     * É aplicado dinamicamente o filtro "postbox_classes_{$page}_{$id}", presente na function postbox_classes() em wp-admin/includes/post.php 
     */
    function box_classes( $classes ){
        $classes[] = "boros_meta_box {$this->css_class}";
        return $classes;
    }
}
