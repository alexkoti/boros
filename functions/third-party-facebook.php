<?php
/**
 * ==================================================
 * FABOOK API =======================================
 * ==================================================
 * 
 * @TODO: REVISAR TUDO!!!
 */



/**
 * INICIAR BOROS_FB
 * 
 */
//require 'facebook/facebook.php';
		// este add_action deverá ser declarado no plugin do job, assim os sites que não precisam da integração com o facebook não precisar iniciar este script
//add_action( 'init', 'boros_fb', 1 );
function boros_fb(){
	if( get_option('boros_fb_active') == true ){
		add_action( 'init', 'boros_fb_init', 2 );
		add_action( 'template_redirect', 'boros_fb_login_check', 2 );
		add_action( 'template_redirect', 'boros_wp_login_check', 2 );
		add_action( 'init', 'boros_fb_logout', 2 );
		add_action( 'init', 'boros_fb_app_logout', 2 );
		
		//add_action( 'template_redirect', 'boros_fb_check_logged_user', 10 );
	}
}

function boros_fb_init(){
	$boros_fb = BorosFb::init();
}

function boros_wp_login_check(){
	if( isset($_GET['wp_login']) and $_GET['wp_login'] == 1 ){
		// está logado no wp, verificar se precisa logar no fb
		if( is_user_logged_in() ){
			if( current_user_can('administrator') ){
				wp_redirect( admin_url() );
			}
			else{
				global $current_user;
				get_currentuserinfo();
				
				$boros_fb = BorosFb::init();
				if( $boros_fb->facebook != false ){
					$user = $boros_fb->check_fb_logged_user();
					if( $user == false ){
						// verificar se é um user também do fb, então mandar para o login do fb
						$user_fb_uid = get_user_meta( $current_user->ID, 'fb_uid', true );
						if( !empty($user_fb_uid) ){
							$params = array(
								'next' => home_url('/?login-ok=1'),
								'scope' => 'email, publish_stream',
							);
							$login_url = $boros_fb->facebook->getLoginUrl($params);
							wp_redirect( $login_url );
							exit();
						}
					}
				}
			}
		}
	}
}

function boros_fb_login_check(){
	if( isset($_GET['fb_login']) and $_GET['fb_login'] == 1 ){
			//pal(0);
		$boros_fb = BorosFb::init();
		if( $boros_fb->facebook != false ){
			//pal(1);
			$user = $boros_fb->check_fb_logged_user();
			//pre($user);
			if( $boros_fb->is_user_logged_in == true ){
				//pal(2);
				// usuário já está logado no fb, verificar se precisa logar no wp
				// @link http://kuttler.eu/code/log-in-a-wordpress-user-programmatically/
				if( !is_user_logged_in() ){
					$user = get_userdatabylogin( $boros_fb->user['username'] );
					$boros_fb->wp_user_login( $user->ID );
				}
				
				// redirecionar
				if( isset($_GET['site_redirect']) ){
					//pal(3);
					/**
					 * Este trecho visa manter as variáveis entre os redirects. 'fb_login' é removido para impedir o loop infinito e 'site_redirect' já será usado como url.
					 * As outras variáveis podem ser chamadas para adicionar aos favoritos ou alguma outra action.
					 */
					$args = array();
					$args['login-ok'] = 1;
					foreach( $_GET as $arg => $val ){
						$args[$arg] = $val;
					}
					unset( $args['fb_login'] );
					unset( $args['site_redirect'] );
					unset( $args['state'] );
					
					/**
					$args = array(
						'login-ok' => 1,
						'foo' => 'bar',
					);
					//pre($args);
					/**/
					wp_redirect( add_query_arg( $args, urldecode($_GET['site_redirect']) ) );
				}
				else{
					wp_redirect( home_url('/?login-ok=1') );
				}
				exit();
			}
		}
	}
}



/**
 * FAZER LOGOUT DO FACEBOOK >>> NÃO UTILIZADO
 * 
 */
function boros_fb_logout(){
	if( isset($_GET['fb_logout']) and $_GET['fb_logout'] == 1 ){
		$boros_fb = BorosFb::init();
		if( $boros_fb->facebook != false ){
			$boros_fb->fb_logout();
		}
		wp_redirect( home_url() );
		exit();
	}
}

/**
 * FAZER LOGOUT DO APP
 * Redireciona para a home
 * 
 */
