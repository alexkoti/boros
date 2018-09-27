<?php
/**
 * FUNÇÕES DE USUÁRIOS
 * Funções específicas para manipulação de dados de usuários.
 * 
 * 
 * 
 * 
 * 
 */



/**
 * ==================================================
 * VERIFICAR SE UM USUÁRIO ESTÁ APROVADO ============
 * ==================================================
 * Filtrar a ação de login do formulário padrão localizado em wp-login.php, barrando a autenticação caso esteja ligado nas opções o bloqueio de usuários
 * Aplicar a action no plugin do trabalho desejado: add_action( 'wp_authenticate_user', 'boros_authenticate_approved_user' );
 * 
 */
function boros_authenticate_approved_user( $userdata ){
	if( is_wp_error($userdata) ){
		return $userdata;
	}
	
	// permitir o login de administradores sem maiores verificações
	if( isset($userdata->caps['administrator']) AND $userdata->caps['administrator'] == true ){
		return $userdata;
	}
	
	// verificar o status do user, caso não possa logar, retornar o erro em vez do acesso normal
	$user_status = boros_verify_approved_user( $userdata->ID );
	if( is_wp_error($user_status) ){
		return $user_status;
	}
	
	return $userdata;
}

function boros_verify_approved_user( $user_id ){
	// verificar usuário caso a opção de login para usuários aprovados esteja ligada
	if( get_option('verify_approved_user') == true ){
		// status do user
		$user_status = get_user_meta( $user_id, 'user_status', true );
		
		// status não definido - sempre verifica se já não é um wp_error
		if( empty($user_status) ){
			$message = apply_filters( 'verify_approved_user_message_default', '<strong>ERRO</strong>: Sua conta precisa ser aprovada antes poder fazer o login no site' );
			return new WP_Error( 'verify_approved_user_default', $message );
		}
		elseif( $user_status == 'disapproved' ){
			$message = apply_filters( 'verify_approved_user_message_disapproved', '<strong>ERRO</strong>: O seu registro não foi aceito!' );
			return new WP_Error( 'verify_approved_user_disapproved', $message );
		}
	}
	return true;
}



/**
 * ==================================================
 * EMAIL LOGIN ======================================
 * ==================================================
 * Substitui o plugin wp-email-login
 * 
 * @http://premium.wpmudev.org/blog/email-login/
 */
//if( !function_exists('dr_email_login_authenticate') ){
//	remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
//	add_filter('authenticate', 'boros_authenticate', 20, 3);
//}

function boros_authenticate($user, $email, $password){
	//Check for empty fields
	if(empty($email) || empty ($password)){
		//create new error object and add errors to it.
		$error = new WP_Error();
		
		if(empty($email)){ //No email
			$error->add('empty_username', '<strong>ERRO</strong>: O campo email está vazio.');
		}
		else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){ //Invalid Email
			$error->add('invalid_username', __('<strong>ERRO</strong>: Este email é inválido.'));
		}
		
		if(empty($password)){ //No password
			$error->add('empty_password', __('<strong>ERRO</strong>: A senha está vazia.'));
		}
		
		return $error;
	}
	
	// verificar por user_login
	$user = get_user_by('login', $email);
	// verificar por email
	if( !$user ){
		$user = get_user_by('email', $email);
	}
	
	if(!$user){
		$error = new WP_Error();
		$error->add('invalid', __('<strong>ERRO</strong>: O email ou senha estão incorretos.'));
		return $error;
	}
	else{ //check password
		if(!wp_check_password($password, $user->user_pass, $user->ID)){ //bad password
			$error = new WP_Error();
			$error->add('invalid', __('<strong>ERRO</strong>: O email ou senha estão incorretos.'));
			return $error;
		}
		else{
			return $user; //passed
		}
	}
}



/**
 * ==================================================
 * USER PROFILE =====================================
 * ==================================================
 * Monstar um array com os dados montados do usuário
 * 
 * @TODO buscar usermetas, montar as propriedades com callback e filtros por NAME
 * 
 */
