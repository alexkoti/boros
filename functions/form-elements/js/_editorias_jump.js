/**
 * 
 * 
 */

jQuery(document).ready(function($){
	
	if( $('.editorias_jump').length > 0 ){
		$('.editorias_jump').change(function(){
			//console.log( $(this).val() );
			window.location = $(this).val();
		});
	}
	
});