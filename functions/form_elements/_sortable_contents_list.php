<?php
/**
 * FORM ELEMENT: SORTABLE_CONTENTS_LIST
 * 
 * 
 * 
 */

function form_element_sortable_contents_list( $data, $data_value, $parent ){
	$contents = new WP_Query();
	$contents->query($data['options']['query']);	// posts habilitados
	$slider_order = get_option($data['name']);		// ordem gravada
	$ordered_slides = array();						// array vazio
	
	/**
	pre($slider_order);
	foreach($contents->posts as $p){
		pre($p->post_title);
	}
	/**/
	?>
	<div class="sortable_box">
		<input type="hidden" class="form_element ids_list" name="<?php echo $data['name']; ?>" id="<?php echo $data['name']; ?>_values" value="<?php echo $data_value; ?>" />
		
		<div class="box_order">
			Slider:
			<?php if( $contents->posts ){ ?>
			<ul class="ui_pages_list sorts menu_build sort_hold_vertical">
			<?php
				
				// se já houver ordem gravada
				if( $slider_order ){
					$slider_itens = explode(',', $slider_order);	// ordem em array
					
					foreach( $slider_itens as $slider ){
						foreach( $contents->posts as $content ){
							if( $content->ID == $slider ){
								$ordered_slides[] = $content;
							}
						}
					}
					foreach( $contents->posts as $content ){
						if( !in_array($content->ID, $slider_itens) ){
							$ordered_slides[] = $content;
						}
					}
				}
				else{
					$ordered_slides = $contents->posts;
				}
				
				if( !empty($ordered_slides) ){
					foreach( $ordered_slides as $destaque ){
						if( has_post_thumbnail($destaque->ID) )
							$thumb = get_the_post_thumbnail($destaque->ID, 'thumbnail');
						else
							$thumb = '<img src="' . get_bloginfo('template_url') . '/css/img/default_thumb.png" alt="" />';
						echo '<li id="page_' . $destaque->ID . '" class="sortline">' . ' [' . $destaque->ID . '] ' . $destaque->post_title . $thumb . '</li>';
					}
				}
			?>
			</ul>
			<?php } else { ?>
			<p>Sem conteúdos disponíveis. Marque em um post, página ou agenda a opção <strong>Destaque da Home[slider]</strong></p>
			<?php } ?>
		</div>
	</div>
<?php
}
?>