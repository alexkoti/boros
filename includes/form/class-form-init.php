<?php

namespace Boros\Form;

/**
 * Timeline de forms
 * 
 * - custom plugin inicia Boros_Form_Init->register_form() no hook wp(10) - neste momento é feito o autoload de Boros_Form_Init
 * - config simples de novos forms são armazenados em Boros_Form_Init->forms - nenhuma class adicional é iniciada
 * - ainda no hook wp com prioridade 100, momento em que todos os forms já foram regitrados, é acionado o Boros_Form_Init->init_forms(),
 * que irá acionar novas instâncias dos forms que passem pelo conditional. Caso não tenha condicional, o form será sempre iniciado.
 * - a nova instância de form irá extender Boros_Form(), que já possui todos os métodos para montra o form e tratamento dos dados:
 *   - interceptar $_POST
 *   - normalizar, sanitizar e validar dados
 *   - tratar uploads
 *   - salvar dados recebidos conforme o tipo(post, term, user, options, generic)
 *   - montar estrutura de elementos de formulário baseado na configuração
 *   - carregar e tratar dados prá-existentes nos casos de edição
 *   - montar o output do formulário(contexto de form vazio ou retorn de erros), incluindo js/css dependentes e mensagens de erro
 * - na classe child estará os métodos para:
 *   - filter/action pré-validação: modificar $_POST antes de validar
 *   - filter/action pós-validação: modificar dados já validados
 *   - filter/action pré-salvamento: modificar ou acionar functions antes de salvar os dados
 *   - filter/action pós-salvamento: redirects, envio de email e/ou outros callbacks
 * 
 * 
 * @todo - permitir receber $_POST em ajax
 * 
 * 
 */

class Boros_Form_Init {

    /**
     * Lista de formulários registrados
     * 
     */
    private $registered_forms = [];

    /**
     * Todas as instâncias de form iniciadas 
     * 
     */
    var $forms = [];

    private function __construct(){}
    
    /**
     * Singleton
     * 
     */
    public static function instance(){
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
            $instance->init();
        }

        return $instance;
    }

    /**
     * Enfileirar ação para inicializar os forms apenas todos terem sido registrados
     * Nas requisições ajax não é executado o hook 'wp', pois não é iniciadoo $wp_query, sendo necessário 
     * utilizar o hook wp_loaded
     * 
     */
    function init(){
        if( wp_doing_ajax() ){
            add_action( 'wp_loaded', [$this, 'init_forms'], 100 );
        }
        else{
            add_action( 'wp', [$this, 'init_forms'], 100 );
        }
    }

    /**
     * Inicializar forms que passarem pelas condicionais
     * 
     */
    function init_forms(){
        pal(__METHOD__, get_class($this));
        foreach( $this->registered_forms as $form_name => $args ){
            $init_form = true;

            // ao executar requisições ajax, não é possível utilizar funções condicionais
            if( wp_doing_ajax() ){
                $init_form = $args['allow_ajax'];
            }
            // caso definido, verificar condicional
            else if( isset($args['conditional']) ){
                $init_form = call_user_func($args['conditional']);
            }

            if( $init_form == true ){
                pal('included and instantiated: '.$args['class_name'], '>>', 30);
                include_once($args['class_file']);
                $class_name = $args['class_name'];
                $form_name  = $args['form_name'];
                if( !isset($this->forms[ $form_name ]) ){
                    $this->forms[ $form_name ] = new $class_name();
                    $this->forms[ $form_name ]->init();
                }
            }
        }

        pre($this, 'wp: Boros_Form_Init');
    }

    /**
     * Registrar novo form no array de controle
     * 
     * @param array $args Array com a configuração mínima para registrar o formulário
     *  $args = [
     *      'form_name'   => (string) Required. Identificador do formulário, usado como índice e para interceptar o $_POST.
     *      'class_name'  => (string) Required. Nome da class que será instanciada para este form.
     *      'class_file'  => (string) Required. Arquivo da classe para ser incluída
     *      'conditional' => (string, closure) Callback que irá determinar se o form deverá ser instanciado nas condições atuais
     *                       Por padrão todos os forms são instanciados.
     *      'allow_ajax'  => (bool) Determinar se deve permitir instanciar a formulário em requisições ajax.
     *  ]
     * 
     */
    public function register_form( array $args ){
        pal( $args['form_name'], 'register form' );
        
        // @todo validar class_name

        // @todo validar class_file

        // @validar conditional
        if( isset($args['conditional']) && !is_callable($args['conditional']) ){
            unset($args['conditional']);
        }
        if( !isset($args['allow_ajax']) ){
            $args['allow_ajax'] = false;
        }

        if( !isset($this->forms[ $args['form_name'] ]) ){
            $this->registered_forms[ $args['form_name'] ] = $args;
        }
    }

}


