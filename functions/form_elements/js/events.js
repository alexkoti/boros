
jQuery(document).ready(function($){
	
	/**
	 * ==================================================
	 * INFORMAÇÕES EXTRAS DO USUÁRIO ====================
	 * ==================================================
	 * Metabox para montar as perguntas extras para os usuários dos eventos
	 * 
	 */
	$('#box_bev_extra_info').delegate('.boros_element_select .input_select', 'change', function(){
		showhide_td_questions( $(this).closest('.duplicate_element') );
	});
	$('#box_bev_extra_info .duplicate_group').bind('duplicate_group_complete', function(event, ui) {
		showhide_td_questions( $(this).find('.duplicate_element') );
	});
	
	showhide_td_questions( $('#box_bev_extra_info .duplicate_group .duplicate_element') );
	
	function showhide_td_questions( obj ){
		obj.each(function(){
			var arr = ['checkbox_group', 'radio'];
			var val = $(this).find('.input_select').val();
			var tr = $(this).find('td[id*="bev_question_values"], td[id*="bev_question_other_value"]').parent('tr');
			if( inArray( val, arr ) ){
				tr.show();
			}
			else{
				tr.hide();
			}
		});
	}
	
	
	
	/**
	 * ==================================================
	 * INFORMAÇÕES DE USUÁRIOS ==========================
	 * ==================================================
	 * Lightboxes e downloads dos usuários dos eventos
	 * 
	 */
	var $user_info = $('#bev_lightbox_content');
	$user_info.dialog({                   
		'dialogClass'   : 'wp-dialog',           
		'modal'         : true,
		'autoOpen'      : false, 
		'height'        : 500,
		'width'         : 600,
		'closeOnEscape' : true,
	});
	$('#box_bev_users').delegate('.bev_user_info_lightbox', 'click', function(event){
		event.preventDefault();
		$('#bev_lightbox_content').html('');
		$user_info.dialog('open');
		
		var data = {
			action : $(this).dataset('action'),
			bev_id : $(this).dataset('bev_id'),
			user_id : $(this).dataset('user_id')
		};
		$.get(ajaxurl, data, function(response){
			$("#bev_lightbox_content").html(response);
		});
	});
	
	/**
	 * Mover usuário entre filas
	 * 
	 */
	$('#box_bev_users').delegate('.bev_user_action', 'click', function(event){
		$btn = $(this);
		event.preventDefault();
		$('#bev_users_loading').fadeIn('medium', function(){
			var data = {
				action : $btn.dataset('action'),
				bev_id : $btn.dataset('bev_id'),
				user_id : $btn.dataset('user_id')
			};
			$.post(ajaxurl, data, function(response){
				$('#box_bev_users').html(response);
				$('#bev_users_loading').fadeOut();
				
				$('#bev_slots').boros_reload_element();
				$('#bev_extra_info_box').lock_extra_info();
			});
		});
	});
	
});


(function($){
	$.fn.lock_extra_info = function() {
		var elem = $(this);
		var lock_layer = $('#bev_extra_info_box .inside .lock_layer');
		if( window.lock_bev_extra_info_box == true ){
			var extra_infos_box = $('#bev_extra_info_box .inside');
			if( lock_layer.length <= 0 ){
				extra_infos_box.append( '<div class="lock_layer"><div class="lock_layer_bg"></div><div class="lock_layer_text">A edição deste controle é desabilitada quando já existirem usuários inscritos na fila de espera ou aprovados.</div></div>' );
				extra_infos_box.addClass('locked');
			}
			else{
				lock_layer.show();
			}
		}
		else{
			lock_layer.hide();
		}
	};
})(jQuery);