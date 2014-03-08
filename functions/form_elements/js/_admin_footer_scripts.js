jQuery(document).ready(function($){
	
	/**
	 * ATIVAR AUTOCOMPLETE NOS BOXES DE TAG-TAXONOMY
	 * tagBox.init() está definido em wp-admin/js/post.dev.js
	 * 
	<code>
	$('.the-tagcloud a').each(function(){
		console.log( $(this).data('events') );
	});
	</code>
	 * @link http://stackoverflow.com/questions/4477530/is-there-a-way-in-jquery-to-detected-if-an-element-has-already-a-bound-event
	 */
	// Ativar na tela de taxonomia
	$('.newtag').each(function(){
		if( $(this).data('events') == undefined ){
			tagBox.init();
		}
	});
	// Ativar nas listagens rápidas de recentes/favoritos
	$('.excs_user_terms .term').click(function(){
		tagBox.flushTags( $(this).closest('.excs_taxonomy_terms_non_hierachical').find('.tagsdiv'), this);
		return false;
	});
	
	
	/**
	 * TAXONOMIAS DE EDITORA DIRETAMENTE NA TELA DE PRODUTO
	 * Normalmente as taxonomias referentes à editora, são herdados da série a qual pertence a revista. Mas quando esta não pertence à nenhuma série ou coleção é 
	 * preciso configurar dentro da própria tela de produto
	 * 
	 * 
	 */
	function show_hide_product_taxonomies(){
		var checkbox = $('#product_unique_taxonomies');
		if( checkbox.is(':checked') ){
			//$('#serie_radio_0').attr('checked', 'checked');
			$('.product_unique_taxonomies_block .unique_tax_input').show();
		}
		else{
			$('.product_unique_taxonomies_block .unique_tax_input').hide();
		}
	}
	// aplicar ação ao botão
	$('#product_unique_taxonomies').change(function(){
		show_hide_product_taxonomies();
	});
	//esconder|mostrar onload >>> apenas se existir o checkbox de controle
	if( $('#product_unique_taxonomies').length ){
		show_hide_product_taxonomies();
	}
	
	
	
	
	
	/**
	 * TABINDEX NO THICKBOX DE HISTÓRIA
	 * Focar o tab nos principais campos, da adição de história.
	 * 
	 */
	if( $('#add_single_history_form').length ){
		var tabindex = 1;
		$('#add_single_history_form #post_title').focus();
		$('#add_single_history_form').find('.tagadd, .category-tabs a').removeAttr('tabindex');
		$('#add_single_history_form').find('.ipt_text, .newtag, #generochecklist input:checkbox').each(function(){
			console.log( $(this).tagName );
			$(this).attr('tabindex', tabindex);
			tabindex++;
		});
	}
	
	
});