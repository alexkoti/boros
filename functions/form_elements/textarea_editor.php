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
        $editor_css = apply_filters( 'boros_admin_editor_css', get_template_directory_uri() . '/css/editor.css' );
        
		// Usar $post->post_content no lugar do post_meta > $this->data['name']
		if( isset($this->data['options']['use_post_content']) )
			$this->data_value = $post->post_content;
		
		// Editor profiles
		$editor_profiles = array(
			'minimal' => array(
				'editor_type' => 'minimal',
				'toolbar' => 'bold italic undo redo code',
				'buttons' => 'bold,italic,|,undo,redo,|,code',
				'buttons2' => '',
				'buttons3' => '',
				'height' => 150,
				'css' => $editor_css,
			),
			'simple' => array(
				'editor_type' => 'simple',
				'toolbar' => 'bold italic link bullist numlist undo redo code',
				'buttons' => 'bold,italic,link,bullist,numlist,|,undo,redo,|,code',
				'buttons2' => '',
				'buttons3' => '',
				'height' => 150,
				'css' => $editor_css,
			),
			'full' => array(
				'editor_type' => 'full',
				'toolbar' => 'bold italic link bullist numlist alignleft aligncenter alignright undo redo image code',
				'buttons' => 'bold,italic,link,bullist,numlist,image,|,justifyleft,justifycenter,justifyright,|,undo,redo,|,code',
				'buttons2' => '',
				'buttons3' => '',
				'height' => 150,
				'css' => $editor_css,
			),
		);
		
		$editor_defs = array(
			'editor_type' => 'simple',
			'buttons' => 'bold,italic,link,bullist,numlist,|,code',
			'buttons2' => '',
			'buttons3' => '',
			'toolbar' => 'bold italic link bullist numlist code',
			'height' => 150,
			'css' => $editor_css,
		);
		
		/**
		 * $editor_attr['editor_type'] - define um profile pré-determinado. A menos que seja definido como 'custom', os demais valores enviados serão ignorados e
		 * usados os valores defaults
		 */
		if( !isset($this->data['options']['editor']) ){
			$this->data['options']['editor'] = 'minimal';
		}
		
		if( is_array($this->data['options']['editor']) ){
			$this->data['options']['editor']['editor_type'] = str_replace(array('[', ']'), array('_', ''), $this->data['name']);
			$editor_attr = wp_parse_args( $this->data['options']['editor'], $editor_defs );
		}
		else{
			$editor_attr = $editor_profiles[$this->data['options']['editor']];
		}
		
		/**
		 * Usar get_option('WPLANG'), pois em uma instalação multisite
		 * 
		 */
		$site_lang_option = get_option('WPLANG');
		if( empty($site_lang_option) ){
			$textarea_lang = 'en';
		}
		else{
			$lang_code = explode('_', $site_lang_option);
			$textarea_lang = $lang_code[0];
		}
		
		// começar a guardar o output do script js em buffer
		ob_start();
		
		// definir o input
		$this->data['attr']['class'] .= "form_textarea_editor editor_type_{$editor_attr['editor_type']}";
		$attrs = make_attributes($this->data['attr']);
		//pal($attrs);
		//$input_content = wpautop($this->data_value); // Não é mais necessário e causa bugs em UL|OL
		$input_content = $this->data_value;
		echo "<textarea {$attrs}>{$input_content}</textarea>{$this->input_helper}";
		//echo "<textarea id='{$this->data['name']}' name='{$this->data['name']}' class='form_element form_textarea form_textarea_editor editor_type_{$editor_attr['editor_type']}' rel='{$this->data['name']}'>{$input_content}</textarea>";
		?>
		
		<script type="text/javascript">
			// identificar idioma da instalação. Muito impotante, pois evita conflito que quebra a função do editor.
			var wp_language = '<?php echo $textarea_lang; ?>';
			
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
				menubar : false,
				//skin: 'wp_theme',
				//theme: 'advanced',
				toolbar: '<?php echo $editor_attr['toolbar']; ?>',
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
				<?php if( $textarea_lang != 'en' ){echo "language:'{$textarea_lang}',";} // identificar idioma da instalação ?>
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
				gecko_spellcheck: false,
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
				//plugins: 'inlinepopups,spellchecker,paste,wordpress,fullscreen,wpeditimage,wpgallery,tabfocus,wplink,wpdialogs', <<< verificar se ainda será preciso o 'inlinepopups' e encontrar um substituto
				plugins: 'paste, wordpress, fullscreen, wpeditimage, wpgallery, tabfocus, wplink, wpdialogs, image, code, textcolor, charmap',
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
	
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}