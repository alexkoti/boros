<?php
/**
 * GREACAPTCHA
 * Novo captcha do google, que utiliza apenas um checkbox de confirmação.
 * 
 * 
 */

class BFE_grecaptcha extends BorosFormElement {
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
	
	var $enqueues = array(
		'js' => array(
			array('google_grecaptcha', 'https://www.google.com/recaptcha/api.js?onload=boros_grecaptcha_onload&render=explicit'),
			'grecaptcha',
		),
    );
    
	function add_defaults(){
		$this->defaults['options']['defer'] = false; # iniciar o recaptcha apenas ao focar em algum elemento do form parent
	}
	
	/**
	 * Contador de instâncias, será utilizado para inserir as variáveis de footer e id dos recaptchas
	 * 
	 */
	private static $counter = 1;
	
	function init(){
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
			'sitekey' => get_option('recaptcha_publickey'),
		);
		$json = json_encode($vars);
		echo "<script type='text/javascript'>var grecaptcha_keys = {$json};</script>" . PHP_EOL;
	}
	
	function includes(){
		require_once( BOROS_LIBS . DIRECTORY_SEPARATOR . 'grecaptcha/recaptchalib.php' );
	}
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
        $class = '';
        $publickey = get_option('recaptcha_publickey');
        if( $this->data['options']['defer'] == true ){
            $class = 'defer-render';
        }
		
		$id = self::$counter;
		$input = "<div class='grecaptcha_render {$class}' id='grecaptcha-{$id}'></div>";
		return $input;
	}
}

