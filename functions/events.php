<?php
/**
 * ==================================================
 * FERRAMENTA DE EVENTOS ============================
 * ==================================================
 * 
 * 
 */

/**
 * ==================================================
 * GENERAL CONFIG ===================================
 * ==================================================
 * 
 * @todo reformular esta function, para incorporar mais opçoes globais de cada job. Atualmente está com o registro do post_type, se é preciso verificar dados extras e a página padrão de inscrição(opcional)
 * 
 */
function bev_events_config(){
	$args = apply_filters( 'bev_events_config', array() );
	
	$defaults = array(
		'post_type' => 'post',
		'verify_missing_data' => true,
		'form_signin_page' => 'inscricao-evento',
	);
	
	return boros_parse_args( $defaults, $args );
}

function bev_is_valid_event( $post_id ){
	$config = bev_events_config();
	$event = get_post( $post_id );
	if( is_null($event) )
		return false;
	if( $event->post_type != $config['post_type'] )
		return false;
	
	return true;
}


/**
 * ==================================================
 * SLOTS / VAGAS ====================================
 * ==================================================
 * 
 * 
 */

/**
 * VAGAS DISPONÍVEIS
 * 
 */
function bev_slots_available( $post_id ){
	$bev_slots = get_post_meta( $post_id, 'bev_slots', true );
	$bev_users_queue = get_post_meta( $post_id, 'bev_users_queue', true );
	$bev_users_accepted = get_post_meta( $post_id, 'bev_users_accepted', true );
	
	//pre($bev_slots, 'bev_slots');
	//pre($bev_users_queue, 'bev_users_queue');
	//pre($bev_users_accepted, 'bev_users_accepted');
	
	/**
	 * Contabilizar quantos usuários estão ocupando vagas:
	 * usuários na fila + usuários aceitos
	 * Quando um usuário é aceito, ele é apenas movido de grupo, sem interferir no total de vagas disponíveis. Apenas quando um usuário é rejeitado é que a vaga é novamente disponibilizada.
	 * 
	 */
	// primeiro verificar se não está vazio
	$bev_users_queue_count = empty($bev_users_queue) ? 0 : count($bev_users_queue);
	$bev_users_accepted_count = empty($bev_users_accepted) ? 0 : count($bev_users_accepted);
	$bev_slots_available = ($bev_slots - $bev_users_queue_count - $bev_users_accepted_count);
	
	//pre($bev_users_queue_count, 'bev_users_queue_count');
	//pre($bev_users_accepted_count, 'bev_users_accepted_count');
	//pre($bev_slots_available, 'bev_slots_available');
	
	return $bev_slots_available;
}

/**
 * Retornar todos os usuários relacionados ao evento, incluindo queue, accepted e removed
 * 
 * @todo: rever para usar os diferentes arrays
 */
function bev_users( $post_id ){
	$bev_users_queue = get_post_meta( $post_id, 'bev_users_queue', true );
	$bev_users_accepted = get_post_meta( $post_id, 'bev_users_accepted', true );
	$bev_users_removed = get_post_meta( $post_id, 'bev_users_removed', true );
	$bev_users_canceled = get_post_meta( $post_id, 'bev_users_canceled', true );
	
	$bev_users = array_merge( (array)$bev_users_accepted, (array)$bev_users_queue, (array)$bev_users_removed, (array)$bev_users_canceled );
	return $bev_users;
}

/**
 * MENSAGENS DE VAGAS DISPONÍVEIS
 * 
 */
function bev_slots_message( $post_id ){
	$n = bev_slots_available( $post_id );
	
	if( $n == 0 ){
		$message = get_post_meta( $post_id, 'bev_slots_message_zero', true );
	}
	elseif( $n == 1 ){
		$message = get_post_meta( $post_id, 'bev_slots_message_one', true );
	}
	else{
		$message = str_replace( '#', $n, get_post_meta( $post_id, 'bev_slots_message_many', true ) );
	}
	return $message;
}

/**
 * Verificar status do evento
 * SEMPRE VERIFICA OS SLOTS DISPONÍVEIS e reajusta o status caso necessário
 * 
 */
add_action( 'save_post', 'bev_status', 999 );
function bev_status( $post_id = null ){
	if( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) ){
		return $post_id;
	}
	
	if( $post_id == null ){
		return $post_id;
	}
	
	$p = get_post($post_id);
	if( $p->post_status == 'auto-draft' or $p->post_type == 'revision' ){
		return $post_id;
	}
	
	$bev_status = get_post_meta($post_id, 'bev_status', true);
	$bev_slots = get_post_meta( $post_id, 'bev_slots', true );
	$bev_slots_available = bev_slots_available( $post_id );
	$bev_users_queue = get_post_meta( $post_id, 'bev_users_queue', true );
	$bev_users_accepted = get_post_meta( $post_id, 'bev_users_accepted', true );
	$bev_users = array_merge( (array)$bev_users_accepted, (array)$bev_users_queue );
	$total_users = count($bev_users);
	
	// impedir que marque o status como aberto caso não existam vagas disponíveis
	if( $bev_slots_available <= 0 and $bev_status == 'open' ){
		$message = array(
			'type' => 'error',
			'code' => 'bev_status',
			'message' => 'Não foi possível modificar o status para aberto porque não existem mais vagas disponíveis.',
		);
		add_post_meta( $post_id, 'bev_admin_message', $message, false );
		update_post_meta( $post_id, 'bev_status', 'closed' );
		return 'closed';
	}
	
	// corrigir quantidade de vagas caso esteja menor que a quantidade de users cadastrados(fila e aprovados)
	if( $total_users > $bev_slots ){
		$message = array(
			'type' => 'error',
			'code' => 'bev_status',
			'message' => 'A quantidade de vagas foi automaticamente ajustada para abrigar os usuários cadastrados.',
		);
		update_post_meta( $post_id, 'bev_slots', $total_users );
		add_post_meta( $post_id, 'bev_admin_message', $message, false );
	}
	
	// por último, verificar a data do evento
	bev_verify_date_limit();
	
	//if( $bev_slots_available >= 1 and $bev_status == 'closed' ){
	//	$message = array(
	//		'type' => 'alert',
	//		'code' => 'bev_status',
	//		'message' => 'Não foi possível modificar o status para fechado porque ainda existem vagas disponíveis.',
	//	);
	//	add_post_meta( $post_id, 'bev_admin_message', $message, false );
	//	update_post_meta( $post_id, 'bev_status', 'open' );
	//	return 'open';
	//}
	return $bev_status;
}

function bev_verify_date_limit(){
	date_default_timezone_set('America/Sao_Paulo');
	$query = array(
		'post_type' => 'evento',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'meta_key' => 'bev_status',
		'meta_value' => 'open',
	);
	$opened_events = new WP_Query();
	$opened_events->query($query);
	if( $opened_events->posts ){
		foreach($opened_events->posts as $p){
			$exp_date = get_post_meta( $p->ID, 'bev_event_date_signin_limit', true );
			$exp = boros_parse_args( array('year' => '0000', 'month' => '1', 'day' => '1', 'hour' => '00', 'minute' => '00' ), $exp_date );
			$limit = "{$exp['year']}-{$exp['month']}-{$exp['day']} {$exp['hour']}:{$exp['minute']}";
			
			$todays_date = date("Y-m-d H:i");
			$today = strtotime($todays_date);
			$expiration_date = strtotime($limit);
			if($expiration_date > $today){
				//pal('ainda dá tempo!');
			}
			else{
				$message = array(
					'type' => 'alert',
					'code' => 'bev_status',
					'message' => 'A data limite para se inscrever acabou e.o status do evento foi modificado para fechado',
				);
				add_post_meta( $p->ID, 'bev_admin_message', $message, false );
				update_post_meta( $p->ID, 'bev_status', 'closed' );
			}
		}
	}
}



/**
 * Filtro do input_helper de vagas restantes
 * 
 */
function bev_metabox_show_slots_avaiable( $input_helper, $value, $context ){
	$bev_slots_available = bev_slots_available( $context['post_id'] );
	$input_helper = str_replace( '#', $bev_slots_available, $input_helper );
	return $input_helper;
}




/**
 * ==================================================
 * TEXTOS ===========================================
 * ==================================================
 * Com verificação de status e textos no formato antigo
 * 
 * $post_id ID do evento
 * $type status|list|read_more - caso seja status, será verificado em qual status está classificado e exibir de acordo
 * 
 * 
 */
function bev_get_text( $post_id, $type, $echo = true ){
	// listagem
	if( $type == 'list' ){
		$bev_text_list = get_post_meta($post_id, 'bev_text_list', true);
		if( !empty($bev_text_list) ){
			$text = apply_filters('the_content', $bev_text_list);
		}
		else{
			$bev_text_archived = get_post_meta($post_id, 'bev_text_archived', true);
			$text = apply_filters('bev_text_archived', $bev_text_archived);
		}
	}
	// leia mais
	elseif( $type == 'read_more' ){
		$bev_text_read_more = get_post_meta($post_id, 'bev_text_read_more', true);
		if( !empty($bev_text_read_more) ){
			$text = apply_filters('the_content', $bev_text_read_more);
		}
		else{
			$test_drive_onde_comprar = get_post_meta($post_id, 'test_drive_onde_comprar', true);
			$text = apply_filters('the_content', $test_drive_onde_comprar);
		}
	}
	// título
	elseif( $type == 'title' ){
		if( is_single() ){
			$text = get_the_title( $post_id );
		}
		else{
			$bev_text_title = get_post_meta($post_id, 'bev_text_title', true);
			$text = apply_filters('the_title', get_post_meta($post_id, 'bev_text_title', true));
			if( empty($text) )
				$text = get_the_title( $post_id );
		}
	}
	// status
	elseif( $type == 'status' ){
		$bev_status = bev_status($post_id);
		$text = apply_filters('the_content', get_post_meta($post_id, "bev_text_{$bev_status}", true));
	}
	
	if( $echo == false )
		return $text;
	echo $text;
}

