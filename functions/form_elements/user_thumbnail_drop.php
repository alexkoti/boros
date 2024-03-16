<?php
/**
 * USER THUMBNAIL DROP
 * Upload de imagem com autosubmit ajax
 * 
 */

class BFE_user_thumbnail_drop extends BorosFormElement {
    
    var $valid_attrs = array(
        'name'        => '',
        'value'       => '',
        'id'          => '',
        'class'       => '',
        'rel'         => '',
    );
    
    var $enqueues = array(
        'js' => array(
            'upload',
        ),
        'css' => array(
            'upload',
        ),
    );

    function add_defaults(){
        $this->defaults['options']['button_send']  = 'Selecionar imagem';           // texto do botão, quando não houver nenhuma
        $this->defaults['options']['button_new']   = 'Selecionar uma nova imagem';  // botão do botão, quando já existir uma imagem
        $this->defaults['options']['drop_message'] = 'Solte a imagem aqui';         // mensagem da área de drop
    }

    function init(){
        add_action( 'wp_footer', 'boros_upload_admin_head' );
        wp_enqueue_script( 'plupload-handlers' );
    }

    function set_input( $value = null ){

        $image_size = 'thumbnail';
        if( isset($this->data['options']['image_size']) ){
            $image_size = $this->data['options']['image_size'];
        }

        $labels = array(
            'button_send'  => $this->data['options']['button_send'],
            'button_new'   => $this->data['options']['button_new'],
            'drop_message' => $this->data['options']['drop_message'],
        );

        //pre( $this->context['object_id'] );
        $user = new WP_User($this->context['object_id']);
        ob_start();
        boros_drop_upload_box( $user, $image_size, $labels, $this->data['name'], 'user' );
        echo "<div>{$this->input_helper}</div>";
        $input = ob_get_contents();
        ob_end_clean();

        return $input;

    }
}

