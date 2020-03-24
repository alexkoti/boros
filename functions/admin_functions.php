<?php
/**
 * FUNÇÔES DE ADMIN: STATIC
 * Funções específicas para a área administrativa
 * Agrupa todas as funções fixas que não precisem de configuração. Estas estão no arquivo 'functions/admin_config.php'
 * Adiciona funções auxiliares e, javascripts e css.
 * 
 * 
 * 
 */

/* ========================================================================== */
/* ADD ACTIONS/FILTERS ====================================================== */
/* ========================================================================== */
//FIXOS
add_action( 'admin_print_styles', 			'admin_styles' );
add_action( 'admin_enqueue_scripts', 		'admin_scripts', 11 ); //prioridade 100 para carregar por último
add_action( 'admin_print_footer_scripts', 	'admin_footer_scripts', 99 );



/* ========================================================================== */
/* ADMIN STYLES: HEAD ======================================================= */
/* ========================================================================== */
/**
 * CSS geral para todo o admin. Carrega em todas as páginas.
 * O thickbox é adicionado em todas as páginas pois será usado para diversas funções, como box de uploads, links, editor html.
 * 
 */
function admin_styles(){
	//CORES STYLES
	wp_enqueue_style( 'editor' );
	wp_enqueue_style( 'editor-buttons' );
	wp_enqueue_style( 'thickbox' );
	wp_enqueue_style( 'jquery-ui-core' );
	wp_enqueue_style( 'wp-jquery-ui-dialog' );
	//CUSTOM STYLES
	wp_enqueue_style( 'custom_admin_css', BOROS_CSS . 'admin.css' , array(), version_id(), 'screen' );
	wp_enqueue_style( 'events', BOROS_CSS . 'events.css' , array(), version_id(), 'screen' );
	wp_enqueue_style( 'form_elements', BOROS_CSS . 'form_elements.css' , array(), version_id(), 'screen' );
}



/* ========================================================================== */
/* ADMIN JAVASCRIPTS: HEAD ================================================== */
/* ========================================================================== */
/**
 * JAVASCRIPTS gerais para todo o admin. Carrega em todas as páginas.
 * 
 * @todo conferir melhor a aplicação em <<todas>> as páginas do admin.
 */
function admin_scripts(){
	//CORE SCRIPTS
	wp_enqueue_script( 'jquery-ui-selectable' );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'thickbox' );
	wp_enqueue_script( 'media-upload' );
	wp_enqueue_script( 'wp-ajax-response' );
	wp_enqueue_script( 'wp-lists' );
	wp_enqueue_script( 'quicktags' );
	wp_enqueue_script( 'postbox' );
	wp_enqueue_script( 'jquery-form' );
	wp_enqueue_script( 'post' );
	wp_enqueue_script( 'tiny_mce' );
	
	//CUSTOM SCRIPTS
	add_custom_admin_scripts();
}

function add_custom_admin_scripts(){
	$scripts = array(
		//'admin_scripts_duplicate_elements',
		//'admin_scripts_element_selectable',
		//'admin_scripts_element_sortable',
		//'admin_scripts_upload',
		'admin',
		'admin_ajax',
		'admin_scripts',
		'form_elements',
		'plugins',
		'events',
		//'current_work',
	);
	
	foreach( $scripts as $script ){
		wp_enqueue_script( $script, BOROS_JS . $script . '.js', array('jquery'), version_id() );
	}
	
	/**
	 * Adicionar scripts apenas para o admin do trabalho corrente, sem interferir na base fixa do plugin
	 * 
	 *
	foreach( glob( BOROS_CONFIG . "js/*.js" ) as $filename ){
		$path = pathinfo( $filename );
		$js_url = BOROS_URL . "config/js/{$path['basename']}";
		wp_enqueue_script( 'work_config_' . $path['filename'], $js_url, array('jquery'), NULL );
	}
	/**/
	
	// declarado separado para que seja aplicado no footer e por último
	//wp_enqueue_script( 'admin_footer_scripts', BOROS_JS . 'admin_footer_scripts.js', array('jquery'), NULL, true );
}



/* ========================================================================== */
/* ADMIN JAVASCRIPTS AND STYLES: FOOTER ===================================== */
/* ========================================================================== */
/**
 * Styles e scripts inline/footer
 * Irão rodar em todas as páginas do admin
 * 
 * 
 * 
 */
function admin_footer_scripts(){

// adicionar o tiny_mce fora do ambiente de edit-post
if ( !isset($pagenow) ) {
	//add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 25 );
	//add_action( 'admin_print_footer_scripts', 'wp_tiny_mce_preload_dialogs', 30 );
}
else{
	//pal('$pagenow não definido nesta página');
}
?>
	<style type="text/css">
	/* corrigir posicionamento do redimensionador inf-dir */
	#post #post-body .wp_themeSkin .mceStatusbar a.mceResize {top:-2px;}
	/* corrigir border no statusbar do tinymce na página de widgets */
	.wp_themeSkin .mceStatusbar {border-top-color:#DFDFDF;}
	</style>
	
	<script type="text/javascript">
	jQuery(document).ready(function($){
	<?php
	/**
	 * Coonfigurações para adicionar tiny_mce ao textareas do core
	 * 
	 * $core_textareas - array de ids dos textareas que deseja aplicar o editor
	 * Arquivos relacionados:
	 * 		/functions/taxonomy_admin.php - remove_filter('wp_filter_kses');
	 * 		/function/user_config.php - remove_filter('wp_filter_kses');
	 * 
	 */
	$modify_cores = true;
	$core_textareas = array(
		'tag-description',
		'description',
	);
	$editor_attr = array(
		'editor_type' => 'simple',
		'toolbar' => 'bold italic link bullist numlist code',
		'buttons' => 'bold,italic,link,bullist,numlist,|,code',
		'buttons2' => '',
		'buttons3' => '',
		'height' => 150,
		'css' => apply_filters( 'boros_admin_editor_css', get_template_directory_uri() . '/css/editor.css' ),
	);
	
	if( $modify_cores = true ){
		foreach( $core_textareas as $textarea ){
		?>
		global_tinymce_config['by_core']['<?php echo $textarea; ?>'] = {
			/* Configurações personalizadas
			 * - as variáveis são recebeidas do array de configuração
			 * 
			 * Notas:
			 * *1 - é preciso declarar os 'theme_advanced_buttons2' e 'theme_advanced_buttons3' para que sejam resetados
			 */
			mode: 'exact',
			elements: '<?php echo $textarea; ?>',
			menubar : false,
			//skin: 'wp_theme',
			//theme: 'advanced',
			toolbar : '<?php echo $editor_attr['toolbar']; ?>',
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
			<?php if( defined('WPLANG') and WPLANG == 'pt_BR' ){echo 'language:"pt",';} // identificar idioma da instalação ?>
			body_class : 'hentry',
			body_id : 'core_textarea',
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
			branding: false,
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
			plugins: 'paste, wordpress, fullscreen, wpeditimage, wpgallery, tabfocus, wplink, wpdialogs, image, code',
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
		<?php
		}
	}
	?>
		
		
		/**
		 * INICIAR TINY_MCEs
		 * Utiliza o global_tinymce_config e processa
		 * Arquivo relacionado: /js/admin_scripts.js
		 * 
		 */
		activate_tiny_mces();
	});
	</script>
<?php
}