/**
 * Ajax mostrando o contador de vagas disponíveis e link para o form de inscrição
 * 
 */
add_action( 'wp_ajax_bev_counter', 'ajax_bev_counter' );
add_action( 'wp_ajax_nopriv_bev_counter', 'ajax_bev_counter' );
function ajax_bev_counter(){
	$bev_id = (int)$_GET['bev_id'];
	echo bev_counter( $bev_id );
	die();
}
function bev_counter( $bev_id ){
	$bev_config = boros_events_config();
	$bev_status = bev_status( $bev_id );
	$bev_slots_available = bev_slots_available( $bev_id );
	$query_args = array(
		'bev_id' => $bev_id,
		'time' => time(), // para evitar cache
	);
	$form_link = add_query_arg( $query_args, get_permalink( $bev_config['form_signin_page'] ) );
	$message = bev_slots_message($bev_id);
	
	// apenas mostrar esse item caso o evento não esteja arquivado
	if( $bev_status != 'archived' ){
		if( $bev_slots_available > 0 ){
			return "<a href='{$form_link}' class='bev_form_link'>{$message}</a>";
		}
		else{
			return "{$message}";
		}
	}
	else{
		return "evento encerrado";
	}
}



/**
 * ==================================================
 * USERS ============================================
 * ==================================================
 * 
 * 
 */

/**
 * Verificar se o usuário foi inscrito no evento pedido
 * 
 */
function bev_signin_status( $bev_id, $user_id ){
	$user = get_user_by( 'id', $user_id );
	$bev_users_queue = get_post_meta( $bev_id, 'bev_users_queue', true );
	$bev_users_accepted = get_post_meta( $bev_id, 'bev_users_accepted', true );
	//pre($user_id, 'user_id');
	//pre($bev_users_accepted, 'bev_accepted');
	//pre($bev_users_queue, 'bev_queue');
	
	if( in_array( $user_id, (array)$bev_users_accepted ) ){
		$status = 'accepted';
	}
	elseif( in_array( $user_id, (array)$bev_users_queue ) ){
		$status = 'in_queue';
	}
	else{
		$status = 'not_accepted';
	}
	
	return $status;
}

function bev_profile_page_user_id(){
	global $current_user;
	get_currentuserinfo();
	if( isset($_POST['user_id']) ){
		$user_id = $_POST['user_id'];
	}
	elseif( isset($_GET['user_id']) ){
		$user_id = $_GET['user_id'];
	}
	else{
		$user_id = $current_user->ID;
	}
	
	//pre($user_id);
	//pre($user_id);
	//pre($user_id);
	//pre($user_id);
	//pre($user_id);
	return $user_id;
}

/**
 * VERIFICAR USUÁRIO NAS LISTAS DE BLOQUEIO
 * 
 * ex: 92864643634
 * 
 */
function bev_verify_blocked_user( $user_id ){
	$bev_blocked_email = (array)get_option('bev_blocked_email');
	$bev_blocked_cpf = (array)get_option('bev_blocked_cpf');
	
	$user = get_user_by( 'id', $user_id );
	$user_email = $user->user_email;
	$user_cpf = get_user_meta( $user_id, 'cpf', true );
	//pre($user_email, 'user_email');
	//pre($user_cpf, 'user_cpf');
	//pre($bev_blocked, 'bev_blocked');
	//pre($bev_blocked_email, 'bev_blocked_email');
	//pre($bev_blocked_cpf, 'bev_blocked_cpf');
	
	if( !empty($bev_blocked_email) and !empty($user_email) and in_array($user_email, $bev_blocked_email) ){
		$status = array(
			'status' => 'blocked',
			'type' => 'email',
			'message' => "Acesso bloqueado: email está na lista de bloqueados.",
		);
	}
	elseif( in_array($user_cpf, $bev_blocked_cpf) and !empty($user_cpf) ){
		$status = array(
			'status' => 'blocked',
			'type' => 'cpf',
			'message' => "Acesso bloqueado: CPF está na lista de bloqueados.",
		);
	}
	elseif( boros_validation_is_cpf_valid( $user_cpf ) == false and !empty($user_cpf) ){
		$status = array(
			'status' => 'blocked',
			'type' => 'cpf',
			'message' => "Acesso bloqueado: CPF inválido.",
		);
	}
	
	/**
	 * Nesse momento o usuário já existe, então ao menos um usuário já está registrado com esse cpf. Essa verificação é feita para certificar que uma pessoa não se cadastre com CPF único e 
	 * depois modifique para um que já esteja sendo usado.
	 * 
	 */
	elseif( bev_verify_cpf_count( $user_cpf ) > 1 and !empty($user_cpf) ){
		$status = array(
			'status' => 'blocked',
			'type' => 'cpf',
			'message' => "Acesso bloqueado: Este CPF já está sendo usado por outro usuário.",
		);
	}
	else{
		$status = array(
			'status' => 'clean',
			'type' => 'both',
			'message' => "Acesso permitido: Usuário está liberado para se inscrever nos eventos.",
		);
	}
	return $status;
}

/**
 * VALIDATE CPFs
 * Callbacks de BorosValidation() - contexto formulário frontend
 * 
 */
function bev_verify_cpf_count( $cpf, $user_id = false ){
	$query_args = array(
		'meta_key' => 'cpf',
		'meta_value' => $cpf,
		'meta_compare' => '=',
		'exclude' => $user_id, // remover usuário corrente, caso determinado, por exemplo na edição de profile
	);
	$users_with_cpf = new WP_User_Query( $query_args );
	
	$count = count($users_with_cpf->results);
	return $count;
}

/**
 * Como não é necessário fazer um in_array em $bev_users_removed, o fato deste ser um array associativo não interfere na verificação
 * 
 */
function bev_user_status_in_event( $user_id, $bev_id ){
	$user = get_user_by( 'id', $user_id );
	$bev_users_queue = get_post_meta( $bev_id, 'bev_users_queue', true );
	$bev_users_accepted = get_post_meta( $bev_id, 'bev_users_accepted', true );
	$bev_users_removed = get_post_meta( $bev_id, 'bev_users_removed', true );
	$bev_users_canceled = get_post_meta( $bev_id, 'bev_users_canceled', true );
	
	//pre($user_id, 'user_id');
	//pre($bev_id, 'bev_id');
	//pre($bev_users_queue, 'bev_users_queue');
	//pre($bev_users_accepted, 'bev_users_accepted');
	//pre($bev_users_removed, 'bev_users_removed');
	//pre($bev_users_canceled, 'bev_users_canceled');
	
	// já aceito
	if( in_array( $user_id, (array)$bev_users_accepted ) ){
		$status = 'accepted';
	}
	// na fila
	elseif( in_array( $user_id, (array)$bev_users_queue ) ){
		$status = 'in_queue';
	}
	// na lista de removidos
	elseif( in_array( $user_id, (array)$bev_users_removed ) ){
		$status = 'removed';
	}
	// na lista de cancelados
	elseif( in_array( $user_id, (array)$bev_users_canceled ) ){
		$status = 'canceled';
	}
	/**
	// na lista de removidos - verificar se foi o admin quem removeu
	elseif( array_key_exists( $user_id, (array)$bev_users_removed ) ){
		// removido pelo admin, não permitir recadastro
		if( $bev_users_removed[$user_id] == 'by_admin' ){
			$status = 'removed';
		}
		// cancelado pelo usuário, permitir recadastro
		else{
			$status = 'not_accepted';
		}
	}
	/**/
	// ainda não aceito
	else{
		$status = 'not_accepted';
	}
	
	return $status;
}

function bev_validate_email_blocked( $name, $value, $args, $message ){
	$bev_blocked = (array)get_option('bev_blocked');
	$bev_blocked_email = (array)get_option('bev_blocked_email');
	//pre($value);
	//pre($bev_blocked);
	//pre($bev_blocked_email);
	
	if( in_array( $value, $bev_blocked_email ) or in_array( $value, $bev_blocked ) ){
		$validation = $args['object'];
		$error = array(
			'name' => $name,
			'message' => $message,
			'type' => 'error'
		);
		$validation->data_errors[$name][$args['rule']] = $error;
	}
	return $value;
}

function bev_validate_email_unique( $name, $value, $args, $message ){
	$user_with_email = get_user_by('email', $value);
	//pre($user_with_email);
	if( $user_with_email != false ){
		$validation = $args['object'];
		if( $validation->context['type'] == 'frontend' ){
			$error = array(
				'name' => $name,
				'message' => $message,
				'type' => 'error'
			);
			$validation->data_errors[$name][$args['rule']] = $error;
		}
	}
	return $value;
}

/**
 * Verificar se faltam dados extras para serem pedidos
 * @todo possibilitar personlizar os dados básicos
 */
function bev_user_verify_required_data( $user_id, $bev_id ){
	$missing = array();
	
	// verificar dados básicos
	$bev_basic_info = array(
		'first_name',
		'last_name',
		'sexo',
		'telefone',
		'data_nascimento',
	);
	foreach( $bev_basic_info as $info ){
		$value = get_user_meta( $user_id, $info, true );
		if( empty($value) ){
			$missing[] = $info;
		}
	}
	
	// verificar dados extras do evento em particular
	$bev_extra_info = get_post_meta( $bev_id, 'bev_extra_info', true );
	if( !empty($bev_extra_info) ){
		foreach( $bev_extra_info as $info ){
			$name = sanitize_title( $info['bev_question_label'] );
			$value = get_user_meta( $user_id, $name, true );
			if( empty($value) ){
				$missing[] = $name;
			}
		}
	}
	return $missing;
}

