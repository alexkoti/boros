<?php
/**
 * FORM ELEMENT: CONNECTED_CONTENTS_LIST_EXTENED
 * >>>>>>>>>>>>>>> gravar informações multiplas em array multinivel, em vez de valores separados por vírgula
 * 
 * 
 */

function form_element_connected_contents_list_extended( $data, $data_value, $parent ){
	// se já houver ordem gravada
	$slider_order = get_option($data['name']);		// ordem gravada
	$ordered_slides = array();						// array vazio
	$slider_itens = json_decode($slider_order, true);
	//pre($slider_itens, $data['name']);
	
	if( !is_array($slider_itens) )
		$slider_itens = array();
	
	//config de cores
	$colors = get_option('chapeu_colors');
	
	// elementos para serem exibidos
	if( isset($data['options']['query']) )
		$contents = get_posts($data['options']['query']);	// posts habilitados -> faz uma query personalizada
	else
		$contents = get_pages();
	
	$build_class = ( $slider_order ) ? '' : 'build_empty';
?>
	<div class="box_connected_contents_list box_connected_contents_list_extended">
		<input type="hidden" class="form_element ids_list" name="<?php echo $data['name']; ?>" id="<?php echo $data['name']; ?>_values" value='<?php if( isset($slider_order)) echo $slider_order; ?>' />
		
		<div class="box_order connected_box">
			<p class="ico_move_right">Escolha entre os conteúdos disponíveis e arraste para o box da direita :</p>
			<ul class="ui_pages_list connected_list sorts menu_source <?php echo $build_class; ?>" id="<?php echo $data['name']; ?>_source">
				<?php 
				foreach( $contents as $content ){
					
					if( !array_key_exists($content->ID, $slider_itens) ){
						if( has_post_thumbnail($content->ID) )
							$thumb = get_the_post_thumbnail($content->ID, 'pequena');
						else
							$thumb = '<img src="' . get_bloginfo('template_url') . '/css/img/default_thumb.png" alt="" />';
						?>
						<li id="<?php echo $content->ID; ?>" class="sortline">
							<div class="item_data">
								<p><?php echo $content->post_title; ?></p>
								<div class="ipt_radio_group" id="radio_<?php echo $data['name']; ?>">
									<?php
									$args = array(
										'name' => $content->ID,
									);
									radio_colors($args);
									?>
								</div>
							</div>
							<div class="item_thumb"><?php echo $thumb; ?></div>
						</li>
						<?php
					}
				}
				?>
			</ul>
		</div>
		
		<div class="box_order connected_box">
			<p class="ico_order">Ordene o slider:</p>
			<ul class="ui_pages_list connected_list sorts menu_build <?php echo $build_class; ?>" id="<?php echo $data['name']; ?>_build">
			<?php
				// se já houver ordem gravada
				if( $slider_order ){
					foreach( $slider_itens as $slider => $color ){
						foreach( $contents as $content ){
							if( $content->ID == $slider ){
								$ordered_slides[] = $content;
							}
						}
					}
				}
				
				if( !empty($ordered_slides) ){
					foreach( $ordered_slides as $content ){
						if( has_post_thumbnail($content->ID) )
							$thumb = get_the_post_thumbnail($content->ID, 'thumbnail');
						else
							$thumb = '<img src="' . get_bloginfo('template_url') . '/css/img/default_thumb.png" alt="" />';
						?>
						<li id="item_<?php echo $content->ID; ?>" class="sortline">
							<div class="item_data">
								<p><?php echo $content->post_title; ?></p>
								
								<?php
								$args = array(
									'name' => $content->ID,
									'checked' => $slider_itens[$content->ID],
								);
								radio_colors($args);
								?>
							</div>
							<div class="item_thumb"><?php echo $thumb; ?></div>
						</li>
						<?php
					}
				}
			?>
			</ul>
		</div>
	</div>
<?php
}
?>