function boros_fb_app_logout(){
	if( isset($_GET['app_logout']) and $_GET['app_logout'] == 1 ){
		$boros_fb = BorosFb::init();
		if( $boros_fb->facebook != false ){
			//$boros_fb->app_logout();
			$boros_fb->fb_logout();
			$boros_fb->wp_logout();
			
			//add_action( 'template_redirect', array($boros_fb, 'redirect_home') );
			
			$args = array();
			foreach( $_GET as $arg => $val ){
				$args[$arg] = false;
			}
			wp_redirect( add_query_arg( $args, self_url() ) );
			exit();
		}
	}
}


/**
 * WRAPPER PARA PEGAR O OBJETO $facebook
 * 
 */
function get_facebook(){
	$boros_fb = BorosFb::init();
	if( $boros_fb->facebook != false )
		return $boros_fb->facebook;
	else
		return false;
}

/**
 * CLASS BOROS_FB
 * 
 */
class BorosFb {
	var $facebook = false;
	var $boros_fb_appid = false;
	var $boros_fb_secret = false;
	var $errors = array();
	var $fb_uid = false;
	var $user = false;
	var $is_user_logged_in = false;
	
	private static $instance;
	
	public static function init(){
		if( empty( self::$instance ) ){
			self::$instance = new BorosFb();
		}
		return self::$instance;
	}
	private function __construct(){
		// primeiro verifica se já foram enviados headers para a página, o que indica que não foi executado em init, e portanto está desativado via admin
		if( headers_sent() ){
			echo 'Plugin Facebook desativado';
			return;
		}
		
		if( !class_exists('Facebook') ){
			require 'facebook/facebook.php';
		}
		
		$this->boros_fb_appid = get_option('boros_fb_appid');
		$this->boros_fb_secret = get_option('boros_fb_secret');
		
		if( $this->boros_fb_appid == false or $this->boros_fb_secret == false ){
			$this->errors['keys_not_found'] = 'Chaves da aplicação não definidos';
			$this->facebook = false;
		}
		else{
			$this->facebook = new Facebook(array(
				'appId'  => get_option('boros_fb_appid'),
				'secret' => get_option('boros_fb_secret'),
			));
		}
		
		$user = $this->check_fb_logged_user();
		//pre($user, 'init');
		//return true;
		
		$this->login_or_create_user();
		//$this->check_fb_logged_user();
		
		//adicionar hook para logout do wordpress
		add_action( 'wp_logout', array($this, 'app_logout') );
		add_action( 'logout_url', array($this, 'logout_url') );
		add_action( 'login_redirect', array($this, 'login_redirect'), 10, 3 );
		add_filter( 'get_avatar', array($this, 'avatar'), 10, 5 );
	}
	
	function check_fb_logged_user(){
		$facebook = $this->facebook;
		//pre($facebook);
		$fb_uid = $facebook->getUser();
		$user = false;
		//pre($fb_uid, '$fb_uid');
		
		if( $fb_uid ){
			try {
				// Proceed knowing you have a logged in user who's authenticated.
				$user = $facebook->api('/me');
				//pre($user, '$user');
				$this->fb_uid = $fb_uid;
				$this->user = $user;
				$this->is_user_logged_in = true;
			}
			catch (FacebookApiException $e){
				error_log($e);
				//pre($e, '$e');
				$fb_uid = null;
			}
		}
		return $user;
	}
	