// pegar as infos básicas do user
function bev_user_basic_data( $user_id ){
	$user = get_user_by( 'id', $user_id );
	$user_data = array();
	$user_data['first_name']      = get_user_meta( $user_id, 'first_name', true );
	$user_data['last_name']       = get_user_meta( $user_id, 'last_name', true );
	$user_data['full_name']       = "{$user_data['first_name'] } {$user_data['last_name'] }";
	$user_data['display_name']    = $user->data->display_name;
	$user_data['email']           = $user->user_email;
	$user_data['sexo']            = get_user_meta( $user_id, 'sexo', true );
	$user_data['telefone']        = get_user_meta( $user_id, 'telefone', true );
	$user_data['data_nascimento'] = get_user_meta( $user_id, 'data_nascimento', true );
	return $user_data;
}

// pegar informações extras conforme o evento
function bev_user_event_data( $user_id, $bev_id ){
	$bev_extra_info = get_post_meta( $bev_id, 'bev_extra_info', true );
	$multi = array('radio', 'checkbox', 'checkbox_group');
	$user_extra = array();
	
	//pal($user_id, '$user_id');
	//pal($bev_id, '$bev_id');
	//pre($bev_extra_info, '$bev_extra_info');
	
	if( !empty($bev_extra_info) ){
		foreach( $bev_extra_info as $info ){
			$name = sanitize_title( $info['bev_question_label'] );
			$meta = get_user_meta( $user_id, $name, true );
			
			if( !empty($meta) ){
				if( in_array($info['bev_question_type'], $multi) ){
					if( in_array( $info['bev_question_type'], $multi ) ){
						$values = empty($info['bev_question_values']) ? false : explode( "\n", $info['bev_question_values'] );
						$values = array_combine(range(1, count($values)), array_values($values));
						$value = '';
						if( $info['bev_question_type'] == 'checkbox' or $info['bev_question_type'] == 'checkbox_group' ){
							$pre_value = array();
							foreach( $meta as $v ){
								$pre_value[] = $values[$v];
							}
							$value = implode(', ', $pre_value);
						}
						else{
							$value = $values[$meta];
						}
					}
					else{
						// ???? verificar
						$value = $meta;
					}
					$user_extra[$info['bev_question_label']] = $value;
				}
				else{
					$user_extra[$info['bev_question_label']] = $meta;
				}
			}
			else{
				$user_extra[$info['bev_question_label']] = '';
			}
		}
	}
	//pre($user_extra, '$user_extra');
	return $user_extra;
}

// lightbox de informações do usuário
add_action( 'wp_ajax_bev_user_info_lightbox', 'bev_user_info_lightbox' );
function bev_user_info_lightbox(){
	$bev_id = (int)$_GET['bev_id'];
	$user_id = (int)$_GET['user_id'];
	$basic_data = bev_user_basic_data( $user_id );
	$extra_data = bev_user_event_data( $user_id, $bev_id );
	
	//echo "<h3>{$basic_data['full_name']}</h3>";
	//echo '<ol>';
	//echo "<li><p><strong>Sexo:</strong></p><p>{$basic_data['sexo']}</p></li>";
	//echo "<li><p><strong>Telefone:</strong></p><p>{$basic_data['telefone']}</p></li>";
	//echo "<li><p><strong>Data de nascimento:</strong></p><p>{$basic_data['data_nascimento']['dia']}/{$basic_data['data_nascimento']['mes']}/{$basic_data['data_nascimento']['ano']}</p></li>";
	//foreach( $extra_data as $key => $value ){
	//	echo "<li><p><strong>{$key}</strong></p><p>{$value}</p></li>";
	//}
	//echo '<ol>';
	
	
	$user_basic_labels = array(
		'email' => 'Email',
		'sexo' => 'Sexo',
		'telefone' => 'Telefone',
		'data_nascimento' => 'Data de nascimento',
	);
	
	$questions = array();
	
	// código de confirmação
	$code = get_user_meta( $user_id, "bev_code_{$bev_id}", true );
	$questions['Código de confirmação'] = "<strong>{$code}</strong>";
	
	// dados básicos
	$basic_data = bev_user_basic_data($user_id);
	foreach( $user_basic_labels as $name => $label ){
		if( $name == 'data_nascimento' and !empty($basic_data[$name]) ){
			if( is_array($basic_data[$name]) ){
				$questions[$label] = "{$basic_data[$name]['dia']}/{$basic_data[$name]['mes']}/{$basic_data[$name]['ano']}";
			}
			else{
				$questions[$label] = $basic_data[$name];
			}
		}
		elseif( $name == 'telefone' and !empty($basic_data[$name]) ){
			if( is_array($basic_data[$name]) ){
				$questions[$label] = "({$basic_data[$name]['ddd']}) {$basic_data[$name]['telefone']}";
			}
			else{
				$questions[$label] = $basic_data[$name];
			}
		}
		elseif( !empty($basic_data[$name]) ){
			$questions[$label] = $basic_data[$name];
		}
	}
	
	// dados extras configurados pelo montador de questões
	$multi = array('radio', 'checkbox');
	if( !empty($extra_data) ){
		foreach( $extra_data as $label => $answer ){
			//pre($answer, $label);
			//if( in_array( $info['bev_question_type'], $multi ) ){
			//	$values = empty($info['bev_question_values']) ? false : explode( "\n", $info['bev_question_values'] );
			//	$values = array_combine(range(1, count($values)), array_values($values));
			//	
			//	$value = '';
			//	if( $info['bev_question_type'] == 'checkbox' ){
			//		foreach( $meta[$name] as $v ){
			//				$value .= "{$values[$v]}, ";
			//		}
			//	}
			//	else{
			//		$value = $values[$meta[$name]];
			//	}
			//}
			if( is_array($answer) ){
				$answer = implode(' ', $answer);
			}
			$questions[$label] = $answer;
		}
	}
	
	// filtro para dados adicionais, configurados pelo formulário fixo
	$additional_info = apply_filters( 'bev_user_info_lightbox_data', array(), $user_id );
	if( !empty($additional_info) ){
		foreach($additional_info as $label => $answer){
			if( is_array($answer) ){
				$answer = implode(' ', $answer);
			}
			$questions[$label] = $answer;
		}
	}
	
	echo "<h3><strong>{$basic_data['full_name']}</strong></h3><table>";
	foreach( $questions as $label => $answer ){
		echo "<tr><td width='250'>{$label}</td><td>{$answer}</td></tr>";
	}
	echo '</table>';
	
	$bev_accepted = get_user_meta( $user_id, 'bev_accepted' );
	if( !empty($bev_accepted) ){
		echo '<h4>Outros eventos que participou:</h4>';
		echo '<ol>';
		foreach( $bev_accepted as $event ){
			$e = get_post($event);
			echo "<li>{$e->post_title}</li>";
		}
		echo '</ol>';
	}
	
	die();
}


/**
 * ==================================================
 * ADICIONAR O USUÁRIO AO EVENTO ====================
 * ==================================================
 * Callback do form bev_signin
 * 
 */
function bev_signin( $args ){
	//global $current_user;
	//get_currentuserinfo();
	
	$add = new BevDriveAddOrRemoveUser( $args['bev_id'], $args['user_id'] );
	$add->queue_user();
	
	//pre( $add->messages );
}

/**
 * Adicionar usuário à um evento
 * Sempre será adicionado à lista accepted. Caso o usuário esteja na lista queue(espera) será movido.
 * 
 * 
 * @TODO >>>>>>>>>>>>> REMOVER USUÁRIO DA LISTA DE APROVADOS CASO TENHA SIDO REMOVIDAS INFOS NECESSÁRIAS
 */
class BevDriveAddOrRemoveUser {
	var $config;
	var $bev_id;
	var $bev;
	var $user_id;
	var $user_profile_complete = true;
	var $user_status;
	var $bev_slots_available;
	var $bev_users_queue;
	var $bev_users_accepted;
	var $messages;
	
	function __construct( $bev_id, $user_id ){
		$this->config = bev_events_config();
		
		$this->bev_id = $bev_id;
		$this->bev = get_post($this->bev_id);
		$this->user_id = $user_id;
		$this->bev_slots_available = bev_slots_available( $bev_id );
		
		$bev_users_queue = get_post_meta( $bev_id, 'bev_users_queue', true );
		$this->bev_users_queue = empty($bev_users_queue) ? array() : $bev_users_queue;
		$bev_users_accepted = get_post_meta( $bev_id, 'bev_users_accepted', true );
		$this->bev_users_accepted = empty($bev_users_accepted) ? array() : $bev_users_accepted;
		
		if( $this->config['verify_missing_data'] == true ){
			$this->verify_user_profile();
		}
		$this->verify_blocked_users();
		
		//add_action( 'show_bev_user_messages', array($this, 'show_messages' ) );
	}
	
	function add_user(){
		//pre($this->user_profile_complete);
		//pre($this->bev_slots_available, 'bev_slots_available');
		//pre($this->user_id, '$user_id');
		//pre($this->bev_users_queue, '$bev_users_queue');
		//pre($this->bev_users_accepted, '$bev_users_accepted');
		
		// verificar se está na fila. Possível apenas foi via formulário de inscrição, então provavelmente possuirá todos os dados
		if( in_array( $this->user_id, $this->bev_users_queue ) ){
			//pal('na fila!!!!!');
			//$this->add_message( 'alert', 'Usuário já está na fila de espera' );
			$this->manage_user( 'accepted' );
		}
		// verificar se já está nos aprovados, não fazer nada!
		elseif( in_array( $this->user_id, $this->bev_users_accepted ) ){
			//pal('já adicionado!!!');
			//$this->add_message( 'alert', "Usuário já estáva adicionado ao evento <strong>{$this->bev->post_title}</strong>" );
			$this->manage_user( 'accepted' );
		}
		// adição direta
		else{
			if( $this->bev_slots_available > 0 and $this->user_profile_complete == true ){
				//pal('adicionado!!!!');
				//$this->add_message( 'sucess', "Usuário adicionado ao evento <strong>{$this->bev->post_title}</strong>" );
				$this->manage_user( 'accepted' );
			}
			else{
				// provavelmente esse passo não será executado no fluxo normal
				if( $this->bev_slots_available <= 0 ){
					//pal('vagas esgotadas');
					$this->add_message( 'error', "Vagas esgotadas para o evento <strong>{$this->bev->post_title}</strong>" );
				}
				elseif( $this->user_profile_complete == false ){
					//pal('perfil incompleto');
					$this->add_message( 'error', "Perfil incompleto! É preciso que o usuário possua todos os dados necessários preenchidos. evento <strong>{$this->bev->post_title}</strong>" );
				}
			}
		}
		
		$this->save_messages();
	}
	
