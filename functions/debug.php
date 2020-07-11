<?php
/**
 * DEBUG FUNCTIONS
 * Funções para debug
 * 
 * @todo refatorar design/css
 * @todo adicionar bam() [Bootstrap Alert HTML] e modificar pam() para utilizar css próprio sem dependância de bootstrap
 * @todo em pam() verificar o enqueue de boostrap e apontar para bam()
 * @todo mover todas as chamadas de functions para métodos da class principal
 * 
 * VERSÃO PARA APLICAÇÃO RÁPIDA
 if( !function_exists('pre') ){function pre( $var = false, $legend = '', $opened = true, $global_controls = false ){ $pre = PRE::init(); $pre->pre( $var, $legend, $opened, $global_controls ); } function pal( $var = false, $legend = '' ){ $pre = PRE::init(); $pre->pal( $var, $legend ); } class PRE { var $controls_visible = false; private static $instance; public static function init(){ if( empty( self::$instance ) ){ self::$instance = new PRE(); } return self::$instance; } private function __construct(){ $this->do_css(); $this->do_js(); } private function do_css(){ echo " <style type='text/css'> #pre_box_controls {background:#fefcf5;border:1px solid #333;clear:both;color:#333;cursor:pointer;font:12px monospace;line-height:130%;margin:5px;padding:5px;text-align:left;} .pre_box {background:#fefcf5;border:1px solid #333;clear:both;color:#333;font:12px monospace;line-height:130%;margin:5px;position:relative;text-align:left;} .boros_element_duplicate_group.layout_block .pre_box {max-width:750px;overflow:hidden;} .boros_element_duplicate_group.layout_table .pre_box {max-width:450px;overflow:hidden;} .pre_box_head {background:#ededed;color:#da1c23;cursor:pointer;font-size:14px;font-weight:normal;margin:0 !important;padding:5px;} .pre_box_footer {font-size:11px;background:#ededed;color:#999;margin:0;padding:5px;} .pre_box_bool {font-size:12px;color:#333;margin:0;padding:5px;} .pre_box_footer strong, .pre_box_bool span {color:#6B7688;} .pre_box pre {border:none;color:#6B7688;font-size:12px;white-space:pre !important;margin:0;padding:5px;word-wrap:normal !important;} .pal_box {background:#F0F0BB;border:1px solid #666;border-left:10px groove #E08B8B;clear:both;color:#666;font:normal 14px monospace;line-height:130%;margin:5px;padding:5px;text-align:left;} .pre_box_content {overflow:auto;} .pre_box_content_opened {display:block;} .pre_box_content_closed {display:none;} #wpwrap #debug_admin_vars {padding:0 20px 10px 165px;} .pre_box {background:#f4f4f4;border:1px solid #dfdfdf;border-radius:3px;} </style>"; } private function do_js(){ echo " <script type='text/javascript'> function toggle_pre( el ){ el.className = ( el.className != 'pre_box_content pre_box_content_closed' ? 'pre_box_content pre_box_content_closed' : 'pre_box_content pre_box_content_opened' ); } function pre_box_toggle( ctrl ){ if( ctrl.className == 'pre_box_control_opened' ){ ctrl.className = 'pre_box_control_closed'; var content_class = 'pre_box_content pre_box_content_closed'; } else{ ctrl.className = 'pre_box_control_opened'; var content_class = 'pre_box_content pre_box_content_opened'; } var elems = document.getElementsByTagName('div'), i; for (i in elems){ if((' ' + elems[i].className + ' ').indexOf(' ' + 'pre_box_content' + ' ') > -1){ elems[i].className = content_class; } } } </script>"; } function esc_html( $var ){ if( function_exists('esc_html') ){ return esc_html( $var ); } else{ return htmlentities( $var, ENT_QUOTES, 'UTF-8', false ); } } function multidimensional_array_map( $func, $arr ){ if( function_exists('multidimensional_array_map') ){ return multidimensional_array_map( $func, $arr ); } else{ if( !is_array($arr) ) return $arr; $newArr = array(); foreach( $arr as $key => $value ){ if( is_scalar( $value ) ){ $nvalue = call_user_func( $func, $value ); } else{ $nvalue = $this->multidimensional_array_map( $func, $value ); } $newArr[ $key ] = $nvalue; } return $newArr; } } function pal( $message, $var_name = false ){ $var = ( $var_name != false) ? "<strong>{$var_name}</strong> &gt; &gt; &gt; " : ''; echo "<div class='pal_box'>" . $var . $this->esc_html($message) . "</div>\n"; } public function pre( $var = false, $legend = '', $opened = true, $global_controls = false ){ $id = uniqid('pre_'); $js = "toggle_pre(document.getElementById('{$id}'));"; $click = 'onclick="' . $js . '";'; if( $opened === true ){ $content_class = 'pre_box_content pre_box_content_opened'; } else{ $content_class = 'pre_box_content pre_box_content_closed'; } if( $global_controls == true and $this->controls_visible == false ){ echo '<div id="pre_box_controls" class="pre_box_control_opened" onclick="pre_box_toggle(this)">abrir/fechar todos</div>'; $this->controls_visible = true; } echo "<div class='pre_box'>\n"; echo ( $legend == '' ) ? '' : "<p class='pre_box_head' {$click}>{$legend}</p>\n"; echo "<div id='{$id}' class='{$content_class}'>\n"; if( is_object($var) || is_array($var) ){ echo "<pre>\n"; if( is_array($var) ){ print_r( $this->multidimensional_array_map( array($this, 'esc_html'), $var ) ); } else{ print_r( $var ); } echo "\n</pre>\n"; if( is_array($var) ){ echo "<p class='pre_box_footer'>TOTAL: <strong>" . count($var) . '</strong></p>'; } } else{ $size = ''; $type = gettype($var); if( $type == 'boolean' ){ $var = ($var == false) ? 'FALSE' : 'TRUE'; } if( $type == 'string' ){ $len = strlen($var); $size = " ({$len})"; } if(strpos($var, "\n") !== FALSE) { $var = "<pre>\n\t\t" . $this->esc_html($var) . "\n\t</pre>"; } else { $var = "<span>\n\t\t" . $this->esc_html($var) . "\n\t</span>"; } echo "<p class='pre_box_bool'>\n\t<em>" . $type . "</em> : \n\t{$var}". $size . "\n</p>\n"; } echo "\n</div></div>\n"; } } }
 * 
 * 
 */

