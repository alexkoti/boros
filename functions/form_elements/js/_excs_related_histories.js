jQuery(document).ready(function($){
	
	
	
	/**
	 * BUSCA DE HISTÓRIAS RELACIONADAS
	 * 
	 * 
	 * 
	 */
	// click botão de busca
	$('.excs_search_submit').click(function(){
		var search_text = get_excs_search_text( $(this) );
		excs_search_history( search_text, $(this) );
		return false;
	});
	
	// enter no campo de texto
	$('input[name=excs_search_text]').keypress(function(e){
		// caso seja Enter(13)
		if( e.which== 13 ){
			var search_text = get_excs_search_text( $(this) );
			excs_search_history( search_text, $(this) );
			e.preventDefault();
		}
	});
	
	// pegar string de texto
	function get_excs_search_text(e){
		var search_text = e.closest('.excs_search_inputs').find('input[name=excs_search_text]').val();
		return search_text;
	}
	
	// fazer busca das histórias
	function excs_search_history( search_text, target ){
		if( search_text != '' ){
			
			// iniciar loading
			var loading = $(target).closest('.excs_related_histories_box').find('.excs_search_inputs .waiting');
			var results = $(target).closest('.excs_related_histories_box').find('.excs_search_results');
			loading.show();
			results.slideUp('normal', function(){
				
				// preparar dados da busca
				var data = {
					action: 'excs_search_history',
					search_text: search_text,
					remove: $('#excs_related_histories_list').val()
				};
				
				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				jQuery.post(ajaxurl, data, function(response) {
					$(target).closest('.excs_related_histories_box').find('.excs_search_results').html(response);
					loading.hide();
					results.slideDown();
				});
				
			});
		}
	}
	
	// delegate nos botões de resultados de busca
	$('.excs_search_results').delegate('.result_select', 'click', function(){
		var parent = $(this).closest('.excs_related_histories_box');
		var new_history = $(this).closest('li').hide();
		parent.find('.excs_related_histories_selected .related_item_list').append( new_history )
		parent.find('.no_results_h').hide();
		new_history.slideDown().yellowFade();
		set_ordered_histories();
	});
	
	// delegate nos botões de itens selecionados
	$('.excs_related_histories_selected').delegate('.result_deselect', 'click', function(){
		$(this).closest('li').slideUp('slow', function(){
			$(this).remove();
			set_ordered_histories();
		});
	});
	
	// sortable das histórias selecionadas
	if( $('.excs_related_histories_selected').length > 0 ){
		$('.excs_related_histories_selected').sortable({
			items : '.related_item',
			placeholder:'ui-sortabled', 
			revert:false,
			tolerance:'intersect',
			cursor:'n-resize',
			forceHelperSize:true,
			forcePlaceholderSize:true,
			opacity: 0.8,
			axis: 'y',
			create: set_ordered_histories,
			update: set_ordered_histories
		});
	}
	
	// mudar o valor do campo a ser salvo com a nova ordem de elementos
	function set_ordered_histories(){
		var raw_order = $('.excs_related_histories_selected').sortable('toArray').toString();
		order = raw_order.split(',');
		$(order).each(function(e){
			order[e] = order[e].replace('related_item_', '');
		});
		$('.ids_list').val( order );
	}
	
	// adicionar botão de limpar pesquisa
	$('input[name=excs_search_text]').bind('focus blur keyup change', function(){
		var $excs_clear_search = $(this).closest('.excs_search_inputs').find('.excs_clear_search');
		if( $(this).val() != '' )
			$excs_clear_search.fadeIn();
		else
			$excs_clear_search.fadeOut();
	});
	$('.excs_clear_search').click(function(){
		$clear_search = $(this);
		$related_histories_box = $(this).closest('.excs_related_histories_box');
		$input = $related_histories_box.find('input[name=excs_search_text]');
		$results = $related_histories_box.find('.excs_search_results');
		
		$results.slideUp(400, function(){
			$(this).empty();
			$clear_search.fadeOut();
			$input.val('').focus();
		});
	});
	
	
	
	
	/**
	 * ADICIONAR HISTÓRIA
	 * 'add_single_history' precisa ser declarada no contexto 'window', para que possa ser acessada pelo iframe.
	 * 
	 * 
	 */
	window.add_single_history = function(){
		// pegar conteúdo do retorno do iframe
		var new_history_html = $('#TB_iframeContent').contents().find('#new_history').html();
		// append na lista de histórias, com slide + fade
		window.box_related_histories.append( new_history_html );
		window.box_related_histories.find('li:last').hide().slideDown().yellowFade();
		// atualizar ordem
		set_ordered_histories();
		// remover thickbox
		tb_remove();
	}
	window.update_single_history = function(){
		// pegar conteúdo do retorno do iframe
		var new_history_html = $('#TB_iframeContent').contents().find('#new_history').html();
		var OuterDiv = $('<div></div>');
		// append na lista de histórias, com slide + fade. Primeiro é preciso colocar o html em uma div anônima, e fazer o select do elemento via jquery. Necessário para o replaceWith
		$(OuterDiv).append(new_history_html);
		var new_history = $(OuterDiv).find('li:first');
		var old_history = '#'+new_history.attr('id');
		$(old_history).replaceWith( new_history_html );
		$(old_history).yellowFade();
		// atualizar ordem
		set_ordered_histories();
		// remover thickbox
		tb_remove();
	}
	$('.add_single_history_button').click(function(){
		window.box_related_histories = $(this).closest('#box_related_histories').find('.excs_related_histories_selected ul');
	});
	$('#add_single_history_form').submit(function(){
		$('body').addClass('loading');
	});
	
	
	
	/**
	 * Ao iniciar o cadastro de um novo produto, a série da mesma(se houver) não estará definida ainda, sem antes salvar. Para cobrir essa limitação, é adicionado a
	 * variável 'serie_id' ao link de thickbox de adição/edição de histórias, sempre um dos radios de série for clicado
	 * 
	 * 
	 */
	$('#box_taxonomy_serie .filter_list_itens li input:radio').click(function(){
		var radio = $(this);
		$('#add_new_history_control a.add_single_history_button').each(function(){
			// parse na url
			var urlvars = url_params( $(this).attr('href') );
			
			if( radio.is(':checked') )
				urlvars.serie = radio.val(); // aplicar ID da série na url
			else
				urlvars.serie = 0; // remover ID da série
			
			// voltar para url
			var new_url = $(this).attr('href').split('?')[0] + '?' + $.param(urlvars);
			$(this).attr('href', new_url);
		});
	});
	
	/**
	 * Filtro da listagem de séries. Adiciona o campo de texto que irá filtrar e limitar a exibição de itens na listagem radio de Séries.
	 * Foi adicionado um mini plugin 'InsContains' para fazer as buscas de texto, semelhante ao 'contains' do core jquery, mas case_insensitive
	 * 
	 * 
	 */
	// custom css expression for a case-insensitive contains()
	jQuery.expr[':'].InsContains = function(a,i,m){
		return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0;
	};
	
	$('.filter_list_input').change( function(){
		var filter = $(this).val();
		var list = $(this).closest('.categorychecklist').find('.filter_list_itens');
		if(filter){
			// this finds all links in a list that contain the input,
			// and hide the ones not containing the input while showing the ones that do
			$(list).find("label span:not(:InsContains(" + filter + "))").closest('li').slideUp();
			$(list).find("label span:InsContains(" + filter + ")").closest('li').slideDown();
		}
		else{
			$(list).find("li").slideDown();
		}
		return false;
	}).keyup( function(){
		// fire the above change event after every letter
		$(this).change();
	});
	
});