	function queue_user(){
		$bev_slots_available = bev_slots_available( $this->bev_id );
		if( $bev_slots_available > 0 or in_array( $this->user_id, $this->bev_users_accepted ) ){
			$this->manage_user( 'queue' );
		}
		
		$this->save_messages();
	}
	
	function add_message( $type, $msg ){
		$this->messages[$type][] = $msg;
	}
	
	function save_messages(){
		// $this->messages já é um array com diversas mensagens, portanto não é preciso adicionar mais que um user_meta
		update_user_meta( $this->user_id, 'bev_messages', $this->messages );
	}
	
	// sempre verificar se o usuário possui os dados completos
	function verify_user_profile(){
		$missing_data = bev_user_verify_required_data( $this->user_id, $this->bev_id );
		//pre($missing_data);
		
		if( empty($missing_data) )
			$this->user_profile_complete = true;
		else
			$this->user_profile_complete = false;
	}
	
	function manage_user( $to ){
		if( $this->user_status['status'] == 'blocked' ){
			$this->add_message( 'error', $this->user_status['message'] );
			// normalmente essa remoção não é necessária, já que provavelmete o usuário já não estará nessa lista
			bev_move_user( $this->bev_id, $this->user_id, 'remove' );
			return;
		}
		
		$to_list   = ( $to == 'queue' ) ? $this->bev_users_queue : $this->bev_users_accepted;
		$list_name = ( $to == 'queue' ) ? 'lista de espera' : 'lista de aprovados';
		
		/**
		 * Primeira parte: profile completo. Mover se for preciso
		 * 
		 */
		if( $this->user_profile_complete == true ){
			// usuário já está ja lista desejada :: não precisa mostrar mensagem
			if( in_array($this->user_id, $to_list) ){
				//$this->add_message( 'alert', "APROVADO: já estava na {$list_name}" );
			}
			else{
				// poderá mover tanto para os aprovados como para a fila
				$this->add_message( 'success', "SUCESSO: usuário adicionado para {$list_name}" );
				bev_move_user( $this->bev_id, $this->user_id, $to );
			}
		}
		/**
		 * Segunda parte: profile incompleto. Remover se for preciso
		 * 
		 */
		else{
			// usuário já está ja lista desejada, porém não possui os dados completos, mover de volta para a fila
			if( in_array($this->user_id, $to_list) ){
				$this->add_message( 'error', "REMOVIDO: o usuário já estava na {$list_name}, mas não possui todos os dados requeridos. Foi movido automaticamente para a fila de aprovação" );
				bev_move_user( $this->bev_id, $this->user_id, 'queue' );
			}
			else{
				$this->add_message( 'error', "REPROVADO: não possui todos os dados requeridos." );
			}
		}
	}
	
	function verify_blocked_users(){
		$this->user_status = bev_verify_blocked_user( $this->user_id );
		//pre($this->user_status);
	}
	
	function remove_user(){
		bev_move_user( $this->bev_id, $this->user_id, 'remove' );
		$this->add_message( 'success', "Usuário removido do evento <strong>{$this->bev->post_title}</strong>" );
		$this->save_messages();
	}
	
	function cancel_user(){
		bev_move_user( $this->bev_id, $this->user_id, 'cancel' );
		$this->add_message( 'success', "Inscrição do usuário cancelada para <strong>{$this->bev->post_title}</strong>" );
		$this->save_messages();
	}
}

add_action( 'init', 'bev_verify_cancel' );
function bev_verify_cancel(){
	global $current_user;
	get_currentuserinfo();
	
	if( isset($_GET['ecancel']) and isset($_GET['eid']) and isset($_GET['user']) ){
		// verificar se o usuário está tentando remover ele mesmo ou se é um admin
		if( ($current_user->ID == $_GET['user']) or current_user_can('manage_options') ){
			bev_move_user( $_GET['eid'], $_GET['user'], 'cancel' );
			wp_redirect( get_permalink($_GET['eid']) );
			exit;
		}
	}
}

/**
 * FUNCTION GLOBAL PARA MOVER USUÁRIOS ENTRE AS LISTAS
 * Considera-se que nesse ponto o usuário já foi verificado em relação às condições de aprovação
 * 
 * @param int $bev_id ID do evento para qual o usuário deverá ser associado
 * @param int $user_id ID do usuário
 * @param string $to - fila para qual o usuário deve ir ( 'accepted', 'remove', 'queue', 'cancel' )
 * 
 */
