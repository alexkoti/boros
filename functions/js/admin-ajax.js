/**
 * ADMIN AJAX
 * Todos os ajax de admin
 * 
 * 
 * 
 */
jQuery(document).ready(function($){
	
	
	/**
	 * APROVAR/DESAPROVAR USUÁRIO
	 * Controle usermeta 'approved_user'
	 * 
	 */
	$('.btn_approve_user').click(function(){
		var $btn = $(this);
		var $parent = $btn.closest('.approve_user_actions');
		var $loading = $parent.find('.loading');
		var $user_status = $parent.find('.user_status');
		
		$loading.show();
		var approve_action = $btn.dataset('approve');
		var data = {
			action: $btn.dataset('action'),
			user_id: $btn.dataset('user'),
			approve: approve_action
		}
		//console.log(data);
		//console.log(ajaxurl);
		$.post( ajaxurl, data, function( response ){
			$user_status.replaceWith(response);
			$loading.hide();
			$parent.removeClass('user_default');
		});
		return false;
	});
	
	/**
	 * ENVIAR EMAIL DE APROVAÇÃO/REPROVAÇÃO DE USUÁRIO
	 * 
	 */
	$('.send_user_status_notification_email').click(function(){
		var ok = confirm('Deseja enviar um email para o usuário sobre o status da conta?');
		if( ok == true ){
			var $btn = $(this);
			var $parent = $btn.closest('.approve_user_actions');
			var $loading = $parent.find('.loading');
			
			$loading.show();
			var data = {
				action: 'send_user_status_notification_email',
				user_id: $btn.dataset('user'),
			}
			$.post( ajaxurl, data, function( response ){
				alert(response);
				$loading.hide();
			});
		}
		return false;
	});
	
	
});