	function login_or_create_user(){
		// verificar se está logado no fb
		$user = $this->check_fb_logged_user();
		
		// verificar se está logado no wp
		if( is_user_logged_in() ){
			// verificar se é um user também do fb, então mandar para o login do fb
			global $current_user;
			get_currentuserinfo();
			$user_fb_uid = get_post_meta( $current_user->ID, 'fb_uid', true );
			if( $this->user == false and !empty($user_fb_uid) ){
				$params = array(
					'next' => home_url('/?fb_login=1'),
					'scope' => 'email, publish_stream',
				);
				$login_url = $this->facebook->getLoginUrl($params);
				wp_redirect( $login_url );
				exit();
			}
		}
		// não está logado no wp, verificar se está no fb, e logar no wp em caso positivo
		else{
			if( $user != false ){
				// pegar username ou id
				$username = isset($this->user['username']) ? $this->user['username'] : $this->user['id'];
				$wp_user = get_user_by( 'login', $username );
				
				// usuário já existe! logar
				if( $wp_user ){
					$this->wp_user_login( $wp_user->ID );
				}
				// não existe, registrar usuário
				else{
					
					// verificar se autorizou o email
					$user_data = array(
						'user_login' => $username,
						'user_pass' => "{$username}UTIpFnLGsk8G7MGNuCBGKgL",
						'user_email' => $this->user['email'],
					);
					
					// tentar registrar
					$wp_user_id = wp_create_user( $user_data['user_login'], $user_data['user_pass'], $user_data['user_email'] );
					
					// em caso de erro, adicionar log de erros
					if( is_wp_error( $wp_user_id ) ){
						pre($wp_user_id, 'ERROR! wp_create_user()');
					}
					// usuário criado!!! adicionar metas e mensagem
					else{
						// usermeta
						$user_metas = array(
							'fb_uid' => 'id',
							'first_name' => 'first_name',
							'last_name' => 'last_name',
							'display_name' => 'name',
							'description' => 'bio',
							'location' => 'location',
							'education' => 'education',
							'gender' => 'gender',
							'locale' => 'locale',
							'verified' => 'verified',
							'timezone' => 'timezone',
						);
						foreach( $user_metas as $wp_meta => $fb_meta ){
							if( isset($this->user[$fb_meta]) )
								update_user_meta( $wp_user_id, $wp_meta, $this->user[$fb_meta] );
						}
						
						// login
						$creds = array();
						$creds['user_login'] = $user_data['user_login'];
						$creds['user_password'] = $user_data['user_pass'];
						$creds['remember'] = true;
						$wp_user = wp_signon( $creds, false );
						if( is_wp_error($wp_user) ){
							pre($wp_user, 'ERROR! wp_signon()');
						}
						
						$url = self_url();
						wp_redirect( $url );
						exit();
					}
				}
			}
		}
	}
	
	/**
	 * AUTO LOGIN
	 * @link http://kuttler.eu/code/log-in-a-wordpress-user-programmatically/
	 * 
	 */
	function wp_user_login( $user_id ){
		// log in automatically
		if ( !is_user_logged_in() ) {
			$user = get_user_by( 'id', $user_id );
			//pre($user);
			wp_set_current_user( $user_id, $user->data->user_login );
			wp_set_auth_cookie( $user_id );
			do_action( 'wp_login', $user->data->user_login );
		}     
	}
	
	function app_logout(){
		$_SESSION["fb_{$this->boros_fb_appid}_code"] = '';
		$_SESSION["fb_{$this->boros_fb_appid}_access_token"] = '';
		$_SESSION["fb_{$this->boros_fb_appid}_access_token"] = '';
	}
	
	function wp_logout(){
		wp_logout();
	}
	
	function logout_url( $url ){
		$url = add_query_arg( 'redirect_to', home_url('/?wp_logout=1'), $url );
		return $url;
	}
	
	function login_redirect( $redirect_to, $request, $user ){
		return home_url('/?wp_login=1');
	}
	
	function redirect_home(){
		wp_redirect( home_url('/') );
		exit();
	}
	
	function fb_logout(){
		$this->facebook->destroySession(); 
	}
	
	function app_loginout_url(){
		if( is_user_logged_in() ){
			// logout wp only)
			global $current_user;
			get_currentuserinfo();
			$user_fb_uid = get_user_meta( $current_user->ID, 'fb_uid', true );
			if( empty($user_fb_uid) ){
				return wp_logout_url( self_url() );
			}
			// logout facebook
			else{
				return add_query_arg( 'app_logout', 1, self_url() );
			}
		}
		else{
			$args = array(
				'fb_login' => 1,
				'site_redirect' => urlencode( self_url() ),
			);
			
			$redirect_url = add_query_arg( $args, home_url() );
			$params = array(
				'next' => $redirect_url,
				'scope' => 'email, publish_stream',
			);
			//return $this->facebook->getLoginUrl();
			return $this->facebook->getLoginUrl($params);
		}
	}
	
	function avatar( $avatar, $id_or_email, $size, $default, $alt ){
		// procurar user com fb_uid
		$user = get_user_by( 'id', $id_or_email );
		if($user){
			$fb_uid = get_user_meta( $user->ID, 'fb_uid', true );
			if( !empty($fb_uid) )
				return "<img src='https://graph.facebook.com/{$fb_uid}/picture' width='{$size}' height='{$size}' />";
		}
		return $avatar;
	}
	
	function test( $var ){
		pre( $this->$var, $var );
	}
}