function bev_move_user( $bev_id, $user_id, $to = 'accepted' ){
	$bev = get_post($bev_id);
	$new_user_email_notify_user = get_option('new_user_email_notify_user');
	/**
	 * É preciso transformar as variáveis de fila( $bev_users_queue e $bev_users_accepted ) em array vazios(competamente) caso não existam no bacno, ou seja, sem nenhum inscrito.
	 * 
	 */
	$bev_users_queue_meta = get_post_meta( $bev_id, 'bev_users_queue', true );
	$bev_users_queue = empty($bev_users_queue_meta) ? array() : $bev_users_queue_meta;
	
	$bev_users_accepted_meta = get_post_meta( $bev_id, 'bev_users_accepted', true );
	$bev_users_accepted = empty($bev_users_accepted_meta) ? array() : $bev_users_accepted_meta;
	
	$bev_users_removed_meta = get_post_meta( $bev_id, 'bev_users_removed', true );
	$bev_users_removed = empty($bev_users_removed_meta) ? array() : $bev_users_removed_meta;
	
	$bev_users_canceled_meta = get_post_meta( $bev_id, 'bev_users_canceled', true );
	$bev_users_canceled = empty($bev_users_canceled_meta) ? array() : $bev_users_canceled_meta;
	
	//pre($user_id, 'user_id');
	//pre($bev_users_queue, 'bev_users_queue');
	//pre($bev_users_accepted, 'bev_users_accepted');
	
	// remover - é adicionado o contexto da remoção, se foi realizado pelo user ou pelo admin
	// ao remover o usuário um slot será re-habilitado ao evento
	if( $to == 'remove' ){
		$bev_users_queue = array_diff( $bev_users_queue, array($user_id) ); // remover user da fila
		$bev_users_accepted = array_diff( $bev_users_accepted, array($user_id) ); // remover user dos aceitos
		$bev_users_canceled = array_diff( $bev_users_canceled, array($user_id) ); // remover user dos cancelados
		if( !in_array($user_id, $bev_users_removed) ){
			$bev_users_removed[] = $user_id; // adicionar aos removidos, caso ainda não esteja
		}
		$position = array_search($user_id, $bev_users_removed);
	}
	// remover de 'queue'(se estiver), adicionar em 'accepted'
	elseif( $to == 'accepted' ){
		$bev_users_queue = array_diff( $bev_users_queue, array($user_id) ); // remover user da fila
		if( !in_array($user_id, $bev_users_accepted) ){
			$bev_users_accepted[] = $user_id;
		}
		$position = array_search($user_id, $bev_users_accepted);
		
		// adicionar user_meta com a id do evento - será útil para resgatar os eventos que participou - SOMENTE APROVADOS
		$bev_accepted = get_user_meta( $user_id, 'bev_accepted' );
		if( !in_array($bev_id, $bev_accepted) )
			add_user_meta( $user_id, 'bev_accepted', $bev_id );
	}
	// adicionar em 'queue'
	elseif( $to == 'queue' ){
		// remover user dos aceitos caso esteja nela - remoção de item simples
		$bev_users_accepted = array_diff( $bev_users_accepted, array($user_id) );
		// tirar da lista dos removidos caso esteja nela - será usado no caso do usuário ser re-adicionado
		$bev_users_removed = array_diff( $bev_users_removed, array($user_id) );
		$bev_users_canceled = array_diff( $bev_users_canceled, array($user_id) );
		if( !in_array($user_id, $bev_users_queue) ){
			$bev_users_queue[] = $user_id;
		}
		$position = array_search($user_id, $bev_users_queue);
	}
	// adicionar em 'canceled'
	elseif( $to == 'cancel' ){
		$bev_users_accepted = array_diff( $bev_users_accepted, array($user_id) );
		$bev_users_queue = array_diff( $bev_users_queue, array($user_id) );
		$bev_users_removed = array_diff( $bev_users_removed, array($user_id) );
		if( !in_array($user_id, $bev_users_canceled) ){
			$bev_users_canceled[] = $user_id; 
		}
		$position = array_search($user_id, $bev_users_canceled);
	}
	
	//pre($bev_users_accepted, 'bev_users_accepted');
	//pre($bev_users_queue, 'bev_users_queue');
	//pre($bev_users_removed, 'bev_users_removed');
	//pre($bev_users_canceled, 'bev_users_canceled');
	// salvar novas filas
	update_post_meta( $bev_id, 'bev_users_queue', $bev_users_queue );
	update_post_meta( $bev_id, 'bev_users_accepted', $bev_users_accepted );
	update_post_meta( $bev_id, 'bev_users_removed', $bev_users_removed );
	update_post_meta( $bev_id, 'bev_users_canceled', $bev_users_canceled );
	
	/**
	 * Salvar user metas: id do evento que participou e código de confirmação
	 * 
	 */
	$bev_signin_code = bev_create_signin_code( $bev_id, $user_id, $position );
	update_user_meta( $user_id, "bev_code_{$bev_id}", $bev_signin_code );
	// adicionar user_meta com a id do evento - será útil para resgatar os eventos que participou - MESMO NÃO TENDO SIDO APROVADO
	$bev_participated = get_user_meta( $user_id, 'bev_participated' );
	if( !in_array($bev_id, $bev_participated) )
		add_user_meta( $user_id, 'bev_participated', $bev_id );
	
	/**
	 * Notificar usuário por email caso seja COLOCADO NA FILA
	 * 
	 */
	if( $to == 'queue' and $new_user_email_notify_user == true ){
		$user = get_user_by( 'id', $user_id );
		$first_name = get_user_meta( $user_id, 'first_name', true );
		$last_name = get_user_meta( $user_id, 'last_name', true );
		$full_name = "{$first_name} {$last_name}";
		
		$admin_email = get_bloginfo('admin_email');
		$email = $user->data->user_email;
		
		//$title = get_option( 'bev_email_signin_title' );
		$title = 'Inscrição em aprovação';
		
		$headers = array(
			'from' => get_bloginfo('name'),
			'from' => $admin_email,
		);
		
		$message = apply_filters( 'the_content', get_option( 'bev_signin_queue' ) );
		$message = str_replace( '[NOME]', $full_name, $message );
		$message = apply_filters( 'bev_email_base', $message, $bev_id, $user_id );
		//pre( $user );
		//pal($email,'$email');
		//pal($message,'$message');
		//pal($full_name,'$full_name');
		//pal('tentativa de envio de email');
		wp_mail( $email, $title, $message, $headers );
		wp_mail( $admin_email, "[Cópia] {$title} : {$full_name}", $message, $headers );
	}
	
	/**
	 * Notificar usuário por email caso seja APROVADO
	 * 
	 */
	if( $to == 'accepted' and $new_user_email_notify_user == true ){
		$user = get_user_by( 'id', $user_id );
		$first_name = get_user_meta( $user_id, 'first_name', true );
		$last_name = get_user_meta( $user_id, 'last_name', true );
		$full_name = "{$first_name} {$last_name}";
		
		$admin_email = get_bloginfo('admin_email');
		$email = $user->data->user_email;
		
		$title = get_option( 'bev_email_signin_title' );
		
		$headers = array(
			'from' => get_bloginfo('name'),
			'from' => $admin_email,
		);
		
		$message = apply_filters( 'the_content', get_option( 'bev_email_signin_text' ) );
		$message = str_replace( '[NOME]', $full_name, $message );
		$message = apply_filters( 'bev_email_base', $message, $bev_id, $user_id );
		//pre( $user );
		//pal($email,'$email');
		//pal($message,'$message');
		//pal($full_name,'$full_name');
		//pal('tentativa de envio de email');
		wp_mail( $email, $title, $message, $headers );
		wp_mail( $admin_email, "[Cópia] {$title} : {$full_name}", $message, $headers );
	}
	
	/**
	 * Notificar usuário por email caso seja CANCELADO pelo usuário
	 * 
	 */
	if( $to == 'cancel' and $new_user_email_notify_user == true ){
		$user = get_user_by( 'id', $user_id );
		$first_name = get_user_meta( $user_id, 'first_name', true );
		$last_name = get_user_meta( $user_id, 'last_name', true );
		$full_name = "{$first_name} {$last_name}";
		
		$title = "O usuário {$full_name} cancelou a inscrição para o evento {$bev->post_title}";
		
		$admin_email = get_bloginfo('admin_email');
		$user_email = $user->data->user_email;
		
		$headers = array(
			'from' => get_bloginfo('name'),
			'from' => $email,
		);
		
		$message = "O usuário {$full_name} cancelou a inscrição para o evento {$bev->post_title}";
		$message = apply_filters( 'bev_email_base', $message, $bev_id, $user_id );
		//pre($email, 'email');
		//pre($headers, 'headers');
		//pre($title, 'title');
		//pre($message, 'message');
		//pal('tentativa de envio de email');
		wp_mail( $user_email, $title, $message, $headers );
		wp_mail( $admin_email, "[Cópia] {$title} : {$full_name}", $message, $headers );
	}
	
	/**
	 * Notificar usuário por email caso seja REMOVIDO/REJEITADO pelo administrador
	 * 
	 */
	if( $to == 'remove' and $new_user_email_notify_user == true ){
		$user = get_user_by( 'id', $user_id );
		$first_name = get_user_meta( $user_id, 'first_name', true );
		$last_name = get_user_meta( $user_id, 'last_name', true );
		$full_name = "{$first_name} {$last_name}";
		
		$admin_email = get_bloginfo('admin_email');
		$email = $user->data->user_email;
		
		//$title = get_option( 'bev_email_signin_title' );
		$title = "{$full_name}, a sua inscrição foi recusada";
		
		$headers = array(
			'from' => get_bloginfo('name'),
			'from' => $admin_email,
		);
		
		$message = apply_filters( 'the_content', get_option( 'bev_signin_not_approved' ) );
		$message = str_replace( '[NOME]', $full_name, $message );
		$message = apply_filters( 'bev_email_base', $message, $bev_id, $user_id );
		//pre( $user );
		//pal($email,'$email');
		//pal($message,'$message');
		//pal($full_name,'$full_name');
		//pal('tentativa de envio de email');
		wp_mail( $email, $title, $message, $headers );
		wp_mail( $admin_email, "[Cópia] {$title} : {$full_name}", $message, $headers );
	}
}

/**
 * Criar código da inscrição
 * 
 */
function bev_create_signin_code( $bev_id, $user_id, $position = 0, $length = 7 ){
	include_once( BOROS_LIBS . 'alphaID.php' );
	return alphaID( "{$bev_id}{$position}{$user_id}", false, $length );
}
function bev_verify_signin_code( $code, $bev_id, $user_id, $position = 0, $length = 7 ){
	include_once( BOROS_LIBS . 'alphaID.php' );
	$alpha = alphaID( $code, true, $length );
	$original_id = "{$bev_id}{$position}{$user_id}";
	
	if( $alpha == (int)$original_id )
		return true;
	else
		return false;
}

/**
 * Aprovar o usuário via admin
 * 
 */
add_action( 'wp_ajax_bev_user_approve', 'bev_user_approve' );
function bev_user_approve(){
	// adicionar usuário
	$manage_user = new BevDriveAddOrRemoveUser( $_POST['bev_id'], $_POST['user_id'] );
	$manage_user->add_user();
	foreach( $manage_user->messages as $code => $messages ){
		echo "<div class='bev_users_box bev_users_{$code}'>";
		foreach( $messages as $msg ){
			echo "<p>{$msg}</p>";
		}
		echo '</div>';
	}
	// recarregar metabox_inside
	bev_users_inside( $_POST['bev_id'], true );
	die();
}

/**
 * Desaprovar usuário
 * 
 */
add_action( 'wp_ajax_bev_user_queue', 'bev_user_queue' );
function bev_user_queue(){
	// desaprovar usuário usuário - colocar na fila
	$manage_user = new BevDriveAddOrRemoveUser( $_POST['bev_id'], $_POST['user_id'] );
	$manage_user->queue_user();
	foreach( $manage_user->messages as $code => $messages ){
		echo "<div class='bev_users_box bev_users_{$code}'>";
		foreach( $messages as $msg ){
			echo "<p>{$msg}</p>";
		}
		echo '</div>';
	}
	// recarregar metabox_inside
	bev_users_inside( $_POST['bev_id'], true );
	die();
}

/**
 * Remover usuário - admin
 * 
 */
add_action( 'wp_ajax_bev_user_remove', 'bev_user_remove' );
function bev_user_remove(){
	// remover usuário usuário
	$manage_user = new BevDriveAddOrRemoveUser( $_POST['bev_id'], $_POST['user_id'] );
	$manage_user->remove_user();
	foreach( $manage_user->messages as $code => $messages ){
		echo "<div class='bev_users_box bev_users_{$code}'>";
		foreach( $messages as $msg ){
			echo "<p>{$msg}</p>";
		}
		echo '</div>';
	}
	// recarregar metabox_inside
	bev_users_inside( $_POST['bev_id'], true );
	die();
}

/**
 * Cancelar inscrição - admin
 * 
 */
add_action( 'wp_ajax_bev_user_cancel', 'bev_user_cancel' );
function bev_user_cancel(){
	// remover usuário usuário
	$manage_user = new BevDriveAddOrRemoveUser( $_POST['bev_id'], $_POST['user_id'] );
	$manage_user->cancel_user();
	foreach( $manage_user->messages as $code => $messages ){
		echo "<div class='bev_users_box bev_users_{$code}'>";
		foreach( $messages as $msg ){
			echo "<p>{$msg}</p>";
		}
		echo '</div>';
	}
	// recarregar metabox_inside
	bev_users_inside( $_POST['bev_id'], true );
	die();
}

/**
 * Recarregar o contador de vagas após mover usuários
 * 
 */
add_action( 'wp_ajax_reload_box_bev_slots', 'reload_box_bev_slots' );
function reload_box_bev_slots(){
	pre($_POST);
	die();
}



/**
 * ==================================================
 * META BOXES =======================================
 * ==================================================
 * 
 * 
 */
class BFE_bev_alerts extends BorosFormElement {
	function set_attributes(){} // resetar esse método
	
	function add_defaults(){
		$this->defaults['options']['show_shortcode'] = true;
	}
	
