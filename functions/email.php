<?php
/**
 * ==================================================
 * CLASSE DE EMAIL ==================================
 * ==================================================
 * Criação e manipulação dos emails enviados pelo site.
 * Controla todos os aspectos relacionados aos emails do site, porém, as configurações deverão ser ativadas pelos plugins de cada trabalho. Utiliza de 'options' com nomes pré-definidos, assim
 * é necessário adicionar esses controles ao admin.
 * 
 * 
 * 
 * @link https://accounts.google.com/DisplayUnlockCaptcha - link de desbloqueio de SMTP
 * @link https://www.google.com/settings/security/lesssecureapps - novo link de desbloqueio
 */


/**
 * INIT =============================================
 * Sempre inicia o mais cedo possível, para que não falhe nenhuma execução de email. Executado um singleton que estará disponível do início ao final do request
 * 
 */
add_action( 'init', 'init_boros_email', 1 );
function init_boros_email(){
	$boros_email = BorosEmail::init();
}



/**
 * ==================================================
 * NEW USER NOTIFICATION ============================
 * ==================================================
 * Sobrescrever a function padrão
 * 
 */
if( !function_exists('wp_new_user_notification') ){
	function wp_new_user_notification( $user_id, $plaintext_pass = '' ){
		$boros_email = BorosEmail::init();
		$boros_email->wp_new_user_notification( $user_id, $plaintext_pass );
	}
}



/**
 * ==================================================
 * PASSWORD CHANGE NOTIFICATION =====================
 * ==================================================
 * Sobrescrever a function padrão
 * 
 */
if ( !function_exists('wp_password_change_notification') ) :
	function wp_password_change_notification(&$user) {
		if ( $user->user_email != get_option('admin_email') ) {
			$name = "{$user->display_name} (username: {$user->user_login})";
			$message = sprintf(__('Password Lost and Changed for user: %s'), $name) . "\r\n";
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			wp_mail(get_option('admin_email'), sprintf(__('[%s] Password Lost/Changed'), $blogname), $message);
		}
	}
endif;



class BorosEmail {
	
	/**
	 * Informações do usuário corrente
	 * 
	 */
	var $user_data;
	
	/**
	 * Singleton
	 * 
	 */
	private static $instance;
	
	public static function init(){
		if( empty( self::$instance ) ){
			self::$instance = new BorosEmail();
		}
		return self::$instance;
	}
	
	/**
	 * CONSTRUCT
	 * Adiciona todos os filtros e hook necessários aos emails
	 * 
	 */
	private function __construct(){
		// configuração geral: content-type, sender e name
		add_filter( 'wp_mail_content_type', array($this, 'content_type') );
		add_filter( 'wp_mail_from', 		array($this, 'wp_mail_from') );
		add_filter( 'wp_mail_from_name', 	array($this, 'wp_mail_from_name') );
		
		// recuperação de senha
		add_filter( 'retrieve_password_title', 		array($this, 'retrieve_password_title') );
		add_filter( 'retrieve_password_message', 	array($this, 'retrieve_password_message'), 10, 4 );
		add_action( 'retrieve_password', 			array($this, 'register_user_data_for_retrieve') );
		
		// novo usuário >>> ATENÇÃO: filtros para o email enviado pela class 'BorosFrontendForm'
		add_filter( 'BFF_new_user_notification_text', array($this, 'new_user_notification_text'), 10, 4 );
		add_filter( 'BFF_new_user_notification_title', array($this, 'new_user_notification_title') );
		
		// filtros nos input_helpers dos campos >>> avisar sobre tgas faltantes
		add_filter( 'BFE_new_user_notification_text_input_helper', array($this, 'new_user_notification_text_warning'), 10, 3 );
		add_filter( 'BFE_new_user_admin_notification_text_input_helper', array($this, 'new_user_admin_notification_text_warning'), 10, 3 );
	}
	
	// mudar o content type
	function content_type( $content_type ){
		if( get_option('email_content_type') == true )
			$content_type = 'text/html';
		return $content_type;
	}
	
	//mudar email sender
	function wp_mail_from( $from_email = false ){
		$email_from = get_option('email_from');
		if( !empty($email_from) ){
			$from_email = $email_from;
		}
		else{
			$from_email = get_bloginfo('admin_email');
		}
		
		//pre($from_email, 'from_email');
		return $from_email;
	}
	
