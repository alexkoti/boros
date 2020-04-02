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
        'multiple'       => false,                // em caso de multiple, salvar as ids separada por vírgula
        'add_button'     => 'selecionar',
        'remove_button'  => 'remover',
        'confirm_button' => 'confirmar',
        'modal_title'    => 'Selecionar',
        'file_type'      => 'image',              // '', 'image', 'audio', 'video'
        'image_size'     => 'thumbnail',          // wp_image_sizes
        'width'          => 150,
        'height'         => 150,
        'default_image'  => BOROS_IMG . 'x.gif',
        'align'          => 'left',
    );

    var $options = array();
    
    var $enqueues = array(
        'js' => 'media-selector',
    );

    private static $counter = 1;
    
    function init(){
		// acionar apenas na primeira instância
		if( self::$counter == 1 ){
			add_action( 'admin_footer', array($this, 'footer') );
		}
		self::$counter++;
    }
    
    function set_input( $value = null ){

        $this->options = wp_parse_args( $this->data['options'], $this->default_options );
        $dim   = "width:{$this->options['width']}px;height:{$this->options['height']}px";
        $opt   = htmlspecialchars(json_encode($this->options));
        $attrs = make_attributes($this->data['attr']);
        $class = ( $value > 0 ) ? 'image-set' : 'image-not-set';
        $align = ' align-' . $this->options['align'];

        ob_start();
        ?>
        <div class="boros-media-selector <?php echo $class . $align; ?>" data-options="<?php echo $opt; ?>">
            <div class="selected-image media-selector-img">
                <?php $this->current_media( $value, $dim ); ?>
            </div>
            <div class="media-selector-actions">
                <button type="button" class="button-link media-selector-btn media-selector-add"><?php echo $this->options['add_button']; ?></button>
                <button type="button" class="button-link media-selector-btn media-selector-remove"><?php echo $this->options['remove_button']; ?></button>
            </div>
            <input type="hidden" value="<?php echo $value; ?>" <?php echo $attrs; ?> />
        </div>
        <?php
        $input = ob_get_contents();
        ob_end_clean();
        return $input;
    }

    function current_media( $value, $dim ){
        //$value = 71;
        if( $value > 0 ){
            $src = wp_get_attachment_image_src( $value, $this->options['image_size'] );
            $img_src = $src[0];
        }
        else{
            $img_src = $this->options['default_image'];
        }
        ?>
        <div class="media-item media-item-image" style="<?php echo $dim; ?>">
            <div class="remove" title="<?php echo $this->options['remove_button']; ?>"></div>
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