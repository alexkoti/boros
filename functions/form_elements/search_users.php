<?php


/**
 * Buscar usuários por ajax
 * 
 * 
 * @todo adicionar option para poder configurar uma query completa nas opções do campo
 * @todo mostrar avatares
 * @todo refatorar os js com o do search_content_list
 * 
 */

class BFE_search_users extends BorosFormElement {

    var $valid_attrs = array(
        'name'     => '',
        'value'    => '',
        'id'       => '',
        'class'    => 'search_content_ids', // não mudar pois o js utiliza essa class
        'rel'      => '',
        'disabled' => false,
        'readonly' => false,
    );
    
    var $enqueues = array(
        'js'  => 'search_content_list',
        'css' => 'search_content_list',
    );
    
    function add_defaults(){
        $options = array(
            'role' => 'all',
        );
        $this->defaults['options'] = $options;
    }
    
    function set_label(){
        if( !empty($this->data['label']) ){
            $this->label = "<span class='non_click_label'>{$this->data['label']}{$this->label_helper}</span>";
        }
    }
    
    
    function set_input( $value = null ){
        
        // começar a guardar o output do script js em buffer
        ob_start();
        
        $attrs = make_attributes($this->data['attr']);
        ?>
        <div class="search_content_box">
            <input type="hidden" value="<?php echo $value; ?>" <?php echo $attrs; ?> />
            
            <div class="search_content_list search_content_list_selected">
            <?php if( empty( $this->data_value ) ){ ?>
                <p class="no_results_h">Sem conteúdos selecionados</p>
                <ul class="related_item_list"></ul>
            <?php } else { ?>
                <p class="results_h">Contéudos selecionados:</p>
                <ul class="related_item_list">
                    <?php
                    $related_item = explode( ',', $this->data_value ); // gera o $related_item
                    $related_item = array_unique($related_item);

                    $index = 1;
                    $user_query = new WP_User_Query(array(
                        'include' => $related_item,
                        'orderby' => 'include',
                    ));
                    $result = $user_query->get_results();
                    foreach($result as $user){
                    ?>
                    <li id="related_item_<?php echo $user->ID; ?>" class="related_item">
                        <div class="related_content">
                            <div class="result_head">
                                <strong><?php echo "[{$user->roles[0]}] {$user->data->display_name} <small>[id: {$user->ID}]</small>"; ?></strong>
                                <input type="button" value="remover este item" class="button result_deselect" />
                                <input type="button" value="selecionar este item" class="button-primary result_select" />
                            </div>
                            <div class='related_index'><?php echo $index; ?></div>
                        </div>
                    </li>
                    <?php
                    $index++;
                    }
                    ?>
                </ul>
            <?php } ?>
            </div>
            
            <hr />
            <div class="search_content_inputs">
                <p class="results_h">Buscar:</p>
                <p>
                    <input type='hidden' name='ajaxaction' value='search_users' />
                    <input type='hidden' name='role' value='<?php echo $this->data['options']['role']; ?>' />
                    <input type="text" name="search_content_text" class="ipt_text" />
                    <span class="search_content_clear"></span>
                    <span class="button search_content_submit">buscar</span>
                    <img class="waiting" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" />
                </p>
            </div>
            
            <div class="search_content_list search_content_list_results"></div>
        </div>
        <?php
        // guardar o output em variável
        $input = ob_get_contents();
        ob_end_clean();
        return $input;
    }
}

add_action('wp_ajax_search_users', 'boros_element_search_users');
function boros_element_search_users(){

    $args = array();
    $args['search'] = "*{$_POST['search_text']}*";
    $args['search_columns'] = array('ID', 'user_login', 'user_nicename', 'user_email', 'user_url');
    if( $_POST['role'] != 'all' ){
        $args['role'] = $_POST['role'];
    }
    if( !empty($_POST['remove']) ){
        $args['exclude'] = explode(',', $_POST['remove']);
    }
    $user_query = new WP_User_Query( $args );
    $result = $user_query->get_results();

    // Check if any posts were found.
    if ( empty($result) ){
        echo '<p class="results_h">Sem resultados. <strong>Atenção: os resultados excluem conteúdos já adicionados.</strong></p>';
        die();
    }
    else{
        echo '<p class="results_h">Resultados:</p>';
        echo '<ul>';
        $index = 1;
        foreach($result as $user){
            ?>
            <li id="related_item_<?php echo $user->ID; ?>" class="related_item">
                <div class="related_content">
                    <div class="result_head">
                        <strong><?php echo "[{$user->roles[0]}] {$user->data->display_name} <small>[id: {$user->ID}]</small>"; ?></strong>
                        <input type="button" value="remover este item" class="button result_deselect" />
                        <input type="button" value="selecionar este item" class="button-primary result_select" />
                    </div>
                    <div class='related_index'><?php echo $index; ?></div>
                </div>
            </li>
            <?php
			$index++;
        }
        echo '</ul>';
    }
    die();
}




