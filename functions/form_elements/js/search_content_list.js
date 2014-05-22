
jQuery(document).ready(function($){
	
	function dup(){
		
		/**
		 * BUSCA DE POSTS/CONTENTS
		 * 
		 * 
		 * 
		 */
		// click botão de busca
		$('.search_content_submit').click(function(){
			var search_text = get_search_content_text( $(this) );
			search_content( search_text, $(this) );
			return false;
		});
		
		// enter no campo de texto
		$('input[name=search_content_text]').keypress(function(e){
			// caso seja Enter(13)
			if( e.which== 13 ){
				var search_text = get_search_content_text( $(this) );
				search_content( search_text, $(this) );
				e.preventDefault();
			}
		});
		
		// pegar string de texto
		function get_search_content_text(e){
			var search_text = e.closest('.search_content_inputs').find('input[name=search_content_text]').val();
			return search_text;
		}
		
		// fazer busca das histórias
		function search_content( search_text, target ){
			if( search_text != '' ){
				
				// input:hidden com a lista deids a serem gravadas
				var $search_content_ids = $(target).closest('.search_content_box').find('.search_content_ids');
				var $query = $(target).closest('.search_content_inputs').find('input[name=search_content_query]');
				var $show_post_type = $(target).closest('.search_content_inputs').find('input[name=show_post_type]');
				var $show_thumbnails = $(target).closest('.search_content_inputs').find('input[name=show_thumbnails]');
				var $show_excerpt = $(target).closest('.search_content_inputs').find('input[name=show_excerpt]');
				var $excerpt_length = $(target).closest('.search_content_inputs').find('input[name=excerpt_length]');
				
				// iniciar loading
				var $loading = $(target).closest('.search_content_box').find('.search_content_inputs .waiting');
				var $results = $(target).closest('.search_content_box').find('.search_content_list_results');
				$loading.show();
				$results.slideUp('normal', function(){
					
					// preparar dados da busca
					var data = {
						action: 'search_content',
						search_text: search_text,
						query: $query.val(),
						show_post_type: $show_post_type.val(),
						show_thumbnails: $show_thumbnails.val(),
						show_excerpt: $show_excerpt.val(),
						excerpt_length: $excerpt_length.val(),
						remove: $search_content_ids.val()
					};
					
					//console.log(data);//return false;
					jQuery.post(ajaxurl, data, function(response) {
						$(target).closest('.search_content_box').find('.search_content_list_results').html(response);
						$loading.hide();
						$results.slideDown();
					});
					
				});
			}
		}
		
		// delegate nos botões de resultados de busca
		$('.search_content_list_results').delegate('.result_select', 'click', function(){
			var parent = $(this).closest('.search_content_box');
			var new_history = $(this).closest('li').hide();
			parent.find('.search_content_list_selected .related_item_list').append( new_history )
			parent.find('.no_results_h').hide();
			new_history.slideDown().yellowFade();
			set_ordered_histories();
		});
		
		// delegate nos botões de itens selecionados
		$('.search_content_list_selected').delegate('.result_deselect', 'click', function(){
			$(this).closest('li').slideUp('slow', function(){
				$(this).remove();
				set_ordered_histories();
			});
		});
		
		// sortable das histórias selecionadas
		if( $('.search_content_list_selected').length > 0 ){
			$('.search_content_list_selected').sortable({
				items : '.related_item',
				placeholder:'ui-sortabled', 
				revert:false,
				tolerance:'intersect',
				cursor:'n-resize',
				forceHelperSize:true,
				forcePlaceholderSize:true,
				opacity: 0.8,
				axis: 'y',
				create: function(event, ui){
					//set_ordered_histories(); // esta declaração está dando problema :(
				},
				update: function(event, ui){
					set_ordered_histories();
				}
			});
		}
		
		// mudar o valor do campo a ser salvo com a nova ordem de elementos
		function set_ordered_histories(){
			$('.search_content_list_selected').each(function(){
				var $raw_input = $(this);
				var raw_order = $raw_input.sortable('toArray').toString();
				order = raw_order.split(',');
				$(order).each(function(e){
					order[e] = order[e].replace('related_item_', '');
				});
				//console.log(order);
				$raw_input.closest('.search_content_box').find('.search_content_ids').val( order );
			});
			$('.related_item_list').each(function(){
				$(this).find('li .related_index').each(function(index){
					$(this).text( index + 1 );
				});
			});
		}
		
		// adicionar botão de limpar pesquisa
		$('input[name=search_content_text]').bind('focus blur keyup change', function(){
			var $search_content_clear = $(this).closest('.search_content_inputs').find('.search_content_clear');
			if( $(this).val() != '' )
				$search_content_clear.fadeIn();
			else
				$search_content_clear.fadeOut();
		});
		$('.search_content_clear').click(function(){
			$clear_search = $(this);
			$related_histories_box = $(this).closest('.search_content_box');
			$input = $related_histories_box.find('input[name=search_content_text]');
			$results = $related_histories_box.find('.search_content_list_results');
			
			$results.slideUp(400, function(){
				$(this).empty();
				$clear_search.fadeOut();
				$input.val('').focus();
			});
		});
	
	}
	
	// iniciar script
	dup();
	
	$('.boros_form_block').bind('duplicate_group_complete', function(event, ui) {
		dup();
	});
	
});