	function set_input( $value = null ){
		global $post;
		ob_start();
		
		$bev_status = bev_status( $post->ID );
		
		$msgs = array();
		
		if( $this->data['options']['show_shortcode'] == true ){
			$msgs[] = "<li class='ok'>shortcode para inserir o botão do evento na nos conteúdos: <input type='text' value='[evento id={$post->ID}]' size='14' readonly='readonly' onclick='this.select()' /></li>";
		}
		
		/**
		 * Mensagens gravadas
		 * 
		 */
		$bev_admin_message = get_post_meta( $post->ID, 'bev_admin_message' );
		if( count($bev_admin_message) > 0 ){
			foreach( $bev_admin_message as $message ){
				$msgs[] = "<li class='{$message['type']}'>{$message['message']}</li>";
			}
			delete_post_meta( $post->ID, 'bev_admin_message' );
		}
		
		/**
		 * Verificar a contagem de vagas
		 * 
		 */
		$bev_slots_available = bev_slots_available( $post->ID );
		$bev_slots = get_post_meta( $post->ID, 'bev_slots', true );
		$bev_users_queue = get_post_meta( $post->ID, 'bev_users_queue', true );
		$bev_users_accepted = get_post_meta( $post->ID, 'bev_users_accepted', true );
		$bev_users_queue_count = empty($bev_users_queue) ? 0 : count($bev_users_queue);
		$bev_users_accepted_count = empty($bev_users_accepted) ? 0 : count($bev_users_accepted);
		if( $bev_slots_available < 0 ){
			$msgs[] = " <li class='error'>Foi detectado que existem mais pessoas inscritas que vagas disponíveis: <br />
						vagas: <strong>{$bev_slots}</strong> <br />
						fila: <strong>{$bev_users_queue_count}</strong> <br />
						aceitos: <strong>{$bev_users_accepted_count}</strong>
						</li>";
		}
		elseif( $bev_slots_available == 0 ){
			$msgs[] = "<li class='alert'>Vagas esgotadas.</li>";
		}
		elseif( $bev_slots_available > 0 and ($bev_status == 'closed' or $bev_status == 'archived') ){
			$msgs[] = " <li class='error'>O evento está fechado/arquivado, porém ainda existem vagas disponíveis: <br />
						vagas: <strong>{$bev_slots}</strong> <br />
						fila: <strong>{$bev_users_queue_count}</strong> <br />
						aceitos: <strong>{$bev_users_accepted_count}</strong>
						</li>";
		}
		
		/**
		 * Verificar o status
		 * 
		 */
		if( $bev_status == 'archived' and $bev_slots_available > 0 ){
			$msgs[] = '<li class="error">O evento está arquivado, porém ainda existem vagas disponíveis.</li>';
		}
		
		/**
		 * Verificar se o formulário de inscrição está habilitado
		 * 
		 */
		$bev_form_active = get_post_meta( $post->ID, 'bev_form_active', true );
		if( empty($bev_form_active) ){
			if( $bev_slots_available > 0 and $bev_status != 'open' )
				$msgs[] = '<li class="alert">O formulário de inscrição está desativado, porém existem vagas disponíveis.</li>';
			else
				$msgs[] = '<li class="alert">O formulário de inscrição está desativado.</li>';
		}
		
		/**
		 * EXIBIR MENSAGENS
		 * 
		 */
		if( !empty($msgs) ){
			echo '<p class="bev_messages_captions"><span class="error">alerta, verificar</span> | <span class="alert">aviso simples</span> | <span class="ok">mensagem de confirmação</span></p>';
			echo '<ol class="bev_messages">';
			foreach( $msgs as $msg )
				echo $msg;
			echo '</ol>';
		}
		else{
			echo '<span class="bev_messages_captions"><span class="ok">Sem mensagens.</span></span>';
		}
		
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}

class BFE_bev_users extends BorosFormElement {
	function set_attributes(){} // resetar esse método
	
