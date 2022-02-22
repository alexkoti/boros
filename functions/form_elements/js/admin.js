/**
 * ADMIN SCRIPTS GERAL
 * 
 * 
 * 
 */
var send_to_input;
jQuery(document).ready(function($){
	
	/**
	 * ==================================================
	 * THICKBOX MONITOR =================================
	 * ==================================================
	 * Monitorar todos os controles da página que possam abrir o thickbox de media,e filtrar a função de callback send_to_editor(), conforme necessário.
	 * 
	 */
	
	/*
	 * Essas duas variáveis controlam o estado dos thickboxes: por padrão são 'false', o que indica que qualquer clique em um link .thickbox irá abrir o 
	 * box de midia e retornar o valor para o editor de text padrão(ou wp_editor). Caso ocorra algum clique em .thickbox que possua o dataset 'callback', 
	 * o estado será alterado e o retorno será manipulado pelos callback definidos.
	 * 
	 * var send_to_editor_index = guarda o name do input ao qual esse controle está relacionado, e que irá receber os dados do box media.
	 * var send_to_editor_callback = function que irá manipular o retorno do midia box. definir no link '.thickbox' como data-callback="nome_do_callback".
	 */
	send_to_editor_index = false;
	send_to_editor_callback = false;
	
	/*
	 * Aplicado delegate para que possa funcionar com duplicate elements
	 * O evento mousedown para que sempre acontece antes do evento click já atribuido ao .thickbox. Assim o 'send_to_editor_index' sempre será 
	 * modificado antes de acionar a função send_to_editor()
	 */
	$('.boros_form_element').delegate('.thickbox[data-callback]', 'mousedown', function(){
		send_to_editor_index = $(this).attr('data-input');
		send_to_editor_callback = $(this).attr('data-callback');
	});
	
	if( $('.thickbox').length ){
		/* 
		 * Verificar se existe um send_to_editor() nativo declarado na página atual. Caso não exista, significa que o arquivo 
		 * 'wp-admin/js/media-upload.js' não carregado na página. Isso não irá impedir o funcionamento do box, mas ficará falando o tb_position(), que
		 * corrige as dimensões do thickbox em relação ao viewport.
		 */
		if(typeof send_to_editor == 'function')
			var original_send_to_editor = send_to_editor;
		else
			var original_send_to_editor = function(){}; // function vazia
		
		
		/*
		 * Aplicar a nova função.
		 * Caso a função definida em data-callback(no link que abre o thickbox), não exista, será feita uma tentativa de aplicar o valor retornado
		 * ao 'send_to_editor_index', sem maiores interações. É um clone de send_to_input(), caso não tenha sido declarado um callback válido no link de thickbox.
		 * 
		 * O callback só será acionado caso tenha sido clicado um link com 'data-callback' definido, o que irá o estado de 'send_to_editor_index' e 'send_to_editor_callback'.
		 * 
		 */
		window.send_to_editor = function(h){
			if( send_to_editor_index == false ){
				//console.log('ORIGINAL send_to_editor()');
				original_send_to_editor(h);
			}
			else{
				//console.log('CUSTOM send_to_editor()');
				var fn = window[send_to_editor_callback];
				if(typeof fn === 'function') {
					//console.log( 'fn' );
					//console.log( send_to_editor_index + ' : ' + h );
					fn( h, send_to_editor_index );
				}
				else{
					//console.log( 'send_to_input' );
					$('input[name="'+send_to_editor_index+'"]').val(h);
					tb_remove();
				}
				send_to_editor_index = false;
				send_to_editor_callback = false;
			}
		}
	}
	
	/**
	 * Append de funcionalidades ao tb_remove() original.
	 * 
	 */
	if(typeof tb_remove == 'function')
		var original_tb_remove = tb_remove;
	else
		var original_tb_remove = function(){}; // function vazia
	window.tb_remove = function(){
		send_to_editor_index = false;
		send_to_editor_callback = false;
		original_tb_remove();
		return false;
	}
	
	
	/**
	 * Função simples para retorno do valor para o input indexado
	 * 
	 */
	function send_to_input(h, send_to_editor_index){
		$('input[name="'+send_to_editor_index+'"]').val(h);
		tb_remove();
	}
	
	
	
	/**
	 * ==================================================
	 * DEPENDENT ELEMENTS ===============================
	 * ==================================================
	 * Nos elementos select, checkbox e radio marcados com a class .provider_input, nas mudanças de valor(change), é feita uma requisição 
	 * ajax de ajax_reload_form_element(), que envia como argumento 'reload_value' o valor do selected/checked
	 * 
	 * @todo permitir args ilimitados e também permitindo vários providers para um mesmo dependent. ex depentend:wp_query, providers:select(category),select(post_type),select(posts_per_page)
	 */
	$('.boros_form_element').delegate('input[type="checkbox"].provider_input:checked, input[type="radio"].provider_input:checked', 'change', function(){
		reload_dependent_input( $(this) );
	});
	$('.boros_form_element').delegate('select.provider_input', 'change', function(event){
		// ver http://api.jquery.com/event.stopImmediatePropagation/
		event.stopPropagation();
		reload_dependent_input( $(this) );
	});
	function reload_dependent_input( $provider ){
		/**
		 * Busca a TR do elemento dependente
		 * Espera-se que o $input dependente esteja no mesmo bloco da configuração, o que corresponde ao .form-table mais próximo na árvore( closest() )
		 * 
		 */
		var dependent = $provider.dataset('dependency_dependent');
		var $dependent_element_cell = $provider.closest('.form-table').find('[data-name="'+dependent+'"]').closest('.boros_form_element');
		var $dependent_element_row = $dependent_element_cell.parent('tr');
		$dependent_element_cell.append( '<div class="reloading_layer"></div>' );
		$dependent_element_cell.addClass('reloading');
		
		/**
		 * Criar o objeto 'data', procurando definir o contexto: post_meta, option, termmeta, usermeta, widget
		 * 
		 */
		var data = {};
		data.action = 'duplicate_element';
		data.args = {  
			index : $provider.closest('.duplicate_element').index(),
			provider_value : $provider.val()
		};
		
		/**
		 * Context
		 * 
		 */
		data.context = {
			name : dependent,
			type : $provider.dataset('type'),
			group : $provider.dataset('group'),
			in_duplicate_group : $provider.dataset('in_duplicate_group')
		}
		if( $provider.dataset('post_id') != undefined )
			data.context.post_id = $provider.dataset('post_id');
		if( $provider.dataset('post_type') != undefined )
			data.context.post_type = $provider.dataset('post_type');
		if( $provider.dataset('option_page') != undefined )
			data.context.option_page = $provider.dataset('option_page');
		
		/**
		if( $('input[name="post_ID"]').length ){
			data.context = {
				type : 'post_meta',
				post_id : $('input[name="post_ID"]').val(),
				post_type : $('input[name="post_type"]').val()
			}
		}
		else{
			data.context = {
				type : 'option',
				option_page : $('input[name="option_page"]').val(),
			}
		}
		/**/
		
		$.post(ajaxurl, data, function(response) {
			//console.log(response);
			$dependent_element_row.delay(5000).replaceWith(response);
		});
	}
	
	
	
	/**
	 * ==================================================
	 * DUPLICABLE ELEMENTS ==============================
	 * ==================================================
	 * 
	 * 
	 * 
	 */
	function duplicable_inputs_sort(obj){
		$(obj).sortable({
			items:'.duplicable_input_item',
			placeholder:'ui-sortabled', 
			revert:false,
			tolerance:'intersect',
			cursor:'n-resize',
			forceHelperSize:true,
			forcePlaceholderSize:true,
			handle:'.duplicable_move',
			opacity: 0.8,
			update: re_index_duplicables,
			axis: 'y'
		});
	};
	if( $('.duplicable_input_box').length > 0 ){
		duplicable_inputs_sort(".duplicable_input_box");
	}
	
	$(".duplicable_input_box").delegate('.duplicable_input_item .duplicable_add', 'click', function(){
		var $box = $(this).closest('.duplicable_input_box');
		// evitar multiplos clicks
		if( $box.is('.loading') ){
			return false;
		}
		$box.addClass('loading');
		var $element = $(this).parent('.duplicable_input_item');
		$element.clone().addClass('loading').hide().insertAfter($element).slideDown(400, function(){
			$(this).removeClass('loading');
			$box.removeClass('loading');
			re_index_duplicables();
		});
	});
	
	$(".duplicable_input_box").delegate('.duplicable_input_item .duplicable_remove', 'click', function(){
		var $box = $(this).closest('.duplicable_input_box');
		// evitar multiplos clicks
		if( $box.is('.loading') ){
			return false;
		}
		$box.addClass('loading');
		var $element = $(this).parent('.duplicable_input_item');
		$element.addClass('loading').slideUp(400, function(){
			$(this).remove();
			$box.removeClass('loading');
			re_index_duplicables();
		});
	});
	
	function re_index_duplicables(){
		$('.duplicable_input_box').each(function(){
			var index = 0;
			// O 'name' padrão, não indexado é armazenado em 'data-name'
			$(this).find('.duplicable_input_item').each(function(){
				index = $(this).index();
				
				$(this).find('.boros_form_input').each(function(){
					var name = $(this).attr('data-name');
					var key = $(this).attr('data-key');
					if( typeof key == 'undefined' ){
						var new_id = name + '_' + index;
						var new_name = name + '[' + index + ']';
					}
					else{
						var new_id = name + '_' + index + '_' + key;
						var new_name = name + '[' + index + '][' + key + ']';
					}
					
					// name
					$(this).attr({
						id: new_id,
						name: new_name
					});
					
					// data-input
					$(this).closest('.boros_form_element').find('[data-input]').attr('data-input', new_name);
				});
			});
		});
	}
	
	
	
	/**
	 * AUTOSELECT DE TEXTO
	 * Seleciona texto dentro do elemento, porÃ©m nÃ£o copia o texto
	 */
	$('.autoselect').click(function(){
		$(this).focus().select();
    });
    

    /**
     * Forçar a exibição de miniaturas em todos os thickboxes
     * 
     */
    if( $('#media-items').length > 0 ){
        $('#media-items .media-item').each(function(){
            var row = $(this);
            var thumb = $(this).find('.thumbnail');
            if( row.find('img.pinkynail').length ){
                console.log( 1 );
            }
            else{
                console.log( 22 );
            }
            var img = $('<img>');
            img.attr( 'src', thumb.attr('src') ).addClass('pinkynail toggle');
            row.prepend( img );
        });
    }
    
});




