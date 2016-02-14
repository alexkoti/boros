<?php
/**
 * FORM ELEMENT: CONNECTED_CONTENTS_LIST
 * 
 * 
 * 
 */

function form_element_connected_contents_list( $data, $data_value, $parent ){
	// se já houver ordem gravada
	$slider_order = str_replace('item_', '', get_option($data['name'])); // ordem gravada
	$slider_itens = explode(',', $slider_order);	// ordem em array
	$ordered_slides = array();						// array vazio
	
	// elementos para serem exibidos
	$query = $data['options']['query'];
	$contents = new WP_Query();
	$contents->query($query);
	
	$build_class = ( $slider_order ) ? '' : 'build_empty';
	
	// começar a guardar o output do script js em buffer
	ob_start();
?>
	<div class="sortable_box box_connected_contents_list">
		<input type="hidden" class="form_element ids_list ipt_size_large" name="<?php echo $data['name']; ?>" id="<?php echo $data['name']; ?>_values" value="<?php if( is_array($data_value)) echo implode(',', $data_value); ?>" />
		<input type="hidden" name="post_meta_key" value="<?php echo $data['options']['query']['meta_key'];?>" />
		
		<div class="box_order connected_box">
			<p class="ico_move_right">Escolha entre os conteúdos disponíveis e arraste para o box da direita :</p>
			<ul class="ui_pages_list connected_list sorts menu_source <?php echo $build_class; ?>" id="<?php echo $data['name']; ?>_source">
			<?php 
			foreach( $contents->posts as $content ){
				if( !in_array($content->ID, $slider_itens) ){
					if( has_post_thumbnail($content->ID) )
						$thumb = get_the_post_thumbnail($content->ID, 'especiais');
					elseif( $content->post_type == 'separador' )
						$thumb = '';
					else
						$thumb = '<img src="' . BOROS_IMG . '/thumb_padrao.gif" alt="" />';
				?>
				<li id="item_<?php echo $content->ID; ?>" class="sortline item_type_<?php echo $content->post_type; ?>">
					<div class="item_data">
						<p><?php echo $content->post_title; ?></p>
					</div>
					<div class="item_thumb"><?php echo $thumb; ?></div>
						
					<div class="remover_destaque">
						<span class=" button" rel="<?php echo $content->ID; ?>">desabilitar destaque</span>
						<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" alt="" />
					</div>
				</li>
				<?php
				}
			}
			?>
			</ul>
		</div>
		
		<div class="box_order connected_box">
			<p class="ico_order">Ordene os conteúdos:</p>
			<ul class="ui_pages_list connected_list sorts menu_build <?php echo $build_class; ?>" id="<?php echo $data['name']; ?>_build">
			<?php
				// se já houver ordem gravada
				if( $slider_order ){
					foreach( $slider_itens as $slider ){
						foreach( $contents->posts as $content ){
							if( $content->ID == $slider ){
								$ordered_slides[] = $content;
							}
						}
					}
				}
				
				if( !empty($ordered_slides) ){
					foreach( $ordered_slides as $content ){
						if( has_post_thumbnail($content->ID) )
							$thumb = get_the_post_thumbnail($content->ID, 'especiais');
						elseif( $content->post_type == 'separador' )
							$thumb = '';
						else
							$thumb = '<img src="' . BOROS_IMG . '/thumb_padrao.gif" alt="" />';
						?>
						<li id="item_<?php echo $content->ID; ?>" class="sortline item_type_<?php echo $content->post_type; ?>">
							<div class="item_data">
								<p><?php echo $content->post_title; ?></p>
							</div>
							<div class="item_thumb"><?php echo $thumb; ?></div>
							
							<div class="remover_destaque">
								<span class=" button" rel="<?php echo $content->ID; ?>">desabilitar destaque</span>
								<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" alt="" />
							</div>
						</li>
						<?php
					}
				}
			?>
			</ul>
		</div>
	</div>
<?php
	// guardar o output em variável
	$input = ob_get_contents();
	ob_end_clean();
	
	// verificar o tipo de layout
	if( !isset($data['layout']) )
		$data['layout'] = 'table';
	
	// exibir conforme o layout
	switch( $data['layout'] ){
		case 'block':
			?>
			<tr>
				<td class="boros_form_element boros_element_text" colspan="2"><?php echo $input; ?></td>
			</tr>
			<?php
			break;
		
		
		case 'table':
		default:
			?>
			<tr>
				<th><label for="<?php echo $data['name']; ?>"><?php echo $data['label']; ?></label></th>
				<td><?php echo $input; ?></td>
			</tr>
			<?php
			break;
	}
}



/**
 * AJAX
 * Remover conteúdos da lista de destaques disponíveis
 * 
 */
// adicionar ajax ao head do admin
add_action('admin_head', 'connected_contents_list_js');
function connected_contents_list_js(){
?>
<script type="text/javascript">
jQuery(document).ready(function($){
	
	/**
	 * Recolher os dados para enviar - NÃO ESQUECER do 'action'
	 * Identificar o box principal no seletor(.box_connected_contents_list_multisite) para que tenha uma ação separada do coneected list comum
	 * 
	 */
	$('.box_connected_contents_list .remover_destaque span').click(function(){
		$(this).next('.ajax-loading').css('visibility', 'visible');
		
		var data = {
			action: 'connected_contents_save',
			post_id: $(this).attr('rel'),
			post_meta: $(this).closest('.box_connected_contents_list').find('input[name=post_meta_key]').val()
		};
		
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			//console.log( response );
			$('#'+response).fadeOut('slow').remove();
		});
		return false;
		
	});
});
</script>
<?php
}

// manipulação dos dados do ajax - apagar post_meta de destaque nacional 'featured_nacional'
add_action( 'wp_ajax_connected_contents_save', 'connected_contents_save' );
function connected_contents_save(){
	global $wpdb; // this is how you get access to the database
	$post_id = $_POST['post_id'];
	$post_meta = $_POST['post_meta'];
	
	delete_post_meta( $post_id, $post_meta );
	
	// string de retorno
	echo "item_{$post_id}";
	
	die(); // this is required to return a proper result
}
?>