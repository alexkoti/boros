<?php
/**
 * ==================================================
 * FORMS PARA FRONTEND ==============================
 * ==================================================
 * 
 * 
 * @TODOS
 *  - fazer redirect para '$config->page_name' em caso de 'redirect_on_sucess' === true, ou redirect para o local deseja em caso de 'redirect_on_sucess' == string
 *  - 'field_type' está sendo usado? Aplicado atualmente nas configs
 *  - revisar o BorosValidation() no construct, pois ele acaba rodando mesmo sem o $_POST
 * 
 *  - melhorar tratamento de erros e mensagens
 *       - onde for usado 'redirect_on_sucess', revisar para usar um método para a filtragem da url caso exista o 'success_code'. Atualmente está se repetindo diversas vezes pelo código
 *       - modificar a config de mensagens, permitindo que seja buscado as mensagens corretas usando o relaod ou usando redirect, através do código das mensagens.
 *         Assim é possível configurar o k => v das mensagens, e a function de mensagens poderá usar tanto as variáveis de $_GET em um redirect ou puxar do config quando for reload.
 * <code>
 * $messages = array(
 *	'success' => array(
 *		'name' => 'mensagem',
 *		'value' => 'sucesso',
 *	),
 *	'error' => array(
 *		'name' => 'error_message',
 *		'value' => 'freaking_error',
 *	),
 *	// este erro só será disparado quando um form que necessita de usuário logado está aberto, e este desloga do site através de outra aba, e em seguida dá o submit no form.
 *	// normalmente esse form não seria exibido para o usuário deslogado, o que permite um submit não autorizado.
 *	'login_required' => array(
 *		'name' => 'alert',
 *		'value' => 'login_required',
 *	)
 *	// possibilitar adicionar novas chaves de erro
 * );
 * </code>
 *  - GRANDE MUDANÇA: modificar o processamento da maioria dos métodos para o action 'boros_frontend_form_output', quando realmente será utilizado. Dessa forma no __construct será apenas registrado de forma mais simples os diversos forms, sem grandes adições na memória.
 * - modificar a aplicação do 'action_append' para utilizar o add_query_arg() nativo do wordpress, deixando a string simples como opção
 * 
 * 
 * @BUGS
 *  - #fef1: não é possível aplicar um input com name 'post_type', ele interfere na requisição da página
 *  - #fef2: usar um campo com name = 'name' em um form de criar usuário, interfere nosubmit dos dados, enviando a página para um not-found; assim como no bug #fef1, talvez seja causado por interferir com a requisição padrão da página, atrapalhando 'name' ou slug do post atual
 * 
 * @WARNINGS
 *  - 'skip_save' só vale para 'metas'
 * 
 */

// fazer o output do formulário
function boros_frontend_form_output( $form_name ){
	do_action( 'boros_frontend_form_output', $form_name );
}

// retornar os dados do form pós-processado. Caso seja preciso usar um <form> personalizado.
// ATENÇÃO: não é possível utilizar essa function com o redirect ativado
function boros_frontend_form_data( $form_name ){
	return apply_filters( 'boros_frontend_form_data', $form_name );
}

/**
 * Modificar nome de arquivo para hash
 * 
 */ 
function boros_hash_filename( $filename ){
    // clone wp_unique_filename();
    $ext  = pathinfo( $filename, PATHINFO_EXTENSION );
    $name = pathinfo( $filename, PATHINFO_BASENAME );
    if( $ext ){
        $ext = '.' . $ext;
    }
    $hash_name = wp_hash( str_replace($ext, '', $filename) . time() );
    
    return "{$hash_name}{$ext}";
}

class BorosFrontendForm {
	// dados postados pelo usuário, unificando $_POST e $_FILES
	var $posted_data;
	
	var $form_name;
	
	//Configuração completa de elementos, como o dlecarado no construct, em array numérico multidimensional
	var $elements;
	
	// configuração de elementos em array associativo sem parents(os parents são indicados como atributos comuns)
	var $elements_plain;
	
	var $self_url;

    var $editing_user = false;
	
	var $config = array(
		'form_name'             => 'test',      // identificador para o hook
		'output_function'       => 'output',    // function de output
		'enctype'               => '',          // permitir uploads com <code>enctype="multipart/form-data"</code>
		'action_append'         => '',          // adicionar argumentos ao action
		
		// post/page/post_type
		'core_post_fields'      => array(),     // defaults apenas para o form corrente
		'post_type'             => false,       // post_type default false, pois pode ser 'user' ou 'taxonomy'
		'taxonomies'            => false,       // array com os termos fixos de taxonomia para aplicar, sem interferência do usuário
		'accepted_metas'        => array(),     // array de metas aceitos
		'accepted_taxonomies'   => array(),     // array de taxonomias aceitas para o usuário escolher
		
		// user
		'auto_login'            => false,       // logar automaticamente em caso de sucesso no registro de novo usuário
		'notification_email'    => false,       // avisar por email
		'numeric_username'      => false,       // criar um username numérico automaticamente, baseado no autoincrement da tabela
		
		'class'                 => '',          // class html para formatação
        'fieldset_parent_class' => 'row',
		'page_name'             => 'any',       // apenas aceitar caso is_page('page_name')
		'redirect_on_sucess'    => false,
		'login_required'        => 'É preciso estar logado para usar este formulário',
		'show_errors'           => true,
		'show_errors_index'     => false,
		'messages' => array(
			'success' => array(
				'message' => 'Post enviado com sucesso!',
				'name' => 'message',
				'value' => 'success',
			),
			'error' => array(
				'message' => 'Ocorreram algum(s) erro(s), por favor verifique.',
				'name' => 'message',
				'value' => 'error',
			),
			'login_required' => array(
				'message' => 'É preciso estar logado para usar este formulário',
				'name' => 'message',
				'value' => 'alert',
			),
		),
		'callbacks' => array(
			'success' => false,
			'error' => false,
		),
		'debug' => false,
	);
	
	/**
	 * $type         usado para o layout do form_element
	 * $object_type  pode ser 'post', 'user', 'login' e 'generic' -- talvez adicionar 'terms' e 'option'
	 * $object_id    0 = new post
	 * $form_name    nome do formulário usado, é útil para identificar corretamente o formulario postado quando existem mais de um formulário na página com names repetidos.
	 *               Este valor poderá ser usado pelo form_element, que poderá comparar o 'name' e o 'form_name'
	 */
	var $context = array(
		'type'        => 'frontend',
		'object_type' => 'post',
		'object_id'   => 0,
		'form_name'   => 'test',
	);
	
	/**
	 * User
	 * 
	 */
	var $user_id = 0;
	var $user;
    var $new_user;
	
	/**
	 * Post gravado, caso seja uma edição
	 * 
	 */
	var $post_id = 0;
	var $post;
	// post criado
	var $new_post_id;
	
	var $validation;
	var $errors = array();
	var $messages = array();
	var $persistent_messages = array();
	
	var $core_post_fields = array(
		'ID' => 0,
		'post_author' => 1,
		'post_date' => '',
		'post_date_gmt' => '',
		'post_content' => '',
		'post_title' => '',
		'post_excerpt' => '',
		'post_status' => 'draft',
		'comment_status' => '',
		'ping_status' => '',
		'post_password' => '',
		'post_name' => '',
		'to_ping' => '',
		'pinged' => '',
		'post_modified' => '',
		'post_modified_gmt' => '',
		'post_content_filtered' => '',
		'post_parent' => 0,
		'guid' => '',
		'menu_order' => 0,
		'post_type' => 'post',
		'post_mime_type' => '',
		'comment_count' => 0,
		'post_category' => '',
		'tags_input' => '',
		'tax_input' => '',
		'page_template' => '',
	);
	var $core_user_fields = array(
		'ID' => 0,
		'user_login' => '',
		'user_pass' => '',
		'user_pass_confirm' => '',
		'user_nicename' => '',
		'user_email' => '',
		'user_email_confirm' => '',
		'user_url' => '',
		'user_registered' => '',
		'user_activation_key' => '',
		'user_status' => '',
		'display_name' => '',
		'rememberme' => '',
	);
	
	// Dados core válidos. Serão usados no reload dos campos.
	var $valid_data = array();
	
	// Metas válidos para gravação. Serão usados no reload dos campos.
	var $valid_meta = array();
	
	// Taxonomy terms aceitos
	var $valid_taxonomy_terms = array();
	
	function __construct( $config, $context, $elements ){
		// caso seja admin ou ajax, interromper
		if( is_admin() and !defined('DOING_AJAX') ){
			return;
		}
		
		$this->self_url = self_url();
		$this->config = boros_parse_args( $this->config, $config );
		$this->context = boros_parse_args( $this->context, $context );
		$this->elements = $elements;
		$this->elements_plain();
		$this->form_name = $this->context['form_name'] = $this->config['form_name'];
		
		// buscar dados pre-existentes no caso de determinados tipos de form
		// post/page/post_type
		if( $this->context['object_type'] == 'post' ){
			if( $this->context['object_id'] != 0 ){
				$this->post_id = $this->context['object_id'];
				$this->post = get_post( $this->context['object_id'] );
			}
		}
		// user
		elseif( $this->context['object_type'] == 'user' ){
			if( $this->context['object_id'] != 0 ){
				$this->user_id = $this->context['object_id'];
				$this->user = get_userdata( $this->context['object_id'] );
			}
		}
		
		// processar os dados de formulário apenas se for postado o form certo, mas ainda assim garantindo o output do form a ser preenchido
		if( isset($_POST['form_name']) and $_POST['form_name'] == $config['form_name'] ){
			//pre($_POST);
			
			// definir o $this->posted_data
			$this->pre_process();
			
			// iniciar validador
			$this->validation = new BorosValidation( $context );
			
			// verificar requireds e adicionar errors
			$this->required( $this->posted_data ); //pre($this->posted_data, '$this->posted_data');
			
			// processar os dados do formulário conforme o contexto
			$this->process_data();
		}
		
		// permitir escolher vários tipos de output
		switch( $this->config['output_function'] ){
			// usar output comum
			case 'output':
				add_action( 'boros_frontend_form_output', array($this, 'output') );
				break;
			// usar output fomatado para bootstrap
			case 'bootstrap_output':
				add_action( 'boros_frontend_form_output', array($this, 'bootstrap_output') );
				break;
			// usar output fomatado para bootstrap 3
			case 'bootstrap3':
				add_action( 'boros_frontend_form_output', array($this, 'bootstrap3_output') );
				break;
			// usar output fomatado para bootstrap 4
			case 'bootstrap4':
				add_action( 'boros_frontend_form_output', array($this, 'bootstrap4_output') );
				break;
            // usar output fomatado para bootstrap 4
            case 'bootstrap5':
                add_action( 'boros_frontend_form_output', array($this, 'bootstrap5_output') );
                break;
            // usar output fomatado para bootstrap 4
            case 'custom':
                add_action( 'boros_frontend_form_output', array($this, 'custom_output') );
                break;
			// não usar nada;
			case false:
			   default:
				break;
		}
		
		// permitir o acesso dos dados ao frontend, através de filter
		add_filter( 'boros_frontend_form_data', array($this, 'data') );
	}
	
	/**
	 * Normalizar os dados e aplicar filtros default às informações puras.
	 * O WordPress adiciona os slashes no load
	 * 
	 * ATENÇÃO wp_unslash() é aplicado apenas em $_POST, pois em $_FILES altera o tmp_name
	 * 
	 */
	function pre_process(){
        $this->posted_data = array_merge( wp_unslash($_POST), $_FILES );
		$this->posted_data = apply_filters( 'boros_frontend_form_posted_data', $this->posted_data );
		$this->posted_data = apply_filters( "boros_frontend_form_posted_data_{$this->form_name}", $this->posted_data );
	}
	
	/**
	 * Retornar dados para o frontend, para usar em <form> personalizado
	 * 
	 */
	function data( $form_name ){
		if( $form_name == $this->form_name ){
			/**
			 * @todo A linha abaixo gera um erro no form de login do quotidiem, o $this->validation->data_errors é null.
			 * Como esta function é requisitada via add_filter, talvez essas variáveis não estejam disponíveis. Modificar a classe para
			 * deixá-las criadas
			 * 
			 */
			if( isset($this->validation->data_errors) ){
				$errors = $this->errors + $this->validation->data_errors;
			}
			else{
				$errors = $this->errors;
			}
			$data = array(
				'form_name' 		=> $this->form_name,
				'config' 			=> $this->config,
				'context' 			=> $this->context,
				'valid_data' 		=> $this->valid_data,
				'valid_meta' 		=> $this->valid_meta,
				'messages' 			=> $this->messages,
				'errors' 			=> $errors,
				'elements' 			=> $this->elements,
				'elements_plain' 	=> $this->elements_plain,
			);
			return $data;
		}
		else{
			return $form_name; // ATENÇÃO: É OBRIGATÓRIO MANTER O RETORNO DO $form_name, PARA QUE SEJA USADO PELOS HOOKS DE TODOS OS FORMS REGISTRADOS
		}
	}
	
