<?php
/**
 * MEDIA SELECTOR
 * 
 * @todo ajustes para outros tipos de arquivos além de imagem: file-icon, file-details
 * @todo múltiplos medias, com drag-drop
 * 
 */

class BFE_media_selector extends BorosFormElement {

    var $valid_attrs = array(
        'name'        => '',
        'value'       => '',
        'id'          => '',
        'class'       => '',
        'rel'         => '',
        'placeholder' => '',
        'size'        => false,
        'disabled'    => false,
        'readonly'    => false,
        'maxlength'   => false,
    );

    var $default_options = array(
        'multiple'       => false,                  // em caso de multiple, salvar as ids separada por vírgula
        'add_text'       => 'selecionar',           // texto do botão de selecionar midia
        'remove_text'    => 'remover',              // texto do botão de remover mídia, é usado no title do (X) na midia selecionada
        'remove_button'  => true,                   // exibir o botão de remover mídia, o 'remove_text' aidna é usado no (x)
        'confirm_button' => 'confirmar',            // texto de confirmação no modal
        'modal_title'    => 'Selecionar',           // tpitulo do modal
        'file_type'      => 'image',                // ''(tudo), 'image', 'audio', 'video', 'application/pdf', '*/pdf', '*/xls'
        'file_orderby'   => 'date',                 // modal, critério de ordenação
        'file_order'     => 'DESC',                 // ordenação
        'select_type'    => 'image',                // tipo de controle: 'image'(mostra thumbnail) ou 'file'(mostra ícone + info)
        'image_size'     => 'thumbnail',            // tamanho da imagem conforme wp_image_sizes, em caso de 'file_type' diferente de 'image', será forçado para 'thumbnail'
        'width'          => 150,                    // largura do thumbnail/ícone
        'height'         => 150,                    // altura do thumbnail/ícone
        'default_image'  => BOROS_IMG . 'x.gif',    // imagem padrão, mesmo para outros tipos não-imagem
        'align'          => 'left',                 // alinhamento do controle
        'show_info'      => false,                  // mostrar a caixa de informações, obrigatório para tipos não-imagem, estabelece largura mínima de 200px
    );

    var $options = array();
    
    var $enqueues = array(
        'js' => array('media-selector'),
    );

    /**
     * Sinalizar se a midia atual possui img associada.
     * 
     */
    var $has_thumb = '';

    private static $counter = 0;
    
    function init(){
        wp_enqueue_media();
        //wp_enqueue_script( 'custom-header' );

		// acionar apenas na primeira instância
		if( self::$counter == 0 ){
			add_action( 'admin_footer', array($this, 'footer') );
		}
		self::$counter++;
    }
    
    function set_input( $value = null ){

        $this->options = wp_parse_args( $this->data['options'], $this->default_options );

        $this->options['select_type'] = (strpos($this->options['file_type'], 'image') === false) ? 'type-file' : 'type-image';
        // forçar para que o wp-image-size em caso de 'type-file' seja thumbnail
        if( $this->options['select_type'] == 'type-file' ){
            $this->options['image_size'] = 'thumbnail';
        }

        $show_info = ($this->options['show_info'] == true) ? ' show-info' : '';
        $opt       = htmlspecialchars(json_encode($this->options));
        $attrs     = make_attributes($this->data['attr']);
        $query_id  = $this->set_query_id();
        $img_set   = ( $value > 0 ) ? 'vale-set' : 'vale-not-set';
        $align     = ' align-' . $this->options['align'];
        $classes   = "{$img_set} {$align} {$this->options['select_type']} {$show_info}";

        ob_start();
        ?>
        <div class="boros-media-selector <?php echo $classes; ?>" data-options="<?php echo $opt; ?>" id="boros-media-selector-<?php echo self::$counter; ?>" data-query-id="<?php echo $query_id; ?>">
            <div class="inner">
                <div class="selected-medias">
                    <?php $this->current_media( $value ); ?>
                </div>
                <div class="media-selector-actions" style="min-width:<?php echo $this->options['width']; ?>px;">
                    <button type="button" class="button-link media-selector-btn media-selector-add"><?php echo $this->options['add_text']; ?></button>
                    <?php if( $this->options['remove_button'] !== false ){ ?>
                    <button type="button" class="button-link media-selector-btn media-selector-remove"><?php echo $this->options['remove_text']; ?></button>
                    <?php } ?>
                </div>
            </div>
            <input type="hidden" value="<?php echo $value; ?>" <?php echo $attrs; ?> />
        </div>
        <?php
        $input = ob_get_contents();
        ob_end_clean();
        return $input;
    }

