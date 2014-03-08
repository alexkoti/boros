<?php
/**
 * FORM ELEMENT: CONNECTED_PAGES_LIST
 * Elemento input:text comum
 * 
 * 
 * 
 */

function form_element_connected_pages_list( $data, $data_value, $parent ){
	$connected_pages = explode( ',', get_option($data['name']) );
?>
	<div class="sortable_box">
		<input type="hidden" class="form_element ids_list" name="<?php echo $data['name']; ?>" id="<?php echo $data['name']; ?>_values" value="<?php if( is_array($data_value)) echo implode(',', $data_value); ?>" />
		
		<div class="box_order connected_box">
			Páginas:
			<ul class="ui_pages_list sorts menu_source" id="menu_site_source">
				<?php echo show_pages_menu_list( $type = 'flat', $filter = '', $filter_insert = '', $exclude = $connected_pages ); ?>
			</ul>
		</div>
		
		<div class="box_order connected_box" id="menu_build_box">
			Slider:
			<ul class="ui_pages_list sorts menu_build" id="menu_site_build">
			<?php
			// carregar o menu personalizado e montar o HTML da lista
			if( $connected_pages != ''){
				
				foreach( $connected_pages as $menu_item ){
					$p = get_page( $menu_item );
					$item_name = $p->post_title;
					echo '<li id="' . $menu_item . '" class="sortline">' . $item_name . '</li>';
				}
			}
			?>
			</ul>
		</div>
	</div>
<?php
}
?>