	function set_input( $value = null ){
		global $post;
		ob_start();
		
		bev_users_inside( $post->ID, false );
		
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}

/**
 * APAGAR DADOS EXTRAS DOS USUÁRIOS
 * NÃO PODE SER DESFEITO!!!
 * 
 */
add_action( 'wp_ajax_bev_erase_extra_user_info', 'bev_erase_extra_user_info' );
function bev_erase_extra_user_info(){
	$bev_id = $_POST['bev_id'];
	$users = bev_users($bev_id);
	$bev_extra_info = get_post_meta( $bev_id, 'bev_extra_info', true );
	foreach( $users as $user_id ){
		foreach( $bev_extra_info as $name => $info ){
			$meta = delete_user_meta( $user_id, sanitize_title($info['bev_question_label']) );
		}
	}
	update_post_meta( $bev_id, 'bev_extra_user_data_deleted', true );
	
	// recarregar metabox_inside
	bev_users_inside( $_POST['bev_id'], true );
	die();
}

function bev_users_inside( $post_id, $display_loading = false ){
	/**
	 * Informações
	 * 
	 */
	$bev_status = get_post_meta( $post_id, 'bev_status', true );
	$bev_users_queue = get_post_meta( $post_id, 'bev_users_queue', true );
	$bev_users_accepted = get_post_meta( $post_id, 'bev_users_accepted', true );
	$bev_users_removed = get_post_meta( $post_id, 'bev_users_removed', true );
	$bev_users_canceled = get_post_meta( $post_id, 'bev_users_canceled', true );
	
	$bev_extra_user_data_deleted = get_post_meta( $post_id, 'bev_extra_user_data_deleted', true );
	
	//pre($bev_status, 'STATUS');
	//pre($bev_users_accepted, 'APROVADOS');
	//pre($bev_users_queue, 'FILA');
	//pre($bev_users_removed, 'REMOVIDOS');
	
	/**
	 * APROVADOS
	 * 
	 */
	$total_accepted = empty($bev_users_accepted) ? 0 : count($bev_users_accepted);
	echo "<h4 style='margin:0'>Aprovados ({$total_accepted})</h4>";
	if( empty($bev_users_accepted) ){
		echo '<p>Sem usuários aprovados.</p>';
	}
	else{
		echo '<p>Usuários aprovados para o evento. Clique nos nomes dos usuários(abrirá em outra janela) para editar/remover o usuário deste evento.</p>';
		if( !empty($bev_users_accepted) ){
			echo '<table class="bev_user_table">';
			foreach( $bev_users_accepted as $user_id ){
				$user = get_user_by( 'id', $user_id );
				if($user){
					$link = admin_url("user-edit.php?user_id={$user_id}");
					$code = get_user_meta( $user_id, "bev_code_{$post_id}", true );
					echo '<tr>';
					echo	"<td width='*'><a href='{$link}' target='_blank'>{$user->data->display_name}</a> ({$code})</td>";
					echo	"<td width='100' class='txt_center'><div class='bev_user_notification_email_box'><a href='#' class='bev_user_notification_email' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_notification_email'>enviar email de notificação</a><span class='loading'></span></div></td>";
					echo	"<td width='100' class='txt_center'><a href='#' class='bev_user_info_lightbox' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_info_lightbox'>ver dados</a></td>";
					if( empty($bev_extra_user_data_deleted) ){
						echo 	"<td width='100' class='txt_center'><a href='#' class='bev_user_action' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_queue' style='color:orange;'>desaprovar</a></td>";
						echo 	"<td width='100' class='txt_center'><a href='#' class='bev_user_action' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_cancel' style='color:skyblue ;'>cancelar inscrição</a></td>";
					}
					echo '</tr>';
				}
				else{
					echo "<tr><td>Este usuário não existe mais! ID:{$user_id}</td></tr>";
				}
			}
			echo '</table>';
		}
	}
	
	/**
	 * FILA
	 * 
	 */
	if( $bev_status != 'archived' ){
		$total_queue = empty($bev_users_queue) ? 0 : count($bev_users_queue);
		echo "<h4 style='border-top:1px solid #dfdfdf;margin:30px 0 0;padding:10px 0 0;'>Fila de aprovação ({$total_queue})</h4>";
		if( empty($bev_users_queue) ){
			echo '<p>Sem usuários na fila de aprovação.</p>';
		}
		else{
			echo '<p>Usuários registrados na fila de aprovação.</p>';
			echo '<table class="bev_user_table">';
			foreach( $bev_users_queue as $user_id ){
				$user = get_user_by( 'id', $user_id );
				if($user){
					$link = admin_url("user-edit.php?user_id={$user_id}");
					$code = get_user_meta( $user_id, "bev_code_{$post_id}", true );
					echo '<tr>';
					echo	"<td width='*'><a href='{$link}' target='_blank'>{$user->data->display_name}</a> ({$code})</td>";
					echo 	"<td width='100' class='txt_center'><a href='#' class='bev_user_info_lightbox' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_info_lightbox'>ver dados</a></td>";
					if( empty($bev_extra_user_data_deleted) ){
						echo "<td width='100' class='txt_center'><a href='#' class='bev_user_action' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_approve' style='color:green;'>aprovar</a></td>";
						echo "<td width='100' class='txt_center'><a href='#' class='bev_user_action' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_remove' style='color:red;'>reprovar</a></td>";
						echo "<td width='100' class='txt_center'><a href='#' class='bev_user_action' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_cancel' style='color:skyblue ;'>cancelar inscrição</a></td>";
					}
					echo '</tr>';
				}
				else{
					echo "<tr><td>Este usuário não existe mais! ID:{$user_id}</td></tr>";
				}
			}
			echo '</table>';
		}
	}
	
	/**
	 * REPROVADOS
	 * 
	 */
	$total_removeds = empty($bev_users_removed) ? 0 : count($bev_users_removed);
	echo "<h4 style='border-top:1px solid #dfdfdf;margin:30px 0 0;padding:10px 0 0;'>Reprovados ({$total_removeds})</h4>";
	if( empty($bev_users_removed) ){
		echo '<p>Sem usuários reprovados.</p>';
	}
	else{
		echo '<p>Usuários reprovados.</p>';
		echo '<table class="bev_user_table">';
		foreach( $bev_users_removed as $user_id ){
			$user = get_user_by( 'id', $user_id );
			if($user){
				$link = admin_url("user-edit.php?user_id={$user_id}");
				$code = get_user_meta( $user_id, "bev_code_{$post_id}", true );
				echo '<tr>';
				echo	"<td width='*'><a href='{$link}' target='_blank'>{$user->data->display_name}</a> ({$code})</td>";
				echo	"<td width='100' class='txt_center'><a href='#' class='bev_user_info_lightbox' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_info_lightbox'>ver dados</a></td>";
				if( empty($bev_extra_user_data_deleted) ){
					echo "<td width='100' class='txt_center'><a href='#' class='bev_user_action' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_queue' style='color:orange;'>colocar na fila</a></td>";
					echo "<td width='100' class='txt_center'><a href='#' class='bev_user_action' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_cancel' style='color:skyblue ;'>cancelar inscrição</a></td>";
				}
				echo '</tr>';
			}
			else{
				echo "<tr><td>Este usuário não existe mais! ID:{$user_id}</td></tr>";
			}
		}
		echo '</table>';
	}
	
	/**
	 * CANCELADOS
	 * 
	 */
	$total_canceled = empty($bev_users_canceled) ? 0 : count($bev_users_canceled);
	echo "<h4 style='border-top:1px solid #dfdfdf;margin:30px 0 0;padding:10px 0 0;'>Inscrições canceladas ({$total_canceled})</h4>";
	if( empty($bev_users_canceled) ){
		echo '<p>Nenhuma inscrição foi cancelada.</p>';
	}
	else{
		echo '<p>Inscrições canceladas.</p>';
		echo '<table class="bev_user_table">';
		foreach( $bev_users_canceled as $user_id ){
			$user = get_user_by( 'id', $user_id );
			if($user){
				$link = admin_url("user-edit.php?user_id={$user_id}");
				$code = get_user_meta( $user_id, "bev_code_{$post_id}", true );
				echo '<tr>';
				echo	"<td width='*'><a href='{$link}' target='_blank'>{$user->data->display_name}</a> ({$code})</td>";
				echo	"<td width='100' class='txt_center'><a href='#' class='bev_user_info_lightbox' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_info_lightbox'>ver dados</a></td>";
				if( empty($bev_extra_user_data_deleted) ){ echo "<td width='100' class='txt_center'><a href='#' class='bev_user_action' data-bev_id='{$post_id}' data-user_id='{$user_id}' data-action='bev_user_queue' style='color:orange;'>colocar na fila</a></td>"; }
				echo '</tr>';
			}
			else{
				echo "<tr><td>Este usuário não existe mais! ID:{$user_id}</td></tr>";
			}
		}
		echo '</table>';
	}
	
	/**
	 * DOWNLOADS
	 * 
	 */
	echo "<h4 style='border-top:1px solid #dfdfdf;margin:30px 0 0;padding:10px 0 0;'>Downloads</h4>";
	if( !empty($bev_users_queue) or !empty($bev_users_accepted) or !empty($bev_users_removed) or !empty($bev_users_canceled) ){
		$args = array(
			'action' => 'bev_download_users_xls',
			'bev_id' => $post_id,
		);
		$link_xls = add_query_arg( $args, admin_url('admin-ajax.php') );
		$args = array(
			'action' => 'bev_download_users_html',
			'bev_id' => $post_id,
		);
		$link_html = add_query_arg( $args, admin_url('admin-ajax.php') );
		echo "<p>Usuários do evento: <a href='{$link_xls}' class='button'>XLS</a> ou <a href='{$link_html}' class='button'>HTML</a></p>";
	}
	
	
	/**
	 * APAGAR DADOS EXTRAS DOS USUÁRIOS
	 * 
	 */
	if( $bev_status == 'archived' ){
		if( empty($bev_extra_user_data_deleted) ){
			echo "<h4 style='border-top:1px solid #dfdfdf;margin:30px 0 0;padding:10px 0 0;'>Apagar dados extras dos usuários</h4>";
			echo '<p style="color:red;">ATENÇÃO: esta operação não poderá ser desfeita, e os usuário não poderão ser mais movidos entre as listas.</p>';
			echo "<p><a href='#' class='bev_user_action button button-erase' data-bev_id='{$post_id}' data-action='bev_erase_extra_user_info'>apagar dados extras dos usuários</a></p>";
		}
		else{
			echo '<p style="color:orange;">Os dados extras dos usuários já foram apagados.</p>';
		}
	}
	
	echo "<div id='bev_lightbox_content' class='bev_lightbox_content'></div>";
	$bev_users_loading_display = ($display_loading == false) ? 'display:none;' : 'display:block;';
	echo "<div id='bev_users_loading' style='{$bev_users_loading_display}'></div>";
	
	/**
	 * Se as filas de usuários inscritos e/ou aprovados estiver preenchida, travar form de perguntas
	 * 
	 */
	$lock_bev_extra_info_box = 'false';
	if( !empty($bev_users_accepted) or !empty($bev_users_queue) ){
		$lock_bev_extra_info_box = 'true';
	}
	echo "	<script type='text/javascript'>
				window.lock_bev_extra_info_box = {$lock_bev_extra_info_box};
				jQuery(document).ready(function($){
					// travar perguntas
					if( $('#bev_extra_info_box').lenght > 0 ){
						$('#bev_extra_info_box').lock_extra_info();
					}
				});
			</script>";
	
	/**
	 * Se qualquer uma das filas já estiver preenchida, não deixa apagar o evento, escondendo o botão de lixeira
	 * 
	 * PS: também foi adicionado no CSS a regra ".post-type-evento .trash" para esconder o botão de apagar na listagem
	 */
	if( !empty($bev_users_queue) or !empty($bev_users_accepted) or !empty($bev_users_removed) or !empty($bev_users_canceled) ){
		echo "	<script type='text/javascript'>
					jQuery(document).ready(function($){
						// esconder botão de apagar
						$('#delete-action').hide();
					});
				</script>";
	}
}

/**
 * Enviar/Reenviar manualmente o email de notificação para o usuário
 * 
 */
add_action( 'wp_ajax_bev_user_notification_email', 'bev_user_notification_email' );
function bev_user_notification_email(){
	$user_id = $_POST['user_id'];
	$bev_id = $_POST['bev_id'];
	$user = get_user_by( 'id', $user_id );
	$first_name = get_user_meta( $user_id, 'first_name', true );
	$last_name = get_user_meta( $user_id, 'last_name', true );
	$full_name = "{$first_name} {$last_name}";
	
	$admin_email = get_bloginfo('admin_email');
	$email = $user->data->user_email;
	
	$title = get_option( 'bev_email_signin_title' );
	
	$headers = array(
		'from' => get_bloginfo('name'),
		'from' => $admin_email,
	);
	
	$message = apply_filters( 'the_content', get_option( 'bev_email_signin_text' ) );
	$message = str_replace( '[NOME]', $full_name, $message );
	$message = apply_filters( 'bev_email_base', $message, $bev_id, $user_id );
	
	$user_email_sent = wp_mail( $email, $title, $message, $headers );
	$admin_email_sent = wp_mail( $admin_email, "[Cópia] {$title} : {$full_name}", $message, $headers );
	
	if( $user_email_sent == true ){
		echo "Notificação enviada para o usuário \n";
	}
	else{
		echo "Notificação para o usuário falhou \n";
	}
	if( $admin_email_sent == true ){
		echo "Cópia da notificação enviada para o administrador \n";
	}
	else{
		echo "Cópia da notificação falhou \n";
	}
	
	die();
}

class BFE_bev_users_extra_info_log extends BorosFormElement {
	function set_attributes(){} // resetar esse método
	
	function set_input( $value = null ){
		global $post;
		ob_start();
		
		echo '<p>As informações extras não podem ser editadas quando já existem usuários na fila ou aprovados.</p>';
		echo '<p>Informações extras pedidas aos usuários:</p>';
		echo '<ol>';
		$bev_extra_info = get_post_meta( $post->ID, 'bev_extra_info', true );
		foreach( $bev_extra_info as $info ){
			echo '<li>';
			echo "<strong>{$info['bev_question_label']}</strong>";
			if( $info['bev_question_type'] == 'radio' or $info['bev_question_type'] == 'checkbox' ){
				echo '<br />opções: <br /> - ';
				$values = empty($info['bev_question_values']) ? false : explode( "\n", $info['bev_question_values'] );
				$values = array_combine(range(1, count($values)), array_values($values));
				echo implode('<br /> - ', $values);
			}
			echo '</li>';
		}
		echo '</ol>';
		
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}

class BFE_bev_date_limit extends BorosFormElement {
	/**
	 * Lista de atributos aceitos pelo elemento, e seus respectivos valores padrão.
	 * Caso seja definido qualquer outro atributo no array de configuração ele será ignorado.
	 * Definir qualquer valor padrão ou string vazia(''), irá obrigatoriamente renderizar o atributo, independente do valor. Valor padrão 'false' só irá renderizar o atributo caso ele
	 * seja definido no array de configuração.
	 * 
	 * Atenção: NÃO INCLUIR dataset - este atributo será adicionado em set_elements(), que irá separar os diversos datasets necessários
	 */
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'size' => false,
		'disabled' => false,
		'readonly' => false,
		'maxlength' => false,
	);
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
		$defaults = array(
			'day' => '',
			'month' => '',
			'year' => '',
			'hour' => '00',
			'minute' => '00',
		);
		$value = boros_parse_args( $defaults, $value );
		ob_start();
		?>
		<p>Limite: 
			<input type="text" class="iptw_30 txt_center" value="<?php echo $value['day']; ?>" name="<?php echo $this->data['name']; ?>[day]" /> /
			<input type="text" class="iptw_30 txt_center" value="<?php echo $value['month']; ?>" name="<?php echo $this->data['name']; ?>[month]" /> /
			<input type="text" class="iptw_50 txt_center" value="<?php echo $value['year']; ?>" name="<?php echo $this->data['name']; ?>[year]" />, às 
			<input type="text" class="iptw_30 txt_center" value="<?php echo $value['hour']; ?>" name="<?php echo $this->data['name']; ?>[hour]" /> : 
			<input type="text" class="iptw_30 txt_center" value="<?php echo $value['minute']; ?>" name="<?php echo $this->data['name']; ?>[minute]" /> minutos <br />
			<?php echo $this->input_helper; ?>
		</p>
		<?php
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}



/**
 * ==================================================
 * COLUNAS DO POST TYPE =============================
 * ==================================================
 * 
 * 
 */
	function bev_column_slots_total( $post_type, $post ){
		echo get_post_meta( $post->ID, 'bev_slots', true );
	}

