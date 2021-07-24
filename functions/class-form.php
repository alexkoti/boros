<?php

/**
 * Classes que deverão extender BOros_Form
 * 
 * Boros_Form_Post
 * Boros_Form_User
 * Boros_Form_Term
 * Boros_Form_Option
 * Boros_Form_Login
 * Boros_Form_Register
 * Boros_Form_Generic
 * 
 */

abstract class Boros_Form {

    /**
     * Índice que será usado para interceptar o $_POST
     * Apenas quando for identificada presença de $_POST[{$post_identifier}], é que será iniciado o processamento 
     * para salvar o formulário.
     * O padrão é 'form_name', mas poderá ser necessário utilizar um name diferente para evitar conflito com outros
     * formulários
     * 
     * 
     */
    private $post_identifier = 'form_name';

    /**
     * Dados de $_POST
     * Irá ser normalizado, sanitizado e validado
     * 
     */
    private $post_data = [];

    final function __construct(){
        pal(__METHOD__, get_class($this), 30);
    }

    function init(){
        pal(__METHOD__, get_class($this), 30);
        $this->setup_form();
        $this->set_form_data();
    }

    /**
     * Configurações do formulário
     * 
     */
    final private function setup_form(){
        $this->form_config();
        $this->form_fields();
    }

    /**
     * Interceptar $_POST e fazer tratamento dos dados
     * sanitize
     * validation
     * 
     */
    final private function set_form_data(){

        $this->form_data = [
            'name' => 'Jose',
            'email' => 'jose@joao.com',
        ];
        
        // permitir filtrar os dados
        if( method_exists( $this, 'filter_post_data' ) ){
            pal('filter_post_data EXISTS', get_class($this));
            $this->form_data = call_user_func( array($this, 'filter_post_data'), $this->form_data );
        }
    }

    /**
     * Recuperar campos da configuração e normalizar atributos
     * 
     */
    final private function set_form_fields(){
        
    }

    /**
     * Definir o output do formulário, prevendo os vários tipos disponíveis:
     * - simple: div com o input dentro
     * - bootstrap-{n}: output conforme padrões do bootstrap
     * 
     */
    final private function set_form_output(){
        
    }

    public function debug(){
        pre( $this, 'debug:' . get_class($this) );
    }

    final private function enqueue_css(){
        
    }

    final private function enqueue_js(){
        
    }

    /**
     * Cada class extendida deverá definir aqui a configuração do formulário
     * 
     */
    abstract protected function form_config();

    /**
     * Cada class extendida deverá definir aqui os campos do formulário
     * 
     */
    abstract protected function form_fields();

}

