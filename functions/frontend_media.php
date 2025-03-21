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
 * EMBED RESPONSIVO
 * Filtrar o auto oembed do the_content, adicionando o html responsivo.
 * Válido para vários serviços, com output próprio:
 * - Youtube, Vimeo
 * - Issuu
 * - Demais embeds
 * 
 * @link https://gist.github.com/loilo/9cffb1af3fa554976d2d884d761250f0 obter informações do iframe
 * @link https://stackoverflow.com/a/71546342 pegar proporção mais próxima
 * 
 */
//add_filter( 'embed_oembed_html', 'tdd_oembed_filter', 10, 4 ) ;
function tdd_oembed_filter($html, $url, $attr, $post_ID){
	// Videos: youtube e vimeo. Adicionar mais serviços se necessário
	if( strpos($html, 'youtube') !== false or strpos($html, 'vimeo') !== false ){

        /**
         * Obter altura x largura do iframe
         * 
         */
        $dom = new DOMDocument();
        // Don't spread warnings when encountering malformed HTML
        $previousXmlErrorBehavior = libxml_use_internal_errors(true);
        // Use XML processing instruction to properly interpret document as UTF-8
        @$dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        foreach ($dom->childNodes as $item) {
            if ($item->nodeType === XML_PI_NODE) {
                $dom->removeChild($item);
            }
        }
        $dom->encoding = 'UTF-8';
        $width  = $dom->getElementsByTagName('iframe')[0]->getAttribute('width');
        $height = $dom->getElementsByTagName('iframe')[0]->getAttribute('height');

        $fx = $width / $height;

        /**
         * Obter a proporção mais próxima e definir class
         * 
         */
        $video_ratio = '16x9';
        $ratio = array(
            '1'                 => '1x1',
            '1.333333333333333' => '4x3',
            '1.777777777777778' => '16x9',
      
        );
        $min = [];
        foreach( $ratio as $calc => $label ){
            $diff = abs($calc - $fx);
            $min["".$diff] = $label;
        }
        ksort($min);
        if( !empty($min) ){
            $video_ratio = reset($min);
        }

		return "<div class='cleaner'></div><div class='videoWrapper embed-responsive embed-responsive-16by9 ratio ratio-{$video_ratio}'>{$html}</div>";
	}
	
	return "<div class='cleaner'></div><div class='responsiveWrapper'>{$html}</div>";
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