    function set_query_id(){
        $file_type = str_replace( array('/', '/*', '*/'), '-', $this->options['file_type'] );
        return "{$file_type}-{$this->options['file_orderby']}-{$this->options['file_order']}";
    }

    function current_media( $id ){

        $info = $this->media_info( $id );
        $has_thumb = '';

        //$value = 71;
        if( $id > 0 ){
            $src = wp_get_attachment_image_src( $id, $this->options['image_size'], true );
            $img_src = $src[0];
            $has_thumb = 'has-thumb';
        }
        else{
            $img_src = $this->options['default_image'];
        }
        $dim = "width:{$this->options['width']}px;height:{$this->options['height']}px";
        ?>
        <div class="media-item">
            <div class="media-icon <?php echo $this->has_thumb; ?>" style="<?php echo $dim; ?>">
                <div class="remove" title="<?php echo $this->options['remove_text']; ?>"></div>
                <img src="<?php echo $img_src; ?>" alt="">
            </div>
            <?php echo $info; ?>
        </div>
        <?php
    }

    function media_info( $id ){

        if( $this->options['show_info'] == false ){
            return false;
        }

        $info = array(
            'title' => 'Arquivo não definido',
            'type'  => '',
            'size'  => '',
            'dims'  => '',
        );
        $attch = get_post( $id );
        $meta  = wp_get_attachment_metadata( $id );
        
        // title, type
        if( !empty($id) ){
            $info['title'] = '<div class="name"><strong>Título:</strong> ' . apply_filters( 'the_title', $attch->post_title, $attch->ID ) . '</div>';
            $info['type']  = "<div class='type'><strong>Tipo do arquivo:</strong> {$attch->post_mime_type}</div>";
        }
        
        // dimensões
        if ( is_array( $meta ) && array_key_exists( 'width', $meta ) && array_key_exists( 'height', $meta ) ){
            $info['dims'] = "<div class='dims'><strong>Dimensões:</strong> {$meta['width']}&nbsp;&times;&nbsp;{$meta['height']}</div>";
        }

        // possui thumbnail
        if( isset($meta['sizes']) ){
            $this->has_thumb = 'has-thumb';
        }
        
        // size
        $file = get_attached_file( $id );
        $file_size = '';
        if( isset( $meta['filesize'] ) ){
            $file_size = $meta['filesize'];
        }
        elseif( file_exists( $file ) ){
            $file_size = filesize( $file );
        }

        if( !empty( $file_size ) ){
            $info['size'] = '<div class="size"><strong>Tamanho:</strong> ' . size_format( $file_size ) . '</div>';
        }

        return "<div class='media-info'>{$info['title']}{$info['type']}{$info['size']}{$info['dims']}</div>";
    }

    function footer(){
        ?>
        <script type="text/template" id="tmpl-boros-media-selector-image">
            <div class="media-item">
                <div class="media-icon {{{data.hthumb}}}" style="width:{{{data.width}}}px;height:{{{data.height}}}px;">
                    <div class="remove" title="{{{data.remove}}}"></div>
                    <img src="{{{data.src}}}" alt="{{{data.alt}}}">
                </div>
                <div class='media-info'>
                <# if( data.title != '' ){ #>
                    <div class="title"><strong>Título:</strong> {{data.title}}</div>
                    <div class="type"><strong>Tipo do arquivo:</strong> {{data.type}}</div>
                    <# if( data.dims != '' ){ #>
                    <div class="dims"><strong>Dimensões:</strong> {{data.dims}}</div>
                    <# } #>
                    <div class="size"><strong>Tamanho:</strong> {{data.size}}</div>
                <# } else { #>
                    Arquivo não definido
                <# } #>
                </div>
            </div>
        </script>
        <?php
    }
}