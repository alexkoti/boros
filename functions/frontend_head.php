<?php

/**
 * ==================================================
 * JS PARA O FRONTEND ===============================
 * ==================================================
 * 
 * @todo unificar JS e CSS em uma class Boros_Enqueue ou utilizar abstract para reduzir. Mover para arquivo separado e marcar este como deprecated.
 * @todo ESCREVER UMA DESCRIÇÃO DETALHADA
 * @todo rever o methodo $this->jquery(), para adicionar scripts já registrados
 * @todo: rever a forma de usar o js do google, definir via option ou constante de wp-config para diferenciar localhost do server
 * @todo: trocar ou adicionar setar a configuração via array único
 * 
 * 
 * .htaccess para utilizar o revv
 * @link http://www.stevesouders.com/blog/2008/08/23/revving-filenames-dont-use-querystring/
 * 
 * <IfModule mod_rewrite.c>
 *     RewriteEngine On
 *     RewriteCond %{REQUEST_FILENAME} !-f
 *     RewriteRule ^(.+)\.(\d+)\.(bmp|css|cur|gif|ico|jpe?g|js|png|svgz?|webp|webmanifest)$ $1.$3 [L]
 * </IfModule>
 * 
 */
class BorosJs {

    var $js_dir      = '';

    var $vendors_dir = '';

    var $current     = '';

    var $options     = array(
        'src'        => '/wp-includes/js/jquery/jquery.min.js',
        'ver'        => null,
        'in_footer'  => true,
        'priority'   => 10,
        'vendor_dir' => '/vendors/',
        'revv'       => false,
    );

    var $queue = array();
    
    private $conditionals = array(
        'head'   => array(),
        'footer' => array(),
    );
    
