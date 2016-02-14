<?php
/**
 * FORM ELEMENT: SELECTABLE_PAGES_LIST
 * Elemento select com as pages
 * 
 * 
 * 
 */

function form_element_selectable_pages_list( $data, $data_value, $parent ){
?>
	<div class="form_ipt_selectable_pages_list">
		<input type="hidden" class="form_element form_hidden_value" name="<?php echo $data['name']; ?>" id="<?php echo $data['name']; ?>" value="<?php echo $data_value; ?>" />
		<p><?php echo $data['label']; ?></p>
		<ul id="selectable_pages_list" class="selectable_pages_list ui_pages_list">
			<li id="page_0" class="level_0">Nenhuma</li>
			<?php
			// filter necessita de argumento em array para ativar o selected
			echo show_pages_menu_list( $type = 'flat', $filter = explode(',', $data_value), $filter_insert = 'ui-selected' );
			?>
		</ul>
	</div>
<?php
}
?>