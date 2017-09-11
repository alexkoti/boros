<?php
/**
 * TAXONOMY_SELECT2
 * 
 * Exemplo de configuração:
 * <code>
 *     array(
 *         'name'    => 'tax_input[pleocroismo-x][]', // importante o [] no final para o envio correto de múltiplos termos no $_POST
 *         'label'   => 'X',
 *         'type'    => 'taxonomy_select2',
 *         'size'    => 'large',
 *         'attr'    => array(
 *             'multiple' => 'multiple',
 *         ),
 *         'options' => array(
 *             'taxonomy' => 'pleocroismo-x',
 *         ),
 *     )
 * </code>
 * 
 * 
 */

class BFE_taxonomy_select2 extends BorosFormElement {
    var $valid_attrs = array(
        'name' => '',
        'id' => '',
        'class' => '',
        'rel' => '',
        'disabled' => false,
        'readonly' => false,
    );
    
    var $enqueues = array(
        'js'  => array('select2.min', 'taxonomy_select2'),
        'css' => array('select2.min'),
    );
    
    function add_defaults(){
        $this->defaults['attr']['multiple']           = 'multiple';
        $this->defaults['options']['type']            = 'post_meta'; // post_meta, option, term_meta
        $this->defaults['options']['taxonomy']        = 'category';
        $this->defaults['options']['show_option_all'] = ' — ';
        $this->defaults['options']['hide_empty']      = false;
        $this->defaults['options']['hierarchical']    = false;
    }
    
    function set_input( $value = null ){
        global $post;
        
        /**
         * Caso seja uma chamada ajax, $post não estará disponível. Todas as variáveis vindo do ajax
         * 
         */
        if( isset($_GET['ajax_post_id']) )
            $post = get_post( intval($_GET['ajax_post_id']) );
        
        // sempre esperar um array de termos
        foreach( (array)$value as $v ){
            $selected_terms = $v;
        }
        
        
        /**
         * Essa verificação que busca o valor gravado em banco em vez do reload enviado em $value, deverá ser usado na edição de post/term/user em frontend_forms
         * 
         */
        // termo selecionado - verifica se está buscando post_meta ou taxonomy_meta
        $selected_terms = false;
        if( isset($_GET['taxonomy']) ){
            if( isset($_GET['tag_ID']) ){
                $selected_terms = get_metadata( 'term', intval($_GET['tag_ID']), $this->data['options']['taxonomy'], true );
            }
        }
        elseif( isset($this->data['options']['object_type']) and $this->data['options']['object_type'] == 'admin_page' ){
            $selected_terms = get_option( $this->data['name'] );
        }
        else{
            $selecteds = wp_get_object_terms( $post->ID, $this->data['options']['taxonomy'] );
            if( !empty($selecteds) ){
                foreach( $selecteds as $tt ){
                    $selected_terms[] = absint( $tt->term_id );
                }
            }
        }
        
        // caso esteja vazio e possua um default, aplicar
        if( empty( $selected_terms ) and !empty( $this->data['std'] ) ){
            $default_term = get_term_by( 'name', $this->data['std'], $this->data['options']['taxonomy'] );
            $selected_terms = $default_term->term_id;
        }
        
        $attrs = make_attributes($this->data['attr']);
        
        //pre($selected_terms, 'selected_terms');
        //pre($attrs, 'attrs');
        
        $terms = get_terms(array(
            'taxonomy'   => $this->data['options']['taxonomy'],
            'hide_empty' => false,
        ));
        
        // começar a guardar o output do script js em buffer
        ob_start();
        
        echo "<select {$attrs}>";
        foreach( $terms as $term ){
            $selected = in_array($term->term_id, $selected_terms) ? ' selected="selected"' : '';
            echo "<option value='{$term->term_id}'{$selected}>{$term->name}</option>";
        }
        echo '</select>';
        
        $input = ob_get_contents();
        ob_end_clean();
        return $input;
    }
}