	// mudar sender name
	function wp_mail_from_name( $from_name ){
		$email_from_name = get_option('email_from_name');
		if( !empty($email_from_name) )
			$from_name = $email_from_name;
		else
			$from_name = get_bloginfo('name');
			
		//pre($from_name, 'from_name');
		return $from_name;
	}
	
	/**
	 * Este método é quase um clone do function padrão presente em plugglabes
	 * 
	 */
	function wp_new_user_notification( $user_id, $plaintext_pass ){
		do_action( 'new_user_notification_pre', $user_id );
		
		$user = new WP_User($user_id);
		$user_login = stripslashes($user->user_login);
		$user_email = stripslashes($user->user_email);
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		
		/**
		 * Montar os $headers conforme os destinatários adicionais
		 * O remetente dos emails é definido nos hooks 'wp_mail_from' e 'wp_mail_from_name' em boros/functions/email.php
		 * 
		 */
		$headers = array();
		$to  = get_option( 'email_from' );
		if( !empty($to) )
			$to = get_option('admin_email'); // fallback caso o 'email_from" não esteja configurado.
		$cc  = get_option( 'email_from_cc' );
		$bcc = get_option( 'email_from_bcc' );
		// Adicionar CC
		if( !empty($cc) ){
			$emails = explode(',', $cc);
			foreach( $emails as $email ){
				if( !empty($email) )
					$headers[] = "Cc: {$email}";
			}
		}
		// Adicionar BCC
		if( !empty($bcc) ){
			$emails = explode(',', $bcc);
			foreach( $emails as $email ){
				if( !empty($email) )
					$headers[] = "Bcc: {$email}";
			}
		}
		
		// Avisar ADMIN - texto padrão
		$admin_title = $this->new_user_admin_notification_title( 'Registro de novo usuário' );
		$admin_message = "	<p>Novo usuário registrado no site {$blogname}:</p>
							<p>Nome de usuário: {$user_login}</p>
							<p>Email: {$user_email}</p>";
		$admin_message = $this->new_user_admin_notification_text( $admin_message, $user_login, $user_email );
		@wp_mail( $to, $admin_title, $admin_message, $headers );
		
		if ( empty($plaintext_pass) )
			return;
		
		// Avisar USER - texto padrão
		$login_url = apply_filters( 'new_user_notification_login_url', wp_login_url() );
		$user_title = $this->new_user_notification_title( 'Seu nome de usuário e senha' );
		$user_message = "	<p>Nome de usuário: <code>{$user_login}</code></p>
							<p>Senha: <code>{$plaintext_pass}</code></p>
							<p>Endereço para login: <code>{$login_url}</code></p>";
		$user_message = $this->new_user_notification_text( $user_message, $user_login, $plaintext_pass, $login_url );
		
		wp_mail($user_email, $user_title, $user_message);
		do_action( 'new_user_notification_pos', $user );
	}
	
	function new_user_admin_notification_title( $title ){
		$custom = get_option('new_user_admin_notification_title');
		if( !empty($custom) )
			$title = $custom;
		return $title;
	}
	
	function new_user_admin_notification_text( $message, $user_login, $user_email ){
		$custom_message = get_option('new_user_admin_notification_text');
		if( !empty($custom_message) ){
			$message = $custom_message;
			$message = str_replace( '[USERNAME]', $user_login, $message );
			$message = str_replace( '[EMAIL]', $user_email, $message );
			$message = apply_filters('the_content', $message);
			$message = apply_filters( 'new_user_admin_notification_text', $message );
		}
		return $message;
	}
	
	function new_user_admin_notification_text_warning( $input_helper, $value, $context ){
		$username = strpos( $value, '[USERNAME]' );
		if( $username === false )
			$input_helper .= '<br /><strong style="color:red;">ATENÇÃO: não foi encontrado o código <code>[USERNAME]</code> no texto do email</strong>';
			
		$email = strpos( $value, '[EMAIL]' );
		if( $email === false )
			$input_helper .= '<br /><strong style="color:red;">ATENÇÃO: não foi encontrado o código <code>[EMAIL]</code> no texto do email</strong>';
		
		return $input_helper;
	}
	
