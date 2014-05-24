<?php

/**
 * ==================================================
 * JS PARA O FRONTEND ===============================
 * ==================================================
 * 
 * @todo ESCREVER UMA DESCRIÇÃO DETALHADA
 * @todo rever o methodo $this->jquery(), para adicionar scripts já registrados
 * @TODO: rever a forma de usar o js do google, definir via option ou constante de wp-config para diferenciar localhost do server
 * @todo: trocar ou adicionar setar a configuração via array único
 */
class BorosJs {
	var $js_dir = '';
	var $current = '';
	
	private $conditionals = array(
		'head' => array(),
		'footer' => array(),
	);
	
	/**
	 * Jquery no footer
	 * Ao instanciar o objeto, define o jquery do CDN Google com fallback para jquery local, via wp_localize_script
	 * 
	 */
	function __construct( $args = array() ){
		add_action( 'wp_head', array($this, 'cond_head') );
		add_action( 'wp_footer', array($this, 'cond_footer') );
		
		// definir o local do jquery
		if( defined('JQUERY_URL') )
			$jquery = JQUERY_URL;
		else 
			$jquery = 'http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js';
		
		$defaults = array(
			'src' => $jquery,
			'ver' => null,
			'in_footer' => true,
		);
		$this->options = boros_parse_args( $defaults, $args );
		
		$this->js_dir = get_bloginfo('template_url') . '/js/';
		
		if( !in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) ){
			wp_deregister_script( 'jquery' );
			wp_enqueue_script(
				$handle = 'jquery',
				$src = $this->options['src'],
				$deps = false,
				$ver = $this->options['ver'],
				$in_footer = $this->options['in_footer']
			);
		}
		
		/**
		 * Adicionar variáveis dinâmicas
		 * A forma ideal para adicionar variáveis gerados por php é colocá-las via localize_script
		 * 
		 */
		$localize_array = array(
			'local_url' => $this->js_dir . 'libs/jquery.js',
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);
		wp_localize_script( 'jquery', 'jquery_vars', $localize_array );
		$this->current = 'jquery';
		
		// scripts de comentários aninhados
		if ( is_singular() && get_option( 'thread_comments' ) )
			wp_enqueue_script( 'comment-reply' );
	}
	
	/**
	 * bug: foi modificado o padrão $in_footer para true no lugar de null, porque quando existe a adição condicional(ex 3Minovação) as adições acabam ficando no head
	 * bug: na mesma situação acima, o jquery acaba sendo renderizado no head, embora os deps fiquem corretamente no footer
	 * 
	 */
	function add( $name, $folder = false, $deps = false, $in_footer = true, $cond = false ){
		$dir = ($folder) ? $folder . '/' : '';
		$src = $this->js_dir . $dir . $name . '.js';
		$this->current = $name;
		if( is_null($in_footer) ){
			$in_footer = false;
		}
		
		if( $cond == true ){
			$pos = ($in_footer == true) ? 'footer' : 'head';
			$this->conditionals[$pos][] = array(
				'name' => $name,
				'src' => $src,
				'cond' => $cond,
			);
		}
		else{
			wp_enqueue_script( $name, $src, $deps, version_id(), $in_footer );
		}
		return $this;
	}
	
	function jquery( $name, $folder = false, $cond = false ){
		$this->current = 'jquery';
		$this->add( $name, $folder, array('jquery'), $this->options['in_footer'], $cond );
		return $this;
	}
	
	function child( $name, $folder = false, $parent = false, $cond = false ){
		if( !$parent )
			$parent = $this->current;
		$this->add( $name, $folder, array($parent) );
		return $this;
	}
	
	function cond_head(){
		$this->cond_out('head');
	}
	
	function cond_footer(){
		$this->cond_out('footer');
	}
	
	function cond_out( $pos ){
		foreach( $this->conditionals[$pos] as $js ){
			echo "<!--[{$js['cond']}]><script src='{$js['src']}'></script><![endif]-->";
		}
	}
	
	/**
	 * Não implementado ainda no core do WordPress :(
	 * 
	 * @link http://stackoverflow.com/a/16221114
	 */
	function cond( $cond, $name = false ){
		if( $name )
			$this->current = $name;
		$GLOBALS['wp_scripts']->add_data( $this->current, 'conditional', $cond );
		return $this;
	}
	
	/**
	 * Adiciona um script com caminho absoluto. Aceita apenas o scr como string
	 * 
	 */
	function abs( $config ){
		if( !is_array($config) ){
			$config = array('src' => $config);
		}
		$defaults = array(
			'name' => 'abs',
			'src' => '',
			'parent' => false,
			'ver' => 1,
			'in_footer' => true,
			'cond' => false,
		);
		$config = boros_parse_args( $defaults, $config );
		if( $config['cond'] == true ){
			$pos = ($config['in_footer'] == true) ? 'footer' : 'head';
			$this->conditionals[$pos][] = array(
				'name' => $config['name'],
				'src' => $config['src'],
				'cond' => $config['cond'],
			);
		}
		else{
			wp_enqueue_script( $config['name'], $config['src'], $config['deps'], $config['ver'], $config['in_footer'] );
		}
		return $this;
	}
	
	function head( $name ){
		return $this;
	}
}



