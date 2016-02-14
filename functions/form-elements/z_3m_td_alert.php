<?php
/**
 * APENAS PARA O JOB 3Minovação
 * 
 * 
 * 
 */

class BFE_z_3m_td_alert extends BorosFormElement {
	function set_attributes(){} // resetar esse método
	
	function set_input( $value = null ){
		global $post;
		ob_start();
		
		$td_status = td_status( $post->ID );
		
		$msgs = array();
		
		/**
		 * Mensagens gravadas
		 * 
		 */
		$td_admin_message = get_post_meta( $post->ID, 'td_admin_message' );
		if( count($td_admin_message) > 0 ){
			foreach( $td_admin_message as $message ){
				$msgs[] = "<li class='{$message['type']}'>{$message['message']}</li>";
			}
			delete_post_meta( $post->ID, 'td_admin_message' );
		}
		
		/**
		 * Verificar a contagem de vagas
		 * 
		 */
		$td_slots_avaiable = td_slots_avaiable( $post->ID );
		$td_slots = get_post_meta( $post->ID, 'td_slots', true );
		$td_users_queue = get_post_meta( $post->ID, 'td_users_queue', true );
		$td_users_accepted = get_post_meta( $post->ID, 'td_users_accepted', true );
		$td_users_queue_count = empty($td_users_queue) ? 0 : count($td_users_queue);
		$td_users_accepted_count = empty($td_users_accepted) ? 0 : count($td_users_accepted);
		if( $td_slots_avaiable < 0 ){
			$msgs[] = " <li class='error'>Foi detectado que existem mais pessoas inscritas que vagas disponíveis: <br />
						vagas: <strong>{$td_slots}</strong> <br />
						fila: <strong>{$td_users_queue_count}</strong> <br />
						aceitos: <strong>{$td_users_accepted_count}</strong>
						</li>";
		}
		elseif( $td_slots_avaiable == 0 ){
			$msgs[] = "<li class='alert'>Vagas esgotadas.</li>";
		}
		elseif( $td_slots_avaiable > 0 and ($td_status == 'closed' or $td_status == 'archived') ){
			$msgs[] = " <li class='error'>O test drive está fechado/arquivado, porém ainda existem vagas disponíveis: <br />
						vagas: <strong>{$td_slots}</strong> <br />
						fila: <strong>{$td_users_queue_count}</strong> <br />
						aceitos: <strong>{$td_users_accepted_count}</strong>
						</li>";
		}
		
		
		
		
		
		/**
		 * Verificar o status
		 * 
		 */
		if( $td_status == 'archived' and $td_slots_avaiable > 0 ){
			$msgs[] = '<li class="error">O test-drive está arquivado, porém ainda existem vagas disponíveis.</li>';
		}
		
		/**
		 * Verificar se o formulário de inscrição está habilitado
		 * 
		 */
		$td_slots_active = get_post_meta( $post->ID, 'td_slots_active', true );
		if( empty($td_slots_active) ){
			if( $td_slots_avaiable > 0 and $td_status != 'open' )
				$msgs[] = '<li class="alert">O formulário de inscrição está desativado, porém existem vagas disponíveis.</li>';
			else
				$msgs[] = '<li class="alert">O formulário de inscrição está desativado.</li>';
		}
		
		/**
		 * Verificar questionário
		 * 
		 */
		$td_questions_active = get_post_meta( $post->ID, 'td_questions_active', true );
		if( empty($td_questions_active) and $td_status != 'archived' ){
			$msgs[] = '<li class="alert">O questionário está desativado para os usuários. Será exibida a mensagem de alerta definida no box <strong>Perguntas do Questionário</strong> no campo <em>Mensagem de questionário não disponível</em>.</li>';
		}
		
		
		/**
		 * EXIBIR MENSAGENS
		 * 
		 */
		if( !empty($msgs) ){
			echo '<p class="td_messages_captions"><span class="error">alerta, verificar</span> | <span class="alert">aviso simples</span> | <span class="ok">mensagem de confirmação</span></p>';
			echo '<ol class="td_messages">';
			foreach( $msgs as $msg )
				echo $msg;
			echo '</ol>';
		}
		else{
			echo '<span class="td_messages_captions"><span class="ok">Sem mensagens.</span></span>';
		}
		
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}