/**
 * ==================================================
 * RELOAD ELEMENT ===================================
 * ==================================================
 * Recarrega com ajax + loading qualquer .boros_form_input, buscando o <tr> mais próximo, aplicando o loading e recarregando o elemento.
 * Deve ser usado para atualizar campos que utilizam de outros valores dependentes para criar seu valor.
 * 
 * Exemplo: temos um campo $x seta o valor de porcentagem, e este campo é atualizado via ajax. Em outro campo $y, que exibe o preço com a porcentagem calculada, 
 * deve ser atualizado através da presente função, que irá buscar o novo valor de $x e calcular novamente $y, devolvendo o HTML do input atualizado.
 * 
 * Esta function está acessível por qualquer script, não sendo preciso estar no closure jquery.ready()
 * @link http://stackoverflow.com/a/1042096
 * 
 * @todo permitir modificar o value do elemento, enviando como parâmetro do ajax
 */
(function($){
	$.fn.boros_reload_element = function() {
		// whatever $().boros_reload_element() should do
		elem = $(this);
		var element_cell = elem.parents('td.boros_form_element').last();
		var element_row = element_cell.parent('tr');
		element_cell.append( '<div class="reloading_layer"></div>' );
		element_cell.addClass('reloading');
		
		/**
		 * Criar o objeto 'data', procurando definir o contexto: post_meta, option, termmeta, usermeta, widget
		 * 
		 */
		var data = {};
		data.action = 'duplicate_element';
		data.args = {
			index : elem.closest('.duplicate_element').index()
		};
		
		/**
		 * Context
		 * 
		 */
		data.context = {
			name : elem.dataset('name'),
			type : elem.dataset('type'),
			group : elem.dataset('group'),
			in_duplicate_group : elem.dataset('in_duplicate_group')
		}
		if( elem.dataset('post_id') != undefined )
			data.context.post_id = elem.dataset('post_id');
		if( elem.dataset('post_type') != undefined )
			data.context.post_type = elem.dataset('post_type');
		if( elem.dataset('option_page') != undefined )
			data.context.option_page = elem.dataset('option_page');
		
		//console.log(data);
		$.post(ajaxurl, data, function(response) {
			//console.log(response);
			element_row.delay(5000).replaceWith(response);
		});
	};
})(jQuery);