    /**
     * jQuery no footer
     * Ao instanciar o objeto, define o jquery do CDN Google com fallback para jquery local, via wp_localize_script
     * 
     */
    function __construct( $args = array() ){
        if( !is_admin() and !in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) ){
            
            $this->options = boros_parse_args( $this->options, $args );
            if( $this->options['ver'] == null ){
                $this->options['ver'] = BOROS_VERSION;
            }

            add_action( 'wp_head', array($this, 'cond_head'), $this->options['priority'] );
            add_action( 'wp_footer', array($this, 'cond_footer'), $this->options['priority'] );
            
            $this->js_dir      = get_bloginfo('template_url') . '/js/';
            $this->vendors_dir = get_bloginfo('template_url') . $this->options['vendor_dir'];
            
            wp_deregister_script( 'jquery' );
            
            $revv = ($this->options['revv'] == true) ? ".{$this->options['ver']}" : '';
            $ver  = ($this->options['revv'] == true) ? NULL : $this->options['ver'];
            $src  = ($this->options['revv'] == true) ? str_replace('.js', "{$revv}.js", $this->options['src']) : $this->options['src'];
            $this->queue[] = array(
                'jquery',
                $src,
                false,
                $ver,
                $this->options['in_footer']
            );
            
            $this->current = 'jquery';
            
            // scripts de comentários aninhados
            if ( is_singular() && get_option( 'thread_comments' ) ){
                $this->queue[] = 'comment-reply';
            }
            
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), $this->options['priority']);
        }
    }
    
    /**
     * Run enqueues
     * 
     */
    function enqueue_scripts(){
        foreach( $this->queue as $args ){
            if( is_array($args) ){
                wp_enqueue_script( $args[0], $args[1], $args[2], $args[3], $args[4] );
            } else {
                wp_enqueue_script( $args );
            }
        }
    }
    
    /**
     * Adição comum, sem dependência de jquery, instalado na pasta /js do tema.
     * 
     */
    function add( $name, $folder = false, $deps = false, $in_footer = true, $cond = false ){
        $dir  = ($folder) ? $folder . '/' : '';
        $revv = ($this->options['revv'] == true) ? ".{$this->options['ver']}" : '';
        $ver  = ($this->options['revv'] == true) ? NULL : $this->options['ver'];
        $src  = "{$this->js_dir}{$dir}{$name}{$revv}.js";
        $this->current = $name;
        if( is_null($in_footer) ){
            $in_footer = false;
        }
        
        if( $cond == true ){
            $pos = ($in_footer == true) ? 'footer' : 'head';
            $this->conditionals[$pos][] = array(
                'name' => $name,
                'src'  => $src,
                'cond' => $cond,
            );
        }
        else{
            $this->queue[] = array( $name, $src, $deps, $ver, $in_footer );
        }
        return $this;
    }
    
    /**
     * Adicionar script dependente de jquery da pasta /js
     * 
     */
    function jquery( $name, $folder = false, $cond = false ){
        $this->current = 'jquery';
        $this->add( $name, $folder, array('jquery'), $this->options['in_footer'], $cond );
        return $this;
    }
    
    /**
     * Adicionar script da pasta /vendors
     * 
     */
    function vendor( $name, $folder = false, $deps = false, $in_footer = true ){
        $dir  = ($folder) ? $folder . '/' : '';
        $revv = ($this->options['revv'] == true) ? ".{$this->options['ver']}" : '';
        $ver  = ($this->options['revv'] == true) ? NULL : $this->options['ver'];
        $src  = "{$this->vendors_dir}{$dir}{$name}{$revv}.js";
        $this->current = $name;
        if( is_null($in_footer) ){
            $in_footer = false;
        }
        
        $this->queue[] = array( $name, $src, $deps, $ver, $in_footer );
        return $this;
    }
    
    /**
     * Adicionar um script dependente, na concatenação
     * Ex: $js->add('thickbox')->child('extendthick');
     * 
     */
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
            'name'      => 'abs',
            'src'       => '',
            'parent'    => false,
            'ver'       => 1,
            'in_footer' => true,
            'cond'      => false,
        );
        $config = boros_parse_args( $defaults, $config );
        if( $config['cond'] == true ){
            $pos = ($config['in_footer'] == true) ? 'footer' : 'head';
            $this->conditionals[$pos][] = array(
                'name' => $config['name'],
                'src'  => $config['src'],
                'cond' => $config['cond'],
            );
        }
        else{
            $this->queue[] = array( $config['name'], $config['src'], $config['deps'], $config['ver'], $config['in_footer'] );
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

    var $vendors_dir = '';

    var $current = '';

    var $options = array(
        'ver'        => null,
        'priority'   => 10,
        'vendor_dir' => '/vendors/',    // definir local da pasta vendor
        'revv'       => false,          // aplicar versão no nome do arquivo, ex "format.2023.01.01.css", necessário regra htaccess para revving
    );

    var $queue = array();
    
    function __construct( $args = array() ){

        $this->options = boros_parse_args( $this->options, $args );
        if( $this->options['ver'] == null ){
            $this->options['ver'] = BOROS_VERSION;
        }
        
        // Adicionar mensagem de aviso de hook deprecated
        if( current_filter() == 'wp_print_styles' ){
            boros_add_dashboard_notification('old_enqueue_css_hook', 'É necessário registrar os CSS no hook "init", em vez do atual "wp_print_styles"');
        }
        $this->css_dir     = get_bloginfo('template_url') . '/css/';
        $this->vendors_dir = get_bloginfo('template_url') . $this->options['vendor_dir'];

        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), $this->options['priority']);
    }
    
    /**
     * Run enqueues
     * 
     */
    function enqueue_styles(){
        foreach( $this->queue as $args ){
            wp_enqueue_style( $args[0], $args[1], $args[2], $args[3], $args[4] );
        }
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
        $dir  = ($folder) ? $folder . '/' : '';
        $revv = ($this->options['revv'] == true) ? ".{$this->options['ver']}" : '';
        $ver  = ($this->options['revv'] == true) ? NULL : $this->options['ver'];
        $src  = "{$this->css_dir}{$dir}{$name}{$revv}.css";
        $this->current = $name;
        $this->queue[] = array($name, $src, $parent, $ver, $media);
    }
    
    /**
     * Adicionar stylesheet da pasta vendors
     * 
     */
    function vendor( $name, $folder = false, $media = 'screen', $parent = false ){
        $dir  = ($folder) ? $folder . '/' : '';
        $revv = ($this->options['revv'] == true) ? ".{$this->options['ver']}" : '';
        $ver  = ($this->options['revv'] == true) ? NULL : $this->options['ver'];
        $src  = "{$this->vendors_dir}{$dir}{$name}{$revv}.css";
        $this->current = $name;
        $this->queue[] = array($name, $src, $parent, $ver, $media);
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
        $this->queue[] = array($config['name'], $config['src'], $config['parent'], $config['version'], $config['media']);
    }
}



