<?php
/**
 * MEDIA SELECTOR
 * 
 * 
 * 
 */

class BFE_media_selector extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'placeholder' => '',
		'size' => false,
		'disabled' => false,
		'readonly' => false,
		'maxlength' => false,
	);
	
	var $enqueues = array(
		'js' => 'thb.media-selector',
	);
	
	function set_input( $value = null ){
		ob_start();
		$input = ob_get_contents();
		ob_end_clean();
		?>
		<input type='button' value='Selecionar Imagem' id='media_selector_test' class='button' />
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$('#media_selector_test').click(function(){
				var media = new THB_MediaSelector({
					title : 'Selecionar Imagem',
					type : 'image',
					multiple : true,
					button : 'Definir imagens',
					close : function(){
						console.log('Media Selector fechada');
					},
					select: function( selected_images ){
						console.log( selected_images );
					}
				});
				media.open();
			});
		});
		</script>
		<?php
		return $input;
	}
}