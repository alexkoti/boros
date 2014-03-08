jQuery(document).ready(function($){
	
	/**
	 * TAXONOMIAS DIRETAMENTE NA TELA DE PRODUTO
	 * Normalmente as taxonomias referentes à editora, são herdados da série a qual pertence a revista. Mas quando esta não pertence à nenhuma série ou coleção é 
	 * preciso
	 * 
	 */
	/**
	$('#product_unique_taxonomies').change(function(){
		var checkbox = $(this);
		if( checkbox.is(':checked') ){
			var results = checkbox.closest('.excs_product_taxonomies').find('.excs_product_taxonomies_fields');
			
			// preparar dados da busca
			var data = {
				action: 'excs_product_taxonomies_get_fields',
				ajax_post_id: $('input[name="post_ID"]').val()
			};
			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			$.get(ajaxurl, data, function(response) {
				results.hide().html(response).slideDown();
			});
		}
		else{
			$(this).closest('.excs_product_taxonomies').find('.excs_product_taxonomies_fields').slideUp().empty();
		}
	});
	/**/
	
	
});