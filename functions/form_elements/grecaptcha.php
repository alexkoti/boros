<?php
/**
 * GREACAPTCHA
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
			array('google_recaptcha', 'https://www.google.com/recaptcha/api/js/recaptcha_ajax.js'),
			'recaptcha',
		)
	);
	
	function includes(){
		require_once( BOROS_LIBS . 'recaptcha/recaptchalib.php' );
	}
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
		ob_start();
		$publickey = get_option('recaptcha_publickey');
		$privatekey = get_option('recaptcha_privatekey');
		$resp = null;
		$error = null;
		
		if( isset($_POST["recaptcha_response_field"]) and ($this->context['form_name'] == $_POST['form_name']) ){
			$resp = recaptcha_check_answer ($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
			if( $resp->is_valid ){
				
			}
			else{
				$error = $resp->error; // set the error code so that we can display it
			}
		}
		
		/**
		 * Múltiplos recaptchas em uma página.
		 * Normalmente não é possível mostrar dois recaptchas em uma página, portanto é necessário clonar via javascript os recaptchas seguintes
		 * 
		 */
		if( isset($this->data['options']['ajax_recaptcha']) and $this->data['options']['ajax_recaptcha'] == true ){
			echo "<div id='{$this->context['form_name']}_recaptcha' class='ajax_recaptcha_div' data-publickey='{$publickey}' data-theme='{$this->data['options']['theme']['theme']}'></div>";
		}
		else{
			// criar tema, caso declarado
			if( isset($this->data['options']['theme']) ){
				echo '<script type="text/javascript">';
				echo 'var RecaptchaOptions = {';
				/**
				if( isset( $this->data['options']['theme']['custom_translations'] ) ){
					echo 'custom_translations : {';
					foreach( $this->data['options']['theme']['custom_translations'] as $o => $t ){
						echo "{$o} : '{$t}',";
					}
					echo '}';
				}
				/**/
				if( isset($this->data['options']['theme']['lang']) ) echo "lang : '{$this->data['options']['theme']['lang']}'";
				if( isset($this->data['options']['theme']['theme']) ) echo ",theme : '{$this->data['options']['theme']['theme']}'";
				echo '}';
				echo '</script>';
			}
			echo recaptcha_get_html($publickey, $error);
		}
		
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}
