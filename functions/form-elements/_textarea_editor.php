<?php
/**
 * FORM ELEMENT: TEXTAREA
 * 
 * 
 * 
 */

function form_element_textarea_editor( $data, $data_value, $parent ){
	global $post;
	// Usar $post->post_content no lugar do post_meta > $data['name']
	if( isset($data['options']['use_post_content']) )
		$data_value = $post->post_content;
	
	// Editor profiles
	$editor_profiles = array(
		'minimal' => array(
			'editor_type' => 'minimal',
			'buttons' => 'bold,italic,|,undo,redo,|,code',
			'buttons2' => '',
			'buttons3' => '',
			'height' => 150,
			'css' => get_bloginfo('template_url') . '/css/site.css',
		),
		'simple' => array(
			'editor_type' => 'simple',
			'buttons' => 'bold,italic,link,bullist,numlist,|,undo,redo,|,code',
			'buttons2' => '',
			'buttons3' => '',
			'height' => 150,
			'css' => get_bloginfo('template_url') . '/css/site.css',
		),
		'full' => array(
			'editor_type' => 'full',
			'buttons' => 'bold,italic,link,bullist,numlist,image,|,justifyleft,justifycenter,justifyright,|,undo,redo,|,code',
			'buttons2' => '',
			'buttons3' => '',
			'height' => 150,
			'css' => get_bloginfo('template_url') . '/css/site.css',
		),
	);
	
	$editor_defs = array(
		'editor_type' => 'simple',
		'buttons' => 'bold,italic,link,bullist,numlist,|,code',
		'buttons2' => '',
		'buttons3' => '',
		'height' => 150,
		'css' => get_bloginfo('template_url') . '/css/site.css',
	);
	
	/**
	 * $editor_attr['editor_type'] - define um profile pré-determinado. A menos que seja definido como 'custom', os demais valores enviados serão ignorados e
	 * usados os valores defaults
	 */
	if( !isset($data['options']['editor']) )
		$data['options']['editor'] = 'minimal';
	
	if( is_array($data['options']['editor']) ){
		$data['options']['editor']['editor_type'] = $data['name'];
		$editor_attr = wp_parse_args( $data['options']['editor'], $editor_defs );
	}
	else{
		$editor_attr = $editor_profiles[$data['options']['editor']];
	}
	
	// começar a guardar o output do script js em buffer
	ob_start();
	?>
	
	<script type="text/javascript">
		<?php 
		// identificar idioma da instalação. Muito impotante, pois evita conflito que quebra a função do editor.
		echo ( WPLANG == 'pt_BR' ) ? 'var wp_language = "pt"' : 'var wp_language = "en";' ;
		?>
		
		/**
		 * A aplicação de cada 'profile' de editor e selecionado conforme a class do textarea original, definido no array de configuração dos metaboxes.
		 * 
		 * 
		 */
		var base_config = {
			/* Configurações personalizadas
			 * - as variáveis são recebeidas do array de configuração
			 * 
			 * Notas:
			 * *1 - é preciso declarar os 'theme_advanced_buttons2' e 'theme_advanced_buttons3' para que sejam resetados
			 */
			mode: "specific_textareas",
			editor_selector : 'editor_type_<?php echo $editor_attr['editor_type']; ?>',
			skin: 'wp_theme',
			theme: 'advanced',
			theme_advanced_buttons1: '<?php echo $editor_attr['buttons']; ?>',
			theme_advanced_buttons2: '<?php echo $editor_attr['buttons2']; ?>', // (*1)
			theme_advanced_buttons3: '<?php echo $editor_attr['buttons3']; ?>', // (*1)
			height: <?php echo $editor_attr['height']; ?>, // altura
			content_css: '<?php echo $editor_attr['css']; ?>', //css interno do editor
			
			/* Configurações fixas
			 * 
			 * Notas:
			 * *a - desabilitado pois não funciona com páginas organizadas com urls amigáveis
			 */
			<?php if( WPLANG == 'pt_BR' ){echo 'language:"pt",';} // identificar idioma da instalação ?>
			body_class : 'hentry',
			body_id : '<?php echo $data['name']; ?>',
			width: '100%', 									// largura
			theme_advanced_toolbar_location: 'top', 		// posicionamento do editor
			theme_advanced_toolbar_align: 'left', 			// posicionamento do editor
			theme_advanced_resizing: true,					// resizing do editor
			theme_advanced_statusbar_location: 'bottom', 	// posição do resizer
			theme_advanced_resize_horizontal: false, 		// permitir apenas o resize vertical
			theme_advanced_path: false, 					// não mostrar o caminho Xpath no statusbar
			dialog_type : 'modal',							// para as janelas modais é preciso definir o 'dialog_type' e o plugin 'inlinepopups'
			relative_urls: false, 							// manter as urls absolutas
			convert_urls: false, 							// converter urls para o caminho relativo se estiver aplicado(*a)
			apply_source_formatting: true, 					// indentar o código
			remove_linebreaks: false, 						// remover <br>s
			remove_script_host: false, 
			gecko_spellcheck: true,
			entities: '38,amp,60,lt,62,gt', 
			//accessibility_focus: true, 
			//tabfocus_elements: 'major-publishing-actions', 
			media_strict: false, 
			paste_remove_styles: true, 						// limpar código ao colar
			paste_remove_spans: true, 						// limpar código ao colar
			paste_strip_class_attributes: 'all', 			// limpar código ao colar
			paste_text_use_dialog: true, 					// limpar código ao colar
			wpeditimage_disable_captions: false, 
			theme_advanced_blockformats: 'p,blockquote,h1,h2,h3,h4,h5,h6',
			plugins: 'inlinepopups,spellchecker,paste,wordpress,fullscreen,wpeditimage,wpgallery,tabfocus,wplink,wpdialogs',
			formats:{
				alignleft : [
					{selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles : {textAlign : 'left'}},
					{selector : 'img,table', classes : 'alignleft'}
				],
				aligncenter : [
					{selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles : {textAlign : 'center'}},
					{selector : 'img,table', classes : 'aligncenter'}
				],
				alignright : [
					{selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles : {textAlign : 'right'}},
					{selector : 'img,table', classes : 'alignright'}
				],
				strikethrough : {inline : 'del'}
			}
		};
		
		var <?php echo $editor_attr['editor_type']; ?>_config = jQuery.extend(true, {}, base_config);
		global_tinymce_config['by_class']['editor_type_<?php echo $editor_attr['editor_type']; ?>'] = <?php echo $editor_attr['editor_type']; ?>_config;
	</script>
	
	<?php
	// guardar o output em variável
	$js = ob_get_contents();
	ob_end_clean();
	
	// definir o input
	$input_content = format_to_edit( wpautop($data_value) );
	$input = "<textarea id='{$data['name']}' name='{$data['name']}' class='form_element form_textarea form_text_editor editor_type_{$editor_attr['editor_type']}' rel='{$data['name']}'>{$input_content}</textarea>";
	
	// verificar o tipo de layout
	if( !isset($data['layout']) )
		$data['layout'] = 'table';
	
	// exibir conforme o layout
	switch( $data['layout'] ){
		case 'simple':
			echo $input;
			echo $js;
			break;
		
		case 'block':
			$label = (!empty($data['label'])) ? "<p><label for='{$data['name']}'>{$data['label']}</label></p>" : "";
			?>
			<tr>
				<td class="boros_form_element boros_element_textarea" colspan="2">
					<?php echo $label; ?>
					<?php echo $input; ?>
					<?php echo $js; ?>
				</td>
			</tr>
			<?php
			break;
		
		
		case 'table':
		default:
			?>
			<tr>
				<th class="boros_form_element boros_element_textarea boros_form_element_th"><?php echo $data['label']; ?></th>
				<td class="boros_form_element boros_element_textarea">
					<?php echo $input; ?>
					<?php echo $js; ?>
				</td>
			</tr>
			<?php
			break;
	}
}
?>