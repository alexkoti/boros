jQuery(document).ready(function($){
	
	// esconder por default a descrição do dano:
	var product_damage_checkbox = $('.ipt_form_checkbox:contains(" Danificado")');
	var product_damage_description = $('#product_damage_description').closest('.boros_form_element');
	
	/**
	 * Verificação de checkbox parents/childs, marcando os parents ao checkar um child e desmarcando os childs ao descheckar o parent
	 * 
	 */
	$('.excs_tax_box_term_list .ipt_form_checkbox').change(function(){
		var checkbox = $(this);
		// caso seja um child, marcar o parent
		if( checkbox.is('.excs_tax_box_term_list .children li .label_checkbox .ipt_form_checkbox') ){
			var parent_check = checkbox.closest('.children').parent().find('.ipt_form_checkbox:first');
			if( checkbox.is(':checked') ){
				parent_check.attr('checked', true);
			}
		}
		
		// caso seja um parent, marcar os childs, se houver
		if( checkbox.not(':checked') ){
			checkbox.closest('li').find('.children .ipt_form_checkbox').attr('checked', false);
		}
		
		toggle_damage_description(checkbox);
		
	}).each(function(){
		toggle_damage_description($(this));
	});
	
	function toggle_damage_description(checkbox){
		// Verificar se é a opção "Danificado" e exibir/ocultar o campo de descrição do dano. Atenção ao espaço em branco no começo
		var top_parent = checkbox.closest('.terms_level_0').find('.label_checkbox:first .ipt_form_checkbox');
		if( top_parent.parent().text() == ' Danificado' ){
			if( top_parent.is(':checked') )
				product_damage_description.show();
			else
				product_damage_description.hide();
		}
	}
	
});