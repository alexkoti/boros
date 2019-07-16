<?php
/**
 * GREACAPTCHA
 * Novo captcha do google, que utiliza apenas um checkbox de confirmação.
 * 
 * 
 */

class BFE_grecaptcha_invisible extends BorosFormElement {
    var $valid_attrs = array(
        'name' => '',
        'value' => '',
        'id' => '',
        'class' => '',
        'rel' => '',
        'size' => false,
        'disabled' => false,
        'readonly' => false,
    );

    var $publickey = '';
    
    var $enqueues = array(
        'js' => array(
            array('google_grecaptcha', 'https://www.google.com/recaptcha/api.js?hl=pt&onload=boros_grecaptcha_invisible_init&render=explicit'),
            'grecaptcha-invisible',
        ),
    );
    
    /**
     * Contador de instâncias, será utilizado para inserir as variáveis de footer e id dos recaptchas
     * 
     */
    private static $counter = 1;
    
    function init(){
        $this->publickey = apply_filters( 'boros_recaptcha_publickey', get_option('recaptcha_publickey') );

        // adicionar as variáveis de footer apenas uma vez
        if( self::$counter == 1 ){
            add_action( 'wp_footer', array($this, 'footer') );
        }
        self::$counter++;
    }
    
    /**
     * Adicionar variáveis dinânicas de javascript
     * 
     */
    function footer(){
        $vars = array(
            'sitekey' => $this->publickey,
        );
        $json = json_encode($vars);
        echo "<script type='text/javascript'>var grecaptcha_keys = {$json};</script>" . PHP_EOL;
    }
    
    /**
     * Saída final do input
     * 
     */
    function set_input( $value = null ){
        
        $id = self::$counter;
        
        $input = "
        <div 
            id='grecaptcha-{$id}'
            class='g-recaptcha'
            data-sitekey='{$this->publickey}'
            data-callback='boros_invisible_recaptcha'
            data-size='invisible'>
        </div>
        ";
        return $input;
    }
}

