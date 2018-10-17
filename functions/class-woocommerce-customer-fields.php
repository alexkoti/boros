<?php

/**
 * Campos adicionais para customers
 * 
 * 
 * Baseado em:
 * @link https://iconicwp.com/blog/the-ultimate-guide-to-adding-custom-woocommerce-user-account-fields/
 * 
 */
class Boros_Woocommerce_Customer_Fields {

    /**
     * Configuração dos campos
     * 
     */
    protected $fields = array();

    public function __construct( $fields = array() ){

        // permitir que os values sejam recuperados em caso de POST
        add_filter( 'boros_customer_fields', array($this, 'reload_post_data') );
        
        $this->fields = apply_filters('boros_customer_fields', $fields);
        if( empty($this->fields) ){
            return;
        }
        //pre($this->fields, 'fields', false);

        // output: registration
        add_action( 'woocommerce_register_form', array($this, 'output_registration_fields'), 10 );
        // output: my-account/edit-account/
        add_action( 'woocommerce_edit_account_form', array($this, 'output_registration_fields'), 10 );
        // output: checkout
        add_filter( 'woocommerce_checkout_fields', array($this, 'output_checkout_fields' ), 10 );
        // output: admin: edit profile
        add_action( 'show_user_profile', array($this, 'output_admin_fields'), 30 );
        // output: admin: edit other users
        add_action( 'edit_user_profile', array($this, 'output_admin_fields'), 30 );

        // validar dados
        add_filter( 'woocommerce_registration_errors',         array($this, 'validate_fields'), 10, 2 );
        add_filter( 'woocommerce_save_account_details_errors', array($this, 'validate_fields'), 10, 2 );

        // ajustar mensagem de erro no alerta de checkout
        add_filter( 'woocommerce_checkout_required_field_notice', array($this, 'error_alerts'), 10, 2 );

        // salvar dados
        add_action( 'woocommerce_created_customer',     array( $this, 'save_fields') );     // register/checkout
        add_action( 'personal_options_update',          array( $this, 'save_fields') );     // edit own account admin
        add_action( 'edit_user_profile_update',         array( $this, 'save_fields') );     // edit other account admin
        add_action( 'woocommerce_save_account_details', array( $this, 'save_fields') );     // edit WC account
    }
    
    /**
     * Retornar corretamente o user_id, que pode variar conforme o contexto
     * 
     */
    protected function get_edit_user_id() {
        return isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : get_current_user_id();
    }

    /**
     * Recuperar dados salvos do usuário, conforme o tipo
     * 
     */
    protected function get_userdata( $user_id, $key ) {
        if ( !$this->is_userdata( $key ) ) {
            return get_user_meta( $user_id, $key, true );
        }
    
        $userdata = get_userdata( $user_id );
    
        if ( ! $userdata || ! isset( $userdata->{$key} ) ) {
            return '';
        }
    
        return $userdata->{$key};
    }

    /**
     * Verificar a informação pedida é usermeta ou userdata
     * 
     */
    protected function is_userdata( $key ) {
        $userdata = array(
            'user_pass',
            'user_login',
            'user_nicename',
            'user_url',
            'user_email',
            'display_name',
            'nickname',
            'first_name',
            'last_name',
            'description',
            'rich_editing',
            'user_registered',
            'role',
            'jabber',
            'aim',
            'yim',
            'show_admin_bar_front',
        );

        return in_array( $key, $userdata );
    }
    
    /**
     * Verificar a visibilidade do campo conforme o contexto
     * 
     */
    protected function is_field_visible( $field_args ) {
        $visible = true;
        $action = filter_input( INPUT_POST, 'action' );

        if( is_admin() && !empty( $field_args['hide_in_admin'] ) ){
            $visible = false;
        }
        elseif( ( is_account_page() || $action === 'save_account_details' ) && is_user_logged_in() && !empty( $field_args['hide_in_account'] ) ){
            $visible = false;
        }
        elseif( ( is_account_page() || $action === 'save_account_details' ) && !is_user_logged_in() && !empty( $field_args['hide_in_registration'] ) ){
            $visible = false;
        }
        elseif( is_checkout() && !empty( $field_args['hide_in_checkout'] ) ){
            $visible = false;
        }

        return $visible;
    }

