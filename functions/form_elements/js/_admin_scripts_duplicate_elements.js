/**
 * ADMIN SCRIPTS: DUPLICATE ELEMENT
 * Duplicar elementos e blocos de elementos.
 * 
 * 
 * TODO: VERIFICAR A "LIMPEZA" DOS INPUTS DUPLICADOS, COM A OPÇÂO DE TER DEFAULTS
 * TODO: DOCUMENTAR MELHOR ESSAS FUNCTIONS
 * 
 */

jQuery(document).ready(function($){
	
	/*
	 * Sortable dos widgets
	 */
	function order_lists(obj){
		$(obj).sortable({
			items:'.duplicate_element',
			placeholder:'ui-sortabled', 
			revert:false,
			tolerance:'intersect',
			cursor:'crosshair',
			forceHelperSize:true,
			forcePlaceholderSize:true,
			handle:'.btn_move',
			opacity: 0.8,
			update: re_index_duplicates,
			axis: 'y'
		});
	};
	
	if( $('.duplicate_group').length > 0 ){
		order_lists(".duplicate_group");
	}
	
	
	
	if( $(".duplicate_element").length ){
		re_index_duplicates();
		
		$(".btn_remove").click(function(){
			// caso seja elemento único
			if( $(this).closest('.duplicate_group').find('.duplicate_element').length == 1 ){
				reset_element($(this).closest('.duplicate_element'));
			}
			else{
				remove_element($(this));
				re_index_duplicates();
			}
		});
		
		$(".dup_btn span").click(function(){
			duplicate_element($(this));
			re_index_duplicates();
		});
	}
	
	
	
	function remove_element(obj){
		obj.closest('.duplicate_element').slideUp(function(){
			$(this).remove();
		});
	}
	
	
	
	function duplicate_element(obj){
		var duplicate = obj.parent().prev().children('.duplicate_element:first').clone(true);
		// resetar valores
		
		duplicate.hide();
		obj.parent().prev().append(duplicate);
		re_index_duplicates();
		reset_element($(duplicate));
		duplicate.slideDown();
	}
	
	
	
	function reset_element(obj){
		obj.find('textarea').html('');
		obj.find('[value]').val('');
		obj.find('[src]').attr('src', '').css('display', 'none');
	}
	
	
	
	// modificar os names para o envio em array
	function re_index_duplicates(){
		// primeiro agrupar por widget
		$('.duplicate_group').each(function(){
		
			var index = 0;
			
			// esconder botões de remover caso só exista um elemento
			/*if( $(this).find('.duplicate_element').length > 1 ){
				$(this).find('.remove').show();
			}
			else{
				$(this).find('.remove').hide();
			}*/
			
			$(this).find('.duplicate_element').each(function(){
				
				
				// re-indexar os inputs
				var prefix = $(this).attr('rel');
				//var index = $(".duplicate_element").index(this);
				$(this).attr('id', prefix + '_' + index);
				
				$(this).find('.form_element').each(function(){
					var original_name = $(this).attr('rel');
					var new_name = original_name.replace(/\[\d\]/g, "[" + index + "]");
					
					var new_id = original_name.replace(/\[\d\]/g, "_" + index + "_");
					new_id = new_id.replace('[', '');
					new_id = new_id.replace(']', '');
					
					// btn thickbox
					$(this).siblings('.thickbox').attr('rel', new_id);
					
					// label
					$(this).siblings('label').attr('for', new_id);
					// id
					$(this).attr('id', new_id);
					// name
					$(this).attr('name', new_name);
				});
				
				index++;
			});
		
		});
	}
	
});