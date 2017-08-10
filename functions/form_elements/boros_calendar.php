<?php
/**
 * Boros Calendar
 * Utilizar meta field próprio para ser usado em outros meta_boxes, no lugar do original Boros_Calendar_Options que possui meta_box exclusivo
 * 
 * 
 */

class BFE_boros_calendar extends BorosFormElement {
    
    var $valid_attrs = array(
        'name' => '',
        'value' => '',
        'id' => '',
        'class' => '',
        'rel' => '',
        'placeholder' => '',
        'size' => false,
        'disabled' => false,
        'readonly' => false,
        'maxlength' => false,
    );
    
    /**
     * Opções
     * 
     * $post_meta         post_meta que devera ser usado
     * $num_months        número de meses a mostrar simultâneamente
     * $calendar_instance instância de Boros_Calendar_Options, obrigatório. Será usado para renderizar o campo
     * 
     */
    function add_defaults(){
        $this->defaults['options']['post_meta'] = 'event_date';
        $this->defaults['options']['num_months'] = 3;
        $this->defaults['options']['calendar_instance'] = false;
    }
    
    /**
     * Saída final do input
     * 
     */
    function set_input( $value = null ){
        $post_id = ( isset($this->context['post_id']) ) ? $this->context['post_id'] : 0;
        if( $this->data['options']['calendar_instance'] != false ){
            return $this->data['options']['calendar_instance']->render_metabox( $post_id );
        }
        else{
            return '<div class="form_element_error">Não foi definida uma instância de Boros_Calendar_Options em [options][calendar_instance]</div>';
        }
    }
}