/* ========================================================================== */
/* OPENGRAPH ================================================================ */
/* ========================================================================== */
/**
 * As propriedades 'image_url', 'locality', 'region' e 'country_name' deverão ser adicionadas como options.
 * 
 * $args['thumbnail_meta_name'] - escolher outro post_meta para buscar a imagem
 * 
 * @deprecated
 * 
 */
function opengraph_tags( $args = false ){
    error_log('@deprecated: opengraph_tags()');
    
    // post type archive
    if( is_post_type_archive() ){
        global $wp_query;
        if( isset($wp_query->query['post_type']) ){
            $pt = $wp_query->query['post_type'];
            $pt_image = get_option("{$pt}_image");
            if( !empty($pt_image) ){
                $thumb = wp_get_attachment_image_src( $pt_image, 'full' );
                $info['image_url'] = $thumb[0];
            }
        }
        
        $post_type_obj = get_queried_object();
        if( !isset($post_type_obj->labels->name) ){
            add_filter( 'post_type_archive_title', 'fix_title_tag', 1 );
        };
    }
    
    // imagem padrão
    $og_image = get_option('og_image');
    if( !empty($og_image) ){
        $image = wp_get_attachment_image_src( get_option('og_image'), 'full' );
        $default_image = $image[0];
    }
    else{
        $default_image = '';
    }
    
    $defaults = array(
        'title'        => wp_title( '', false, 'right' ),
        'site_name'    => get_bloginfo('name'),
        'separator'    => ' | ',
        'image_url'    => $default_image,
        'image_mime'   => false,
        'image_width'  => false,
        'image_height' => false,
        'description'  => get_bloginfo('description'),
        'og_type'      => 'blog',
        'url'          => home_url( '/' ),
        'p'            => false,
        'locale'       => 'pt_BR',
    );
    $info = boros_parse_args( $defaults, $args );
    
    if( is_singular() ){
        global $post;
        $info['p'] = $post;
        
        if( !isset($args['og_type']) ){
            $info['og_type'] = 'article';
        }
        
        // refazer title apenas se não tiver no args
        if( !isset($args['title']) ){
            $info['title'] = get_the_title( $post->ID );
        }
        
        //url
        $info['url'] = get_permalink($post->ID);
        
        if( !isset($args['description']) ){
            //criar novo description. Fallback para o excerpt em caso de content vazio.
            if( !empty($post->post_excerpt) ){
                $info['description'] = wp_trim_words($post->post_excerpt);
            }
            else{
                $raw_content = $post->post_content;
                $text = strip_shortcodes( $raw_content );
                $text = str_replace(']]>', ']]&gt;', $text);
                $text = strip_tags($text);
                $excerpt_length = apply_filters('excerpt_length', 55);
                $words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
                if ( count($words) > $excerpt_length ) {
                    array_pop($words);
                    $info['description'] = wptexturize(implode(' ', $words));
                } else {
                    $info['description'] = wptexturize(implode(' ', $words));
                }
            }
        }
        
        // criar novo thumb
        // custom meta name?
        $thumb_id = false;
        if( isset($args['thumbnail_meta_name']) ){
            $thumb_id = get_post_meta($post->ID, $args['thumbnail_meta_name'], true);
            // fallback de volta para o _thumbnail_id
            if( empty($thumb_id) ){
                $thumb_id = get_post_thumbnail_id($post->ID);
            }
        }
        else{
            $thumb_id = get_post_thumbnail_id($post->ID);
        }
        $thumb = wp_get_attachment_image_src( $thumb_id, 'full' );
        if( $thumb != false ){
            $info['image_url']    = $thumb[0];
            $info['image_width']  = $thumb[1];
            $info['image_height'] = $thumb[2];
            $info['image_mime']   = get_post_mime_type($thumb_id );
        }
    }
    
    //pre($info);
    $info = apply_filters('opengraph_items', $info);
?>

<!-- share opengraph -->
<meta property="og:title"        content="<?php echo $info['title']; ?>"/>
<meta property="og:type"         content="<?php echo $info['og_type']; ?>"/>
<meta property="og:url"          content="<?php echo $info['url']; ?>"/>
<meta property="og:image"        content="<?php echo $info['image_url']; ?>"/>
<meta property="og:image:type"   content="<?php echo $info['image_mime']; ?>"/>
<meta property="og:image:width"  content="<?php echo $info['image_width']; ?>"/>
<meta property="og:image:height" content="<?php echo $info['image_height']; ?>"/>
<meta property="og:site_name"    content="<?php echo $info['site_name']; ?>"/>
<meta property="og:description"  content="<?php echo $info['description']; ?>"/>
<meta property="og:locale"       content="<?php echo $info['locale']; ?>"/>

<?php
}

