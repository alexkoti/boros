

jQuery(document).ready(function($){
	
	$('#duplicate_contact').click(function(){
		$('#trip_contact_list li:first').clone().appendTo( $('#trip_contact_list') );
		$('#trip_contact_list li:last input').val('');
		$('#trip_contact_list li:last select option:first').attr("selected", true);
		reindex_trip_contacts();
	});
	
	function reindex_trip_contacts(){
		//console.log('reindex_trip_contacts');
		$('#trip_contact_list li').each(function(){
			var index = $(this).index();
			$(this).find('input, select').each(function(){
				//console.log( $(this).attr('name') );
				var original_name = $(this).attr('name');
				var new_name = original_name.replace(/\[\d\]/g, "[" + index + "]");
				$(this).attr('name', new_name);
				//console.log( new_name );
			});
		});
	}
	
	function sortable_trip_contacts(obj){
		$(obj).sortable({
			items:'li',
			placeholder:'ui-sortabled', 
			revert:false,
			tolerance:'intersect',
			forceHelperSize:true,
			forcePlaceholderSize:true,
			opacity: 0.8,
			update: reindex_trip_contacts,
			axis: 'y'
		});
	};
	if( $('#trip_contact_list').length > 0 ){
		sortable_trip_contacts("#trip_contact_list");
	}
	
	$('#trip_comercial_contatos').delegate('.remove_contact', 'click', function(){
		//console.log($(this));
		$(this).closest('li').slideUp(400, function(){
			$(this).remove();
		});
		reindex_trip_contacts();
	});
	
});