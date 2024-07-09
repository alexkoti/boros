<?php

/**
 * Class para geração de tags de compartilhamento
 * 
 * É possível passar qualquer argumento de $info no construct, para que o output seja personalizado.
 * 
 * @todo: atributo 'thumbnail_meta_name' para escolher utro meta de imagem destacada
 * @todo: aplicar trim e striptags em 'description'
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
     * Dados padrão para imagem, caso os índices sejam false, serão ignorados na renderização
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
        'image'           => array(
            'src'    => false,
            'width'  => false,
            'height' => false,
            'mime'   => false,
            'alt'    => false,
        ),
        'url'             => false,
        'type'            => false,
        'language'        => false,
    );

    /**
     * Dados de produto
     * 
     */
    var $product;
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
        // valores vazios são desconsiderados, pois pode ser um valor intencional
        foreach( $this->info as $key => $value ){
            if( $value === false || ($key == 'image' && empty($value['src'])) ){
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
            $this->info['title'] = apply_filters( 'the_title', $this->post->post_title, $this->post->ID );
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
            $text = apply_filters('the_content', $text);
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
                $this->default_image = array(
                    'src'    => false,
                    'width'  => false,
                    'height' => false,
                    'mime'   => false,
                    'alt'    => false,
                );
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
     * Definir dados de produto, caso estejam false
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
                $meta = str_pad("property='{$key}'", 26);
                echo "<meta {$meta} content='{$value}' />\n";
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
                $meta = str_pad("itemprop='{$key}'", 22);
                echo "<meta {$meta} content='{$value}' />\n";
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
                $meta = str_pad("property='{$key}'", 31);
                echo "<meta {$meta} content='{$value}' />\n";
            }
        }
    }
}
