
jQuery(document).ready(function($){
	
	function activate_recaptcha( obj ){
		var target = obj.attr('href');
		var ajax_recaptcha_div = $(target).find('.ajax_recaptcha_div');
		var div_id = ajax_recaptcha_div.attr('id');
		var theme = ajax_recaptcha_div.data('theme');
		var publickey = ajax_recaptcha_div.data('publickey');
		Recaptcha.create( publickey, div_id, { tabindex: 1, theme: theme } );
	}
	
	$('.ajax_recaptcha_show').click(function(){
		activate_recaptcha( $(this) );
	});
	
	if( $('.ajax_recaptcha_div').length > 0 ){
		var visible = $('.ajax_recaptcha_div:visible');
		var tab = visible.closest('.tab-pane').attr('id');
		activate_recaptcha( $('a[href="#'+tab+'"]') );
	}
	
});