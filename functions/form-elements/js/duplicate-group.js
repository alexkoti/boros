/**
 * ADMIN SCRIPTS: DUPLICATE ELEMENT
 * Duplicar elementos e blocos de elementos.
 * 
 * 
 * 
 * TODO: VERIFICAR A "LIMPEZA" DOS INPUTS DUPLICADOS, COM A OPÇÂO DE TER DEFAULTS
 * TODO: DOCUMENTAR MELHOR ESSAS FUNCTIONS
 * 
 */
jQuery(document).ready(function($){
	
	/**
	 * SORTABLE DOS DUPLICATES
	 * 
	 * 'handle' - restrito apenas a uma pequena barra texturizada acima do elemento Isso possibilita o drag sem comprometer a seleção de texto dentro do elemento.
	 * 'create' - foi inserido uma correção de altura, para que não exista 'pulo' no scroll. Verificação adicional para carregamento de imagens.
	 * @link http://stackoverflow.com/a/4735975
	 */
	function order_lists(obj){
		$(obj).sortable({
			items:'.duplicate_element',
			placeholder:'ui-sortabled', 
			revert:false,
			tolerance:'intersect',
			cursor:'n-resize',
			forceHelperSize:true,
			forcePlaceholderSize:true,
			handle:'.btn_move',
			opacity: 0.8,
			update: re_index_duplicates,
			axis: 'y'
		});
	};
	if( $('.duplicate_group').length > 0 ){
		order_lists(".duplicate_group");
	}
	
	/**
	 * Aplicar indexação inical onload
	 * 
	 */
	if( $(".duplicate_element").length ){
		re_index_duplicates();
	}
	
	/**
	 * REMOVER ELEMENT
	 * 
	 */
	function remove_element(obj){
		// guardar grupo duplicável
		var $box = obj.closest('.duplicate_box');
		var $group = $box.find('.duplicate_group');
		// adiciona o loading - esconde os botões de remover enquanto a animação ocorre
		$box.addClass('loading');
		// adicionado 'loading' ao .duplicate_element para que este fique sem min-height, que causa um glitch na animação
		obj.closest('.duplicate_element').addClass('loading').slideUp(function(){
			// remover elemento
			$(this).remove();
			// remove o loading
			$box.removeClass('loading');
			re_index_duplicates();
		});
	}
	$('.duplicate_group').delegate('.duplicate_element .btn_remove .btn', 'click', function(){
		// caso seja elemento único apenas reseta os campos. É provável que esse controle nunca seja acionado pois estará invisível nesse contexto.
		if( $(this).closest('.duplicate_group').find('.duplicate_element').length == 1 ){
			reset_element($(this).closest('.duplicate_element'));
		}
		else{
			remove_element($(this));
		}
	});
	
	/**
	 * DUPLICATE ELEMENT
	 * No botão de adição é aplicadoo o bind('click'), e em seguida o unbind('click'), para que a ação de duplicar seja acionada apenas uma vez. Ao término da 
	 * ação, quando é finalizado o slideDown(), é readicionado o click ao botão.
	 * 
	 * @todo usar o context do dataset
	 */
	function duplicate_element(obj){
		var $box = obj.closest('.duplicate_box');
		var $group = $box.find('.duplicate_group');
		var $item = $group.find('.duplicate_element');
		var $item_first = $item.filter(':first');
		
		/**
		 * Criar o objeto 'data', procurando definir o contexto: post_meta, option, termmeta, usermeta, widget
		 * 
		 */
		var data = {};
		data.action = 'boros_form_element_ajax';
		data.task = 'duplicate_group';
		data.args = {  
			index : $item.length
		};
		
		/**
		 * Context
		 * 
		 */
		data.context = {
			name : $item_first.dataset('name'),
			type : $item_first.dataset('type'),
			parent : $item_first.dataset('parent'),
			group : $item_first.dataset('group')
		}
		if( $item_first.dataset('post_id') != undefined )
			data.context.post_id = $item_first.dataset('post_id');
		if( $item_first.dataset('post_type') != undefined )
			data.context.post_type = $item_first.dataset('post_type');
		if( $item_first.dataset('option_page') != undefined )
			data.context.option_page = $item_first.dataset('option_page');
		
		// adiciona o loading
		$box.addClass('loading');
		
		/**
		 * Aqui é clonado o .duplicate_element, porém APENAS O INNER_HTML DA TABELA .boros_options_block é substituida pelo retorno do ajax.
		 * Não é feita a troca completa de .duplicate_element vindo por ajax devido a bugs no momento do append.
		 * 
		 * Detalhes: temos a lista sem duplicados, e ao clicar no botão de duplicar, é clonado o primeiro .duplicate_element e adicionado ao final(append)
		 * <code> Antes:
		 * 	<ul>
		 * 		<li class='duplicate_element'><table class="boros_options_block">{content}</table></li> <!-- item que será clonado -->
		 * 		<li class='duplicate_element'><table class="boros_options_block">{content}</table></li>
		 * 	</ul>
		 * </code>
		 * 
		 * No novo elemento, o innerHTML da tabela .boros_options_block é substituido pelo retorno do ajax, que poderá ser um único element ou um grupo deles. Cada
		 * element retornado é um <tr>
		 * <code> Depois:
		 * 	<ul>
		 * 		<li class='duplicate_element'><table class="boros_options_block">{content}</table></li>
		 * 		<li class='duplicate_element'><table class="boros_options_block">{content}</table></li>
		 * 		<li class='duplicate_element'><table class="boros_options_block">{AJAX_CONTENT}</table></li> <!-- item adicionado -->
		 * 	</ul>
		 * </code>
		 * 
		 */
		$.post(ajaxurl, data, function(response){
			var duplicate = $item_first.clone();
			duplicate.find('.boros_options_block').html( response );
			duplicate.hide().addClass('loading');
			$group.append(duplicate);
			duplicate.slideDown(400, function(){
				// remove o loading
				$box.removeClass('loading');
				duplicate.removeClass('loading');
				// reabilita o click
				obj.bind('click', function(){
					$(this).unbind('click');
					duplicate_element(obj);
				});
			});
			re_index_duplicates();
			
			/**
			 * Aqui é feito um 'trigger' no evento customizado 'duplicate_complete', para que possa ser adicionado via bind(). Exemplo:
			 * $('.duplicate_element').bind('duplicate_complete', function(event, params){ my_function(); });
			 * 
			 */
			$item.trigger( 'duplicate_item_complete', [duplicate.attr('id')] );
			$group.trigger( 'duplicate_group_complete' );
		});
	}
	$(".dup_btn span").bind('click', function(){
		$(this).unbind('click');
		duplicate_element($(this));
	});
	
	/**
	 * COMPACT VIEW
	 * Botão para habilitar visualização compacta, que mostrará apenas o primiro element de cada grupo
	 * 
	 */
	$('.duplicate_compact input[type="checkbox"]').change(function(){
		var $box = $(this).closest('.boros_form_block').find('.duplicate_box');
		var $label = $(this).closest('label');
		var $group = $box.find('.duplicate_group');
		if( $(this).is(':checked') ){
			$label.addClass('active');
			$box.addClass('duplicate_element_compact');
		}
		else{
			$label.removeClass('active');
			$box.removeClass('duplicate_element_compact');
		}
	});
	
	function reset_element(obj){
		obj.find('textarea').html('');
		obj.find('[value]').val('');
		obj.find('[src]').attr('src', '').css('display', 'none');
	}
	
	function re_index_duplicates(){
		$('.duplicate_group').each(function(){
			var index = 0;
			
			// cada bloco duplicável, que poderá ter vários form_elements
			$(this).find('.duplicate_element').each(function(){
				// O 'id' padrão, não indexado é armazenado em 'data-name'
				var prefix = $(this).attr('data-name');
				
				// 'index' do element dentro de '.duplicate_group'
				index = $(this).index();
				
				$(this).attr('id', prefix + '_' + index);
					
				// Indicador numérico do elemento duplicado
				$(this).find('.duplicate_index').text( index + 1 );
				
				// reindexar cada um dos elements
				$(this).find('.boros_form_element').each(function(){
					// verificar se é um element múltiplo
					var multiple = ( $(this).find('.boros_form_input').length > 1 ) ? true : false;
					if( multiple ){
						//console.log( $(this) );
					}
					
					/**
					 * Não é preciso atualizar os ids e labels, pois o element duplicado vem por ajax, já com a indexação aplicada! weee :D
					 * 
					 */
					$(this).find('.boros_form_input').each(function(){
						var original_name = $(this).dataset('name');
						var new_name = prefix + '[' + index + ']' + '[' + original_name + ']';
						// caso seja checkbox_group, adicionar multiplicador
						if( multiple == true && $(this).is('[type="checkbox"]') ){
							new_name += '[]';
						}
						//var new_id = prefix + '_' + index + '_' + original_name;
						
						// name
						$(this).attr('name', new_name);
						
						// data-input
						$(this).closest('.boros_form_element').find('[data-input]').attr('data-input', new_name);
						
						/**
						 * Bug do jquery.sortable: ao reordenar os elementos, os radio são resetados
						 * 
						 * @todo conferir com checkboxes e outros elementos
						 */
						if( $(this).is('[type="radio"]') ){
							var checked = $(this).attr('data-checked');
							
							// verificar se já possui o data-checked e corrigir
							if( checked == 1 ){
								$(this).prop('checked', true);
							}
							
							// caso ele seja dinamicamente adicionado, não possuirá o data-checked:
							if (typeof checked === "undefined"){
								if( $(this).is(':checked') ){
									$(this).attr('data-checked', 1);
								}
							}
						}
					});
				});
			});
		});
	}
	
	/**
	 * Corrigir radios dentro dos sortables
	 * 
	 */
	$('.duplicate_group [type="radio"]').each(function(){
		if( $(this).is(':checked') ){
			$(this).attr('data-checked', 1);
		}
	});
	$('.duplicate_group [type="radio"]').bind('click', function(){
		if( $(this).is(':checked') ){
			// remover os checked dos outros
			$('[name="'+$(this).attr('name')+'"]').prop('checked', false);
			$('[name="'+$(this).attr('name')+'"]').attr('data-checked', 0);
			
			// atribuir o checked apenas neste elemento
			$(this).attr('data-checked', 1);
			$(this).prop('checked', true);
		}
	});
	
});
