
jQuery(document).ready(function($){
	$('.boros_element_content_order').each(function(){
		
		var config = {
			update: new_content_order,
			create: new_content_order,
			axis:'y',
			cursor:'crosshair',
			forceHelperSize:true,
			forcePlaceholderSize:true,
			items:'.sort_item',
			opacity: 0.6,
			placeholder:'ui-state-highlight', 
			revert:false
		}
		$( '.content_order_list', this).sortable( config );
		
	});
	
	function new_content_order( event, ui ){
		var order = $(this).sortable('toArray', {attribute:'rel'});
		$(this).closest('.boros_element_content_order').find('.content_order_values').val( order );
	}
});