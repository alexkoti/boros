/**
 * Para tornar a function acessível globalmente, é preciso declarar como uma variável em constxto global, fora de jQuery.ready(), depois atribui-se a 
 * function correta dentro de jQuery.ready(), assim é possível usar $(selector) em vez de jQuery(selector)
 * 
 */
var attach_select;
jQuery(document).ready(function($){
	
	/**
	 * ATRIBUIR RETORNO DO THICKBOX DE ADICIONAR IMAGEM
	 * Função para receber os dados do thickbox de nova imagem
	 * 
	 */
	// 
	// var h = conteúdo enviado pelo wordpress, nesse caso foi filtrado no hook 'image_send_to_editor' para retornar apenas a ID do attach
	attach_select = function (h, send_to_editor_index){
		//console.log(h);
		//console.log(send_to_editor_index);
		/**
		var $input = $('input[name="'+send_to_editor_index+'"]');
		var post_ID = $input.closest('form').find('input[name="post_ID"]').val();
		
		var post_data = {
			action: 'attach_select',
			att_id: h,
			input_name: send_to_editor_index,
			post_ID: post_ID,
			meta_key: $input.attr('name')
		}
		/**/
		
		var $input = $('input[name="'+send_to_editor_index+'"]');
		/**
		 * Criar o objeto 'data', procurando definir o contexto: post_meta, option, termmeta, usermeta, widget
		 * 
		 */
		var data = {};
		data.action = 'attach_select';
		data.value = h;
		
		/**
		 * Context
		 * 
		 */
		data.context = {
			name : $input.dataset('name'),
			type : $input.dataset('type'),
			parent : $input.dataset('parent'),
			group : $input.dataset('group'),
			in_duplicate_group : $input.dataset('in_duplicate_group')
		}
		if( $input.dataset('user_id') != undefined )
			data.context.user_id = $input.dataset('user_id');
		if( $input.dataset('post_id') != undefined )
			data.context.post_id = $input.dataset('post_id');
		if( $input.dataset('post_type') != undefined )
			data.context.post_type = $input.dataset('post_type');
		if( $input.dataset('option_page') != undefined )
			data.context.option_page = $input.dataset('option_page');
		
		//console.log( data );//return false;
		
		$.post(ajaxurl, data, function(response){
			$input.closest('.attach_select').find('.attach_select_view').slideUp(function(){
				$(this).html( response ).slideDown();
				// atualizar campo hidden
				$input.val( h );
			});
		});
		
		// remover o thickbox
		tb_remove();
	}
});



