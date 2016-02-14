/**
 * ADMIN SCRIPTS: ELEMENT SORTABLE
 * Elemetos de formulário com drag and drop.
 * 
 * 
 * Functions relacionadas:
 * - FORM_ELEMENTS
 * 		connected_pages_list
 * 		connected_contents_list
 * 		sortable_contents_list
 * 
 * 
 */

jQuery(document).ready(function($){
	
	/*
	 * Sortable do slider
	 */
	function sort_lists(obj){
		var config = {
			items:'.sortline',
			placeholder:'ui-sortabled', 
			revert:false,
			tolerance:'intersect',
			cursor:'crosshair',
			forceHelperSize:true,
			forcePlaceholderSize:true,
			//connectWith:'.sorts',
			opacity: 0.6,
			update: new_order,
			create: new_order
		}
		
		//$(this).sortable(config);
		
		$(obj).each(function(){
			if( $(this).children().size() > 0 ){
				if( $(this).is('.sort_hold_vertical') )
					config.axis = 'y';
				else
					config.axis = '';
				
				// conectar apenas no mesmo parent
				var this_sorts = $(this).find('.sorts');
				config.connectWith = this_sorts;
				
				$(this_sorts).sortable(config);
			}
		});
	};
	
	function new_order(){
		// computar apenas a ordem do build
		if( $(this).is('.menu_build') ){
			var ids = new Array();
			var menu = $(this).attr('id');
			$(this).find("li").each(function(){
				var selected_id = $(this).attr('id');
				selected_id = selected_id.replace('page_', ''); // >>>>>>>>>>>>>>>>>>>>>>>>>>>>> MUDAR PARA 'ITEM_ID'
				selected_id = selected_id.replace('cat_', '');
				selected_id = selected_id.replace('item_', '');
				if( selected_id != '' ){
					ids.push( selected_id );
				}
			});
			
			//alert(ids);
			$(this).parent().parent().find('.ids_list:first').val(ids);
		}
		
		// Adicionar classes extras
		if( $(this).children().size() <= 0 ){
			$(this).addClass('build_empty');
		}
		else{
			$(this).removeClass('build_empty');
		}
	}
	
	if( $('.sortable_box').length > 0 ){
		sort_lists('.sortable_box');
	}
	
});