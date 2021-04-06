<?php
/**
 * TAXONOMY_SELECT2
 * 
 * Exemplo de configuração:
 * <code>
 *     array(
 *         'name'    => 'tax_input[pleocroismo-x]',
 *         'label'   => 'X',
 *         'type'    => 'taxonomy_select2',
 *         'size'    => 'large',
 *         'attr'    => array(
 *             'number' => 10,
 *             'multiple' => 'multiple',
 *             'input_min_len' => 4,
 *         ),
 *         'options' => array(
 *             'taxonomy' => 'pleocroismo-x',
 *         ),
 *     )
 * </code>
 * 
 * 
 */

class BFE_taxonomy_select2_ajax extends BorosFormElement {

    var $valid_attrs = array(
        'name'     => '',
        'id'       => '',
        'class'    => '',
        'rel'      => '',
        'disabled' => false,
        'readonly' => false,
    );
    
    var $enqueues = array(
        'js'  => array('select2.min', 'taxonomy-select2-ajax'),
        'css' => array('select2.min', 'taxonomy-select2-ajax'),
    );
    
    function add_defaults(){
        $this->defaults['options']['number']   = 6;
        $this->defaults['options']['multiple'] = false;
        $this->defaults['options']['input_min_len'] = 3;
    }
    
    function set_input( $value = null ){
        global $post;

        $options  = wp_parse_args($this->data['options'], $this->defaults);
        $taxonomy = isset($options['taxonomy']) ? $options['taxonomy'] : $this->data['name'];

        // termos selecionados
        $selected_terms = wp_get_object_terms( $post->ID, $taxonomy );

        // usado pelo ajax, para identificar a taoxnomia
        $this->data['attr']['dataset']['taxonomy']      = $taxonomy;
        $this->data['attr']['dataset']['number']        = $options['number'];
        $this->data['attr']['dataset']['input_min_len'] = $options['input_min_len'];
        if( $options['multiple'] == true ){
            $this->data['attr']['multiple'] = 'multiple';
        }
        // atribuir corretamente o valor do POST
        $this->data['attr']['name'] .= '[]';

        $attrs = make_attributes($this->data['attr']);
        
        // output buffer
        ob_start();
        
        echo "<select {$attrs}>";
        if( !empty($selected_terms) ){
            foreach( $selected_terms as $term ){
                echo "<option value='{$term->term_id}' selected='selected'>{$term->name}</option>";
            }
        }
        echo '</select>';
        
        $input = ob_get_contents();
        ob_end_clean();
        return $input;
    }
}

/**
 * Ajax da busca pelo select2
 * 
 */
add_action( 'wp_ajax_boros_taxonomy_select2', 'ajax_boros_taxonomy_select2' );
function ajax_boros_taxonomy_select2(){

    if( empty($_GET['term']) ){
        wp_send_json(array(
            'total'   => 0,
            'results' => array(),
        ));
    }

    $terms = get_terms(array(
        'taxonomy'   => $_GET['taxonomy'],
        'search'     => $_GET['term'],
        'hide_empty' => false,
        'number'     => $_GET['number']
    ));

    if( !empty($terms) ){
        $results = array();
        foreach( $terms as $term ){
            $results[] =  array(
                'id'   => $term->term_id,
                'text' => $term->name,
            );
        }
        wp_send_json(array(
            'total' => count($terms),
            'results' => $results,
        ));
    }
    else{
        wp_send_json(array(
            'total'   => 0,
            'results' => array(),
        ));
    }

    die();
}




