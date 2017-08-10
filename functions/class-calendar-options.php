<?php
/**
 * Calendar Options
 * 
 * 
 * 
 */


class Boros_Calendar_Options {
    
    protected $ver = '0.1.0';
    
    /**
     * Post type do calendário.
     * 
     */
    protected $post_type = 'post';
    
    /**
     * Name do post_meta que salva os dia(s) do evento.
     * 
     */
    protected $post_meta = 'event_date';
    
    /**
     * Name usado na criação do transient_name.
     * 
     */
    protected $pt_name = 'post';
    
    /**
     * Name que será usado para salvar o post_meta com todos os registros de datas do datepicker separados por vírgula.
     * 
     */
    protected $post_meta_index = false;
    
    /**
     * Usar o meta_box padrão da classe, caso não seja usado, deverá ser criado separadamente.
     * 
     */
    protected $use_meta_box = true;
    
    /**
     * Título do meta_box.
     * 
     */
    protected $meta_box_title = 'Dias do evento';
    
    /**
     * Número de meses para exibir.
     * 
     */
    protected $num_months = 4;
    
    protected $nonce_action = 'boros_calendar_save_event_dates';
    
    protected $nonce_name = 'boros_calendar_save_event_dates_nonce';
    
    /**
     * Construct
     * 
     */
    function __construct( $config = array() ){
        
        $vars = array(
            'post_type',
            'post_meta',
            'post_meta_index',
            'use_meta_box',
            'meta_box_title',
        );
        foreach( $vars as $v ){
            if( isset($config[$v]) ){
                $this->$v = $config[$v];
            }
        }
        
        // name do campo a ser salvo, será a lista de datas gmt separadas por vírgula
        // caso não tenha sido definido em $config e permaneça false(padrão) será usado o sufixo _index
        if( $this->post_meta_index == false ){
            $this->post_meta_index = "{$this->post_meta}_index";
        }
        
        // definir o post_type name apropriado
        $this->pt_name = Boros_Calendar::generate_post_type_name( $this->post_type, $this->post_meta ); //pre($pt_name);
        
        // adicionar css e js
        add_action( 'admin_enqueue_scripts', array($this, 'enqueues') );
        
        // usar o processo de meta_box/save da classe
        // caso seja false, deverá ser feita a exibição do campo diretamente via render_metabox();
        if( $this->use_meta_box == true ){
            add_action( 'add_meta_boxes', array($this, 'add') );
        }
        
        // salvar dados
        add_action( 'save_post', array($this, 'save'), 10, 2 );
    }
    
    /**
     * GET
     * 
     * @ver 0.1.0
     */
    function __get( $var ){
        return $this->$var;
    }
    
    /**
     * SET
     * 
     * @ver 0.1.0
     */
    function __set( $var, $val ){
        
    }
    
    /**
     * Registrar meta_box próprio
     * 
     */
    function add(){
        add_meta_box( 'boros-calendar-event-dates', $this->meta_box_title, array( $this, 'meta_box' ), $this->post_type, 'normal', 'default' );
    }
    
    /**
     * Exibir o meta_box
     * 
     */
    function meta_box( $post ){
        echo $this->render_metabox( $post->ID );
    }
    
    /**
     * Exibir o campo, pode ser reutilizado caso não se queira usar o metabox da própria classe
     * 
     */
    function render_metabox( $post_id ){
        
        $nonce_field = wp_nonce_field( $this->nonce_action, $this->nonce_name, true, false );
        $value = get_post_meta( $post_id, $this->post_meta_index, true );
        
        return "
        {$nonce_field}
        <div class='date_picker_multiple_box date_picker_multiple_cols_{$this->num_months}'>
            <input type='hidden' style='width:100%' name='{$this->post_meta_index}' value='{$value}' class='date_picker_input' id='date_picker_input_{$this->post_meta_index}'  />
            <div class='date_picker_multiple_calendars' id='date_picker_calendars_{$this->post_meta_index}' data-num-months='{$this->num_months}'></div>
        </div>";
    }
    
    /**
     * Enqueues CSS e JS
     * 
     */
    function enqueues( $hook ){
        // limitar para as páginas de postagem
        if( !in_array( $hook, array('post.php', 'post-new.php') ) ){
            return;
        }
        
        $css_url = BOROS_CSS . 'date_picker_multiple.css';
        $js_url  = BOROS_JS . 'date_picker_multiple.js';
        wp_enqueue_style( 'boros-calendar-css', $css_url, false, version_id(), 'screen', true );
        wp_enqueue_script( 'boros-calendar-js', $js_url, array('jquery'), true );
    }
    
    public function save( $post_id, $post ) {
        // Add nonce for security and authentication.
        $nonce_name   = isset( $_POST[$this->nonce_name] ) ? $_POST[$this->nonce_name] : '';
 
        // Check if nonce is set.
        if ( ! isset( $nonce_name ) ) {
            return $post_id;
        }
 
        // Check if nonce is valid.
        if ( ! wp_verify_nonce( $nonce_name, $this->nonce_action ) ) {
            return $post_id;
        }
 
        // Check if user has permissions to save data.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }
 
        // Check if not an autosave.
        if ( wp_is_post_autosave( $post_id ) ) {
            return $post_id;
        }
 
        // Check if not a revision.
        if ( wp_is_post_revision( $post_id ) ) {
            return $post_id;
        }
        
        // check if there was a multisite switch before
        if ( is_multisite() && ms_is_switched() ) {
            return $post_id;
        }
        
        // Sanitize the user input.
        $value = sanitize_text_field( $_POST[$this->post_meta_index] );
        
        // Recuperar o valor antigo e salvar apenas se o valor postado for diferente
        $old = get_post_meta( $post_id, $this->post_meta_index, true );
        if( $old != $value ){
            // atualizar o valor
            update_post_meta( $post_id, $this->post_meta_index, $value );
            
            // salvar os intervalos
            $this->save_event_dates( $post, $value );
        }
    }
    
    /**
     * Salvar post_metas múltiplos para cada dia de ocorrência do evento
     * Apagar transients relacionados
     * 
     */
    function save_event_dates( $post, $value ){
        $delete_dates = array();
        
        // gravar post_metas
        $event_dates = explode(',', $value);
        sort($event_dates);
        
        // apagar todos os post_metas anteriores
        delete_post_meta($post->ID, $this->post_meta);
        
        // adicionar as novas datas e sinalizar quais meses e anos devem ser refeitos os transients
        foreach( $event_dates as $date ){
            add_post_meta( $post->ID, $this->post_meta, $date );
            
            $month = date('m', strtotime($date));
            $year  = date('Y', strtotime($date));
            $delete_dates[$year][] = $month;
        }
        
        // Salvar datas de início e término do evento. Será usado nas queries.
        update_post_meta( $post->ID, "{$this->post_meta}_start", $event_dates[0] );
        update_post_meta( $post->ID, "{$this->post_meta}_end", end($event_dates) );
        
        // Salvar o post_meta name usado para salvar as datas
        update_post_meta( $post->ID, 'boros_calendar_post_meta', $this->post_meta );
        
        // resetar o transient respectivos
        delete_transient("brscldr_{$this->pt_name}_{$this->post_meta}");
        foreach( $delete_dates as $year => $months ){
            foreach( $months as $month ){
                delete_transient("brscldr_{$this->pt_name}_{$this->post_meta}_{$month}_{$year}");
            }
        }
    }
    
}