	function process_data(){
		if( isset($this->posted_data['form_name']) and $this->posted_data['form_name'] == $this->form_name ){
			//pre($this->posted_data);
			
			if( $this->context['object_type'] == 'user' ){
				if( $this->context['object_id'] == 0 ){
					$this->create_user();
				}
				else{
					$this->edit_user();
				}
			}
			elseif( $this->context['object_type'] == 'post' ){
				if( $this->context['object_id'] == 0 ){
					$this->create_post();
				}
				else{
					$this->edit_post();
				}
			}
			elseif( $this->context['object_type'] == 'login' ){
				$this->login();
			}
			elseif( $this->context['object_type'] == 'generic' ){
				$this->generic_form();
			}
		}
	}
    
    /**
     * Definir o valide_data e valid_meta
     * 
     * @todo aplicar em outros métodos
     */
    function valid_infos(){
        $post_data = array();
        foreach( $this->core_post_fields as $field => $default ){
            if( isset($this->posted_data[$field]) ){
                $post_data[$field] = $this->posted_data[$field];
            }
        }
        
        $post_meta = array();
        // alertar que o modelo de 'accepted_metas' está antigo
        if( !is_assoc_array($this->config['accepted_metas']) ){
            wp_die('ALERTA: o modelo de accepted_metas está no formato antigo, corrigir mudando para array associativo com defaults <strong>create_post()</strong>');
        }
        foreach( $this->config['accepted_metas'] as $field => $default ){
            if( isset($this->posted_data[$field]) ){
                $post_meta[$field] = $this->posted_data[$field];
            }
            else{
                $post_meta[$field] = $default;
            }
        }
        //pre( $post_data, 'ACCEPTED POST_DATA' );
        //pre( $post_meta, 'ACCEPTED POST_META' );
        
        
        $this->valid_data = $this->validate( $this->context, $post_data );
        $this->valid_meta = $this->validate( $this->context, $post_meta );
    }
	
	/**
	 * Normalizar o array de elementos colocando index associativo
	 * Adicionar atributos identificando o tipo de elemento(core_type), se é core(colunas de tabela de post, user, etc), meta(post_meta, user_meta, etc) ou taxonomia(categoria, tag, custom taxonomy)
	 * 
	 */
	function elements_plain(){
		foreach( $this->elements as $index => $box ){
			$temp_itens = array();
			foreach( $box['itens'] as $item ){
				if( isset($item['name']) ){
					$item['parent'] = $box['id'];
					if( substr($item['name'], 0, 9) == 'tax_input' ){
						$item['core_type'] = 'tax_input';
					}
					elseif( array_key_exists($item['name'], $this->config['accepted_metas']) ){
						$item['core_type'] = 'meta';
					}
					else{
						$item['core_type'] = 'core';
					}
					$this->elements_plain[$item['name']] = $item;
					$temp_itens[] = $item;
				}
			}
			$this->elements[$index]['itens'] = $temp_itens;
		}
	}
	
	/**
	 * Manipular dados genéricos, que não envolvam objetos do WordPress. A principal ação é enviar os dados validados e pós-processados À função de callback.
	 * 
	 */
    function generic_form(){
        $this->valid_infos();
        //pre($_POST, '_POST');
        //pre($this->valid_data, 'valid_data');
        //pre($this->valid_meta, 'valid_meta');
        //pre($this->validation->data_errors);
        //die();
        
        if( empty( $this->validation->data_errors ) ){
            $error = false;
            
            // acionar callbacks: elements
            $this->do_callbacks( $this->valid_data );
            $this->do_callbacks( $this->valid_meta );
            
            //pre($this->valid_data, 'valid_data');
            //pre($this->valid_meta, 'valid_meta');
            
            // acionar callbacks: form->config
            $error = $this->form_callback( $this->config['callbacks']['success'] );
            
            // deprecated: typo error ('sucess')
            if( isset($this->config['callbacks']['sucess']) ){
                $error = $this->form_callback( $this->config['callbacks']['sucess'] );
            }
            
            if( $error == false ){
                // registrar mensagem de sucesso
                $this->messages['success'] = $this->config['messages']['success'];
                
                // redirect
                if( $this->config['redirect_on_sucess'] !== false ){
                    wp_redirect( $this->get_redirect_url('success') );
                    exit();
                }
            } else {
                // acionar callbacks: form->config
                $this->form_callback( $this->config['callbacks']['error'] );
            }
        }
        else{
            // acionar callbacks: form->config
            $this->form_callback( $this->config['callbacks']['error'] );
            $this->errors = array_merge( $this->errors, $this->validation->data_errors );
        }
    }
	
	// NÃO USADO NO MOMENTO!!!
	// mapear mensagens em versão numerica para ser usado após redirect
	function index_messages(){
		$error_index = array();
		$i = 1;
		foreach( $this->elements as $box ){
			$itens = $box['itens'];
			foreach( $itens as $item ){
				if( isset($item['validate']) ){
					$u = 1;
					foreach( $item['validate'] as $validation => $args ){
						if( isset($args['message']) ){
							$error_index[$i][$u] = $args['message'];
							$u++;
						}
					}
					$i++;
				}
			}
		}
		$message_keys = array();
		$message_keys[1] = $this->config['messages']['success'];
		$message_keys[2] = $error_index ;
		//pre($message_keys);
		//pre($error_index);
	}
	
	function user_info(){
		if( is_user_logged_in() ){
			global $current_user;
			wp_get_current_user();
			//pre($current_user->data, '$current_user');
		}
	}
	
	function show_errors(){
		//pre($this->errors);
		//pre($this->validation->data_errors);
		if( !empty( $this->errors ) ){
			$output = "<div class='form_errors'><p class='erro_title error'><strong>{$this->config['messages']['error']}</strong></p>";
			foreach( $this->errors as $error ){
				// erro em formato WP_Error
				if( is_wp_error($error) ){
					foreach( $error->errors as $code => $messages ){
						$output .= "<div class='alert-message error {$code}' rel='{$code} is_wp_error'>";
						foreach( $messages as $message ){
							// filtrar caso seja a mensagem de login
							$pattern = '/(para o usuário <strong>[0-9].<\/strong>)/';
							$message = preg_replace($pattern, '', $message);
							$output .= "<p class='form_error'>{$message}</p>";
						}
						$output .= "</div>";
					}
				}
				else{
					foreach( $error as $message ){
						$output .= "<div class='alert-message error {$message['name']}' rel='{$message['name']}'>";
						$output .= "<p class='form_error'>{$message['message']}</p>";
						$output .= "</div>";
					}
				}
			}
			$output .= '</div>';
			return $output;
		}
	}
	
	function show_messages(){
		echo '<div class="form_messages">';
		// mensagens onload, usando variáveis da classe
		foreach( $this->messages as $code => $message ){
			// novo modelo de mensagens de erro
			if( is_array( $message ) ){
				$message = $message['message'];
			}
			else{
				$msg = $message;
			}
			echo "<div class='alert-message message {$code} alert alert-success' rel='{$code}'>";
			echo $message;
			echo "</div>";
		}
		
		// static message, após redirect, mas sem os errors
		if( empty($this->errors) ){
			// @deprecated - versão antiga, que so possuia 'message=1'
			if( isset( $_GET['message'] ) and $_GET['message'] == 1 ){
				echo "<div class='alert-message message sucess alert alert-success'>";
				echo "{$this->config['messages']['success']}";
				echo "</div>";
			}
			// pegar as mensagens no novo formato
			if( isset($_GET) ){
				foreach( $_GET as $k => $v ){
					foreach( $this->config['messages'] as $code => $message ){
						if( isset($message['name']) and $k == $message['name'] and $v == $message['value'] ){
							echo "<div class='alert-message message {$code} alert alert-{$code}'>";
							echo $message['message'];
							echo "</div>";
						}
					}
				}
			}
		}
		echo '</div>';
	}
	