function gplus_tags( $args = false ){
    
    // post type archive
    if( is_post_type_archive() ){
        global $wp_query;
        if( isset($wp_query->query['post_type']) ){
            $pt = $wp_query->query['post_type'];
            $pt_image = get_option("{$pt}_image");
            if( !empty($pt_image) ){
                $thumb = wp_get_attachment_image_src( $pt_image, 'full' );
                $info['image_url'] = $thumb[0];
            }
        }
        
        $post_type_obj = get_queried_object();
        if( !isset($post_type_obj->labels->name) ){
            add_filter( 'post_type_archive_title', 'fix_title_tag', 1 );
        };
    }
    
    // imagem padrão
    $og_image = get_option('og_image');
    if( !empty($og_image) ){
        $image = wp_get_attachment_image_src( get_option('og_image'), 'full' );
        $default_image = $image[0];
    }
    else{
        $default_image = '';
    }
    
    $defaults = array(
        'title'       => wp_title( '', false, 'right' ),
        'separator'   => ' | ',
        'image_url'   => $default_image,
        'description' => get_bloginfo('description'),
        'url'         => home_url( '/' ),
        'p'           => false,
    );
    $info = boros_parse_args( $defaults, $args );
    
    if( is_singular() ){
        global $post;
        $info['p'] = $post;
        
        // refazer title apenas se não tiver no args
        if( !isset($args['title']) ){
            $info['title'] = get_the_title( $post->ID ) . $info['separator'] . get_bloginfo('name');
        }
        
        //criar novo description. Fallback para o content em caso de content vazio.
        if( !isset($args['description']) ){
            if( !empty($post->post_excerpt) ){
                $info['description'] = wp_trim_words($post->post_excerpt);
            }
            else{
                $raw_content = wp_trim_words($post->post_content);
                $text = strip_shortcodes( $raw_content );
                $text = str_replace(']]>', ']]&gt;', $text);
                $text = strip_tags($text);
                $excerpt_length = apply_filters('excerpt_length', 55);
                $words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
                if ( count($words) > $excerpt_length ) {
                    array_pop($words);
                    $info['description'] = wptexturize(implode(' ', $words));
                } else {
                    $info['description'] = wptexturize(implode(' ', $words));
                }
            }
        }
        
        // criar novo thumb
        // custom meta name?
        if( isset($args['thumbnail_meta_name']) ){
            $thumb_id = get_post_meta($post->ID, $args['thumbnail_meta_name'], true);
            // fallback de volta para o _thumbnail_id
            if( empty($thumb_id) ){
                $thumb_id = get_post_thumbnail_id($post->ID);
            }
        }
        else{
            $thumb_id = get_post_thumbnail_id($post->ID);
        }
        $thumb = wp_get_attachment_image_src( $thumb_id, 'full' );
        if( $thumb ){
            $info['image_url'] = $thumb['0'];
        }
    }
    elseif( is_post_type_archive() ){
        global $wp_query;
        if( isset($wp_query->query['post_type']) ){
            $pt = $wp_query->query['post_type'];
            $pt_image = get_option("{$pt}_image");
            if( !empty($pt_image) ){
                $thumb = wp_get_attachment_image_src( $pt_image, 'full' );
                $info['image_url'] = $thumb[0];
            }
        }
    }
    
    //pre($info);
    $gplus = apply_filters('gplus_items', $info);
?>
<!-- share gplus -->
<meta itemprop="name"            content="<?php echo $info['title']; ?>" />
<meta itemprop="description"     content="<?php echo $info['description']; ?>" />
<meta itemprop="image"           content="<?php echo $info['image_url']; ?>" />

<?php
}

function fix_title_tag( $name ){
    global $wp_query;
    $post_type_obj = get_post_type_object( $wp_query->query_vars['post_type'] );
    return " - {$post_type_obj->labels->name}";
}
