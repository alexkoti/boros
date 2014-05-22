/**
 * ADMIN SCRIPTS: ELEMENT SELECTABLE
 * Lista com seleção de múltiplos elementos shift/control+click
 * 
 * 
 * 
 * 
 */

jQuery(document).ready(function($){
	
	/**
	 * SELECTABLE
	 * 
	 */
	if( $('.selectable_pages_list').length ){
		$('.selectable_pages_list').selectable({
			stop: function(){
				var ids = new Array();
				//var result = $("#select-result").empty();
				$(".ui-selected", this).each(function(){
					var selected_id = $(this).attr('id');
					ids.push( selected_id.replace('page_','') );
					$(this).closest('.form_ipt_selectable_pages_list').find('.form_hidden_value').val(ids);
					//console.log( ids );
				});
			}
		});
	}
	
});