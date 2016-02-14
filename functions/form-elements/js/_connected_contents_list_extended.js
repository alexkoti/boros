/* form_element: text *//**
 * ADMIN SCRIPTS: ELEMENT CONNECTED CONTENTS LIST - EXTENDED
 * Elemetos de formulário com drag and drop, gravando dados em array associativo.
 * 
 * 
 * Functions relacionadas:
 * - FORM_ELEMENTS
 * 		connected_pages_list
 * 		connected_contents_list
 * 		sortable_contents_list
 * 
 * 
 * 
 */

jQuery(document).ready(function($){
	
	/*
	 * Sortable do slider
	 */
	function sort_list_extended(obj){
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
			update: new_order_extended,
			create: new_order_extended
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
	
	
	// guardar dados no input:hidden
	function new_order_extended( a ){
		var obj = $(this);
	
		// computar apenas a ordem do build
		if( $(obj).is('.menu_build') ){
			new_order_extended_values( $(obj) );
		}
		
		// Adicionar classes extras
		if( $(obj).children().size() <= 0 ){
			$(obj).addClass('build_empty');
		}
		else{
			$(obj).removeClass('build_empty');
		}
	}
	function new_order_extended_values( obj ){
		//console.log(obj);
		var ids = new Object;
		var menu = $(obj).attr('id');
		$(obj).find("li.sortline").each(function(){
			//console.log( $(this) );
			// pegar id do item
			var selected_id = $(this).attr('id');
			selected_id = selected_id.replace('item_', '');
			// pegar cor escolhida
			var selected_color = $(this).find('input[type=radio]:checked').val();
			//console.log(selected_id);
			//console.log(selected_color);
			ids[selected_id] = selected_color;
		});
		
		//console.log(ids);
		$(obj).closest('.box_connected_contents_list_extended').find('.ids_list:first').val( JSON.stringify(ids) );
	}
	
	if( $('.box_connected_contents_list_extended').length > 0 ){
		sort_list_extended('.box_connected_contents_list_extended');
	}
	
	// reordenar e guardar ao mudar de cor
	$('.connected_box .ipt_radio_group [type=radio]').change(function(){
		//console.log( $(this).closest('.menu_build') );
		new_order_extended_values( $(this).closest('.menu_build') );
	});
	
});