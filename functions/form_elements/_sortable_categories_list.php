<?php
/**
 * FORM ELEMENT: SORTABLE_CATEGORIES_LIST
 * 
 * 
 * @TODO: CORRIGIR FUNCIONAMENTO DESTE CONTROLE
 * 
 * 
 */

function form_element_sortable_categories_list( $data, $data_value, $parent ){
		
		if( function_exists('mycategoryorder_init') ){
	?>
	
	<p>Para o controle das categorias do menu do blog, configurar em <a href="<?php echo get_bloginfo('wpurl').'/wp-admin/edit.php?page=mycategoryorder'; ?>">My Category Order</a>.</p>
	
	<?php } else { ?>
	
	<input type="hidden" class="form_element blog_ipt ids_list" name="<?php echo $data['name']; ?>" id="<?php echo $data['name']; ?>_values" value="<?php if( is_array($data_value)) echo implode(',', $data_value); ?>" />
	
	<div class="box_order">
		Categorias:
		<ul class="ui_pages_list sorts menu_source" id="menu_blog_source">
			<?php echo show_categories_menu_list( $type='flat' ); ?>
		</ul>
	</div>
	
	<div class="box_order_cats" id="menu_blog_build_box">
		Menu:
		<ul class="ui_pages_list sorts menu_build" id="menu_blog_build">
		<?php
		// carregar o menu personalizado e montar o HTML da lista
		$fhios_blog_cats = get_option($data['name']);
		if( $fhios_blog_cats != ''){
			
			foreach( $fhios_blog_cats as $menu_item ){
				$c = get_category( $menu_item );
				$item_name = $c->name;
				echo '<li id="' . $menu_item . '" class="' . $line . ' sortline">' . $item_name . '</li>';
			}
		}
		?>
		</ul>
	</div>
	<?php } ?>
<?php
}
?>