/**
 * ==================================================
 * VARIÁVEIS DEFINIDAS ==============================
 * ==================================================
 * Listar todas as variáveis globais definidas até o momento.
 * 
 * @link http://stackoverflow.com/a/13629899
 * 
 */
//$ignore = array('GLOBALS', '_FILES', '_COOKIE', '_POST', '_GET', '_SERVER', '_ENV', 'ignore');
//$vars   = array_diff_key(get_defined_vars($GLOBALS) + array_flip($ignore), array_flip($ignore));
//pre($vars);



/**    
 * ==================================================
 * VERIFICAR FUNCTIONS DO HOOK ======================
 * ==================================================
 * 
 * Listar functions adicionadas a determinado hook
 * List all filters
 * List all actions
 * Todos os filtros
 * 
 * @link https://wordpress.stackexchange.com/a/223736
 * 
 */
function boros_hooked_functions( $hook = '' ) {
    global $wp_filter;
    if( empty( $hook ) || !isset( $wp_filter[$hook] ) ) return;

    $ret = '';
    foreach( $wp_filter[$hook] as $priority => $realhook ){
        foreach( $realhook as $hook_k => $hook_v ){
            $hook_echo = (is_array($hook_v['function']) ? get_class($hook_v['function'][0]) . ':' . $hook_v['function'][1] : $hook_v['function']);
            $ret .= "\n$priority $hook_echo";
        }

    }
    return $ret;
}



/**
 * ==================================================
 * CURRENT ADMIN SCREEN INFO ========================
 * ==================================================
 * 
 */
add_action( 'admin_footer', 'boros_current_screen_info' );
function boros_current_screen_info() {
	global $current_user;
	wp_get_current_user();
	$show_debug = get_user_meta($current_user->ID, 'show_debug', true);
	if(!empty($show_debug)){
		global $plugin_page, $page_hook, $admin_page_hooks, $hook_suffix, $pagenow, $typenow, $current_screen;
		$vars = array(
			'plugin_page'      => $plugin_page,
			'page_hook'        => $page_hook,
			'hook_suffix'      => $hook_suffix,
			'pagenow'          => $pagenow,
			'typenow'          => $typenow,
			'admin_page_hooks' => $admin_page_hooks,
			'current_screen'   => $current_screen,
		);
		echo '<div id="debug_admin_vars">';
		pre( $vars, 'debug: variáveis de admin', $opened = false );
		echo '</div>';
	}
}