function boros_get_user_profile( $user_id = 0, $fields = array() ){
	if( is_int($user_id) ){
		if( $user_id == 0 ){
			$user = wp_get_current_user();
		}
		else{
			$user = new WP_User($user_id);
		}
	}
	
	/**
	 * Buscar o full_name primeiro em um user_meta único, senão montar com base em first_name e last_name
	 * 
	 */
	$user->__set( 'full_name', $user->get('full_name') );
	if( empty($user->data->full_name) ){
		$user->__set( 'first_name', $user->get('first_name') );
		$user->__set( 'last_name', $user->get('last_name') );
		$user->__set( 'full_name', "{$user->data->first_name} {$user->data->last_name}" );
	}
	
	/**
	 * Buscar os outros metas opcionais
	 * 
	 */
	if( !empty($fields) ){
		foreach( $fields as $field ){
			$user->__set( $field, $user->get($field) );
		}
	}
	
	//pre($user);
	return $user;
}



/* ========================================================================== */
/* GET USER ROLES =========================================================== */
/* ========================================================================== */
/**
 * Pegar o role do usuário atual
 * ATENÇÃO: funciona apenas para usuários logados. Para não logados, usar get_user_role($user_id);
 * 
 * @return	string $user_role - role do usuário
 * @link	http://wordpress.org/support/topic/get-a-users-role-by-user-id?replies=7
 */
function get_current_user_role(){
	global $current_user;
	wp_get_current_user();
	$user_roles = $current_user->roles;
	$user_role = array_shift($user_roles);
	return $user_role;
}



/**
 * Pegar o role de um usuário específico. Funciona para usuários não logados
 * 
 * @return	string $user_id - id do usuário
 * @return	string $user_role - role do usuário
 */
function get_user_role( $user_id ) {
	global $wpdb;
	$user = get_userdata( $user_id );

	$capabilities = $user->{$wpdb->prefix . 'capabilities'};
	if ( !isset( $wp_roles ) )
		$wp_roles = new WP_Roles();
	
	//pre($user);pre($wp_roles->role_names);

	foreach ( $wp_roles->role_names as $role => $name ){
		if ( array_key_exists( $role, $capabilities ) )
			echo $role;
	}
}

class BorosUserMeta {
	var $meta_boxes;
	var $validation;
	var $errors = array();
	var $context = array(
		'type' => 'user_meta',
	);
	
	function __construct( $config ){
		$this->meta_boxes = update_element_config($config);
		
		add_action( 'show_user_profile', array( $this, 'edit' ) );
		add_action( 'edit_user_profile', array( $this, 'edit' ) );
		add_action( 'profile_update', array( $this, 'save' ) );
		add_filter( 'load_element_config', array($this, 'load_element_config'), 10, 2 );
	}
	
	function edit( $user ){
		foreach( $this->meta_boxes as $block ){
			if( isset($block['title']) )
				echo "<h3 id='{$block['id']}_title'>{$block['title']}</h3>";
			?>
			<table class="form-table boros_form_block boros_options_block" id="<?php echo $block['id'];?>">
				<?php if( isset($block['desc']) ){ ?>
				<td colspan="2" class="boros_form_desc">
					<div><?php echo $block['desc']; ?></div>
				</td>
				<?php } ?>
				
				<?php
				foreach( $block['itens'] as $element ){
					$data_value = null;
					/**
					 * Alguns itens, como taxonomy_radio, que substituem names de inputs core do WordPress, não necessariamente declaram 'name', gerando erro no script.
					 */
					if( isset( $element['name'] ) ){
						$data_value = get_user_meta( $user->ID, $element['name'] ); // chamar o valor gravado para o input
						if( count($data_value) == 1 )
							$data_value = $data_value[0];
					}
					
					// se estiver vazio, usar o valor padrão
					if( empty( $data_value ) and isset( $element['std']) ) $data_value = $element['std'];
					
					//pre($data_value);
					$this->context['user_id'] = $user->ID;
					$this->context['name'] = isset($element['name']) ? $element['name'] : '';
					$this->context['group'] = $block['id'];
					$this->context['in_duplicate_group'] = false;
					//pre($this->context);
					
					// renderizar o elemento
					create_form_elements( $this->context, $element, $data_value );
				}
				?>
				
				<?php if( isset($block['help']) ){ ?>
				<tr>
					<td colspan="2" class="boros_form_extra_info">
						<div>
							<span class="ico"></span> 
							<?php echo $block['help']; ?>
						</div>
					</td>
				</tr>
				<?php } ?>
			</table>
			<?php
		}
	}
	