	/**
	 * O plugin wp-email-login interfere nesse método, pois em wp_signon() é afetado pelo filtro 'authenticate', portanto, caso o plugin esteja ativo, não é necessário 
	 * realizar outras verificações aqui para permitir o login pelo email.
	 * 
	 * @todo melhorar a filtro das mensagens> Atualmente as strings se repetem nos arquivos boros/functions/user.php e página de opções section_users.php do plugin child
	 */
	function login(){
        //pre($this->posted_data);
        // é preciso para pode recarregar os inputs no reload
        $this->valid_data = $this->validate( $this->context, $this->posted_data );
        
        /**
         * Retornar em caso de erro na pré-validação, ex recaptcha
         * 
         */
        $this->errors = array_merge( $this->errors, $this->validation->data_errors );
        if( !empty( $this->validation->data_errors ) || !empty( $this->errors ) ){
            return;
        }
        //pre($this->errors);
        //die();
		
		$creds = array();
		$creds['user_login']    = $this->posted_data['user_login'];
		$creds['user_password'] = $this->posted_data['user_pass'];
        $creds['remember']      = isset($this->posted_data['rememberme']) ? true : false;
		
		$user = wp_signon( $creds, false );
		
		// erro no login: um só objeto de erro já define as mensagens de erro de ambos os campos do login( username/email e senha )
		if( is_wp_error($user) ){
			foreach( $user->errors as $code => $message ){
				//pre( $code);
				//pre( $message);
				
				// mensagem original
				$msg = $message[0];
				//pre($msg, 'erro original');
				
				// mensagens padrão do WordPress
				$default_messages = array(
					'login_message_user_login_empty' => '<strong>ERRO</strong>: O campo do nome de usuário está vazio.',
					'login_message_user_pass_empty'  => '<strong>ERRO</strong>: O campo da senha está vazio.',
					'login_message_invalid_user'     => '<strong>ERRO</strong>: Nome de usuário inválido.',
					'login_message_invalid_pass'     => '<strong>Erro:</strong> a senha fornecida para o e-mail',
					'login_message_user_default'     => '<strong>ERRO</strong>: Sua conta precisa ser aprovada antes poder fazer o login no site',
					'login_message_user_disapproved' => '<strong>ERRO</strong>: O seu registro não foi aceito!',
				);
				
				/**
				 * Monstar as mensagens customizadas. Caso não tenham sido definidas via admin, utilizar
				 * a lista padrão definida no código.
				 * 
				 */
				$custom_messages = array(
					'login_message_user_login_empty' => get_option('login_message_user_login_empty'),
					'login_message_user_pass_empty'  => get_option('login_message_user_pass_empty'),
					'login_message_invalid_user'     => get_option('login_message_invalid_user'),
					'login_message_invalid_pass'     => get_option('login_message_invalid_pass'),
					'login_message_user_default'     => get_option('login_message_user_default'),
					'login_message_user_disapproved' => get_option('login_message_user_disapproved'),
				);
				$custom_default_messages = array(
					'login_message_user_login_empty' => '<strong>ERRO</strong>: Email vazio!',
					'login_message_user_pass_empty'  => '<strong>ERRO</strong>: Senha vazia!',
					'login_message_invalid_user'     => '<strong>ERRO</strong>: Não existe uma conta com este email',
					'login_message_invalid_pass'     => '<strong>ERRO</strong>: A senha que você forneceu está incorreta.',
					'login_message_user_default'     => '<strong>ERRO</strong>: Seu registro foi recebido e seu cadastro está em aprovação.',
					'login_message_user_disapproved' => '<strong>ERRO</strong>: O seu registro não foi aceito!',
				);
				$custom_messages = apply_filters( 'boros_login_messages', boros_parse_args($custom_messages, $custom_default_messages), $creds );
				
				// filtrar as mensagens de erro padrão do wp pelos customizados
                // é feita uma busca pelo conteúdo da string
				foreach( $default_messages as $msg_k => $msg_v ){
					$pos = stripos( $msg, $msg_v );
					// trocar pelo custom message e interomper o loop
					if( $pos !== false and !empty($custom_messages[$msg_k]) ){
						$msg = $custom_messages[$msg_k];
						// adicionar o link "Esqueceu sua senha?" ao final da mensagem
						if( $msg_k == 'login_message_invalid_user' or $msg_k == 'login_message_invalid_pass' ){
							$link = wp_lostpassword_url();
							$msg .= " <a href='{$link}'>Esqueceu sua senha?</a>";
						}
						break;
					}
				}
				//pre($msg, 'erro filtrado');
				
				// Adicionar a mensagem de erro conforme o tipo e associando ao campo correto no form
				switch( $code ){
					case 'empty_password':
						$error = array(
							'name'    => $code,
							'message' => $msg,
							'type'    => 'error',
						);
						$this->errors['user_pass']['empty_password'] = $error;
						break;
                        
					case 'incorrect_password':
						$error = array(
							'name'    => $code,
							'message' => $msg,
							'type'    => 'error',
						);
						$this->errors['user_pass']['incorrect_password'] = $error;
						break;
					
					case 'invalid_username':
						$error = array(
							'name'    => $code,
							'message' => $msg,
							'type'    => 'error',
						);
						$this->errors['user_login']['invalid_username'] = $error;
						break;
					
					case 'verify_approved_user_default':
						$error = array(
							'name'    => $code,
							'message' => $msg,
							'type'    => 'error',
						);
						$this->errors['user_login']['verify_approved_user_default'] = $error;
						break;
					
					case 'verify_approved_user_disapproved':
						$error = array(
							'name'    => $code,
							'message' => $msg,
							'type'    => 'error',
						);
						$this->errors['user_login']['verify_approved_user_disapproved'] = $error;
						break;
					
					default:
						$error = array(
							'name'    => $code,
							'message' => $msg,
							'type'    => 'error',
						);
						$this->errors['user_login'][$code] = $error;
						break;
				}
			}
			$this->errors = array_merge( $this->errors, $this->validation->data_errors );
		}
		else{

            /**
             * Logar o usuário globalmente no WordPress, permitindo acessar o admin
             * 
             */
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, true);

			// caso tenha logado com sucesso, verificar o redirect ou usar referer(retornar à mesma página que enviou os dados)
			if( $this->config['redirect_on_sucess'] == false ){
				$url = self_url();
			}
			else{
				$url = $this->get_redirect_url('success');
			}
			wp_redirect( $url );
			exit();
		}
	}
	
	/**
	 * Criar usuário
	 * 
	 * @todo adicionar 'save_as_user_meta'
     * @todo trocar wp_create_user() por wp_insert_user(), para que seja possíve definri outras informações como role e user_metas
	 */
	function create_user(){
		//pre($this->posted_data, 'posted_data');
		$user_data = array();
		foreach( $this->core_user_fields as $field => $default ){
			if( isset($this->posted_data[$field]) ){
				$user_data[$field] = $this->posted_data[$field];
			}
			else{
				$user_data[$field] = $default;
			}
		}
		$user_meta = array();
		// alertar que o modelo de 'accepted_metas' está antigo
		if( !is_assoc_array($this->config['accepted_metas']) ){
			wp_die('ALERTA: o modelo de accepted_metas está no formato antigo, corrigir mudando para array associativo com defaults <strong>create_user()</strong>');
		}
		foreach( $this->config['accepted_metas'] as $field => $default ){
			if( isset($this->posted_data[$field]) ){
				$user_meta[$field] = $this->posted_data[$field];
			}
			else{
				$user_meta[$field] = $default;
			}
		}
		
		$this->valid_data = $this->validate( $this->context, $user_data );
		$this->valid_meta = $this->validate( $this->context, $user_meta );
		//pre( $this->valid_data, 'VALID DATA' );
		//pre( $this->valid_meta, 'VALID META' );
        
        /**
         * Verificar segundo email
         * Existem dois cenários: com ou sem o campo de confirmação de email
         * 
         * 1) Com campo de confirmação: comparar os valores dos dois campos
         * 2) Sem campo de confirmação: não fazer nenhuma verificação
         * 
         */
        if( isset($this->valid_data['user_email_confirm']) and $this->valid_data['user_email'] != $this->valid_data['user_email_confirm'] ){
            $this->errors[] = new WP_Error( 'email_not_match', 'Os e-mails não são iguais' );
            $error = array(
                'name' => 'user_email_confirm',
                'message' => 'Os e-mails não são iguais',
                'type' => 'error'
            );
            $this->validation->data_errors['user_email_confirm']['email_not_match'] = $error;
        }
        
		
		/**
		 * Decidir qual será o user name
		 * 
		 */
		// forçar username numérico; irá ignorar o campo 'user_login' caso seja enviado
		if( $this->config['numeric_username'] == true ){
			//pal(1);
			$this->valid_data['user_login'] = $this->create_numeric_username();
		}
		// caso não tenha sido declardo o 'user_login', usar o campo email
		elseif( (!isset($this->valid_data['user_login']) or empty($this->valid_data['user_login'])) ){
			//pal(2);
			// usar email
			if( isset($this->valid_data['user_email']) ){
				//pal(21);
				$this->valid_data['user_login'] = $this->valid_data['user_email'];
			}
			// fallback para numeric username
			else{
				//pal(22);
				$this->valid_data['user_login'] = $this->create_numeric_username();
			}
		}
		
		/**
		 * Verificar password
		 * Existem dois cenários: com ou sem o campo de confirmação de senha
		 * 
		 * 1) Com campo de confirmação: comparar os valores dos dois campos
		 * 2) Sem campo de confirmação: não fazer nenhuma verificação
		 */
		if( isset($this->valid_data['user_pass_confirm']) and $this->valid_data['user_pass'] != $this->valid_data['user_pass_confirm'] ){
			$this->errors[] = new WP_Error( 'password_not_match', "As senhas não são iguais \n" );
			$error = array(
				'name' => 'user_pass_confirm',
				'message' => 'As senhas não são iguais',
				'type' => 'error'
			);
			$this->validation->data_errors['user_pass_confirm']['password_not_match'] = $error;
		}
        
		/**
		 * Verificar senha vazia
		 * 
		 */
		if( empty($this->valid_data['user_pass']) ){
			$this->errors[] = new WP_Error( 'password_empty', "Você não pode deixar a senha vazia \n" );
			$error = array(
				'name' => 'user_pass_empty',
				'message' => 'Você não pode deixar a senha vazia',
				'type' => 'error'
			);
			$this->validation->data_errors['user_pass']['password_empty'] = $error;
		}
		
        //pre($_POST);
        //pre( $user_data, 'USER_DATA' );
        //pre( $user_meta, 'USER_META' );
        //pre( $this->valid_data, 'VALID DATA' );
        //pre( $this->valid_meta, 'VALID META' );
        //pre( $this->validation->data_errors, 'VALID ERRORS' );
        //die('teste de criação de usuário');
		
		/**
		 * Filtro de pós-processamento
		 * Permitir a análise dos dados do formulário antes do envio para a ação do formulário, possibilitando adicionar 
		 * ou remover erros conforme a necessidade. Algumas ações só são possíveis após ter todos os dados do form devidamente
		 * processados.
		 * 
		 * Exemplo de uso: um formulário não exige por padrão o telefone ou celular, porém é necessário que pelo menos um deles
		 * seja preenchido, nesse momento será possível através do filtro verificar a presença deles e adicionar erros personalizados.
		 * 
		 */
		$this->validation->data_errors = apply_filters('boros_frontend_form_validation_erros', $this->validation->data_errors, $this->form_name, $this->valid_data, $this->valid_meta, $this->elements, $this->context);
		$this->errors = apply_filters('boros_frontend_form_erros', $this->errors, $this->form_name, $this->valid_data, $this->valid_meta, $this->elements, $this->context);
		
		// verificar se existe algum erro de validação ou erros gerais(WP_Error, password_not_match)
		if( empty( $this->validation->data_errors ) and empty( $this->errors ) ){
			// verificar se username já existe
			//pal('SEM ERROS! username_exists?');
			$user_id = username_exists( sanitize_user($this->valid_data['user_login']) );
			
			// user não existe, registrar novo
			if( !$user_id ){
				//pal('tentar registrar');
				// tentar registrar
				$user_id = wp_create_user( $this->valid_data['user_login'], $this->valid_data['user_pass'], $this->valid_data['user_email'] );
				//pal($user_id . ' usuário criado');
				
				// em caso de erro, adicionar log de erros
				if( is_wp_error( $user_id ) ){
					//pal('$user_id is_wp_error');
                    //$this->errors[] = $user_id;
                    $this->validation->data_errors['user_login']['user_already_exists'] = array(
                        'name'    => 'user_login',
                        'message' => $user_id->get_error_message(),
                        'type'    => 'error',
                    );
				}
				// usuário criado!!! adicionar metas e mensagem
				else{
					// atualizar o user_id ao objeto, para que possa ser usado pelos callbacks
					$this->user_id = $user_id;
					
					// carregar novo usuário
					$this->new_user = get_user_by('id', $user_id);
					
					foreach( $this->valid_meta as $meta => $value ){
						update_user_meta( $user_id, $meta, $value );
					}
					
					// atualizar o display_name na tabela de users
					if( isset($this->valid_meta['first_name']) and isset($this->valid_meta['last_name']) ){
						wp_update_user( array ('ID' => $user_id, 'display_name' => "{$this->valid_meta['first_name']} {$this->valid_meta['last_name']}") );
						update_user_meta( $user_id, 'full_name', "{$this->valid_meta['first_name']} {$this->valid_meta['last_name']}" );
					}
					elseif( isset($this->valid_meta['full_name']) ){
						wp_update_user( array ('ID' => $user_id, 'display_name' => $this->valid_meta['full_name']) );
					}
					
					$this->messages['success'] = 'Usuário criado com sucesso!';
					$persistent_messages[$this->form_name]['create_user'] = 'Usuário criado com sucesso!';
					
					/**
					 * "HERE BE DRAGONS"
					 * ATENÇÃO!!! >>> CALLBACKS QUE NECESSITEM DE USUÁRIO AUTENTICADO NÃO IRÃO FUNCIONAR!!!
					 * Acionar form callbacks simples, que não exigem usuário logado
					 * CUIDADO!!! Caso o callback necessite do usuário logado, o callback não funcionará corretamente!!!
					 * 
					 */
					 // @todo remover após certificar que não existem jobs que utilizem esse callback escrito errado
					$this->form_callback( $this->config['callbacks']['success'] );
					// deprecated: typo error ('sucess')
					if( isset($this->config['callbacks']['sucess']) ){
						$this->form_callback( $this->config['callbacks']['sucess'] );
					}
                    
                    /**
                     * Notificar por email
                     * 
                     */
                    //pal('user_created');
                    //update_user_option( $user_id, 'default_password_nag', true, true ); //Set up the Password change nag.
                    if( $this->config['notification_email'] == true ){
                        $this->new_user_notification( $user_id, $this->valid_data, $this->valid_meta );
                    }
					
					/**
					 * Autologin no novo usuário
					 * No autologin o redirect é necessário pois nesse momento os cookies e validações já ocorreram, e é preciso acessar uma 
					 * nova página para que o 'is_user_logged_in()' retorne true.
					 * 
					 */
					if( isset($this->config['auto_login']) and $this->config['auto_login'] == true ){
						//pal('auto_login');
						$creds = array();
						$creds['user_login'] = $this->valid_data['user_login'];
						$creds['user_password'] = $this->valid_data['user_pass'];
						$creds['remember'] = true;
						
						// login
						$user = wp_signon( $creds, false );
						
						// redirect
						if( is_wp_error($user) ){
							//pal('erro de autenticação');
							$this->errors[] = $user;
						}
						else{
							wp_redirect( $this->get_redirect_url('success') );
							exit();
						}
					}
					
					/**
					 * Redirect simples sem autologin
					 * 
					 */
					if( $this->config['redirect_on_sucess'] != false ){
						wp_redirect( $this->get_redirect_url('success') );
						exit();
					}
				}
                $this->errors = array_merge( $this->errors, $this->validation->data_errors );
			}
			// user já existe, adicionar erro
			else{
				$this->errors[] = new WP_Error( 'user_already_exists', 'Nome de usuário já existe, escolha outro.' );
			}
		}
		else{
			$this->errors = array_merge( $this->errors, $this->validation->data_errors );
		}
	}
	
	function edit_user(){
		// Não pode usar o current user, pois pode ser um admin editando um usuário comum
		$user = $this->editing_user = get_user_by( 'id', $this->context['object_id'] );
		
		/**
		 * Separar apenas os dados do $_POST permitidos dentro do modelo configurado.
		 * 
		 */
		$user_data = array();
		foreach( $this->core_user_fields as $field => $default ){
			if( isset($this->posted_data[$field]) )
				$user_data[$field] = $this->posted_data[$field];
			else
				$user_data[$field] = $default;
		}
		$user_meta = array();
		// alertar que o modelo de 'accepted_metas' está antigo
		if( !is_assoc_array($this->config['accepted_metas']) ){
			wp_die('ALERTA: o modelo de accepted_metas está no formato antigo, corrigir mudando para array associativo com defaults <strong>edit_user()</strong>');
		}
		foreach( $this->config['accepted_metas'] as $field => $default ){
			if( isset($this->posted_data[$field]) )
				$user_meta[$field] = $this->posted_data[$field];
			else
				$user_meta[$field] = $default;
		}
		//pre($this->posted_data);
		//pre( $user_data, 'USER_DATA' );
		//pre( $user_meta, 'USER_META' );
		//pre( $user_meta, 'USER_META' );
		//pre( $this->errors, 'ERRORS' );
		
		$this->valid_data = $this->validate( $this->context, $user_data );
		$this->valid_meta = $this->validate( $this->context, $user_meta );
		//pre($this->valid_data, 'VALID DATA');
		//pre($this->valid_meta, 'VALID META');
		//pre( $this->errors, 'ERRORS' );
		//return;
		
		// verificar password, caso tenha sido enviada
		if( isset($this->valid_data['user_pass']) ){
			if( $this->valid_data['user_pass'] != $this->valid_data['user_pass_confirm'] ){
				$error = array(
					'name' => 'user_pass_confirm',
					'message' => 'As senhas não são iguais',
					'type' => 'error',
				);
				$this->errors['user_pass']['password_not_match'] = $error;
				$this->errors['user_pass_confirm']['password_not_match'] = $error;
			}
			// remover pass do array de gravação
			if( empty($this->valid_data['user_pass']) ){
				unset($this->valid_data['user_pass']);
			}
		}
		
		// verificar email
		if( isset($this->valid_data['user_email']) ){
			$email_exists = email_exists( $this->valid_data['user_email'] );
			if( $email_exists == true and $email_exists != $user->ID ){
				$this->errors[] = new WP_Error( 'email_already_exists', 'Este email já está sendo usado por outra pessoa' );
			}
            
            
		}
        
        /**
         * Filtrar erros e permitir adicionar/remover
         * 
         */
		$this->validation->data_errors = apply_filters('boros_frontend_form_validation_erros', $this->validation->data_errors, $this->form_name, $this->valid_data, $this->valid_meta, $this->elements, $this->context);
		
		//pre($this->validation->data_errors);
		//pre($this->errors);
		/**
		 * Verificar se usuário tem permissão para editar o user
		 * ID do usuário corrent = usuário pedido > usuário editando o próprio profile : OK!
		 * Usuário com caps 'edit_users' : OK!
		 * 
		 */
		if( $this->valid_data['ID'] == $user->ID or current_user_can('edit_users') ){
			// verificar se existe algum erro de validação ou erros gerais(WP_Error, password_not_match)
			if( empty( $this->validation->data_errors ) and empty( $this->errors ) ){
				// dados básicos presentes em $core_user_fields
				$user_result = wp_update_user( $this->valid_data );
				
				// pode acontecer algum erro, guardar
				if( is_wp_error($user_result) ){
					$this->errors[] = $user_result;
				}
				// tudo ocorreu bem salvar os user metas
				else{
					foreach( $this->valid_meta as $meta => $value ){
                        
                        $config = array_search_kv( 'name', $meta, $this->elements );
                        if( isset($config['skip_save']) and $config['skip_save'] == true ){
                            continue;
                        }

						// verificar se o meta_value é false, e remover
						if( $value === false or empty($value) )
							delete_user_meta( $user->ID, $meta );
						else
							update_user_meta( $user->ID, $meta, $value );
					}
					
					$this->messages['success'] = $this->config['messages']['success'];
					$persistent_messages[$this->form_name]['create_user'] = 'Usuário criado com sucesso!';
					
					// atualizar o display_name na tabela de users
					if( isset($this->valid_meta['first_name']) and isset($this->valid_meta['last_name']) ){
						wp_update_user( array ('ID' => $user->ID, 'display_name' => "{$this->valid_meta['first_name']} {$this->valid_meta['last_name']}") );
					}
					elseif( isset($this->valid_meta['full_name']) ){
						wp_update_user( array ('ID' => $user->ID, 'display_name' => $this->valid_meta['full_name']) );
					}
					
					/**
					 * CALLBACKS
					 * 
					 */
					//pre($this->valid_data);
					//pre($this->valid_meta);
					//pre( $this->errors, 'ERRORS' );
					//$this->form_callback( $this->config['callbacks']['success'] );
					// deprecated: typo error ('sucess')
					if( isset($this->config['callbacks']['sucess']) ){
						$this->form_callback( $this->config['callbacks']['sucess'] );
					}
					if( empty( $this->validation->data_errors ) ){
						// acionar callbacks: elements
						$this->do_callbacks( $this->valid_data );
						$this->do_callbacks( $this->valid_meta );
						
						// acionar callbacks: form->config
						$this->form_callback( $this->config['callbacks']['success'] );
						if( isset($this->config['callbacks']['sucess']) ){
							$this->form_callback( $this->config['callbacks']['sucess'] );
						}
						
						// registrar mensagem de sucesso
						$this->messages['success'] = $this->config['messages']['success'];
						
						// redirect
						if( $this->config['redirect_on_sucess'] != false ){
							wp_redirect( $this->get_redirect_url('success') );
							exit();
						}
					}
					else{
						// acionar callbacks: form->config
						$this->form_callback( $this->config['callbacks']['error'] );
						$this->errors = array_merge( $this->errors, $this->validation->data_errors );
					}
					
					// @todo é possível que este bloco nunca seja usado, rever comparando e testando com o bloco anterior com o 'redirect_on_sucess'
					if( $this->config['redirect_on_sucess'] != false ){
						wp_redirect( $this->get_redirect_url('success') );
						exit();
					}
				}
			}
			else{
                $this->form_callback( $this->config['callbacks']['error'] );
				$this->errors = array_merge( $this->errors, $this->validation->data_errors );
			}
		}
		else{
			die('HAXXOR!!!11!!1');
		}
	}
	
	function new_user_notification( $user_id, $user_data, $user_meta ){
		do_action( 'BFF_new_user_notification_pre', $user_id, $user_data, $user_meta );
		
		$user = new WP_User($user_id);
		$user_login = stripslashes($user->user_login);
		$user_email = stripslashes($user->user_email);
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		
		/**
		 * Montar os $headers conforme os destinatários adicionais
		 * O remetente dos emails é definido nos hooks 'wp_mail_from' e 'wp_mail_from_name' em boros/functions/email.php
		 * 
		 */
		$headers = array();
		$to  = get_option( 'email_from' );
		if( !empty($to) )
			$to = get_option('admin_email'); // fallback caso o 'email_from" não esteja configurado.
		$cc  = get_option( 'email_from_cc' );
		$bcc = get_option( 'email_from_bcc' );
		// Adicionar CC
		if( !empty($cc) ){
			$emails = explode(',', $cc);
			foreach( $emails as $email ){
				if( !empty($email) )
					$headers[] = "Cc: {$email}";
			}
		}
		// Adicionar BCC
		if( !empty($bcc) ){
			$emails = explode(',', $bcc);
			foreach( $emails as $email ){
				if( !empty($email) )
					$headers[] = "Bcc: {$email}";
			}
		}
		
		// Avisar ADMIN
		$admin_message = "  <p>Novo usuário registrado no site:</p>
							<p>Nome de usuário: {$user_login}</p>
							<p>Email: {$user_email}</p>";
		$admin_message       = apply_filters( 'BFF_new_user_notification_admin', $admin_message, $user, $user_data, $user_meta );
        $admin_message_title = sprintf(__('[%s] New User Registration'), $blogname);
        $admin_message_title = apply_filters('BFF_new_user_notification_admin_title', $admin_message_title, $user, $user_data, $user_meta);
		wp_mail( get_option('admin_email'), $admin_message_title, $admin_message, $headers );
		
		// Avisar USER
		$login_url = home_url('/login/');
		$user_message = "<p>Nome de usuário: <code>{$user_login}</code></p>
						 <p>Senha: <code>{$user_data['user_pass']}</code></p>
						 <p>Endereço para login: <code>{$login_url}</code></p>";
		$user_message = apply_filters( 'the_content', $user_message );
		// aplicar filtro final, como por exemplo holder em HTML
		$user_message = apply_filters( 'BFF_new_user_notification_text', $user_message, $user_login, $user_data['user_pass'], $login_url, $user_data, $user_meta );
        // novo hook com mais informações
		$user_message = apply_filters( 'BFF_new_user_notification_message', $user_message, $login_url, $user, $user_data, $user_meta );
		
		$user_title = sprintf(__('[%s] Your username and password'), $blogname);
		$user_title = apply_filters( 'BFF_new_user_notification_title', $user_title, $user, $user_data, $user_meta );
		
		//pre($user_title);
		//pre($user_message);
		wp_mail($user_email, $user_title, $user_message);
		
		do_action( 'BFF_new_user_notification_pos', $user_id, $user_data, $user_meta, $user_title, $user_message );
	}
	
	function create_post(){
		//pre($this->posted_data, 'RAW $this->posted_data');
		
		$post_data = array();
		foreach( $this->core_post_fields as $field => $default ){
			if( isset($this->posted_data[$field]) ){
				$post_data[$field] = $this->posted_data[$field];
			}
		}
		
		$post_meta = array();
		// alertar que o modelo de 'accepted_metas' está antigo
		if( !is_assoc_array($this->config['accepted_metas']) ){
			wp_die('ALERTA: o modelo de accepted_metas está no formato antigo, corrigir mudando para array associativo com defaults <strong>create_post()</strong>');
		}
		foreach( $this->config['accepted_metas'] as $field => $default ){
			if( isset($this->posted_data[$field]) ){
				$post_meta[$field] = $this->posted_data[$field];
			}
			else{
				$post_meta[$field] = $default;
			}
		}
        
        //pre( $post_data, 'ACCEPTED POST_DATA' );
        //pre( $post_meta, 'ACCEPTED POST_META' );
        //sep();
        
        $this->valid_data = $this->validate( $this->context, $post_data );
        $this->valid_meta = $this->validate( $this->context, $post_meta );
        
        //pre($this->valid_data, 'VALID DATA');
        //pre($this->valid_meta, 'VALID META');
        //sep();
		
		// mesclar dados 'core_post_fields' da config
		$this->valid_data = boros_parse_args( $this->config['core_post_fields'], $this->valid_data );
		// mesclar dados 'core_post_fields' da class
		$this->valid_data = boros_parse_args( $this->core_post_fields, $this->valid_data );
		
		/**
		 * Aplicar termos de taxonomia setados pelo usuário.
		 * 
		 * @todo Caso seja configurado um array de termos, apenas esses termos serão válidos para que o usuário aplique.
		 */
		if( !empty( $this->config['accepted_taxonomies'] ) ){
			$this->validate_taxonomy_terms($post_data);
		}
		//pre($this->valid_taxonomy_terms, 'valid_taxonomy_terms');
		if( !empty($this->valid_taxonomy_terms) ){
			$this->valid_data['tax_input'] = $this->valid_taxonomy_terms;
		}
		
		// remover os vazios de core_data, para que o próprio WordPress processe corretamente os valores.
		$this->valid_data = array_non_empty_items( $this->valid_data );
		
		// mesclar dados 'accepted_metas' da config
		$this->valid_meta = boros_parse_args( $this->config['accepted_metas'], $this->valid_meta );
		
		//pre($this->valid_data, 'VALID DATA');
		//pre($this->valid_meta, 'VALID META');
		//pre($this->validation->data_errors, 'VALIDATION $this->validation->data_errors');
		//die('CREATE POST');
		
		// adicionar filtro para pós validação, por exemplo para verificar campos dependentes de respostas de outros campos
		$this->valid_data = apply_filters( 'boros_frontend_form_pos_validation_data', $this->valid_data, $this->valid_meta, $this->validation->data_errors, $this->form_name );
		$this->valid_meta = apply_filters( 'boros_frontend_form_pos_validation_meta', $this->valid_meta, $this->valid_data, $this->validation->data_errors, $this->form_name );
		$this->validation->data_errors = apply_filters( 'boros_frontend_form_pos_validation_errors', $this->validation->data_errors, $this->valid_data, $this->valid_meta, $this->form_name );
		
		//pre($this->validation->data_errors, 'ERRORS'); die();
		
		// verificar errors, caso negativo, adicionar post
		if( empty( $this->validation->data_errors ) ){
			// filtrar título do post com informações extras postadas
			$this->valid_data['post_title'] =  $this->template_tags( $this->valid_data['post_title'], $this->valid_data );
			$this->valid_data['post_title'] =  $this->template_tags( $this->valid_data['post_title'], $this->valid_meta );
            
			$this->valid_data['post_content'] =  $this->template_tags( $this->valid_data['post_content'], $this->valid_data );
			$this->valid_data['post_content'] =  $this->template_tags( $this->valid_data['post_content'], $this->valid_meta );
			
			// mesclar dados defaults_config
			//$insert_data = boros_parse_args( $this->config['core_post_fields'], $this->valid_data );
			// mesclar dados core_config
			//$insert_data = boros_parse_args( $this->core_post_fields, $insert_data );
			//$new_post_id = wp_insert_post( $insert_data, 1 ); // segundo argumento habilita WP_Error
			$this->new_post_id = wp_insert_post( $this->valid_data, 1 ); // segundo argumento habilita WP_Error
			
			if( is_wp_error($this->new_post_id) ){
				$this->errors[] = $this->new_post_id;
			}
			else{
				// adicionar ID ao valid_data, para ser usado pelos callbacks
				$this->valid_data['ID'] = $this->new_post_id;
				
				// carrega o novo post para o objeto
				$this->post = get_post($this->new_post_id);
				
				// fixed taxonomy terms
				if( !empty( $this->config['taxonomies'] ) ){
					foreach( $this->config['taxonomies'] as $taxonomy => $terms ){
						wp_set_object_terms( $this->new_post_id, $terms, $taxonomy );
					}
				}
				
				// post_metas e arquivos
				foreach( $this->valid_meta as $meta_key => $meta_value ){
					$config = array_search_kv( 'name', $meta_key, $this->elements );
					
					// Salvar upload. Mesmo que esteja configurado para 'skip_save', o arquivo será enviado para o Mídia do WordPress, e o ID do attachment será salvo como post_meta
					if( isset($config['type']) && $config['type'] == 'file' ){
						// apenas caso tenha sido enviado de fato algum arquivo, caso contrário pular, ou salvará o array de upload com dados vazios
						if( isset($meta_value['size']) and $meta_value['size'] > 0 ){
                            
							$attachment_id = $this->save_file( $meta_value, $this->new_post_id, $config ); //pre($attachment_id, 'attachment_id');
							// não salvar post_meta em caso de erro no upload e registrar o erro
							if( is_wp_error($attachment_id) ){
								$this->errors[] = $attachment_id;
								$meta_value = false;
								continue;
							}
							else{
								/**
								 * Atualizar também o valid_meta para o ID do anexo, pois inicialmente ele possui apenas os dados puros de upload (name, type, tmp_name, size), e irá permitir o uso pelos callbacks
								 * 
								 */
								$this->valid_meta[$meta_key] = $attachment_id;
								$meta_value = $attachment_id;
							}
						}
						else{
							continue;
						}
					}
					
					if( isset($config['skip_save']) and $config['skip_save'] == true ){
						continue;
					}
					
					// verificar se o meta_value é false, e remover
					if( $meta_value === false or !boros_check_empty_var($meta_value) ){
						delete_post_meta( $this->new_post_id, $meta_key );
					}
					else{
						update_post_meta( $this->new_post_id, $meta_key, $meta_value );
					}
				}
				$this->messages['success'] = $this->config['messages']['success'];
				
				// acionar callbacks: elements
				$this->do_callbacks( $this->valid_data );
				$this->do_callbacks( $this->valid_meta );
				// acionar callbacks: form->config
				$this->form_callback( $this->config['callbacks']['success'] );
				// acionar callbacks escrito errado('sucess') e adicionar erro em 'boros_dashboard_notifications'
				// @deprecated
				if( isset($this->config['callbacks']['sucess']) ){
					$this->form_callback( $this->config['callbacks']['sucess'] );
					$alerts = get_option('boros_dashboard_notifications');
					$alerts['callback_index_typo'] = 'Foi identificado o uso de um callback de formulário de frontend com index errado <strong>sucess</strong>';
					update_option('boros_dashboard_notifications', $alerts);
				}
				
				// redirect
				if( $this->config['redirect_on_sucess'] != false ){
					wp_redirect( $this->get_redirect_url('success') );
					exit();
				}
				
				// reset data, apenas caso seja um reload de página
				$this->valid_data = array();
				$this->valid_meta = array();
			}
		}
		else{
			// registrar erros
			$this->errors = array_merge( $this->errors, $this->validation->data_errors );
			
			// acionar callbacks de erro
			$this->form_callback( $this->config['callbacks']['error'] );
		}
	}
	
	function validate_taxonomy_terms( $post_data ){
		foreach( $this->elements_plain as $element ){
			if( $element['core_type'] == 'tax_input' ){
				$taxonomy = $element['options']['taxonomy'];
				
				// verificar required
				if( (!isset($post_data['tax_input'][$taxonomy]) or empty($post_data['tax_input'][$taxonomy]))and isset($element['validate']['required']) ){
					$error = array(
						'name' => 'required',
						'message' => $element['validate']['required']['message'],
						'type' => 'error'
					);
					$this->validation->data_errors[$element['name']]['required'] = $error;
				}
				else{
					// verificar se a taxonomia é válida
					if( isset($this->config['accepted_taxonomies'][$taxonomy]) ){
						// verificar se existe limitação de termos
						if( !empty($this->config['accepted_taxonomies'][$taxonomy]) ){
							if( is_array($post_data['tax_input'][$taxonomy]) ){
								//pal(2);
								$this->valid_taxonomy_terms[$taxonomy] = array_intersect($this->config['accepted_taxonomies'][$taxonomy], $terms);
							}
							elseif( in_array($post_data['tax_input'][$taxonomy], $this->config['accepted_taxonomies'][$taxonomy]) ){
								$this->valid_taxonomy_terms[$taxonomy] = array($post_data['tax_input'][$taxonomy]);
							}
						}
						// liberado todos os termos
						else{
							$this->valid_taxonomy_terms[$taxonomy] = $post_data['tax_input'][$taxonomy];
						}
					}
				}
			}
		}
		
		/**
		foreach( $this->elements as $box ){
			foreach( $box['itens'] as $element ){
				pre($element);
				if( substr($element['name'], 0, 9) == 'tax_input' ){
					$taxonomy = $element['options']['taxonomy'];
					
					// verificar required
					if( !isset($post_data['tax_input'][$taxonomy]) and isset($element['validate']['required']) ){
						$error = array(
							'name' => 'required',
							'message' => $element['validate']['required']['message'],
							'type' => 'error'
						);
						$this->validation->data_errors[$element['name']]['required'] = $error;
					}
					else{
						// verificar se a taxonomia é válida
						if( isset($this->config['accepted_taxonomies'][$taxonomy]) ){
							// verificar se existe limitação de termos
							if( !empty($this->config['accepted_taxonomies'][$taxonomy]) ){
								if( is_array($post_data['tax_input'][$taxonomy]) ){
									$this->valid_taxonomy_terms[$taxonomy] = array_intersect($this->config['accepted_taxonomies'][$taxonomy], $terms);
								}
								else{
									$this->valid_taxonomy_terms[$taxonomy] = array($post_data['tax_input'][$taxonomy]);
								}
							}
							// liberado todos os termos
							else{
								$this->valid_taxonomy_terms[$taxonomy] = $post_data['tax_input'][$taxonomy];
							}
						}
					}
				}
			}
		}
		/**/
	}
	
	function save_file( $file_info, $parent_id, $elem_config ){
		if( !function_exists( 'wp_handle_upload' ) ){
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
        
        /**
         * Permitir que somente este processo consiga subir qualquer tipo de mime type.
         * Os tipos de mime types permitidos e erro poderá ser verificado na validação do campo.
         * 
         */
        define('ALLOW_UNFILTERED_UPLOADS', true);

        do_action('boros_pre_upload', $this->form_name, $elem_config, $this);

        // If we were to have a unique user account for uploading
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        // modificar o filename, para que não seja utilizado o nome original
        if( isset($elem_config['options']['hash_filename']) and $elem_config['options']['hash_filename'] == true ){
            $file_info['name'] = boros_hash_filename( $file_info['name'] );
        }
        // filtrar informações do arquivo
        $file_info = apply_filters( 'boros_filter_uploaded_file_data', $file_info, $parent_id, $user_id );
        // salvar o arquivo no local correto
		$movefile = wp_handle_upload( $file_info, array( 'test_form' => false ) );
		if( $movefile ){
			// erro no upload, registrar erro
			if( isset($movefile['error']) ){
				return new WP_Error( 'upload_error', $movefile['error'] );
			}
			// sucesso no upload, registrar no Mídia
			else{
				$uploads = wp_upload_dir();
				$attachment = array(
					 'post_mime_type' => $file_info['type'],
					 'post_title' => $file_info['name'],
					 'post_content' => '',
					 'post_status' => 'inherit',
				);
                
                // aplicar modificações a partir das configurações do elemento, sem precisar usar o filtro 'boros_frontend_form_pre_file_insert'
                $allowed_keys = array(
                    'post_content',
                    'post_status',
                    'post_excerpt',
                    'post_date',
                    //'tax_input',
                    'meta_input',
                );
                if( isset($elem_config['options']['post_data']) ){
                    foreach( $elem_config['options']['post_data'] as $key => $value ){
                        if( in_array($key, $allowed_keys) ){
                            $attachment[$key] = $value;
                        }
                    }
                }
                
                // permitir modificação no array de post para o attachment
                $attachment = apply_filters( 'boros_frontend_form_pre_file_insert', $attachment, $movefile['file'], $elem_config, $file_info, $parent_id );
                
				$attach_id = wp_insert_attachment( $attachment, $movefile['file'], $parent_id );
				if( !$attach_id ) {
					return new WP_Error( 'insert_attachment_error', 'Erro ao salvar a imagem' );
				}
				else{
					require_once(ABSPATH . "wp-admin" . '/includes/image.php');
					$attach_data = wp_generate_attachment_metadata( $attach_id, $movefile['file'] );
					wp_update_attachment_metadata( $attach_id,  $attach_data );
                    
                    // Adicionar taxonomy terms personalizados:
                    // em wp_insert_post(), que é utilizado pelo wp_insert_attachment(), os termos só são adicionados caso o 
                    // usuário corrente possua permissão 'assign_terms', por isso é necessário aplicar os termos 
                    // separadamente de wp_insert_attachment()
                    // @link https://developer.wordpress.org/reference/functions/wp_insert_post/#comment-2164
                    // 
                    // Caso esteja usando o plugin enhanced-media-library, é necessário que o form seja inicializado em 'wp_loaded' 
                    // prioridade acima de 10, pois em 'init' a taxonomia 'media_category' ainda não foi registrada.
                    if( isset( $elem_config['options']['post_data']['tax_input'] ) ){
                        foreach( $elem_config['options']['post_data']['tax_input'] as $tax => $terms ){
                            $tt = wp_set_object_terms( $attach_id, $terms, $tax );
                        }
                    }
                    
					return $attach_id;
				}
			}
		}
		else{
			wp_die('Requisição de envio de arquivo inválida!!!', 'Erro no envio do arquivo');
		}
	}
	
	function edit_post(){
        //pal('edit_post');
        //pre( $_POST, '$_POST' );
        //pre( $this->posted_data, 'posted_data' );
        //die();
		//pre($this->posted_data, 'RAW $this->posted_data');
		
		$post_data = array();
		foreach( $this->core_post_fields as $field => $default ){
			if( isset($this->posted_data[$field]) ){
				$post_data[$field] = $this->posted_data[$field];
			}
		}
		
		$post_meta = array();
		// alertar que o modelo de 'accepted_metas' está antigo
		if( !is_assoc_array($this->config['accepted_metas']) ){
			wp_die('ALERTA: o modelo de accepted_metas está no formato antigo, corrigir mudando para array associativo com defaults <strong>create_post()</strong>');
		}
		foreach( $this->config['accepted_metas'] as $field => $default ){
			if( isset($this->posted_data[$field]) ){
				$post_meta[$field] = $this->posted_data[$field];
			}
			else{
				$post_meta[$field] = $default;
			}
		}
        
        //pre( $post_data, 'ACCEPTED POST_DATA' );
        //pre( $post_meta, 'ACCEPTED POST_META' );
        //sep();
        
        $this->valid_data = $this->validate( $this->context, $post_data );
        $this->valid_meta = $this->validate( $this->context, $post_meta );
        
        //pre($this->valid_data, 'VALID DATA');
        //pre($this->valid_meta, 'VALID META');
        //sep();
		
		// mesclar dados 'core_post_fields' da config
		$this->valid_data = boros_parse_args( $this->config['core_post_fields'], $this->valid_data );
		// mesclar dados 'core_post_fields' da class
		$this->valid_data = boros_parse_args( $this->core_post_fields, $this->valid_data );
		
		/**
		 * Aplicar termos de taxonomia setados pelo usuário.
		 * 
		 * @todo Caso seja configurado um array de termos, apenas esses termos serão válidos para que o usuário aplique.
		 */
		if( !empty( $this->config['accepted_taxonomies'] ) ){
			$this->validate_taxonomy_terms($post_data);
		}
		//pre($this->valid_taxonomy_terms, 'valid_taxonomy_terms');
		if( !empty($this->valid_taxonomy_terms) ){
			$this->valid_data['tax_input'] = $this->valid_taxonomy_terms;
		}
		
		// remover os vazios de core_data, para que o próprio WordPress processe corretamente os valores.
		$this->valid_data = array_non_empty_items( $this->valid_data );
		
		// mesclar dados 'accepted_metas' da config
		$this->valid_meta = boros_parse_args( $this->config['accepted_metas'], $this->valid_meta );
		
		//pre($this->valid_data, 'VALID DATA');
		//pre($this->valid_meta, 'VALID META');
		//pre($this->validation->data_errors, 'VALIDATION $this->validation->data_errors');
		//die('EDIT POST');
		
		// adicionar filtro para pós validação, por exemplo para verificar campos dependentes de respostas de outros campos
		$this->valid_data = apply_filters( 'boros_frontend_form_pos_validation_data', $this->valid_data, $this->valid_meta, $this->validation->data_errors, $this->form_name );
		$this->valid_meta = apply_filters( 'boros_frontend_form_pos_validation_meta', $this->valid_meta, $this->valid_data, $this->validation->data_errors, $this->form_name );
		$this->validation->data_errors = apply_filters( 'boros_frontend_form_pos_validation_errors', $this->validation->data_errors, $this->valid_data, $this->valid_meta, $this->form_name );
		
		//pre($this->validation->data_errors, 'ERRORS');
		
		// verificar errors, caso negativo, editar post
		if( empty( $this->validation->data_errors ) ){
            
            // filtrar título do post com informações extras postadas
            if( isset($this->valid_data['post_title']) ){
                $this->valid_data['post_title'] =  $this->template_tags( $this->valid_data['post_title'], $this->valid_data );
                $this->valid_data['post_title'] =  $this->template_tags( $this->valid_data['post_title'], $this->valid_meta );
            }
			
			// mesclar dados defaults_config
			//$insert_data = boros_parse_args( $this->config['core_post_fields'], $this->valid_data );
			// mesclar dados core_config
			//$insert_data = boros_parse_args( $this->core_post_fields, $insert_data );
			//$new_post_id = wp_insert_post( $insert_data, 1 ); // segundo argumento habilita WP_Error
			$this->new_post_id = wp_update_post( $this->valid_data, 1 ); // segundo argumento habilita WP_Error
			
			if( is_wp_error($this->new_post_id) ){
				$this->errors[] = $this->new_post_id;
			}
			else{
				// adicionar ID ao valid_data, para ser usado pelos callbacks
				//$this->valid_data['ID'] = $this->new_post_id;
				
				// carrega o novo post para o objeto
				$this->post = get_post($this->new_post_id);
				
				// fixed taxonomy terms
				if( !empty( $this->config['taxonomies'] ) ){
					foreach( $this->config['taxonomies'] as $taxonomy => $terms ){
						wp_set_object_terms( $this->new_post_id, $terms, $taxonomy );
					}
				}
				
				// post_metas e arquivos
				foreach( $this->valid_meta as $meta_key => $meta_value ){
					$config = array_search_kv( 'name', $meta_key, $this->elements );
					
					// Salvar upload. Mesmo que esteja configurado para 'skip_save', o arquivo será enviado para o Mídia do WordPress, e o ID do attachment será salvo como post_meta
					if( $config['type'] == 'file' ){
						// apenas caso tenha sido enviado de fato algum arquivo, caso contrário pular, ou salvará o array de upload com dados vazios
						if( isset($meta_value['size']) and $meta_value['size'] > 0 ){
                            
							$attachment_id = $this->save_file( $meta_value, $this->new_post_id, $config ); //pre($attachment_id, 'attachment_id');die();
							// não salvar post_meta em caso de erro no upload e registrar o erro
							if( is_wp_error($attachment_id) ){
								$this->errors[] = $attachment_id;
								$meta_value = false;
								continue;
							}
							else{
								/**
								 * Atualizar também o valid_meta para o ID do anexo, pois inicialmente ele possui apenas os dados puros de upload (name, type, tmp_name, size), e irá permitir o uso pelos callbacks
								 * 
								 */
								$this->valid_meta[$meta_key] = $attachment_id;
								$meta_value = $attachment_id;
							}
						}
						else{
							continue;
						}
					}
					
					if( isset($config['skip_save']) and $config['skip_save'] == true ){
						continue;
					}
					
					// verificar se o meta_value é false, e remover
					if( $meta_value === false or !boros_check_empty_var($meta_value) ){
						delete_post_meta( $this->new_post_id, $meta_key );
					}
					else{
						update_post_meta( $this->new_post_id, $meta_key, $meta_value );
					}
				}
				$this->messages['success'] = $this->config['messages']['success'];
				
				// acionar callbacks: elements
				$this->do_callbacks( $this->valid_data );
				$this->do_callbacks( $this->valid_meta );
				// acionar callbacks: form->config
				$this->form_callback( $this->config['callbacks']['success'] );
				// acionar callbacks escrito errado('sucess') e adicionar erro em 'boros_dashboard_notifications'
				// @deprecated
				if( isset($this->config['callbacks']['sucess']) ){
					$this->form_callback( $this->config['callbacks']['sucess'] );
					$alerts = get_option('boros_dashboard_notifications');
					$alerts['callback_index_typo'] = 'Foi identificado o uso de um callback de formulário de frontend com index errado <strong>sucess</strong>';
					update_option('boros_dashboard_notifications', $alerts);
				}
				
				// redirect
				if( $this->config['redirect_on_sucess'] != false ){
					wp_redirect( $this->get_redirect_url('success') );
					exit();
				}
				
				// reset data, apenas caso seja um reload de página
				$this->valid_data = array();
				$this->valid_meta = array();
			}
		}
		else{
			// registrar erros
			$this->errors = array_merge( $this->errors, $this->validation->data_errors );
			
			// acionar callbacks de erro
			$this->form_callback( $this->config['callbacks']['error'] );
		}
	}
	
	function save(){
		
	}
	
	function validate( $context, $data ){
		$valids = array();
		foreach( $this->elements as $box ){
			foreach( $box['itens'] as $element ){
				if( isset($element['name']) and isset($data[ $element['name'] ]) ){
					$this->validation->add( $element );
					$validated = $this->validation->verify_data( $element, $data[ $element['name'] ] );
					
					// são armazenados em $valids mesmo que sejam FALSE, para que o restante do script manipule corretamente os dados
					$valids[ $element['name'] ] = $validated;
				}
			}
		}
		//pre($this->validation->data_errors);
		return $valids;
	}
	
	function required( $data ){
		foreach( $this->elements as $box ){
			//pre($box, '$box');
			foreach( $box['itens'] as $element ){
				// required declarado na config base #DEPRECATED
				if( isset($element['required']) and !isset($data[$element['name']]) ){
					$error = array(
						'name' => 'required',
						'message' => $element['required'],
						'type' => 'error'
					);
					$this->validation->data_errors[$element['name']]['required'] = $error;
				}
				
				// required declarado no validate
				if( isset($element['validate']['required']) and !boros_check_empty_var( $data, $element['name'] ) ){
					$error = array(
						'name' => 'required',
						'message' => $element['validate']['required']['message'],
						'type' => 'error'
					);
					$this->validation->data_errors[$element['name']]['required'] = $error;
				}
				
				/**
				 * file upload - o envio do form possuirá um array de informações do upload, mesmo quando é enviado vazio.
				 * O isset() é necessário para o carregamento normal da página, que não possuirá os dados requeridos.
				 * A verificação do 'error' é necessária para a validação do form enviado
				 * 
				 */
				if( isset($element['validate']['required']) and $element['type'] == 'file' and isset($data[$element['name']]) and $data[$element['name']]['error'] != 0 ){
					$error = array(
						'name' => 'required',
						'message' => $element['validate']['required']['message'],
						'type' => 'error'
					);
					$this->validation->data_errors[$element['name']]['required'] = $error;
				}
			}
		}
	}
	
	// recarregar baseado no name
	function reload_val( $name ){
		
	}
	
	// recarregar baseado no element
	function reload_input_value( $item ){
		// resetar valores caso necessário
		if( isset($item['options']['reset_after_submit']) and $item['options']['reset_after_submit'] == true ){
			if( isset( $item['std']) ){
				$return = $item['std'];
			}
			else{
				return null;
			}
		}
		
		// recarregar valid_data
		if( isset($item['name']) and array_key_exists( $item['name'], $this->valid_data ) ){
			return $this->valid_data[ $item['name'] ];
		}
		// recarregar valid_meta
		elseif( isset($item['name']) and array_key_exists( $item['name'], $this->valid_meta ) ){
			$return = $this->valid_meta[ $item['name'] ];
		}
		// recarregar valid_taxonomy_terms
		elseif( isset($item['name']) and $item['core_type'] == 'tax_input' ){
			//pal($item['options']['taxonomy']);
			//pre($this->valid_taxonomy_terms);
			//pre($this->valid_taxonomy_terms[$item['options']['taxonomy']]);
			if( isset($this->valid_taxonomy_terms[$item['options']['taxonomy']]) ){
				$return = $this->valid_taxonomy_terms[$item['options']['taxonomy']];
			}
			//return $this->valid_meta[ $item['name'] ];
		}
		// recarregar padrão, caso exista
		elseif( isset( $item['std']) ){
			$return = $item['std'];
		}
		else{
			return null;
		}
        
        // esc_html
        $return = $this->esc_html($return);
        
        return $return;
	}
    
    function esc_html( $value ){
        if( is_array($value) ){
            foreach( $value as $k => $v ){
                $value[$k] = $this->esc_html($v);
            }
        }
        else{
            $value = esc_html($value);
        }
        return $value;
    }
	
	// desativado
	function add_persistent_messages( $user_id, $messages ){
		set_transient( "user_message_{$user_id}", $messages, 300 );
	}
	
	/**
	 * Callbacks de elementos
	 * 
	 * ATENÇÃO: possível bug caso existam elementos com names iguais em box diferentes
	 */
	function do_callbacks( $data ){
		foreach( $data as $k => $v ){
			$config = array_search_kv( 'name', $k, $this->elements );
			if( isset($config['callbacks']) ){
				foreach( $config['callbacks'] as $callback ){
					$this->do_callback( $callback, $k, $v, $config );
				}
			}
		}
	}
	
	function do_callback( $callback, $k, $v, $config ){
		//pre($k);
		//pre($v);
		//pre($callback);
		
		$args = array(
			'name'    => $k,
			'value'   => $v,
			'args'    => isset( $callback['args'] ) ? $callback['args'] : false,
            'context' => $this->context,
		);
		
		if( method_exists( $this, $callback['function'] ) ){
			//pal("Método da class BorosFrontendForm: {$callback['function']}");
			call_user_func( array($this, $callback['function']), $args );
		}
		elseif( function_exists( $callback['function'] ) ){
			call_user_func( $callback['function'], $args );
		}
	}
	
	/**
	 * Executar os callbacks do formulário.
	 * Poderá executar em caso de sucesso ou erro, em quantidade ilimitada.
	 * 
	 * O $callback possui o nome da função em $callback['function'] e argumentos adicionais em $callback['args']
	 * 'args' possuirá por padrão o 'object' BorosFrontendForm completo, além de argumentos adicionais declarados
	 * no array de configuração.
	 * 
	 */
	function form_callback( $callbacks = false ){
		if( empty($callbacks) )
			return;
		
		foreach( $callbacks as $callback ){
			if( method_exists( $this, $callback['function'] ) ){
				//pal("Método da class BorosFrontendForm: {$callback['function']}");
				// não é necessário adicionar o <code>$callback['args']['object'] = $this;</code> porque o método já pode acessar as informações de valid_{meta|data}
				return call_user_func( array($this, $callback['function']), $callback['args'] );
			}
			elseif( function_exists( $callback['function'] ) ){
				$callback['args']['object'] = $this;
				return call_user_func( $callback['function'], $callback['args'] );
			}
		}
	}
	
	function notify_by_email( $args ){
		$title =  $this->template_tags( $args['title'], $this->valid_data );
		$title =  $this->template_tags( $title, $this->valid_meta );
		
		$message = $args['message'];
		//pre($message, 'PRE_MESSAGE');
		$message = $this->template_tags( $args['message'], $this->valid_data );
		$message = $this->template_tags( $message, $this->valid_meta );
		$message = nl2br( $message );
		$message = apply_filters( 'boros_notify_by_email_message', $message, $args, $this->valid_data, $this->valid_meta, $this->form_name );
		
		//pre($args, 'ARGS');
		//pre($message, 'POS_MESSAGE');
		//pre($this->valid_data, 'VALID_DATA');
		//pre($this->valid_meta, 'VALID_META');
		
		/**
		 * Montar os $headers conforme os destinatários adicionais
		 * O remetente dos emails é definido nos hooks 'wp_mail_from' e 'wp_mail_from_name' em boros/functions/email.php
		 * 
		 */
		$headers = array();
		
		// Adicionar CC
		if( isset($args['cc']) and !empty($args['cc']) ){
			$emails = (!is_array($args['cc'])) ? explode(',', $args['cc']) : $args['cc'];
			foreach( $emails as $email ){
				if( !empty($email) )
					$headers[] = "Cc: {$email}";
			}
		}
		// Adicionar BCC
		if( isset($args['bcc']) and !empty($args['bcc']) ){
			$emails = (!is_array($args['bcc'])) ? explode(',', $args['bcc']) : $args['bcc'];
			foreach( $emails as $email ){
				if( !empty($email) )
					$headers[] = "Bcc: {$email}";
			}
		}
		
		/**
		 * Adicionar Reply-To
		 * Em caso de formulários de contato, modificar o valor para o mesmo email da pessoa que enviou o forumlário, através do valor 'reply_back'
		 * 
		 */
		if( isset($args['reply_to']) and !empty($args['reply_to']) ){
			if( $args['reply_to'] == 'reply_back' ){
				$email = $this->valid_meta['email'];
			}
			else{
				$email = $args['reply_to'];
			}
			$headers[] = "Reply-To: {$email}";
		}
		
		//pre($this->elements_plain, 'ELEMENTS');
		//pre($this->valid_data, 'VALID DATA');
		//pre($this->valid_meta, 'VALID META');
		//pre($args, 'ARGS');
		//pre($message, 'MESSAGE');
		//pre($headers, 'HEADERS');
		//pal('to: ' . $args['to']);
		//pal('title: ' . $title);
		//pal('message: ' . $message);
		//die('EMAIL TEST');
		
		$to      = apply_filters('boros_frontend_form_notify_by_email_to',      $args['to'], $this, $this->valid_data, $this->valid_meta);
		$title   = apply_filters('boros_frontend_form_notify_by_email_title',   $title     , $this, $this->valid_data, $this->valid_meta);
		$message = apply_filters('boros_frontend_form_notify_by_email_message', $message   , $this, $this->valid_data, $this->valid_meta);
		$headers = apply_filters('boros_frontend_form_notify_by_email_headers', $headers   , $this, $this->valid_data, $this->valid_meta);
		
		$sent = wp_mail( $to, $title, $message, $headers );
	}
	
	/**
	 * Obrigatório o formato das tags em array chave => valor
	 * 
	 * @todo - verificar quando precisa aplicar o filtro 'the_content' para textareas e wp_editor
	 */
	function template_tags( $text, $tags ){
		$the_content = array(
			'textarea',
			'textarea_editor',
			'wp_editor',
		);
		$multi = array(
			'select',
			'radio',
			'checkbox_group',
		);
		foreach( $tags as $name => $value ){
			$tag = '%%' . strtoupper($name) . '%%';
			if( !is_array($value) ){
				if( !empty($value) ){
					if( isset($this->elements_plain[$name]) ){
						if( in_array($this->elements_plain[$name]['type'], $the_content) ){
							$value = apply_filters( 'the_content', $value );
						}
						elseif( $this->elements_plain[$name]['type'] == 'file' ){
							if( $value !== false ){
								$value = wp_get_attachment_url($value);
							}
							else{
								$value = 'Anexo não salvo';
							}
						}
						elseif( $this->elements_plain[$name]['type'] == 'checkbox' and $value == true ){
							$value = 'sim';
						}
						elseif( in_array($this->elements_plain[$name]['type'], $multi) ){
							$v = array();
							foreach( $this->elements_plain[$name]['options']['values'] as $key => $label ){
								if( $value == $key ){
									$v[] = $label;
								}
							}
							$value = implode(', ', $v);
						}
					}
				}
				else{
					if( isset($this->elements_plain[$name]) and $this->elements_plain[$name]['type'] == 'checkbox' ){
						$value = 'não';
					}
				}
				$text = str_replace( $tag, esc_html(strip_tags($value)), $text );
			}
			else{
				if( isset($this->elements_plain[$name]) and in_array($this->elements_plain[$name]['type'], $multi) ){
					$v = array();
					foreach( $this->elements_plain[$name]['options']['values'] as $key => $label ){
						if( in_array($key, $value) ){
							$v[] = trim( str_replace('&nbsp;', '', $label) );
						}
					}
					$value = implode(', ', $v);
					$text = str_replace( $tag, esc_html(strip_tags($value)), $text );
				}
			}
		}
		return apply_filters( 'boros_frontend_form_template_tags_text', $text, $tags );
	}
	
	function save_as_user_meta( $args ){
		global $current_user;
		wp_get_current_user();
		$user_meta = get_user_meta( $current_user->ID, $args['name'], true );
		
		if( !empty($user_meta) and $args['args']['overwrite'] == false )
			return;
		
		update_user_meta( $current_user->ID, $args['name'], $args['value'] );
	}
	
	/**
	 * Criar usuário numérico a partir da ID do último usuário registrado.
	 * Devido às diferenças na coluna ID quando se apaga registros, não é confiável que o próximo número do autoincrement será igual ao último registro + 1, já que o registro mais recente poderá
	 * ter sido apagado, gerando a diferença. Por exemplo: o ultimo registro foi ID = 50, portanto o autoincrement será 51, mas caso se apague os registros de 40 a 50, o autoincrement continua em 51 e não 41.
	 */
	function create_numeric_username(){
		global $wpdb;
		/**
		$query = "	SELECT `ID`
					FROM {$wpdb->prefix}users
					ORDER BY `ID` DESC
					LIMIT 1";
		$last_user = $wpdb->get_results( $query );
		return $last_user[0]->ID + 1;
		/**/
		
		$query = "SHOW TABLE STATUS LIKE '{$wpdb->base_prefix}users'";
		$users_table = $wpdb->get_results( $query );
		return $users_table[0]->Auto_increment;
	}
    
    /**
     * Montar a action do formulário
     * 
     */
    function create_form_action(){
        // URL atual
        $action = self_url();

        // adicionar queryvars
        if( isset($this->config['action_append']) ){
            if( is_array($this->config['action_append']) ){
                $action = add_query_arg( $this->config['action_append'], $action );
            }
            else{
                $action .= $this->config['action_append'];
            }
        }

        // adicionar âncoras
        if( isset($this->config['action_anchor']) ){
            $action .= $this->config['action_anchor'];
        }

        echo apply_filters( 'boros_form_action', $action, $this->form_name, $this->context );
    }

    /**
     * Output personalizado que deverá ser criado pela class extendida
     * 
     */
    public function custom_output( $form_name ){}
    
	/**
	 * @todo verificar o $this->create_numeric_username() nesta function
	 * 
	 */
	function output( $form_name ){
		if( $this->form_name == $form_name ){
			//pre( $this->posted_data, 'this->posted_data' );
			
			//pre($this->context);
			$this->create_numeric_username();
			
			/**
			 * Mensagem de login requerido
			 * 
			 */
			if( $this->config['login_required'] == true and !is_user_logged_in() ){
				?>
				<div class="boros_frontend_form" id="<?php echo $form_name; ?>_box">
					<?php echo $this->config['login_required']; ?>
				</div>
				<?php
				return;
			}
			
			/**
			 * Formulário liberado
			 * 
			 * @WARNING :  foi removido a class do <form> e mantido apenas o do parent
			 */
			$class = isset($this->config['class']) ? "boros_frontend_form {$this->config['class']}" : 'boros_frontend_form';
			
			if( !empty( $this->errors ) ){
				$class .= ' form_error';
			}
			if( isset($this->messages['success'])){
				$class .= ' form_success';
			}
			?>
			<div class="<?php echo $class; ?>" id="<?php echo $form_name; ?>_box">
				<?php $this->show_messages(); ?>
				<?php echo $this->show_errors(); ?>
				
				<form action="<?php $this->create_form_action(); ?>" method="post" id="<?php echo isset($this->config['form_id']) ? $this->config['form_id'] : $form_name; ?>" <?php echo $this->config['enctype']; ?>>
					<input type="hidden" name="form_name" value="<?php echo $this->config['form_name']; ?>" />
					<?php
					/**
					 * Adicionar input:hidden do contexto
					 * 
					 */
					foreach( $this->context as $k => $v ){
						echo "<input type='hidden' name='{$k}' value='{$v}' />\n";
					}
					
					foreach( $this->elements as $index => $box ){
						$parent = $box['id'];
						$itens  = $box['itens'];
						
						echo "<div class='boros-form-block' id='{$parent}-{$index}'>";
						
							// descrição
							if( isset($box['desc']) and !empty($box['desc']) ){
								?>
								<div class="boros-form-desc">
									<h2><?php echo $box['title']; ?></h2>
									<div class="boros-form-desc-text"><?php echo $box['desc']; ?></div>
								</div>
								<?php
							}
							
							foreach( $itens as $item ){
								$data_value = null;
								
								// se estiver vazio, usar o valor padrão
								//if( empty( $data_value ) and isset( $item['std']) ) $data_value = $item['std'];
								
								$data_value = $this->reload_input_value( $item );
								
								// o parent é a ID do box
								$this->context['group'] = $box['id'];
								if( !isset($item['layout']) )
									$item['layout'] = 'frontend';
								create_form_elements( $this->context, $item, $data_value, $this->context['group'] );
							}
							
							
							// info help de rodapé
							if( isset($box['help']) ){
								?>
								<div class="boros-form-extra-info">
									<div>
										<span class="ico"></span> 
										<?php echo $box['help']; ?>
									</div>
								</div>
								<?php
							}
							
						echo '</div>';
					}
					?>
				</form>
			</div>
			<?php
		}
	}
	
	/**
	 * output bootstrap --igual ao $thhis->output(), mas com formatação para o bootstrap
	 * 
	 * 
	 * @todo fazer o output da lista de errors, com ancoras e opcional
	 */
	function bootstrap_output( $form_name ){
		if( $this->form_name == $form_name ){
			//pre( $this->posted_data, 'this->posted_data' );
			
			//pre($this->context);
			$this->create_numeric_username();
			
			// class css
			$class = isset($this->config['class']) ? $this->config['class'] : '';
			if( !empty( $this->errors ) ){
				$class .= ' form_error';
			}
			if( isset($this->messages['success'])){
				$class .= ' form_success';
			}
			
			/**
			 * Mensagem de login requerido
			 * 
			 */
			if( $this->config['login_required'] == true and !is_user_logged_in() ){
				?>
				<div class="<?php echo $class; ?>" id="<?php echo $form_name; ?>">
					<?php echo $this->config['login_required']; ?>
				</div>
				<?php
				return;
			}
			
			/**
			 * Formulário liberado
			 * 
			 */
			?>
			<form action="<?php $this->create_form_action(); ?>" method="post" class="<?php echo $class; ?>" id="<?php echo isset($this->config['form_id']) ? $this->config['form_id'] : $form_name; ?>" <?php echo $this->config['enctype']; ?>>
				<?php $this->show_messages(); ?>
				<?php
				/**
				 * Mensagens de erro gerais. Este bloco pode exibir uma mensagem de erro geral, podendo exibir mensagens com âncoras.
				 * 
				 */
				if( !empty( $this->errors ) ){
					echo "<div class='alert alert-error alert-danger'>{$this->config['messages']['error']}</div>";
					
					if( $this->config['show_errors_index'] == true ){
						echo '<div class="alert alert-error alert-danger">';
						foreach( $this->errors as $input_name => $errors ){
							foreach( $errors as $error ){
								echo "<p><a href='#{$input_name}'>{$error['message']}</a></p>";
							}
						}
						echo '</div>';
					}
					
					if( $this->config['debug'] == true ){
						pre($this->errors, 'bootstrap3_output errors');
					}
				}
				?>
				<input type="hidden" name="form_name" value="<?php echo $this->config['form_name']; ?>" />
			<?php
				/**
				 * Adicionar input:hidden do contexto
				 * 
				 */
				foreach( $this->context as $k => $v ){
					echo "<input type='hidden' name='{$k}' value='{$v}' />\n";
				}
				
				foreach( $this->elements as $box ){
					$parent 	= $box['id'];
					$itens 		= $box['itens'];
					
					echo "<fieldset id='{$parent}'>";
					
						// descrição
						if( isset($box['title']) ){
							?>
							<legend><?php echo $box['title']; ?></legend>
							<?php if( isset($box['desc']) and !empty($box['desc']) ) echo "{$box['desc']} <hr />"; ?>
							<?php
						}
						
						foreach( $itens as $item ){
							$data_value = null;
							
							// adicionar os erros guardados
							if( isset($this->errors[$item['name']]) and $this->config['show_errors'] == true ){
								$item['errors'] = $this->errors[$item['name']];
							}
							
							// se estiver vazio, usar o valor padrão
							//if( empty( $data_value ) and isset( $item['std']) ) $data_value = $item['std'];
							
							$data_value = $this->reload_input_value( $item );
							
							// o parent é a ID do box
							$this->context['group'] = $box['id'];
							if( empty($item['layout']) ){
								$item['layout'] = 'bootstrap';
							}
							create_form_elements( $this->context, $item, $data_value, $this->context['group'] );
						}
						
						// info help de rodapé
						if( isset($box['help']) and !empty($box['help']) ){
							?>
							<hr />
							<?php echo $box['help']; ?>
							<?php
						}
						
					echo '</fieldset>';
				}
			?>
			</form>
			<?php
		}
	}
	
	/**
	 * output bootstrap3 --igual ao $thhis->bootstrap_output(), mas com formatação para o bootstrap 3
	 * 
	 * 
	 * @todo fazer o output da lista de errors, com ancoras e opcional
	 */
	function bootstrap3_output( $form_name ){
		if( $this->form_name == $form_name ){
			$this->create_numeric_username();
			
			// class css
			$class = isset($this->config['class']) ? $this->config['class'] : '';
			if( !empty( $this->errors ) ){
				$class .= ' form_error';
			}
			if( isset($this->messages['success'])){
				$class .= ' form_success';
			}
			
			/**
			 * Mensagem de login requerido
			 * 
			 */
			if( $this->config['login_required'] == true and !is_user_logged_in() ){
				?>
				<div class="<?php echo $class; ?>" id="<?php echo $form_name; ?>">
					<?php echo $this->config['messages']['login_required']['message']; ?>
				</div>
				<?php
				return;
			}
			
			/**
			 * Formulário liberado
			 * 
			 */
			?>
			<form action="<?php $this->create_form_action(); ?>" method="post" class="<?php echo $class; ?>" id="<?php echo isset($this->config['form_id']) ? $this->config['form_id'] : $form_name; ?>" <?php echo $this->config['enctype']; ?>>
				<?php $this->show_messages(); ?>
				<?php
				/**
				 * Mensagens de erro gerais. Este bloco pode exibir uma mensagem de erro geral, podendo exibir mensagens com âncoras.
				 * 
				 */
				if( !empty( $this->errors ) ){
					echo "<div class='alert alert-error alert-danger'>{$this->config['messages']['error']}</div>";
					
					if( $this->config['show_errors_index'] == true ){
						echo '<div class="alert alert-error alert-danger">';
						foreach( $this->errors as $input_name => $errors ){
							foreach( $errors as $error ){
								echo "<p><a href='#{$input_name}'>{$error['message']}</a></p>";
							}
						}
						echo '</div>';
					}
					
					if( $this->config['debug'] == true ){
						pre($this->errors, 'bootstrap3_output errors');
					}
				}
				?>
				<input type="hidden" name="form_name" value="<?php echo $this->config['form_name']; ?>" />
			<?php
				/**
				 * Adicionar input:hidden do contexto
				 * 
				 */
				foreach( $this->context as $k => $v ){
					echo "<input type='hidden' name='{$k}' value='{$v}' />\n";
				}
				
				echo "<div class='{$this->config['fieldset_parent_class']}'>";
				foreach( $this->elements as $box ){
					$parent    = $box['id'];
					$itens     = $box['itens'];
					$box_class = isset($box['class']) ? "group_container {$box['class']}" : 'group_container';
					
					echo "<fieldset id='{$parent}' class='{$box_class}'>";
					
						// descrição
						if( isset($box['title']) ){
							if( isset($box['title']) and !empty($box['title']) ) echo "<legend>{$box['title']}</legend>";
							if( isset($box['desc']) and !empty($box['desc']) ) echo "{$box['desc']} <hr />";
						}
						
						foreach( $itens as $item ){
							$data_value = null;
							
							// adicionar os erros guardados
							if( isset($this->errors[$item['name']]) and $this->config['show_errors'] == true ){
								$item['errors'] = $this->errors[$item['name']];
							}
							
							// se estiver vazio, usar o valor padrão
							//if( empty( $data_value ) and isset( $item['std']) ) $data_value = $item['std'];
							
							$data_value = $this->reload_input_value( $item );
							
							// o parent é a ID do box
							$this->context['group'] = $box['id'];
							if( empty($item['layout']) ){
								$item['layout'] = 'bootstrap4';
							}
							create_form_elements( $this->context, $item, $data_value, $this->context['group'] );
						}
						
						// info help de rodapé
						if( isset($box['help']) and !empty($box['help']) ){
							echo "<div class='col-md-12'>{$box['help']}</div>";
						}
						
					echo '</fieldset>';
				}
				echo '</div>';
			?>
			</form>
			<?php
		}
	}
	
	/**
	 * output bootstrap4
	 * 
	 * 
	 * @todo fazer o output da lista de errors, com ancoras e opcional
	 */
	function bootstrap4_output( $form_name ){
		if( $this->form_name == $form_name ){
			$this->create_numeric_username();
			
			// class css
			$class = isset($this->config['class']) ? $this->config['class'] : '';
			if( !empty( $this->errors ) ){
				$class .= ' form_error';
			}
			if( isset($this->messages['success'])){
				$class .= ' form_success';
			}
			
			/**
			 * Mensagem de login requerido
			 * 
			 */
			if( $this->config['login_required'] == true and !is_user_logged_in() ){
				?>
				<div class="<?php echo $class; ?>" id="<?php echo $form_name; ?>">
					<?php echo $this->config['messages']['login_required']['message']; ?>
				</div>
				<?php
				return;
			}
			
			/**
			 * Formulário liberado
			 * 
			 */
			?>
			<form action="<?php $this->create_form_action(); ?>" method="post" class="<?php echo $class; ?>" id="<?php echo isset($this->config['form_id']) ? $this->config['form_id'] : $form_name; ?>" <?php echo $this->config['enctype']; ?>>
				<?php $this->show_messages(); ?>
				<?php
				/**
				 * Mensagens de erro gerais. Este bloco pode exibir uma mensagem de erro geral, podendo exibir mensagens com âncoras.
				 * 
				 */
				if( !empty( $this->errors ) ){
					echo "<div class='alert alert-error alert-danger'>{$this->config['messages']['error']}</div>";
					
					if( $this->config['show_errors_index'] == true ){
						echo '<div class="alert alert-error alert-danger">';
						foreach( $this->errors as $input_name => $errors ){
							foreach( $errors as $error ){
								echo "<p><a href='#{$input_name}'>{$error['message']}</a></p>";
							}
						}
						echo '</div>';
					}
					
					if( $this->config['debug'] == true ){
						pre($this->errors, 'bootstrap3_output errors');
					}
				}
				?>
				<input type="hidden" name="form_name" value="<?php echo $this->config['form_name']; ?>" />
			<?php
				/**
				 * Adicionar input:hidden do contexto
				 * 
				 */
				foreach( $this->context as $k => $v ){
					echo "<input type='hidden' name='{$k}' value='{$v}' />\n";
				}
				
				echo '<div class="row">';
				foreach( $this->elements as $index => $box ){
					$parent    = $box['id'];
					$itens     = $box['itens'];
					$box_class = isset($box['class']) ? "group_container {$box['class']}" : 'group_container';

                    if( isset($box['before']) && !empty($box['before']) ){
                        echo $box['before'];
                    }
					
					echo "<fieldset class='{$box_class} fieldset form-fieldset' id='{$parent}-{$index}'><div class='form-row'>";
					
						// descrição
						if( isset($box['title']) ){
							if( isset($box['title']) and !empty($box['title']) ) echo "<legend>{$box['title']}</legend>";
							if( isset($box['desc']) and !empty($box['desc']) ) echo "{$box['desc']} <hr />";
						}
						
						foreach( $itens as $item ){

                            if( isset($item['skip_output']) && $item['skip_output'] == true ){
                                continue;
                            }

							$data_value = null;
							
							// adicionar os erros guardados
							if( isset($this->errors[$item['name']]) and $this->config['show_errors'] == true ){
								$item['errors'] = $this->errors[$item['name']];
							}
							
							// se estiver vazio, usar o valor padrão
							//if( empty( $data_value ) and isset( $item['std']) ) $data_value = $item['std'];
							
							$data_value = $this->reload_input_value( $item );
							
							// o parent é a ID do box
							$this->context['group'] = $box['id'];
							if( empty($item['layout']) ){
								$item['layout'] = 'bootstrap3';
							}
							create_form_elements( $this->context, $item, $data_value, $this->context['group'] );
						}
						
						// info help de rodapé
						if( isset($box['help']) and !empty($box['help']) ){
							echo "<div class='col-md-12'>{$box['help']}</div>";
						}
						
					echo '</div></fieldset>';

                    if( isset($box['after']) && !empty($box['after']) ){
                        echo $box['after'];
                    }
				}
				echo '</div>';
			?>
			</form>
			<?php
		}
	}
	
    /**
     * output bootstrap5
     * 
     */
    function bootstrap5_output( $form_name ){
        if( $this->form_name == $form_name ){
            $this->create_numeric_username();
            
            // class css
            $form_class = ['container'];
            if( isset($this->config['class']) ){
                $form_class[] = $this->config['class'];
            }
            if( !empty( $this->errors ) ){
                $form_class[] = 'form_error';
            }
            if( isset($this->messages['success'])){
                $form_class[] = 'form_success';
            }
            
            /**
             * Mensagem de login requerido
             * 
             */
            if( $this->config['login_required'] == true and !is_user_logged_in() ){
                ?>
                <div class="<?php echo $form_class; ?>" id="<?php echo $form_name; ?>">
                    <?php echo $this->config['messages']['login_required']['message']; ?>
                </div>
                <?php
                return;
            }
            
            /**
             * Formulário liberado
             * 
             */
            ?>
            <form action="<?php $this->create_form_action(); ?>" method="post" class="<?php echo implode(' ', $form_class); ?>" id="<?php echo isset($this->config['form_id']) ? $this->config['form_id'] : $form_name; ?>" <?php echo $this->config['enctype']; ?>>
                <?php $this->show_messages(); ?>
                <?php
                /**
                 * Mensagens de erro gerais. Este bloco pode exibir uma mensagem de erro geral, podendo exibir mensagens com âncoras.
                 * 
                 */
                if( !empty( $this->errors ) ){
                    echo "<div class='alert alert-error alert-danger'>{$this->config['messages']['error']}</div>";
                    
                    if( $this->config['show_errors_index'] == true ){
                        echo '<div class="alert alert-error alert-danger">';
                        foreach( $this->errors as $input_name => $errors ){
                            foreach( $errors as $error ){
                                echo "<p><a href='#{$input_name}'>{$error['message']}</a></p>";
                            }
                        }
                        echo '</div>';
                    }
                    
                    if( $this->config['debug'] == true ){
                        pre($this->errors, 'bootstrap5_output errors');
                    }
                }
                ?>
                <input type="hidden" name="form_name" value="<?php echo $this->config['form_name']; ?>" />
            <?php
            /**
             * Adicionar input:hidden do contexto
             * 
             */
            foreach( $this->context as $k => $v ){
                echo "<input type='hidden' name='{$k}' value='{$v}' />\n";
            }
            
            foreach( $this->elements as $index => $box ){
                $parent    = $box['id'];
                $itens     = $box['itens'];
                $box_class = isset($box['class']) ? "row {$box['class']}" : 'row';

                if( isset($box['before']) && !empty($box['before']) ){
                    echo $box['before'];
                }
                
                echo "<div class='{$box_class}' id='{$parent}-{$index}'>";
                
                    // descrição
                    if( isset($box['title']) ){
                        if( isset($box['title']) and !empty($box['title']) ){
                            echo "<div class='section-header col-md-12'>";
                                echo "<div class='section-title'>{$box['title']}</div>";
                                if( isset($box['desc']) and !empty($box['desc']) ){
                                    echo "<div class='section-description'>{$box['desc']}</div>";
                                }
                            echo "</div>";
                        }
                    }
                    
                    foreach( $itens as $item ){

                        if( isset($item['skip_output']) && $item['skip_output'] == true ){
                            continue;
                        }

                        $data_value = null;
                        
                        // adicionar os erros guardados
                        if( isset($this->errors[$item['name']]) and $this->config['show_errors'] == true ){
                            $item['errors'] = $this->errors[$item['name']];
                        }
                        
                        // se estiver vazio, usar o valor padrão
                        //if( empty( $data_value ) and isset( $item['std']) ) $data_value = $item['std'];
                        
                        $data_value = $this->reload_input_value( $item );
                        
                        // o parent é a ID do box
                        $this->context['group'] = $box['id'];
                        if( empty($item['layout']) ){
                            $item['layout'] = 'bootstrap3';
                        }
                        create_form_elements( $this->context, $item, $data_value, $this->context['group'] );
                    }
                    
                    // info help de rodapé
                    if( isset($box['help']) and !empty($box['help']) ){
                        echo "<div class='col-md-12'>{$box['help']}</div>";
                    }
                    
                echo '</div>';

                if( isset($box['after']) && !empty($box['after']) ){
                    echo $box['after'];
                }
            }
            ?>
            </form>
            <?php
        }
    }
	
	/**
	 * ==================================================
	 * MÉTODOS AUXILIARES ===============================
	 * ==================================================
	 * Métodos para rotinas simples
	 * 
	 */
	 
	/**
	 * Criar as urls de redirecionamento
	 * 
	 */
	function get_redirect_url( $code = 'success' ){
		if( $this->config['redirect_on_sucess'] !== false ){
			$url = $this->config['redirect_on_sucess'];
			if( isset($this->config['messages'][$code]['name']) and isset($this->config['messages'][$code]['value']) ){
				$url = add_query_arg( $this->config['messages'][$code]['name'], $this->config['messages'][$code]['value'], $url );
			}
		}
		// fallback caso esta function tenha sido chamada sem ter o 'redirect_on_sucess' definido
		else{
			$url = $this->self_url;
		}
		
		/**
		 * Adicionar as variáveis que poderão ser usadas para a filtragem
		 * 
		 */
		$args = array(
			'form_name'   => $this->form_name,
			'new_post_id' => $this->new_post_id,
			'user_id'     => $this->user_id,
            'valid_data'  => $this->valid_data,
            'valid_meta'  => $this->valid_meta,
		);
		return apply_filters( 'boros_frontend_form_redirect_url', $url, $args );
	}
	
}



