<?php
/**
 * APENAS PARA O JOB 3Minovação
 * 
 * 
 * 
 */

class BFE_z_3m_related_user_data extends BorosFormElement {
	function set_attributes(){} // resetar esse método
	
	function set_input( $value = null ){
		if( in_category('Pergunte ao Consultor') or get_post_type() == 'ideia' ){
			global $post;
			$user_id = get_post_meta( $post->ID, 'related_user', true );
			// anônimo
			if( $user_id == 0 ){
				$name = get_post_meta( $post->ID, 'nome', true );
				$email = get_post_meta( $post->ID, 'email', true );
				$telefone = get_post_meta( $post->ID, 'telefone', true );
				$profile = 'Formulário enviado por não usuário:';
			}
			// user
			else{
				$user = get_userdata( $user_id );
				$first_name = get_user_meta( $user_id, 'first_name', true );
				$last_name = get_user_meta( $user_id, 'last_name', true );
				$name = "{$first_name} {$last_name}";
				$email = $user->user_email;
				$telefone = get_user_meta( $user_id, 'telefone', true );
				$profile_link = admin_url( '/user-edit.php?user_id=' . $user_id );
				$profile = "Formulário enviado pelo usuário <a href='{$profile_link}'>{$name}</a> (user ID: {$user_id})";
			}
			
			
			$input = "
			<table>
				<tr>
					<td colspan='2'>{$profile}</td>
				</tr>
				<tr>
					<td>Nome:</td>
					<td>{$name}</td>
				</tr>
				<tr>
					<td>E-mail:</td>
					<td>{$email}</td>
				</tr>
				<tr>
					<td>Telefone:</td>
					<td>{$telefone}</td>
				</tr>
			</table>
			";
		}
		else{
			$input = 'Nenhum usuário está relacionado à este post.';
		}
		return $input;
	}
}