<?php
/**
 * 
 * 
 * 
 * 
 */

register_widget('free_box');
class free_box extends WP_Widget {
	function free_box(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array(
			'classname' => 'free_box', 
			'description' => 'Box de conteúdo livre',
		);
		
		// opções do controle
		$control_ops = array(
			'width' => 500,
			'id_base' => 'free_box'
		);
		
		// registrar o widget
		$this->WP_Widget( 'free_box', 'Box livre', $widget_ops, $control_ops );
	}
	function widget($args, $instance){
		extract($args);
		
		if( $instance['link'] == '' ){
			$title = "<span>{$instance['titulo']}</span>";
		}
		else{
			$title = "<a href='{$instance['link']}'>{$instance['titulo']}</a>";
		}
		
		// exibir dados
		echo $before_widget;
		?>
		<div class="sidebar_box rss_box <?php echo "box_widget_layout_{$instance['layout']}";?>">
			<h2><?php echo $title; ?></h2>
			<div class="sidebar_box_content">
				<?php echo wpautop($instance['html']); ?>
			</div>
		</div>
		<?php
		echo $after_widget;
		
	}
	function form($instance){
		// sempre limpar valores vazios
		$instance = array_filter($instance);
		// defaults
		$defaults = array(
			'layout' => 'simples',
			'titulo' => 'Título do box',
			'link' => '',
			'html' => 'texto livre - html permitido',
		);
		// mesclar dados
		$instance = wp_parse_args( (array) $instance, $defaults );
		
		//configs dinâmicas do tiny_mce
		$editor_attr = array(
			'editor_type' => 'simple',
			'buttons' => 'bold,italic,link,unlink,|,bullist,numlist,|,undo,redo,|,code',
			'buttons2' => '',
			'buttons3' => '',
			'height' => 250,
			'css' => get_bloginfo('template_url') . '/css/site.css',
		);
		?>
			<div class="free_box_form">
				<p>
					Layout do Box: <br />
					<label for="<?php echo $this->get_field_id('layout'); ?>_simples" class="label_radio">
						<input type="radio" name="<?php echo $this->get_field_name('layout'); ?>" <?php checked('simples', $instance['layout']); ?> value="simples" id="<?php echo $this->get_field_id('layout'); ?>_simples" class="ipt_radio" /> Simples
					</label> 
					<label for="<?php echo $this->get_field_id('layout'); ?>_box" class="label_radio">
						<input type="radio" name="<?php echo $this->get_field_name('layout'); ?>"<?php checked('box', $instance['layout']); ?>  value="box" id="<?php echo $this->get_field_id('layout'); ?>_box" class="ipt_radio" /> Box
					</label> 
				</p>
				
				<hr />
				
				<p>
					<label for="<?php echo $this->get_field_id('titulo'); ?>">Título do Box:</label><br />
					<input type="text" id="<?php echo $this->get_field_id('titulo'); ?>" name="<?php echo $this->get_field_name('titulo'); ?>" value="<?php echo $instance['titulo']; ?>" class="ipt_size_full" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('link'); ?>">Link do chapéu:</label><br />
					<input type="text" id="<?php echo $this->get_field_id('link'); ?>" name="<?php echo $this->get_field_name('link'); ?>" value="<?php echo $instance['link']; ?>" class="ipt_size_full" />
				</p>
				
				<hr />
				
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
						//skin: 'wp_theme',
						//theme: 'advanced',
						menubar : false,
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
						body_id : '<?php echo $this->get_field_id('html'); ?>',
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
						plugins: 'paste,wordpress,fullscreen,wpeditimage,wpgallery,tabfocus,wplink,wpdialogs',
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
					
					/**
					 * ATENÇÃO >>> Configurando .mode = 'none', o tinymce não será renderizado onload, apenas as configs atribuidas para serem ativadas via
					 * tinyMCE.execCommand('mceRemoveEditor', false, id_do_elemento)
					 * 
					 */
					<?php
					$editor_profile = $editor_attr['editor_type'];
					$editor_id 		= $this->get_field_id('html');
					
					echo 
					"var {$editor_profile}_config = jQuery.extend(true, {}, base_config);
					{$editor_profile}_config.mode = 'none';
					{$editor_profile}_config.editor_selector = 'editor_type_{$editor_profile}';
					global_tinymce_config['by_class']['editor_type_{$editor_profile}'] = {$editor_profile}_config;
					
					global_tinymce_config['by_id']['{$editor_id}'] = jQuery.extend(true, {}, base_config);
					global_tinymce_config['by_id']['{$editor_id}'].mode = 'none';
					global_tinymce_config['by_id']['{$editor_id}'].elements = '{$editor_id}';
					";
					?>
				</script>
				<p>
					<label for="<?php echo $this->get_field_id('html'); ?>">Conteúdo (HTML permitido)</label><br />
					<textarea name="<?php echo $this->get_field_name('html'); ?>" id="<?php echo $this->get_field_id('html'); ?>" class="ipt_textarea form_element form_element_height_<?php echo $editor_attr['height']; ?> form_textarea form_text_editor editor_type_<?php echo $editor_attr['editor_type']; ?>"><?php echo format_to_edit(wpautop($instance['html'])); ?></textarea>
				</p>
			</div>
		<?php
	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['layout'] = $new_instance['layout'];
		$instance['titulo'] = $new_instance['titulo'];
		$instance['link'] = $new_instance['link'];
		$instance['html'] = $new_instance['html'];
		return $instance;
	}
}