function current_screen_info(){
	boros_current_screen_info();
}


/**
 * ==================================================
 * CURRENT PAGE INFO ================================
 * ==================================================
 * Mostrar dados da página corrent:
 *  - URL
 *  - template usado(arquivo .php)
 */
//add_action( 'wp_footer', 'boros_current_page_info' );
function boros_current_page_info(){
    global $template, $current_user;
    if( is_user_logged_in() ){
        wp_get_current_user();
        $show_debug = get_user_meta($current_user->ID, 'show_debug', true);
        if(!empty($show_debug)){
        ?>
        <ul class="header_debug" 
            style="position:fixed;width:100%;bottom:0;left:0;opacity:0.3;background:#FFFFE0;border:1px dotted #E6DB55;font-size:11px;margin:0;overflow:hidden;padding:4px;" 
            onMouseOver="this.style.opacity='1'"
            onMouseOut="this.style.opacity='0.3'"
        >
            <li><span title="Para trabalhar com forms">(?)</span> URL corrente: <code><?php echo self_url(); ?></code></li>
            <li>Template-name: <code><?php echo basename($template); ?></code></li>
            <li>Template-path: <code><?php echo $template; ?></code></li>
        </ul>
        <?php
        }
    }
}

function current_page_info(){
	boros_current_page_info();
}

/**
 * ==================================================
 * PRE ==============================================
 * ==================================================
 * Listar valores de um array ou objetct com <pre>
 *
 * @param mix $var qualquer tipo de variável.
 * @param string $legend legenda para identificar a saida.
 */
if( !function_exists('pre') ){
function pre( $var = false, $legend = '', $opened = true, $global_controls = false ){
    $pre = PRE::init();
    $pre->pre( $var, $legend, $opened, $global_controls );
}
}

/**
 * ==================================================
 * PAL ==============================================
 * ==================================================
 * {P}rint {AL}ert
 * Exibir string mensagem na página.
 *
 * @param mix $message mensagem que deseja exibir
 * @param mix $var_name exibir prefixo com o nome da variável, ou texto de introdução
 */
if( !function_exists('pal') ){
function pal( $var = false, $legend = '', $pad = 0 ){
	$pre = PRE::init();
	$pre->pal( $var, $legend, $pad );
}
}

/**
 * ==================================================
 * PCM ==============================================
 * ==================================================
 * {P}rint {C}o{M}ment
 * Exibir string em comentário HTML
 *
 * @param mix $message mensagem que deseja exibir
 * @param mix $var_name exibir prefixo com o nome da variável, ou texto de introdução
 */
if( !function_exists('pcm') ){
function pcm( $var = false, $legend = '', $pad = 0, $pad_pos = 'left' ){
    if( !empty($legend) ){
        if( $pad_pos == 'left' ){
            $legend = str_pad("{$legend} ", $pad, ' ', STR_PAD_LEFT);
            $legend = "{$legend}: ";
        }
        else{
            $legend = str_pad("{$legend} ", $pad, '-', STR_PAD_RIGHT);
            $legend = "{$legend}:> "; // dois pontos para não acionar fim de comentário
        }
    }
    echo "\n<!-- {$legend}{$var} -->";
}
}

/**    
 * ==================================================
 * ERROR LOG ========================================
 * ==================================================
 * {P}rint {E}rror {L}og
 * Adicionar error_log, ajustando arrays e objects para string
 * 
 */
if( !function_exists('pel') ){
function pel( $var = false, $legend = '', $pad = 0, $pad_pos = 'left' ){
    if( $pad_pos == 'left' ){
        $legend = str_pad("{$legend} ", $pad, ' ', STR_PAD_LEFT);
        $legend = "{$legend}: ";
    }
    else{
        $legend = str_pad("{$legend} ", $pad, '-', STR_PAD_RIGHT);
        $legend = "{$legend}> ";
    }
    $var    = print_r($var, true);
    error_log( "{$legend}{$var}" );
}
}

