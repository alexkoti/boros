<?php
/**
 * TEXTAREA
 * textarea simples
 * 
 * @TODO Adicionar mais modelos preé-configurados de editor
 * 
 * 
 */

class BFE_textarea_editor extends BorosFormElement {
	/**
	 * ATENçÃO: foi removido o 'value' da lista, pois ele não possui esse atributo.
	 * 
	 */
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => 'ipt_textarea_editor',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
		'cols' => 60,
		'rows' => 20,
	);
	
	function set_input( $value = null ){
		global $post;
		// Usar $post->post_content no lugar do post_meta > $this->data['name']
		if( isset($this->data['options']['use_post_content']) )
			$this->data_value = $post->post_content;
		
		// Editor profiles
		$editor_profiles = array(
			'minimal' => array(
				'editor_type' => 'minimal',
				'buttons' => 'bold,italic,|,undo,redo,|,code',
				'buttons2' => '',
				'buttons3' => '',
				'height' => 150,
				'css' => get_bloginfo('template_url') . '/css/editor.css',
			),
			'simple' => array(
				'editor_type' => 'simple',
				'buttons' => 'bold,italic,link,bullist,numlist,|,undo,redo,|,code',
				'buttons2' => '',
				'buttons3' => '',
				'height' => 150,
				'css' => get_bloginfo('template_url') . '/css/editor.css',
			),
			'full' => array(
				'editor_type' => 'full',
				'buttons' => 'bold,italic,link,bullist,numlist,image,|,justifyleft,justifycenter,justifyright,|,undo,redo,|,code',
				'buttons2' => '',
				'buttons3' => '',
				'height' => 150,
				'css' => get_bloginfo('template_url') . '/css/editor.css',
			),
		);
		
		$editor_defs = array(
			'editor_type' => 'simple',
			'buttons' => 'bold,italic,link,bullist,numlist,|,code',
			'buttons2' => '',
			'buttons3' => '',
			'height' => 150,
			'css' => get_bloginfo('template_url') . '/css/editor.css',
		);
		
		/**
		 * $editor_attr['editor_type'] - define um profile pré-determinado. A menos que seja definido como 'custom', os demais valores enviados serão ignorados e
		 * usados os valores defaults
		 */
		if( !isset($this->data['options']['editor']) )
			$this->data['options']['editor'] = 'minimal';
		
		if( is_array($this->data['options']['editor']) ){
			$this->data['options']['editor']['editor_type'] = $this->data['name'];
			$editor_attr = wp_parse_args( $this->data['options']['editor'], $editor_defs );
		}
		else{
			$editor_attr = $editor_profiles[$this->data['options']['editor']];
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
				body_id : '<?php echo $this->data['name']; ?>',
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
				apply_source_formatting: false, 				// indentar o código
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
		// definir o input
		$this->data['attr']['class'] .= "form_textarea_editor editor_type_{$editor_attr['editor_type']}";
		$attrs = make_attributes($this->data['attr']);
		//pal($attrs);
		$input_content = wpautop($this->data_value);
		echo "<textarea {$attrs}>{$input_content}</textarea>{$this->input_helper}";
		//echo "<textarea id='{$this->data['name']}' name='{$this->data['name']}' class='form_element form_textarea form_textarea_editor editor_type_{$editor_attr['editor_type']}' rel='{$this->data['name']}'>{$input_content}</textarea>";
	
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}