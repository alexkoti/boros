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

    var $js_dir      = '';

    var $vendors_dir = '';

    var $current     = '';

    var $options     = array(
        'src'       => '/wp-includes/js/jquery/jquery.js',
        'ver'       => null,
        'in_footer' => true,
        'priority'  => 10,
    );
    var $queue = array();
    
    private $conditionals = array(
        'head' => array(),
        'footer' => array(),
    );
    
    /**
     * jQuery no footer
     * Ao instanciar o objeto, define o jquery do CDN Google com fallback para jquery local, via wp_localize_script
     * 
     */
    function __construct( $args = array() ){
        if( !is_admin() and !in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) ){
            add_action( 'wp_head', array($this, 'cond_head'), $this->options['priority'] );
            add_action( 'wp_footer', array($this, 'cond_footer'), $this->options['priority'] );
            $this->options = boros_parse_args( $this->options, $args );
            
            $this->js_dir      = get_bloginfo('template_url') . '/js/';
            $this->vendors_dir = get_bloginfo('template_url') . '/vendors/';
            
            wp_deregister_script( 'jquery' );
            $this->queue[] = array(
                'jquery',
                $this->options['src'],
                false,
                $this->options['ver'],
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
            $this->queue[] = array( $name, $src, $deps, version_id(), $in_footer );
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
        $dir = ($folder) ? $folder . '/' : '';
        $src = $this->vendors_dir . $dir . $name . '.js';
        $this->current = $name;
        if( is_null($in_footer) ){
            $in_footer = false;
        }
        
        $this->queue[] = array( $name, $src, $deps, version_id(), $in_footer );
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
    
    function __construct(){
        
        // Adicionar mensagem de aviso de hook deprecated
        if( current_filter() == 'wp_print_styles' ){
            boros_add_dashboard_notification('old_enqueue_css_hook', 'É necessário registrar os CSS no hook "init", em vez do atual "wp_print_styles"');
        }
        $this->css_dir     = get_bloginfo('template_url') . '/css/';
        $this->vendors_dir = get_bloginfo('template_url') . '/vendors/';
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
     * Adicionar stylesheet da pasta vendors
     * 
     */
    function vendor( $name, $folder = false, $media = 'screen', $parent = false ){
        $dir = ($folder) ? $folder . '/' : '';
        $src = $this->vendors_dir . $dir . $name . '.css';
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
 * 
 * $args['thumbnail_meta_name'] - escolher outro post_meta para buscar a imagem
 * 
 */
function opengraph_tags( $args = false ){
    
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
        'image_type'   => false,
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

/**
 * Class para geração de tags de compartilhamento
 * 
 */

class Boros_Share_Tags {

    var $post = false;

    var $term = false;

    var $post_type = false;

    var $is_home = false;

    var $is_singular = false;

    var $is_wc_product = false;

    var $is_post_type_archive = false;

    var $is_term_archive = false;

    /**
     * Imagem padrão. O estado inicial é um array vazio,
     * caso não exista, será modificado para false
     * 
     */
    var $default_image = array();

    /**
     * Todas as informações possíveis que poderão ser usadas.
     * O ordem dos items é importante pois os elementos posteriores dependem dos primeiros items definidos
     * 
     */
    var $info = array(
        'append_sitename' => false,     // adicionar nome do site após o título
        'separator'       => false,
        'site'            => false,
        'creator'         => false,     // conta twitter
        'title'           => false,
        'description'     => false,
        'image_size'      => false,     // wp image size
        'fallback_image'  => true,      // caso não exista imagem definida, usar imagem padrão 
        'image'           => false,
        'url'             => false,
        'type'            => false,
        'language'        => false,
    );

    /**
     * Dados de produto
     * 
     */
    var $product_info = array(
        'product_price'           => false,
        'product_formatted_price' => false,
        'product_currency'        => false,
        'product_sku'             => false,
    );

    function __construct( $args = array() ){

        //pre($args, 'Boros_Share_Tags args');

        // aplicar valores customizados
        foreach( $this->info as $key => $value ){
            if( isset($args[$key]) ){
                $this->info[$key] = $args[$key];
            }
        }

        // aplicar valores customizados para produto
        foreach( $this->product_info as $key => $value ){
            if( isset($args[$key]) ){
                $this->product_info[$key] = $args[$key];
            }
        }

        // conditions
        if( is_home() || is_front_page() ){
            $this->is_home = true;
        }
        elseif( is_singular() ){
            global $post;
            $this->is_singular = true;
            $this->post = $post;
        }
        elseif( is_category() || is_tag() || is_tax() ){
            $this->is_term_archive = true;
            $this->term = get_queried_object();
        }
        elseif( is_post_type_archive() ){
            $this->is_post_type_archive = true;
            $this->post_type = get_queried_object();
        }

        // buscar valores padrão em caso de false
        // valores vazio são desconsiderados, pois pode ser um valor intencional
        foreach( $this->info as $key => $value ){
            if( $value === false ){
                $func = "set_{$key}";
                $this->$func();
            }
        }

        // verificar se é single de produto WooCommerce
        if( $this->is_singular && $this->post->post_type == 'product' && class_exists('WooCommerce') ){
            $this->is_wc_product = true;
            $this->info['type'] = 'product';
        }

        // definir informações de produto, caso necessário
        if( $this->is_wc_product ){
            $this->set_product_info();
        }

        $this->info = apply_filters( 'boros_share_info', $this->info, $this );
        //pre($this->info, 'Boros_Share_Tags info');
        //pre($this->product_info, 'Boros_Share_Tags info');
    }

    function set_title(){
        if( $this->is_home ){
            $this->info['title'] = $this->info['site'];
        }
        elseif( $this->is_singular ){
            $this->info['title'] = apply_filters( 'the_title', $this->post->post_title );
        }
        elseif( $this->is_term_archive ){
            $this->info['title'] = $this->term->name;
        }
        elseif( $this->is_post_type_archive ){
            $this->info['title'] = $this->post_type->labels->name;
        }
        else{
            $this->info['title'] = wp_title( '', false, 'right' );
        }

        // adicionar o nome do site após o título
        if( $this->is_home === false && $this->info['append_sitename'] == true ){
            $this->info['title'] .= $this->info['separator'] . $this->info['site'];
        }
    }

    function set_description(){
        if( $this->is_home ){
            $this->info['description'] = get_bloginfo('description');
        }
        elseif( $this->is_singular ){
            $text = !empty($this->post->post_excerpt) ? $this->post->post_excerpt : $this->post->post_content;
            $this->info['description'] = $this->plain_text( $text );
        }
        elseif( $this->is_term_archive ){
            $this->info['description'] = $this->plain_text( $this->term->description );
        }
        elseif( $this->is_post_type_archive ){
            $this->info['description'] = $this->plain_text( $this->post_type->description );
        }
        else{
            $this->info['description'] = get_bloginfo('description');
        }
    }

    function set_image(){
        if( $this->is_home ){
            $this->info['image'] = $this->get_default_image();
        }
        elseif( $this->is_singular ){
            $image_id = get_post_thumbnail_id( $this->post->ID );
            if( $image_id != false ){
                $this->info['image'] = $this->set_image_data( $image_id );
            }
        }
        elseif( $this->is_term_archive ){
            $image_id = get_term_meta( $this->term->term_id, 'image', true );
            if( $image_id != false ){
                $this->info['image'] = $this->set_image_data( $image_id );
            }
        }
        elseif( $this->is_post_type_archive ){
            $image_id = get_option("{$this->post_type->name}_posttype_image");
            if( $image_id != false ){
                $this->info['image'] = $this->set_image_data( $image_id );
            }
        }
        else{
            $this->info['image'] = $this->get_default_image();
        }

        // fallback habilitado?
        if( $this->info['image'] === false && $this->info['fallback_image'] == true ){
            $this->info['image'] = $this->get_default_image();
        }
    }

    function set_image_size(){
        $this->info['image_size'] = 'large';
    }

    function set_url(){
        if( $this->is_home ){
            $this->info['url'] = home_url('/');
        }
        elseif( $this->is_singular ){
            $this->info['url'] = get_permalink( $this->post->ID );
        }
        elseif( $this->is_term_archive ){
            $this->info['url'] = get_term_link( $this->term->term_id );
        }
        elseif( $this->is_post_type_archive ){
            $this->info['url'] = get_post_type_archive_link( $this->post_type->name );
        }
        else{
            $this->info['url'] = self_url();
        }
    }

    function set_site(){
        $this->info['site'] = get_bloginfo('name');
    }

    function set_creator(){
        $this->info['creator'] = false;
    }

    function set_type(){
        $this->info['type'] = 'blog';
    }

    function set_language(){
        $this->info['language'] = str_replace( '-', '_', get_bloginfo('language') );
    }
    
    function set_separator(){
        $this->info['separator'] = ' | ';
    }

    /**
     * Determinar se será aplicado o nome do site após o título da página.
     * Utiliza $this->info['separator'] como separador
     * 
     */
    function set_append_sitename(){
        $this->info['append_sitename'] = false;
    }

    /**
     * Recuperar informações de uma imagem
     * 
     */
    function set_image_data( $image_id ){
        $image = wp_get_attachment_image_src( $image_id, $this->info['image_size'] );
        $alt   = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
        if( empty($alt) ){
            $alt = false;
        }
        return array(
            'src'    => $image[0],
            'width'  => $image[1],
            'height' => $image[2],
            'mime'   => get_post_mime_type($image_id),
            'alt'    => $alt,
        );
    }

    /**
     * Recuperar imagem padrão, conforme padrão salvo na option 'og_image'
     * 
     */
    function get_default_image(){
        if( $this->default_image === false ){
            return false;
        }

        if( empty($this->default_image) ){
            $og_image = get_option('og_image');
            if( !empty($og_image) ){
                $image_id = get_option('og_image');
                $image = wp_get_attachment_image_src( $image_id, $this->info['image_size'] );
                $this->default_image = $this->set_image_data( $image_id );
            }
            else{
                $this->default_image = false;
            }
            return $this->default_image;
        }
    }

    /**
     * Formatar texto, removendo tags e quebras de linha
     * 
     */
    function plain_text( $text ){
        $text = strip_tags( $text );
        $text = preg_replace('/\v(?:[\v\h]+)/', '', $text);
        return $text;
    }

    /**
     * Definir dados de produto, caso estjam false
     * 
     */
    function set_product_info(){

        $this->product = new WC_Product( $this->post->ID );
        if( $this->product_info['product_currency'] === false ){
            $this->product_info['product_currency'] = get_woocommerce_currency();
        }
        if( $this->product_info['product_price'] === false ){
            $this->product_info['product_price'] = $this->product->get_price();
        }
        if( $this->product_info['product_formatted_price'] === false ){
            $this->product_info['product_formatted_price'] = strip_tags( wc_price( $this->product_info['product_price'] ) );
        }
        if( $this->product_info['product_sku'] === false ){
            $this->product_info['product_sku'] = $this->product->get_sku();
        }
    }

    /**
     * Output tags OpenGraph
     * 
     */
    function tags_opengraph(){
        $tags = array(
            'og:title'        => $this->info['title'],
            'og:type'         => $this->info['type'],
            'og:url'          => $this->info['url'],
            'og:image'        => $this->info['image']['src'],
            'og:image:type'   => $this->info['image']['mime'],
            'og:image:width'  => $this->info['image']['width'],
            'og:image:height' => $this->info['image']['height'],
            'og:site_name'    => $this->info['site'],
            'og:description'  => $this->info['description'],
            'og:locale'       => $this->info['language'],
        );

        if( $this->is_wc_product ){
            $tags['product:price:currency'] = $this->product_info['product_currency'];
            $tags['product:price:amount']   = $this->product_info['product_price'];
        }

        echo "\n<!-- opengraph share -->\n";
        foreach( $tags as $key => $value ){
            if( $value !== false ){
                echo "<meta property='{$key}' content='{$value}' />\n";
            }
        }
    }

    /**
     * Output tags GPlus
     * 
     */
    function tags_gplus(){
        $tags = array(
            'name'        => $this->info['title'],
            'description' => $this->info['description'],
            'image'       => $this->info['image']['src'],
        );

        echo "<!-- gplus share -->\n";
        foreach( $tags as $key => $value ){
            if( $value !== false ){
                echo "<meta itemprop='{$key}' content='{$value}' />\n";
            }
        }
    }

    /**
     * Output tags Twitter
     * 
     */
    function tags_twitter(){
        $tags = array(
            'twitter:card'         => ($this->info['type'] == 'product') ? 'product' : 'summary',
            'twitter:site'         => $this->info['site'],
            'twitter:creator'      => $this->info['creator'],
            'twitter:title'        => $this->info['title'],
            'twitter:description'  => $this->info['description'],
            'twitter:image'        => $this->info['image']['src'],
            'twitter:image:alt'    => $this->info['image']['alt'],
            'twitter:image:width'  => $this->info['image']['width'],
            'twitter:image:height' => $this->info['image']['height'],
        );

        if( $this->is_wc_product ){
            $tags['twitter:label1'] = 'Preço';
            $tags['twitter:data1']  = $this->product_info['product_formatted_price'];
            if( !empty($this->product_info['product_sku']) ){
                $tags['twitter:label2'] = 'SKU';
                $tags['twitter:data2']  = $this->product_info['product_sku'];
            }
        }

        echo "<!-- twitter card share -->\n";
        foreach( $tags as $key => $value ){
            if( $value !== false ){
                echo "<meta property='{$key}' content='{$value}' />\n";
            }
        }
    }
}
