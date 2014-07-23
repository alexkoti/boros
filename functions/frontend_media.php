<?php
/**
 * FUNÇÕES DE MÍDIA
 * Funções específicas para manipulação de imagens e arquivos anexados - GERAL PARA O SITE TODO, ADMIN E FRONTEND
 * As configurações de cada site fica nos arquivos do tema e as funções relativas ao admin ficam no plugin boros_admin_extended
 * A princípio a maioria das funções são estáticas, mas poderão exigir modificações nas saídas HTML
 * 
 * 
 */

/**
 * ==================================================
 * IMAGENS DO POST ==================================
 * ==================================================
 * Pega as imagens anexadas ao post
 *
 * @return $images - objeto com as imagens anexadas ao post.
 */
function get_post_images( $post_id = 0 ){
	if( $post_id == 0 ){
		global $post;
		$post_id = $post->ID;
	}
	
	$images = get_children(array(
			'post_parent' => $post_id,
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'orderby' => 'menu_order',
			'order' => 'ASC'));
	return $images;
}

/**
 * ==================================================
 * oEMBED BY CUSTOM FIELD ===========================
 * ==================================================
 * Obter saída oEmbed a partir de custom field/option
 * 
 */
function auto_video( $args ){
	$defaults = array(
		'post_id' => false,
		'post_meta' => false,
		'width' => 500,
		'height' => 250,
	);
	$attrs = boros_parse_args( $defaults, $args );
	
	if( $attrs['post_id'] == false ){
		global $post;
		$post_id = $post->ID;
	}
	if( $attrs['post_meta'] == false ){
		return false;
	}
	
	global $wp_embed;
	$video_url = get_post_meta( $attrs['post_id'], $attrs['post_meta'], true );
	$video_html = $wp_embed->run_shortcode("[embed width='{$attrs['width']}' height='{$attrs['height']}']{$video_url}[/embed]");
	// CORRIGIR TRANSPARENT DO IFRAME DO YOUTUBE: MALDITO!!!!!! @link http://stackoverflow.com/a/4788044/679195
	$video_html = str_replace( '?', '?wmode=opaque&', $video_html);
	//pre($wp_embed);
	//pre($video_html);
	
	return $video_html;
}

function youtube_embed( $args ){
	$defaults = array(
		'post_id' => false,
		'post_meta' => false,
		'width' => 500,
		'height' => 250,
	);
	$attrs = boros_parse_args( $defaults, $args );
	
	if( $attrs['post_id'] == false ){
		global $post;
		$post_id = $post->ID;
	}
	if( $attrs['post_meta'] == false ){
		return false;
	}
	
	global $wp_embed;
	$video_url = get_post_meta( $attrs['post_id'], $attrs['post_meta'], true );
	$video_url_vars = parse_url($video_url);
	parse_str($video_url_vars['query'], $video_vars);
	$iframe = "<iframe width='{$attrs['width']}' height='{$attrs['height']}' src='http://www.youtube.com/embed/{$video_vars['v']}/' frameborder='0' allowfullscreen></iframe>";
	
	return $iframe;
}

function custom_oembed( $post_id = false, $post_meta = false ){
	if( !$post_id ){
		global $post;
		$post_id = $post->ID;
	}
	if( !$post_meta ){
		return false;
	}
	
	$autovideo = new WP_Embed();
	add_filter( 'autovideo', array($autovideo, 'autoembed'), 8 );
	$post_video = get_post_meta($post_id, $post_meta, true);
	if( $post_video ){
		$html = preg_replace('@</?p>@', '', apply_filters('autovideo', $post_video));
		// CORRIGIR TRANSPARENT DO IFRAME DO YOUTUBE: MALDITO!!!!!! @link http://stackoverflow.com/a/4788044/679195
		$html = str_replace( '?', '?wmode=opaque&', $html);
		return $html;
	}
}



/**
 * VIDEO RESPONSIVO
 * Filtrar o auto oembed do the_content, adicionando o html responsivo
 * 
 */
add_filter( 'embed_oembed_html', 'tdd_oembed_filter', 10, 4 ) ;
function tdd_oembed_filter($html, $url, $attr, $post_ID) {
    return "<div class='cleaner'></div><div class='videoWrapper'>{$html}</div>";
}



/**
 * ATRIBUTOS DO LINK DE ATTACHMENT
 * 
 * @todo adicionar novos parâmetros
 */
add_filter( 'wp_get_attachment_link', 'attachment_link_attributes', 10, 6 );
function attachment_link_attributes( $output, $id, $size, $permalink, $icon, $text ){
	//pre($output, 'output');
	//pre($id, 'id');
	//pre($size, 'size');
	//pre($permalink, 'permalink');
	//pre($icon, 'icon');
	//pre($text, 'text');
	//pal('============================');
	$output = str_replace('<a ', "<a data-attachid='{$id}' ", $output);
	$output = str_replace('</a>', '<span class="loading"></span></a>', $output);
	return $output;
}






