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
        'image_size'     => 'thumbnail',            // tamanho da imagem conforme wp_image_sizes
        'width'          => 150,
        'height'         => 150,
        'default_image'  => BOROS_IMG . 'x.gif',
        'align'          => 'left',
    );

    var $options = array();
    
    var $enqueues = array(
        'js' => array('media-selector'),
    );

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

        $opt      = htmlspecialchars(json_encode($this->options));
        $attrs    = make_attributes($this->data['attr']);
        $class    = ( $value > 0 ) ? 'image-set' : 'image-not-set';
        $align    = ' align-' . $this->options['align'];
        $query_id = $this->set_query_id();

        ob_start();
        ?>
        <div class="boros-media-selector <?php echo $class . $align; ?>" data-options="<?php echo $opt; ?>" id="boros-media-selector-<?php echo self::$counter; ?>" data-query-id="<?php echo $query_id; ?>">
            <div class="selected-medias">
                <?php $this->current_media( $value ); ?>
            </div>
            <div class="media-selector-actions" style="min-width:<?php echo $this->options['width']; ?>px;">
                <button type="button" class="button-link media-selector-btn media-selector-add"><?php echo $this->options['add_text']; ?></button>
                <?php if( $this->options['remove_button'] !== false ){ ?>
                <button type="button" class="button-link media-selector-btn media-selector-remove"><?php echo $this->options['remove_text']; ?></button>
                <?php } ?>
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

    function current_media( $value ){
        //$value = 71;
        if( $value > 0 ){
            $src = wp_get_attachment_image_src( $value, $this->options['image_size'] );
            $img_src = $src[0];
        }
        else{
            $img_src = $this->options['default_image'];
        }
        $dim = "width:{$this->options['width']}px;height:{$this->options['height']}px";
        ?>
        <div class="media-item media-item-image" style="<?php echo $dim; ?>">
            <div class="remove" title="<?php echo $this->options['remove_text']; ?>"></div>
            <img src="<?php echo $img_src; ?>" alt="">
        </div>
        <?php
    }

    function footer(){
        ?>
        <script type="text/template" id="tmpl-boros-media-selector-image">
            <div class="media-item media-item-image" style="width:{{{data.width}}}px;height:{{{data.height}}}px;">
                <div class="remove" title="{{{data.remove}}}"></div>
                <img src="{{{data.src}}}" alt="{{{data.alt}}}">
            </div>
        </script>
        <?php
    }
}