/**
 * FORM ELEMENTS
 * Arquivo de scripts gerais para form elements.
 * Usar caso o código necessário opara o element seja muito pequeno, não necessitando de um arquivo separado, OU quando é preciso compartilhar o mesmo script 
 * para diversos elements, pois os enqueues de elements são contextuais, e só carregam quando o element é utilizado na página.
 * 
 */

jQuery(document).ready(function($){
	
	/**
	 * TAXONOMY CHECKBOX
	 * 
	 */
	$('.force_hierachical .input_checkbox').change(function(){
		var checkbox = $(this);
		// caso seja um child, marcar o parent
		if( checkbox.is('.force_hierachical .children li .input_checkbox') ){
			var parent_check = checkbox.closest('.children').parent().find('.input_checkbox:first');
			if( checkbox.is(':checked') ){
				parent_check.attr('checked', true);
			}
		}
		
		// caso seja um parent, marcar os childs, se houver
		if( checkbox.not(':checked') ){
			checkbox.closest('li').find('.children .input_checkbox').attr('checked', false);
		}
	})
	
});