	// mudar o título do email
	function new_user_notification_title( $title ){
		$custom = get_option('new_user_notification_title');
		if( !empty($custom) )
			$title = $custom;
		return $title;
	}
	
	function new_user_notification_text( $message, $user_login, $user_pass, $login_url ){
		$custom_message = get_option('new_user_notification_text');
		if( !empty($custom_message) ){
			$message = $custom_message;
			$message = str_replace( '[USERNAME]', $user_login, $message );
			$message = str_replace( '[SENHA]', $user_pass, $message );
			$message = str_replace( '[LINK]', $login_url, $message );
			$message = str_replace( '[CONTATO]', page_permalink_by_name('contato', false), $message );
			$message = apply_filters('the_content', $message);
			$message = apply_filters( 'new_user_notification_text', $message );
		}
		return $message;
	}
	
	function new_user_notification_text_warning( $input_helper, $value, $context ){
		$username = strpos( $value, '[USERNAME]' );
		if( $username === false )
			$input_helper .= '<br /><strong style="color:red;">ATENÇÃO: não foi encontrado o código <code>[USERNAME]</code> no texto do email</strong>';
			
		$senha = strpos( $value, '[SENHA]' );
		if( $senha === false )
			$input_helper .= '<br /><strong style="color:red;">ATENÇÃO: não foi encontrado o código <code>[SENHA]</code> no texto do email</strong>';
			
		$link = strpos( $value, '[LINK]' );
		if( $link === false )
			$input_helper .= '<br /><strong style="color:red;">ATENÇÃO: não foi encontrado o código <code>[LINK]</code> no texto do email</strong>';
		return $input_helper;
	}
	
	
	//mudar titulo da recuperação de senha
	function retrieve_password_title( $title ){
		$custom_title = get_option('retrieve_password_title');
		if( empty($custom_title) ){
			$title .= get_bloginfo('name');
			return $title;
		}
		else{
			return $custom_title;
		}
	}
	
	/**
	 * Modificar mensagem da recuperação de senha
	 * 
	 * @link http://wp.smashingmagazine.com/2011/10/25/create-perfect-emails-wordpress-website/
	 */
	function retrieve_password_message( $message, $key, $user_login, $user ){
		$custom_message = get_option('retrieve_password_message');
		
		// usar mensagem padrão
		if( empty($custom_message) ){
			// @link http://daringfireball.net/2009/11/liberal_regex_for_matching_urls
			preg_match_all( '@\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))@', $message, $links );
			$retrieve_link = '';
			foreach( $links[0] as $link ){
				$pos = strpos( $link, 'wp-login.php' );
				if( $pos !== false ){
					$retrieve_link = $link;
					continue;
				}
			}
			
			// filtrar link de recuperação de senha
			// @todo: talvez filtrar via regex para pegar apenas a URL
			$message = wpautop( $message );
			$message = str_replace( '<http', '<a href="http', $message );
			$message = str_replace( '></p>', '">Redefinir senha</a></p>', $message );
			$message .= "\n\n\n Caso o link não funcione, copie o seguinte endereço e cole na barra de endereços do seu navegador: \n\n {$retrieve_link}";
		}
		// usar mensagem personalizada
		else{
			// montar a url de recuperação de senha
			$login_args = array(
				'action' => 'rp',
				'key' => $key,
				'login' => $user->user_login,
			);
			$login_url = add_query_arg( $login_args, wp_login_url(home_url()) );
			
			$message = apply_filters('the_content', $custom_message);
			$message = str_replace( '[LINK]', $login_url, $message );
			$message = str_replace( '[NAME]', $user->display_name, $message );
			$message = str_replace( '[CONTATO]', page_permalink_by_name('contato', false), $message );
		}
		
		//die('INTERROMPIDO PARA TESTES');
		
		// aplicar o holder definido em boros_email_base
		$message = apply_filters( 'retrieve_password_message_text', $message );
		return $message;
	}
	
	/**
	 * Guardar informação do usuário quando é feita a requisição de senha perdida
	 * A action 'retrieve_password', é executada
	 * 
	 */
	function register_user_data_for_retrieve( $user_login ){
		$this->user_data = get_user_by( 'login', $user_login );
	}
}



