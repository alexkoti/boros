<?php
/**
 * APENAS PARA O JOB 3Minovação
 * 
 * 
 * 
 */

class BFE_z_3m_td_users extends BorosFormElement {
	function set_attributes(){} // resetar esse método
	
	function set_input( $value = null ){
		global $post;
		ob_start();
		
		/**
		 * Informações
		 * 
		 */
		$td_status = get_post_meta( $post->ID, 'td_status', true );
		$td_users_queue = get_post_meta( $post->ID, 'td_users_queue', true );
		$td_users_accepted = get_post_meta( $post->ID, 'td_users_accepted', true );
		
		//pre($td_users_queue, 'FILA');
		//pre($td_users_accepted, 'APROVADOS');
		
		/**
		 * APROVADOS
		 * 
		 */
		$total_accepted = empty($td_users_accepted) ? 0 : count($td_users_accepted);
		echo "<h4 style='margin:0'>Aprovados ({$total_accepted})</h4>";
		if( empty($td_users_accepted) ){
			echo '<p>Sem usuários aprovados.</p>';
		}
		else{
			echo '<p>Usuários aprovados para o test drive. Clique nos nomes dos usuários(abrirá em outra janela) para editar/remover o usuário deste test drive.</p>';
			if( !empty($td_users_accepted) ){
				echo '<table>';
				foreach( $td_users_accepted as $user_id ){
					$user = get_user_by( 'id', $user_id );
					$link = admin_url("user-edit.php?user_id={$user_id}");
					$first_name = get_user_meta( $user_id, 'first_name', true );
					$last_name = get_user_meta( $user_id, 'last_name', true );
					
					// verificar se respondeu ou não o questionário
					$questions_status = '';
					$user_questions = get_user_meta( $user_id, "td_answers_{$post->ID}", true );
					if( empty($user_questions) ){
						if( $td_status == 'archived' )
							$questions_status = '<span style="color:red;">usuário não respondeu ao questionário</span>';
					}
					else{
						$questions_status = "<span class='td_questions_lightbox' data-td_id='{$post->ID}' data-user_id='{$user_id}' data-action='td_view_questions'>ver respostas para o questionário</span>";
					}
					
					$questions_url = get_permalink( get_page_ID_by_name('test-drive-questionario') );
					echo '<tr>';
						echo "<td><a href='{$link}' target='_blank'>{$first_name} {$last_name}</a></td>";
						echo "<td>{$questions_status} (<a href='{$questions_url}?td_id={$post->ID}&user_id={$user_id}' target='_blank'>editar repostas</a>)</td>";
					echo '</tr>';
				}
				echo '</table>';
				echo "<div id='td_questions_lightbox_content' class='td_lightbox_content'></div>";
			}
		}
		
		/**
		 * FILA
		 * 
		 */
		if( $td_status != 'archived' ){
			$total_queue = empty($td_users_queue) ? 0 : count($td_users_queue);
			echo "<h4 style='border-top:1px solid #dfdfdf;margin:30px 0 0;padding:10px 0 0;'>Fila de aprovação ({$total_queue})</h4>";
			if( empty($td_users_queue) ){
				echo '<p>Sem usuários na fila de aprovação.</p>';
			}
			else{
				echo '<p>Usuários registrados na fila de aprovação. Clique nos nomes dos usuários(abrirá em outra janela) para conferir os dados de cada usuário e realizar a aprovação.</p>';
				if( !empty($td_users_queue) ){
					echo '<ol>';
					foreach( $td_users_queue as $user_id ){
						$user = get_user_by( 'id', $user_id );
						$link = admin_url("user-edit.php?user_id={$user_id}");
						$first_name = get_user_meta( $user_id, 'first_name', true );
						$last_name = get_user_meta( $user_id, 'last_name', true );
						echo "<li><a href='{$link}' target='_blank'>{$first_name} {$last_name}</a></li>";
					}
					echo '</ol>';
				}
			}
		}
		
		/**
		 * REVIEWS
		 * 
		 */
		if( $td_status == 'archived' ){
			echo "<h4 style='border-top:1px solid #dfdfdf;margin:30px 0 0;padding:10px 0 0;'>Review selecionados</h4>";
			echo "<p><span class='td_reviews_lightbox' data-td_id='{$post->ID}' data-action='td_manage_reviews'>Escolher e ordenar os reviews para exibição no site</span></p>";
			echo "<div id='td_reviews_lightbox_content' class='td_lightbox_content'></div>";
		}
		
		echo "<h4 style='border-top:1px solid #dfdfdf;margin:30px 0 0;padding:10px 0 0;'>Downloads</h4>";
		if( !empty($td_users_queue) or !empty($td_users_accepted) ){
			$args = array(
				'action' => 'td_download_users',
				'td_id' => $post->ID,
			);
			$link = add_query_arg( $args, admin_url('admin-ajax.php') );
			echo "<p>Usuários do test drive: <a href='{$link}' target='_blank' class='button'>download de todos(aprovados e fila)</a></p>";
			
			$args = array(
				'action' => 'td_download_users',
				'filter' => 'not_answered',
				'td_id' => $post->ID,
			);
			$link = add_query_arg( $args, admin_url('admin-ajax.php') );
			echo "<p>Usuários que ainda não responderam o questionário: <a href='{$link}' target='_blank' class='button'>download de todos que não responderam</a></p>";
			
			$args = array(
				'action' => 'td_download_answers',
				'td_id' => $post->ID,
				'version' => 'xls',
			);
			$link_xls = add_query_arg( $args, admin_url('admin-ajax.php') );
			$args = array(
				'action' => 'td_download_answers',
				'td_id' => $post->ID,
				'version' => 'html',
			);
			$link_html = add_query_arg( $args, admin_url('admin-ajax.php') );
			echo "<p>Questionários: Fazer download dos questionários deste test drive em <a href='{$link_xls}' target='_blank' class='button'>XLS</a> ou <a href='{$link_html}' target='_blank' class='button'>HTML</a></p>";
		}
		
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}