/**    
 * ==================================================
 * MENSAGEM HTML ====================================
 * ==================================================
 * {P}rint {A}lert {H}TML
 * Aceita tags HTML
 * Está utilizando classes do bootstrap
 * 
 * @todo: embutir css
 */
if( !function_exists('pam') ){
    function pam( $message = '', $type = 'primary', $legend = '', $close_button = false ){
        if( !empty($legend) ){
            $legend = "<strong>{$legend}:</strong> ";
        }
        $extra_class = '';
        $button      = '';
        if( $close_button == true ){
            $extra_class = ' alert-dismissible';
            $button = '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
        }
        echo "<div class='alert alert-{$type}{$extra_class}' role='alert'>{$legend}{$message}{$button}</div>";
    }
}


/**
 * Separador simples, usar apenas para melhorar a legibilidade em debugs complexos
 * 
 */
if( !function_exists('sep') ){
function sep( $message = '' ){
	echo "<div style='background:red;color:#fff;font:12px normal arial, sans-serif;height:10px;line-height:10px;margin:50px 0;padding:5px;'>{$message}</div>";
}
}

/**
 * ==================================================
 * CLASS PRE ========================================
 * ==================================================
 * Classe construtora para debug de variáveis. Foi usado singleton para deixar o output do css e js únicos.
 * Usar as functions wrapper: pre(), pal()
 * 
 * @todo adicionar o método prex(), para exibição de grandes blocos de arrays|objects
 */
