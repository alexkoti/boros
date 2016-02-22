/**
 * Para tornar a function acessível globalmente, é preciso declarar como uma variável em constxto global, fora de jQuery.ready(), depois atribui-se a 
 * function correta dentro de jQuery.ready(), assim é possível usar $(selector) em vez de jQuery(selector)
 * 
 */
var boros_special_image;
jQuery(document).ready(function($){
	
	/**
	 * REMOVER IMAGEM
	 * #edittag .special_image_view <<< especal para edição em taxonomy
	 * 
	 * 
	 */
	$('.boros_form_block, #edittag .special_image_view').delegate('.special_img_remove .btn', 'click', function( event ){
		var $button = $(this);
		var $input = $button.closest('.special_image').find('input.input_special_image');
		var $view =  $button.closest('.special_image_view');
		
		/**
		 * O ajax irá executar special_image_remove(), que irá apagar o option/post_meta/etc caso não esteja dentro
		 */
		var data = {};
		data.action    = 'boros_form_element';
		data.classname = 'special_image';
		data.task      = 'remove';
		
		/**
		 * Context
		 * 
		 */
		data.context = {
			name : $input.dataset('name'),
			type : $input.dataset('type'),
			in_duplicate_group : $input.dataset('in_duplicate_group')
		};
		if( $input.dataset('post_id') != undefined )
			data.context.post_id = $input.dataset('post_id');
		
		data.options = {
			width : $view.dataset('width'),
			default_image : $view.dataset('default_image')
		};
		
		//mostrar loading
		$button.addClass('loading');
		
		$.post(ajaxurl, data, function(response){
			// remover imagem da página
			$button.closest('.special_image').find('.special_image_view').slideUp(500, function(){
				// o css() e o delay() é necessário para que o html seja atualizado dentra da view e possa ocorrer a animação com a altura correta
				$(this).empty().html(response).delay(10).slideDown(500);
				$input.val('');
			});
			
			// esconder loading
			$button.removeClass('.waiting');
			$button.parent().hide();
		});
		return false;
	});
	
	/**
	 * ATRIBUIR RETORNO DO THICKBOX DE ADICIONAR IMAGEM
	 * Função para receber os dados do thickbox de nova imagem
	 * 
	 */
	// var h = conteúdo enviado pelo wordpress, nesse caso foi filtrado no hook 'image_send_to_editor' para retornar apenas a ID do attach
	boros_special_image = function (h, send_to_editor_index){
		var $input = $('input[name="'+send_to_editor_index+'"]');
		/**
		 * Criar o objeto 'data', procurando definir o contexto: post_meta, option, termmeta, usermeta, widget
		 * 
		 */
		var data = {};
		data.action    = 'boros_form_element';
		data.classname = 'special_image';
		data.task      = 'swap';
		data.value     = h;
		
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
		
		//console.log(data);
		$.post(ajaxurl, data, function(response){
			$input.closest('.special_image').find('.special_image_view').slideUp(600, function(){
				$(this).html(response).slideDown(600);
				// atualizar campo hidden
				$input.attr('value', h);
			});
		});
		
		// mostrar botão de remover
		$input.closest('.special_image').find('.special_img_remove').show();
		
		// mudar a class do bloco de actions para mostrar o botão de selecionar entre as existentes
		$input.closest('.special_image').find('.special_image_actions').SwitchClass('has_no_images', 'has_images');
		
		// mudar o texto do botão de enviar imagem
		$input.closest('.special_image').find('.special_img_new').text('Enviar outra imagem');
		
		// remover o thickbox
		tb_remove();
	}
});