	function save( $user_id ){
		if ( !current_user_can( 'edit_user', $user_id ) )
			return false;
		
		$context = array(
			'user_id' => $user_id,
			'type' => 'user_meta',
		);
		$this->validation = new BorosValidation( $context );
		
		foreach( $this->meta_boxes as $block ){
			$elements = $block['itens'];
			foreach( $elements as $element ){
				if( isset($element['name']) ){
					$value = isset( $_POST[ $element['name'] ] ) ? $_POST[ $element['name'] ] : false;
					//pre( $value, "{$element['name']} - PRE" );
					
					$this->validation->add( $element );
					$value = $this->validation->verify_user_meta( $user_id, $element, $value );
					//pre( $value, "{$element['name']} - POS" );
					
					if( $value !== false ){
						update_user_meta( $user_id, $element['name'], $value );
					}
					else{
						delete_user_meta( $user_id, $element['name'] );
					}
					
					// callbacks
					if( isset($element['callbacks']) ){
						foreach( $element['callbacks'] as $callback ){
							$callback['args']['value'] = $value;
							call_user_func( $callback['function'], $callback['args'] );
						}
					}
				}
			}
		}
		
		/**
		 * Armazenar erros
		 * Ao contrário do form de frontend por exemplo, os erros gerados pelo form de usuário não impedem a gravação de dados
		 * 
		 */
		$this->errors = array_merge( $this->errors, $this->validation->user_errors );
		// gravar erros
		update_user_meta( $user_id, "user_errors_{$user_id}", $this->errors, true );
	}
	
	function load_element_config( &$config, $context ){
		if( $context['type'] != 'user_meta' ){
			return $config;
        }
		
        $config = $this->meta_boxes;
		if( isset($context['in_duplicate_group']) and $context['in_duplicate_group'] == true ){
			$element_config = $config[$context['group']]['itens'][$context['parent']]['group_itens'][$context['name']];
		}
		else{
			$element_config = $config[$context['group']]['itens'][$context['name']];
        }
		return $element_config;
	}
}

add_action( 'admin_notices', 'boros_user_show_errors' );
function boros_user_show_errors(){
	$screen = get_current_screen();
	if( $screen->id == 'user-edit' or $screen->id == 'profile' ){
		$user_id = boros_user_profile_page_user_id();
		$errors = get_user_meta( $user_id, "user_errors_{$user_id}", true );
		//pre($errors);
		if( !empty($errors) ){
			foreach( $errors as $error ){
				foreach( $error as $message ){
					echo "<div class='error {$message['name']}' rel='{$message['name']}'>";
					echo "<p class='form_error'>{$message['message']}</p>";
					echo "</div>";
				}
			}
		}
		delete_user_meta( $user_id, "user_errors_{$user_id}" );
	}
}

/**
 * Pegar a ID do usuário da página de profile corrente, independente se é a própria ou de outro user.
 * 
 */
function boros_user_profile_page_user_id(){
	global $current_user;
	wp_get_current_user();
	
	if( isset($_POST['user_id']) ){
		$user_id = $_POST['user_id'];
	}
	elseif( isset($_GET['user_id']) ){
		$user_id = $_GET['user_id'];
	}
	else{
		$user_id = $current_user->ID;
	}
	return $user_id;
}