if( !class_exists('PRE') ){

class PRE {
	var $controls_visible = false;
	
	private static $instance;
	
	public static function init(){
		if( empty( self::$instance ) ){
			self::$instance = new PRE();
		}
		return self::$instance;
	}
	
	// imprimir js e css
	private function __construct(){
		$this->do_css();
		$this->do_js();
	}
	
	private function do_css(){
		echo "
		<style type='text/css'>
		#pre_box_controls {background:#fefcf5;border:1px solid #333;clear:both;color:#333;cursor:pointer;font:12px monospace;line-height:130%;margin:5px;padding:5px;text-align:left;}
		.pre_box {background:#fefcf5;border:1px solid #333;clear:both;color:#333;font:12px monospace;line-height:130%;margin:5px;position:relative;text-align:left;}
		.boros_element_duplicate_group.layout_block .pre_box {max-width:750px;overflow:hidden;}
		.boros_element_duplicate_group.layout_table .pre_box {max-width:450px;overflow:hidden;}
		.pre_box_head {background:#ededed;color:#da1c23;cursor:pointer;font-size:14px;font-weight:normal;margin:0 !important;padding:5px;}
		.pre_box_footer {font-size:11px;background:#ededed;color:#999;margin:0;padding:5px;}
		.pre_box_bool {font-size:12px;color:#333;margin:0;padding:5px;}
		.pre_box_footer strong, .pre_box_bool span {color:#6B7688;}
		.pre_box pre {border:none;color:#6B7688;font-size:12px;white-space:pre !important;margin:0;padding:5px;word-wrap:normal !important;}
		.pal_box {background:#F0F0BB;border:1px solid #666;border-left:10px groove #E08B8B;clear:both;color:#666;font:normal 14px monospace;line-height:130%;margin:5px;padding:5px;text-align:left;}
		.pre_box_content {overflow:auto;}
		.pre_box_content_opened {display:block;}
		.pre_box_content_closed {display:none;}
		/* apenas para admin */
		#wpwrap #debug_admin_vars {padding:0 20px 10px 165px;}
		.pre_box {background:#f4f4f4;border:1px solid #dfdfdf;border-radius:3px;}
		</style>";
	}
	
	private function do_js(){
		echo "
		<script type='text/javascript'>
		function toggle_pre( el ){
			el.className = ( el.className != 'pre_box_content pre_box_content_closed' ? 'pre_box_content pre_box_content_closed' : 'pre_box_content pre_box_content_opened' );
		}
		function pre_box_toggle( ctrl ){
			if( ctrl.className == 'pre_box_control_opened' ){
				ctrl.className = 'pre_box_control_closed';
				var content_class = 'pre_box_content pre_box_content_closed';
			}
			else{
				ctrl.className = 'pre_box_control_opened';
				var content_class = 'pre_box_content pre_box_content_opened';
			}
			var elems = document.getElementsByTagName('div'), i;
			for (i in elems){
				if((' ' + elems[i].className + ' ').indexOf(' ' + 'pre_box_content' + ' ') > -1){
					elems[i].className = content_class;
				}
			}
		}
		</script>";
	}
	
	function esc_html( $var ){
		if( function_exists('esc_html') ){
			return esc_html( $var );
		}
		else{
			return htmlentities( $var, ENT_QUOTES, 'UTF-8', false );
		}
	}
	
	function multidimensional_array_map( $func, $arr ){
		if( function_exists('multidimensional_array_map') ){
			return multidimensional_array_map( $func, $arr );
		}
		else{
			if( !is_array($arr) )
				return $arr;
			$newArr = array();
			foreach( $arr as $key => $value ){
				if( is_scalar( $value ) ){
					$nvalue = call_user_func( $func, $value );
				}
				else{
					$nvalue = $this->multidimensional_array_map( $func, $value );
				}
				$newArr[ $key ] = $nvalue;
			}
			return $newArr;
		}
    }
    
    /**
     * String Pad str_pad() para multi-byte characters
     * 
     */
    function mb_str_pad( $input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT){
        $diff = strlen( $input ) - mb_strlen( $input );
        return str_pad( $input, $pad_length + $diff, $pad_string, $pad_type );
    }
	
	function pal( $message, $var_name = false, $pad = 0  ){
        $legend = '';
        if( !empty($var_name) ){
            $legend = $this->mb_str_pad("{$var_name} ", $pad, '-', STR_PAD_RIGHT);
            $legend = "<strong>{$legend}</strong>&gt; ";
        }
        //$var = str_replace('~', '&nbsp;', $var);
        //$var = str_replace('~', '-', $var);
		echo "<div class='pal_box'>" . $legend . $this->esc_html($message) . "</div>\n";
	}
	
	public function pre( $var = false, $legend = '', $opened = true, $global_controls = false ){
		$id = uniqid('pre_');
		$js = "toggle_pre(document.getElementById('{$id}'));";
		$click = 'onclick="' . $js . '";';
		if( $opened === true ){
			$content_class = 'pre_box_content pre_box_content_opened';
		}
		else{
			$content_class = 'pre_box_content pre_box_content_closed';
		}
		
		// adicionar toggle global
		if( $global_controls == true and $this->controls_visible == false ){
			echo '<div id="pre_box_controls" class="pre_box_control_opened" onclick="pre_box_toggle(this)">abrir/fechar todos</div>';
			$this->controls_visible = true;
		}
		
		echo "<div class='pre_box'>\n";
		echo ( $legend == '' ) ? '' : "<p class='pre_box_head' {$click}>{$legend}</p>\n";
		echo "<div id='{$id}' class='{$content_class}'>\n";
		if( is_object($var) || is_array($var) ){
			echo "<pre>\n";
			if( is_array($var) ){
				print_r( $this->multidimensional_array_map( array($this, 'esc_html'), $var ) );
			}
			else{
				print_r( $var );
			}
            echo "\n</pre>\n";
            if( is_array($var) ){
                echo "<p class='pre_box_footer'>TOTAL: <strong>" . count($var) . '</strong></p>';
            }
		}
		else{
			$size = '';
			$type = gettype($var);
			if( $type == 'boolean' ){
                $var = ($var == false) ? 'FALSE' : 'TRUE';
            }
			if( $type == 'string' ){
				$len = strlen($var);
				$size = " ({$len})";
			}
			
			// verificar se a variável é multilinha e trocar para <pre>
			if(strpos($var, "\n") !== FALSE) {
				$var = "<pre>\n\t\t" . $this->esc_html($var) . "\n\t</pre>";
			}
			else {
				$var = "<span>\n\t\t" . $this->esc_html($var) . "\n\t</span>";
			}
			echo "<p class='pre_box_bool'>\n\t<em>" . $type . "</em> : \n\t{$var}". $size . "\n</p>\n";
		}
		echo "\n</div></div>\n";
	}
}

}