    /**
     * Recarregar o valor do campo em caso de POST
     * 
     */
    public function reload_post_data( $fields ) {
        
        if ( empty( $_POST ) ) {
            return $fields;
        }
    
        foreach ( $fields as $key => $field_args ) {
            if ( empty( $_POST[ $key ] ) ) {
                $fields[ $key ]['value'] = '';
                continue;
            }

            $fields[ $key ]['value'] = $_POST[ $key ];
        }
    
        return $fields;
    }

    /**
     * Exibir os campos de registro
     * 
     */
    public function output_registration_fields(){

        foreach( $this->fields as $key => $field_args ){
            $value = null;
    
            if( !$this->is_field_visible( $field_args ) ){
                continue;
            }
    
            if( is_user_logged_in() ){
                $user_id = $this->get_edit_user_id();
                $value   = $this->get_userdata( $user_id, $key );
            }
    
            $value = isset( $field_args['value'] ) ? $field_args['value'] : $value;
            woocommerce_form_field( $key, $field_args, $value );
        }
    }

    /**
     * Exibir os campos no checkout
     * 
     */
    public function output_checkout_fields( $checkout_fields ){

        foreach ( $this->fields as $key => $field_args ) {
            if ( !$this->is_field_visible( $field_args ) ) {
                continue;
            }
    
            $checkout_fields['account'][ $key ] = $field_args;
        }
    
        return $checkout_fields;
    }

    /**
     * Exibir campos no admin
     * 
     */
    public function output_admin_fields(){
        ?>
        <h2><?php _e( 'Additional Information', 'boros' ); ?></h2>
        <table class="form-table" id="iconic-additional-information">
            <tbody>
            <?php
            foreach( $this->fields as $key => $field_args ){
                if ( !$this->is_field_visible( $field_args ) ) {
                    continue;
                }

                $user_id = $this->get_edit_user_id();
                $value   = $this->get_userdata( $user_id, $key );
            ?>
                <tr>
                    <th>
                        <label for="<?php echo $key; ?>"><?php echo $field_args['label']; ?></label>
                    </th>
                    <td>
                        <?php $field_args['label'] = false; ?>
                        <?php woocommerce_form_field( $key, $field_args, $value ); ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Salvar dados
     * 
     */
    public function save_fields( $customer_id ) {
        $sanitized_data = array();
    
        foreach( $this->fields as $key => $field_args ) {
            $sanitize = isset( $field_args['sanitize'] ) ? $field_args['sanitize'] : 'wc_clean';
            $value    = isset( $_POST[ $key ] ) ? call_user_func( $sanitize, $_POST[ $key ] ) : '';
            
            if ( $this->is_userdata( $key ) ) {
                $sanitized_data[ $key ] = $value;
                continue;
            }
    
            update_user_meta( $customer_id, $key, $value );
        }
    
        if ( !empty( $sanitized_data ) ) {
            $sanitized_data['ID'] = $customer_id;
            wp_update_user( $sanitized_data );
        }
    }

    /**
     * Validar dados
     * 
     */
    public function validate_fields( $errors, $username ) {

        foreach( $this->fields as $key => $field_args ){
            if( empty( $field_args['required'] ) ) {
                continue;
            }

            if( !isset( $_POST['register'] ) && !empty( $field_args['hide_in_account'] ) ){
                continue;
            }

            if( isset( $_POST['register'] ) && !empty( $field_args['hide_in_registration'] ) ){
                continue;
            }

            if( empty( $_POST[ $key ] ) ){
                if( isset($field_args['errors']['required']) ){
                    $message = $field_args['errors']['required'];
                }
                else{
                    $message = sprintf( __('%s is a required field.', 'boros'), '<strong>' . $field_args['label'] . '</strong>' );
                }
                $errors->add( $key, $message );
            }
        }
        //pre($errors);

        return $errors;
    }

    /**
     * Trocar o formato de mensagem padrão('%s is a required field.') pela mensagem personalizada do campo
     * 
     */
    public function error_alerts( $message, $field_label ){
        
        foreach( $this->fields as $key => $field_args ){
            if( ($field_args['label'] == $field_label) && isset($field_args['errors']['required']) ){
                $message = $field_args['errors']['required'];
            }
        }

        return $message;
    }

}