/**
 * ==================================================
 * CSS PARA O FRONTEND ==============================
 * ==================================================
 * 
 * @todo ESCREVER UMA DESCRIÇÃO DETALHADA
 */
class BorosCss {
	var $css_dir = '';
	var $current = '';
	
	function __construct(){
		$this->css_dir = get_bloginfo('template_url') . '/css/';
	}
	
	/**
	 * Adicionar stylesheet ao <head> da página
	 * 
	 * @param string $name Nome do arquivo; será o mesmo o mesmo nome a ser registrado no handler
	 * @param string $folder optional Sub-pasta de thema/css onde está o stylesheet
	 * 
	 * @return o próprio objeto, para que seja possível o encadeamento.
	 */
	function add( $name, $folder = false, $media = 'screen', $parent = false ){
		$dir = ($folder) ? $folder . '/' : '';
		$src = $this->css_dir . $dir . $name . '.css';
		$this->current = $name;
		wp_enqueue_style($name, $src, $parent, version_id(), $media);
		return $this;
	}
	
	/**
	 * Adicionar stylesheet child(dependente)
	 * 
	 * @param string $name Nome do arquivo; será o mesmo o mesmo nome a ser registrado no handler
	 * @param string $folder optional Sub-pasta de thema/css onde está o stylesheet
	 * @param string $parent optional Parent do stylesheet; caso seja encadeado, é usado o como parent o $current do objeto
	 * 
	 * @return o próprio objeto, para que seja possível o encadeamento.
	 */
	function child( $name, $folder = false, $media = 'screen', $parent = false ){
		if( !$parent )
			$parent = $this->current;
		$this->add( $name, $folder, $media, array($parent) );
		return $this;
	}
	
	/**
	 * Define o 'rel' do stylesheet como 'alternate stylesheet'.
	 * Caso a chamada seja encadeada, não é preciso inserir o $name
	 * 
	 * @param string $name optional Nome do stylesheet a ser modificado o rel
	 * 
	 * @return o próprio objeto, para que seja possível o encadeamento.
	 */
	function alt( $name = false ){
		if( $name )
			$this->current = $name;
		$GLOBALS['wp_styles']->add_data( $this->current, 'alt', 'alternate stylesheet' );
		return $this;
	}
	
	
	/**
	 * Adiciona o stylesheet com comentários condicionais para internat explorer
	 * 
	 * $param string $cond Condicional para filtragem
	 * @param string $name optional Nome do stylesheet a ser condicionado
	 * 
	 * @return o próprio objeto, para que seja possível o encadeamento.
	 */
	function cond( $cond, $name = false ){
		if( $name )
			$this->current = $name;
		$GLOBALS['wp_styles']->add_data( $this->current, 'conditional', $cond );
		return $this;
	}
	
	/**
	 * ATENÇÃO:: NÃO ESTÁ FUNCIONANDO!!!
	 * Configurar a media do stylesheet, caso seja diferente de 'screen'
	 * Não funciona nos encadeamentos de child(), apenas em add(), pois sempre referencia o $current do objeto
	 * 
	 * @param $media Nome da media a ser atribuída
	 * 
	 * @return o próprio objeto, para que seja possível o encadeamento.
	 */
	function media( $media, $name = false ){
		if( $name )
			$this->current = $name;
		$GLOBALS['wp_styles']->add_data( $this->current, 'media', $media );
		return $this;
	}
	
	/**
	 * Adiciona um css com caminho absoluto. Aceita apenas o scr como string
	 * 
	 */
	function abs( $config ){
		if( !is_array($config) ){
			$config = array('src' => $config);
		}
		$defaults = array(
			'name' => 'custom',
			'src' => '',
			'parent' => false,
			'version' => '1',
			'media' => 'screen',
		);
		$config = boros_parse_args($defaults, $config);
		wp_enqueue_style($config['name'], $config['src'], $config['parent'], $config['version'], $config['media']);
		return $this;
	}
}



