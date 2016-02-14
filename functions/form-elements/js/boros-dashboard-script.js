jQuery(document).ready(function($){
	
	$('#boros_dashboard_notifications_widget ol li .dashicons-dismiss').on('click', function(){
		var btn = $(this);
		var li = btn.parent('li');
		
		var data = {
			action : 'boros_dashboard_notifications_widget_remove_item',
			alert  : li.dataset('alert-name'),
			nonce  : li.dataset('alert-nonce')
		};
		
		$.post( ajaxurl, data, function( response ){
			if( response != -1 ){
				li.slideUp().remove();
			}
		});
	});
	
});