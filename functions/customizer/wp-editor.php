<?php

/**
 * WP Editor no Customizer
 * 
 * Necessário fazer include da class via add_action( 'customize_register' )
 * 
 * @link https://github.com/maddisondesigns/customizer-custom-controls
 * 
 */
class Boros_Customizer_WP_Editor extends WP_Customize_Control {

    public $type = 'boros_wp_editor';

    public function enqueue(){
        wp_enqueue_script( 'skyrocket-custom-controls-js', BOROS_URL . '/functions/customizer/js/wp-editor.js', array( 'jquery' ), BOROS_VERSION_ID, true );
        wp_enqueue_editor();
    }

    public function to_json() {
        parent::to_json();
        $this->json['skyrockettinymcetoolbar1'] = isset( $this->input_attrs['toolbar1'] ) ? esc_attr( $this->input_attrs['toolbar1'] ) : 'bold italic bullist numlist alignleft aligncenter alignright link';
        $this->json['skyrockettinymcetoolbar2'] = isset( $this->input_attrs['toolbar2'] ) ? esc_attr( $this->input_attrs['toolbar2'] ) : '';
        $this->json['skyrocketmediabuttons'] = isset( $this->input_attrs['media_buttons'] ) && ( $this->input_attrs['media_buttons'] === true ) ? true : false;
        //$this->json['skyrocketmediabuttons'] = true;
    }

    /**
     * Render the control in the customizer
     */
    public function render_content(){
    ?>
        <div class="tinymce-control">
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <?php if( !empty( $this->description ) ) { ?>
                <span class="customize-control-description"><?php echo esc_html( $this->description ); ?></span>
            <?php } ?>
            <textarea id="<?php echo esc_attr( $this->id ); ?>" class="customize-control-tinymce-editor" <?php $this->link(); ?>><?php echo esc_attr( $this->value() ); ?></textarea>
        </div>
    <?php
    }
}