/* ========================================================================== */
/* OPENGRAPH ================================================================ */
/* ========================================================================== */
/**
 * As propriedades 'image_url', 'locality', 'region' e 'country_name' deverão ser adicionadas como options.
 * 
 */
function opengraph_tags(){
	$og_image = get_option('og_image');
	if( !empty($og_image) ){
		$image = wp_get_attachment_image_src( get_option('og_image'), 'full' );
		$default_image = $image[0];
	}
	else{
		$default_image = '';
	}
	
	$defaults = array(
		'title' 		=> get_bloginfo('name') . ' - ' . get_bloginfo('description'),
		'image_url' 	=> $default_image,
		'description' 	=> get_bloginfo('description'),
		'og_type' 		=> 'blog',
		'og_url' 		=> home_url( '/' ),
		'separator' => '',
	);
	extract( $defaults );
	
	if( is_singular() ){
		global $post;
		$title = get_bloginfo( 'name' );
		
		$separator = ' : ';
		$og_type = 'article';
		
		//url
		$og_url = get_permalink($post->ID);
		
		//criar novo description. Fallback para o excerpt em caso de content vazio.
		if( !empty($post->post_excerpt) ){
			$description = wp_trim_excerpt($post->post_excerpt);
		}
		else{
			$raw_content = wp_trim_excerpt($post->post_content);
			$text = strip_shortcodes( $raw_content );
			$text = str_replace(']]>', ']]&gt;', $text);
			$text = strip_tags($text);
			$excerpt_length = apply_filters('excerpt_length', 55);
			$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
			if ( count($words) > $excerpt_length ) {
				array_pop($words);
				$description = wptexturize(implode(' ', $words));
			} else {
				$description = wptexturize(implode(' ', $words));
			}
		}
		//criar novo thumb
		$thumb = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full' );
		if( $thumb ){
			$image_url = $thumb['0'];
		}
	}
	elseif( is_post_type_archive() ){
		global $wp_query;
		$pt = $wp_query->query['post_type'];
		$pt_image = get_option("{$pt}_image");
		if( !empty($pt_image) ){
			$thumb = wp_get_attachment_image_src( $pt_image, 'full' );
			$image_url = $thumb[0];
		}
	}
	
	$post_type_obj = get_queried_object();
	if( !isset($post_type_obj->labels->name) ){
		add_filter( 'post_type_archive_title', 'fix_title_tag', 1 );
	};
?>
<meta property="og:title"        content="<?php echo $title . $separator . wp_title( '', false, 'left' ); ?>"/>
<meta property="og:type"         content="<?php echo $og_type; ?>"/>
<meta property="og:url"          content="<?php echo $og_url; ?>"/>
<meta property="og:image"        content="<?php echo $image_url; ?>"/>
<meta property="og:site_name"    content="<?php bloginfo('name'); ?>"/>
<meta property="og:description"  content="<?php echo $description; ?>"/>
<?php
}

function gplus_tags(){
	$default_image = wp_get_attachment_image_src( get_option('og_image'), 'full' );
	$gplus = array(
		'name' 			=> get_bloginfo('name') . ' - ' . get_bloginfo('description'),
		'image' 		=> $default_image[0],
		'description' 	=> get_bloginfo('description'),
	);
	if( is_singular() ){
		global $post;
		$gplus['name'] = get_the_title( $post->ID );
		
		//criar novo description. Fallback para o excerpt em caso de content vazio.
		if( !empty($post->post_excerpt) ){
			$gplus['description'] = wp_trim_excerpt($post->post_excerpt);
		}
		else{
			$raw_content = wp_trim_excerpt($post->post_content);
			$text = strip_shortcodes( $raw_content );
			$text = str_replace(']]>', ']]&gt;', $text);
			$text = strip_tags($text);
			$excerpt_length = apply_filters('excerpt_length', 55);
			$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
			if ( count($words) > $excerpt_length ) {
				array_pop($words);
				$gplus['description'] = wptexturize(implode(' ', $words));
			} else {
				$gplus['description'] = wptexturize(implode(' ', $words));
			}
		}
		
		//criar novo thumb
		$thumb = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full' );
		if( $thumb ){
			$gplus['image'] = $thumb['0'];
		}
	}
?>
<meta itemprop="name"            content="<?php echo $gplus['name']; ?>" />
<meta itemprop="description"     content="<?php echo $gplus['description']; ?>" />
<meta itemprop="image"           content="<?php echo $gplus['image']; ?>" />
<?php
}

function fix_title_tag( $name ){
	global $wp_query;
	$post_type_obj = get_post_type_object( $wp_query->query_vars['post_type'] );
	return " - {$post_type_obj->labels->name}";
}