	function bev_column_slots_available( $post_type, $post ){
		echo bev_slots_available($post->ID);
	}

	function bev_column_status( $post_type, $post ){
		$bev_status = bev_status($post->ID);
		switch( $bev_status ){
			case 'closed':
				echo 'fechado';
				break;
			case 'archived':
				echo 'encerrado/arquivado';
				break;
			case 'open':
				echo 'aberto';
				break;
		}
	}



/**
 * ==================================================
 * DOWNLOADS ========================================
 * ==================================================
 * 
 * 
 */
add_action( 'wp_ajax_bev_download_users_xls', 'bev_download_users_xls' );
function bev_download_users_xls(){
	bev_download_users('xls');
}

add_action( 'wp_ajax_bev_download_users_html', 'bev_download_users_html' );
function bev_download_users_html(){
	bev_download_users('html');
}

function bev_download_users( $download_type = 'xls' ){
	require BOROS_LIBS . 'php-excel.class.php';
	$bev_id = (int)$_GET['bev_id'];
	$bev = get_post($bev_id);
	
	// array com todos os usuários, separados por grupo
	$bev_all_users = array();
	$bev_all_users['bev_users_queue']    = (array)get_post_meta( $bev_id, 'bev_users_queue', true );
	$bev_all_users['bev_users_accepted'] = (array)get_post_meta( $bev_id, 'bev_users_accepted', true );
	$bev_all_users['bev_users_removed']  = (array)get_post_meta( $bev_id, 'bev_users_removed', true );
	$bev_all_users['bev_users_canceled'] = (array)get_post_meta( $bev_id, 'bev_users_canceled', true );
	
	//pre($bev_users_queue, 'bev_users_queue');
	//pre($bev_users_accepted, 'bev_users_accepted');
	//pre($bev_users_removed, 'bev_users_removed');
	//pre($bev_users_canceled, 'bev_users_canceled');
	//pre($bev_all_users, 'bev_all_users');
	
	// Headers do excel
	$headers = array();
	// Linhas de dados do excel
	$data = array();
	$html_data = array();
	
	$group_status = array(
		'bev_users_queue'    => 'fila',
		'bev_users_accepted' => 'aprovado',
		'bev_users_removed'  => 'reprovado',
		'bev_users_canceled' => 'cancelado',
		
	);
	$group_labels = array(
		'bev_users_queue'    => 'Usuários na fila de aprovação',
		'bev_users_accepted' => 'Usuários aprovados',
		'bev_users_removed'  => 'Usuários reprovados',
		'bev_users_canceled' => 'Usuários cancelados',
		
	);
	
	$user_basic_info_model = apply_filters( 'bev_download_basic_info', array(
		'full_name' => 'Nome completo',
		'user_email' => 'Email',
		'sexo' => 'Sexo',
		'telefone' => 'Telefone',
		'data_nascimento' => 'Data de nascimento',
	));
	foreach( $user_basic_info_model as $key => $label ){
		$headers[] = $label;
	}
	
	/**
	 * Informações extras criadas pelo montador de questões
	 * 
	 */
	$bev_extra_info = get_post_meta( $bev_id, 'bev_extra_info', true );
	$user_extra_info_model = array();
	if( !empty($bev_extra_info) ){
		foreach( $bev_extra_info as $info ){
			$user_extra_info_model[sanitize_title( $info['bev_question_label'] )] = $info['bev_question_label'];
			$headers[] = $info['bev_question_label'];
		}
	}
	
	/**
	 * Filtro para dados adicionais do formulário fixo
	 * 
	 */
	$additional_user_info_model = apply_filters( 'bev_download_additional_user_info_model', array() );
	if( !empty($additional_user_info_model) ){
		foreach( $additional_user_info_model as $key => $label ){
			$headers[] = $label;
		}
	}
	
	// última coluna: status
	$headers[] = 'Status';
	$headers[] = 'Código de confirmação';
	
	// adicionar header aos dados finais
	$data[] = $headers;
	
	// pegar usuários
	foreach( $bev_all_users as $group => $users ){
		if( !empty($users) ){
			foreach( $users as $user_id ){
				$pre_data = array();
				$user = get_user_by('id', $user_id);
				if($user){
					// dados básicos
					foreach( $user_basic_info_model as $meta_key => $meta_label ){
						if( $meta_key == 'user_email' ){
							$pre_data[] = $user->data->user_email;
						}
						elseif( $meta_key == 'full_name' ){
							$pre_data[] = $user->data->display_name;
						}
						else{
							$meta_value = get_user_meta( $user_id, $meta_key, true );
							if( !empty($meta_value) ){
								if( $meta_key == 'data_nascimento' ){
									if( is_array($meta_value) ){
										$pre_data[] = "{$meta_value['dia']}/{$meta_value['mes']}/{$meta_value['ano']}";
									}
									else{
										$pre_data[] = $meta_value;
									}
								}
								elseif( $meta_key == 'telefone' ){
									if( is_array($meta_value) ){
										$pre_data[] = "({$meta_value['ddd']}) {$meta_value['telefone']}";
									}
									else{
										$pre_data[] = $meta_value;
									}
								}
								else{
									$pre_data[] = $meta_value;
								}
							}
							else{
								$pre_data[] = '';
							}
						}
					}
					
					// dados extras configurados pelo montador de questões
					$extra_data = bev_user_event_data( $user_id, $bev_id );
					if( !empty($extra_data) ){
						foreach( $user_extra_info_model as $key => $label ){
							$pre_data[] = issetor( $extra_data[$label], 'não respondido');
						}
					}
					
					// dados adicionais, configurados pelo formulário fixo
					if( !empty($additional_user_info_model) ){
						foreach( $additional_user_info_model as $key => $label ){
							$answer = get_user_meta( $user_id, $key, true );
							if( !empty($answer) ){
								if( is_array($answer) ){
									$answer = implode(' ', $answer);
								}
								$pre_data[] = $answer;
							}
							else{
								$pre_data[] = '';
							}
						}
					}
					
					// status
					$pre_data[] = $group_status[$group];
					
					// código de cofirmação
					$pre_data[] = get_user_meta( $user_id, "bev_code_{$bev_id}", true );
					
					// dados do XLS
					$data[] = $pre_data;
					// dados do HTML
					$html_data[$group_labels[$group]][$pre_data[0]] = $pre_data;
				}
			}
		}
	}
	
	if( $download_type == 'xls' ){
		$today = date('Y-m-d');
		$xls = new Excel_XML('UTF-8', false, "Usuários {$bev->post_title} {$today}");
		$xls->addArray($data);
		$xls->generateXML("evento_{$bev->post_name}_{$today}_todos_usuarios");
		die();
	}
	elseif( $download_type == 'html' ){
		$labels = $data[0];
		$today = date('Y-m-d');
		$title = "Usuários {$bev->post_title} {$today}";
		$filename = "evento_{$bev->post_name}_{$today}_todos_usuarios";
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Content-type: application/force-download");
		header("Content-Disposition: attachment; filename='{$filename}.html';" );
		header("Content-Transfer-Encoding: binary");
		?>
		<!DOCTYPE HTML>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8">
			<title><?php echo $title; ?></title>
			<style type="text/css">
				body {
					font:12px arial, sans-serif;
					margin:20px;
					padding:0;
				}
				div {
					border-bottom:1px dashed #444;
					padding:10px 0;
				}
				h2 {
					border-bottom:1px dashed #444;
					font-size:20px;
					margin:0 0 10px;
					padding:10px 0;
				}
				h2.list_header {
					margin:30px 0 0;
				}
				h3 {
					font-size:14px;
					margin:0;
				}
				table{
					font-size:12px;
					margin:0;
					width:600px;
				}
				table td {
					padding:0 10px 0 0;
				}
				@media print {
					h2 {
						margin:0 0 10px;
					}
					h2.list_header {
						page-break-before: always;
					}
					div{
						page-break-inside: avoid;
					}
				}
			</style>
		</head>
		<body>
		<?php
		$i = 1;
		echo "<h2>{$title}</h2>";
		foreach( $html_data as $group_label => $users ){
			ksort($users);
			echo "<h3>{$group_label}</h3>";
			echo '<ol>';
			foreach( $users as $user => $questions ){
				echo "<li><a href='#{$i}'>{$user}</a></li>";
				$i++;
			}
			echo '</ol>';
		}
		
		$i = 1;
		foreach( $html_data as $group_label => $users ){
			ksort($users);
			echo "<h2 class='list_header'>{$group_label}</h2>";
			foreach( $users as $user => $questions ){
				//pre($answers, $user);
				echo "<div id='{$i}'><h3><strong>{$user}</strong></h3><table>";
				foreach( $questions as $key => $answer ){
					echo "<tr><td width='250'>{$labels[$key]}</td><td>{$answer}</td></tr>";
				}
				echo '</table></div>';
				$i++;
			}
		}
		?>
		</body>
		</html>
		<?php
		die();
	}
}


