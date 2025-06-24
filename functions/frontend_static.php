<?php
/**
 * ==================================================
 * STATIC FILTERS AND ACTIONS =======================
 * ==================================================
 * Filtros e ações fixas que não precisam ser configuradas e que valem para qualquer trabalho.
 * 
 * 
 */



/**
 * ==================================================
 * AJAX URL =========================================
 * ==================================================
 * 
 * 
 */
add_action( 'wp_head','boros_frontend_ajaxurl' );
function boros_frontend_ajaxurl() {
?>
<script type="text/javascript">
var ajaxurl   = '<?php echo admin_url('admin-ajax.php'); ?>';
var home_url  = '<?php echo home_url('/'); ?>';
var theme_url = '<?php if( defined('THEME') ){ echo THEME; } else { echo get_template_directory_uri(); } ?>/';
</script>
<?php
}



/**
 * ==================================================
 * EXTRA BODY AND POST CLASS ========================
 * ==================================================
 * 
 * Adicionar novas classes à função original
 * 
 */
add_filter( 'post_class', 'new_post_class', 10, 3 );
function new_post_class( $classes, $class, $post_id ){
	// Pegar todas as taxonomias e ordenar por prioridade caso exista essa definição em get_option();
	$taxonomies = get_option( 'taxonomy_priorities' );
	if( !$taxonomies )
		$taxonomies = get_taxonomies();
	
	foreach( $taxonomies as $taxonomy ){
		$terms = get_the_terms( $post_id, $taxonomy );
		if( !empty($terms) ){
            ////@bug fatal error em walk_simple_taxonomy();
            //pre($terms);
			//$args = array(
			//	'orderby' => 'name',
			//	'order' => 'ASC',
			//	'hide_empty' => 0,
			//	'depth' => 0,
			//);
			//// organizar os termos em ordem hierárquica, do nível mais baixo para o mais alto
			//$ordered_terms = walk_simple_taxonomy( $terms, $args['depth'], $args );
			//
			//foreach( $ordered_terms as $level ){
			//	$last_level = end($level);
			//	foreach( $level as $term ){
			//		$classes[] = "{$taxonomy}-{$term->slug}";
			//	}
			//}
            
			foreach( $terms as $term ){
				$classes[] = "{$taxonomy}-{$term->slug}";
			}
		}
	}
	return $classes;
}

add_filter( 'body_class', 'new_body_class' );
function new_body_class( $classes ){
    global $post;

    if( isset($post->post_type) ){
        $classes[] = "type-{$post->post_type}";
        $classes[] = "post-type-{$post->post_type}";
    }
    
    if( isset($post->post_name) ){
        $classes[] = "item-name-{$post->post_name}";
    }
    return $classes;
}



/**
 * Aplicar cor do tema no admin bar no frontend
 * Aplicação simplificada, apenas utiliza a cor $base-color e $text-color da barra inicial, sem
 * aplicação nos sub-itens e hovers.
 * 
 */
add_action('wp_head', function(){
    if( is_user_logged_in() && is_admin_bar_showing() ){
        $colors = array(
            'fresh'     => array('#333333', '#ffffff'),
            'blue'      => array('#52accc', '#ffffff'),
            'light'     => array('#e5e5e5', '#999999'),
            'modern'    => array('#3858e9', '#ffffff'),
            'coffee'    => array('#59524c', '#ffffff'),
            'ectoplasm' => array('#523f6d', '#ffffff'),
            'midnight'  => array('#363b3f', '#ffffff'),
            'ocean'     => array('#738e96', '#ffffff'),
            'sunrise'   => array('#cf4944', '#ffffff'),
        );
        $color = get_user_meta( wp_get_current_user()->ID, 'admin_color', true);
        if( array_key_exists( $color, $colors ) ){
            echo "<style>#wpadminbar{background-color:{$colors[$color][0]};color:{$colors[$color][1]}}#wpadminbar *, #wpadminbar *:before{color:{$colors[$color][1]};}</style>";
        }
    }
});



/**
 * @todo @deprecated @remover
 * 
 */
function old_browser_alert(){
?>
<!--[if lte IE 8]>
	<style type="text/css">
	#ie_msg {
		background:#ffffe6;
		border:1px solid #eddf65;
		clear:both;
		font:arial, sans-serif;
		margin:10px auto;
		padding:10px;
		position:relative;
		width:940px;
	}
	#ie_msg h2 {
		clear:both;
		color:#b71100;
		float:left;
		font:18px arial, sans-serif;
		margin:0;
		padding:0 0 5px;
		position:relative;
		text-transform:none;
		width:90%;
	}
	#ie_msg .msg {
		clear:both;
		margin:10px 0;
		padding:0 2px;
	}
	#ie_msg .browsers img {
		margin:0 2px;
	}
	#ie_msg #ieclose {
		display:block;
		position:absolute;
		right:10px;
		top:10px;
	}
	#ie_msg p {
		margin:0;
	}
	</style>
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<div id="ie_msg">
					<h2>Seu navegador não é mais compatível.</h2>
					<p class="msg">Atualize seu navegador para um mais moderno. Algumas funções deste site podem não funcionar corretamente. <br>
					Encontre clicando abaixo alguns navegadores modernos que podem proporcionar uma melhor experiência para você:</p>
					<p class="browsers">
						<a href="http://www.apple.com/br/safari/" target="_blank"><img src="<?php echo CSS_IMG; ?>/ie1.jpg" alt="" /></a>
						<a href="http://www.google.com/chrome?hl=pt-BR" target="_blank"><img src="<?php echo CSS_IMG; ?>/ie2.jpg" alt="" /></a>
						<a href="http://www.opera.com/download/" target="_blank"><img src="<?php echo CSS_IMG; ?>/ie3.jpg" alt="" /></a>
						<a href="http://br.mozdev.org/download/" target="_blank"><img src="<?php echo CSS_IMG; ?>/ie4.jpg" alt="" /></a>
						<a href="http://windows.microsoft.com/pt-BR/internet-explorer/products/ie/home" target="_blank"><img src="<?php echo CSS_IMG . '/ie6.jpg'; ?>" alt="" class="last" /></a>
					</p>
				</div>
			</div>
		</div>
	</div>
<![endif]-->
<?php
}



