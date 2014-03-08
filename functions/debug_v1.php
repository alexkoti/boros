<?php
/**
 * DEBUG FUNCTIONS
 * Funções para debug >>> NÂO CONFUNDIR COM /config/debug.php !!!
 * 
 */

/**
 * ==================================================
 * ADMIN SCREEN =====================================
 * ==================================================
 * 
 */
function current_screen_info() {
	if( !is_admin() ) return;
	global $plugin_page, $page_hook, $admin_page_hooks, $hook_suffix, $pagenow, $typenow, $current_screen;
	pre( $plugin_page, 'plugin_page' );
	pre( $page_hook, 'page_hook' );
	pre( $admin_page_hooks, 'admin_page_hooks' );
	pre( $hook_suffix, 'hook_suffix' );
	pre( $pagenow, 'pagenow' );
	pre( $typenow, 'typenow' );
	pre( $current_screen, 'current_screen' );
}

/**
 * ==================================================
 * CURRENT PAGE INFO ================================
 * ==================================================
 * Mostrar dados da página corrent:
 *  - URL
 *  - template usado(arquivo .php)
 */
function current_page_info(){
	global $template;
	?>
	<style type="text/css">
	.header_debug {
		background:#FFFFE0 !important;
		border:1px dotted #E6DB55 !important;
		color:#666 !important;
		font:11px/20px 'courier new', monospace !important;
		margin:10px 0 0 !important;
		padding:4px !important;
		text-align:left !important;
	}
	</style>
	<ul class="header_debug">
		<li><span title="Para trabalhar com forms">(?)</span> URL corrente: <code><?php echo self_url(); ?></code></li>
		<li>Template: <code><?php echo basename($template); ?></code></li>
	</ul>
	<?php
}

/**
 * ==================================================
 * PRE ==============================================
 * ==================================================
 * Listar valores de um array ou objetct com <pre>
 *
 * @param	mix		$var			qualquer tipo de variável.
 * @param	string	$legend		legenda para identificar a saida.
 */
function pre( $var = false, $legend = '' ){
	$pre = PRE::init();
	$pre->pre( $var, $legend );
}

/**
 * ==================================================
 * PAL ==============================================
 * ==================================================
 * {P}rint {AL}ert
 * Exibir string mensagem na página.
 *
 * @param	mix	$message		mensagem que deseja exibir
 * @param	mix	$var_name	exibir prefixo com o nome da variável, ou texto de introdução
 */
function pal( $var = false, $legend = '' ){
	$pre = PRE::init();
	$pre->pal( $var, $legend );
}
function perror( $var = false, $legend = '' ){
	$pre = PRE::init();
	$pre->pal( $var, $legend, 'error' );
}
function psuccess( $var = false, $legend = '' ){
	$pre = PRE::init();
	$pre->pal( $var, $legend, 'success' );
}
function pmessage( $var = false, $legend = '' ){
	$pre = PRE::init();
	$pre->pal( $var, $legend, 'message' );
}

/**
 * Separador simples, usar apenas para melhorar a legibilidade em debugs complexos
 * 
 */
function sep( $message = '' ){
	echo "<div style='background:red;color:#fff;font-size:10px;height:10px;line-height:10px;margin:50px 0;padding:5px;'>{$message}</div>";
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
class PRE {
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
		.pre_box {background:#fefcf5;border:1px solid #333;clear:both;color:#333;font:12px monospace;line-height:90%;margin:5px;text-align:left;min-width:500px}
		.boros_element_duplicate_group.layout_block .pre_box {max-width:750px;overflow:hidden;}
		.boros_element_duplicate_group.layout_table .pre_box {max-width:450px;overflow:hidden;}
		.pre_box_head {background:#FCF4DA;color:#da1c23;cursor:pointer;font-size:14px;font-weight:bold;margin:0;padding:5px;}
		.pre_box_footer {border-top:1px solid #fcf4da;font-size:11px;background:#fff;color:#333;margin:0;padding:5px;}
		.pre_box_bool {font-size:14px;color:#333;margin:0;padding:5px;}
		.pre_box_footer strong, .pre_box_bool span {color:#da1c23;}
		.pre_box pre {font-size:12px;white-space:pre;margin:0;padding:5px;}
		.pal_box {background:#fcf8e3;border:1px solid #333;border-left:10px groove orange;clear:both;color:#333;font:normal 14px monospace;line-height:90%;margin:5px;padding:5px;text-align:left;}
		.pal_box.error{background:#f2dede;border-left:10px groove red;}
		.pal_box.success{background:#dff0d8;border-left:10px groove green;}
		.pal_box.message{background:#d9edf7;border-left:10px groove cyan;}
		</style>";
	}
	
	private function do_js(){
		echo "
		<script type='text/javascript'>
		function toggle_pre( el ){
			el.style.display = ( el.style.display != 'none' ? 'none' : '' );
		}
		</script>";
	}
	
	function pal( $message, $var_name = false, $class = '' ){
		$var = ( $var_name != false) ? "<strong>{$var_name}</strong> &gt; &gt; &gt; " : '';
		$message = esc_html($message);
		echo "<div class='pal_box {$class}'>{$var}{$message}</div>\n";
	}
	
	public function pre( $var = false, $legend = '' ){
		$id = uniqid('pre_');
		$js = "toggle_pre(document.getElementById('{$id}'));";
		$click = 'onclick="' . $js . '";';
		
		echo "<div class='pre_box'>\n";
		echo ( $legend == '' ) ? '' : "<p class='pre_box_head' {$click}>{$legend}</p>\n";
		echo "<div id='{$id}'>\n";
		if( is_object($var) || is_array($var) ){
			echo "<pre>\n";
			if( is_array($var) ){
				print_r( multidimensional_array_map( 'esc_html', $var ) );
			}
			else{
				print_r( $var );
			}
			echo "\n</pre>\n";
			echo "<p class='pre_box_footer'>TOTAL: <strong>" . count($var) . '</strong></p>';
		}
		else{
			$size = '';
			$type = gettype($var);
			if( $type == 'boolean' )
				$var = ($var == false) ? 'FALSE' : 'TRUE';
			if( $type == 'string' ){
				$len = strlen($var);
				$size = " ({$len})";
			}
			echo "<p class='pre_box_bool'>\n\t<em>" . $type . "</em> : \n\t<span>\n\t\t" . esc_html($var) . "\n\t</span>". $size . "\n</p>\n";
		}
		echo "\n</div></div>\n";
	}
}

/**
 * ==================================================
 * PREX =============================================
 * ==================================================
 * Listar valores de um array ou objetct em ol multinível
 * Para evitar o pré-requisito de javascript linkado, as ações foram adicionadas inline.
 *
 * @param	array	$var			array ou object para exibir em layout folder-tree
 * @param	int		$collapse	default true - iniciar com a árvore de subníveis fechada
 * 
 * @todo	Melhorar o método print_one_level_variables(), repensar esse bloco para funcionar como verificar rápido de GLOBALS
 */
function prex( $var, $args = array() ){
	new prex( $var, $args );
}
class prex {
	var $next = 1;
	var $cores = array(
		'group' => '#4547DF',
		'vazio' => '#AAAAAA',
		'valor_bg' => '#FFFFEF',
		'valor_txt' => '#444444',
	);
	var $styles = array();
	
	function __construct( $var, $args ){
		$defaults = array(
			'legend' => '',
			'collapse' => true,
			'display' => 'all',
			'subvariable' => false,
		);
		$attr = wp_parse_args( $args, $defaults );
		extract( $attr, EXTR_SKIP );
		
		if( $display == 'first_level' ){
			echo "<div class='prex_block' style='background:#FFF;border:1px dotted #FF7F7F;'><span style='color:red;padding:5px;'>variável: <strong>$legend</strong></span>";
			$this->set_styles();
			$this->do_styles();
			$this->print_first_level_variables($var, $collapse);
			$this->do_js();
			echo '</div>';
		}
		elseif( $display == 'all' ){
			echo "<div class='prex_block' style='background:#FFF;border:1px dotted #FF7F7F;'><span style='color:red;padding:5px;'>variável: <strong>$legend</strong></span>";
			$this->set_styles();
			$this->do_styles();
			$this->print_all_variables($var, $collapse);
			$this->do_js();
			echo '</div>';
		}
	}
	
	function set_styles(){
		$css = array(
			'td_span' => "border:1px dotted {$this->cores['group']};font-size:11px;padding:2px;max-width:99%;",
		);
		$this->styles = $css;
	}
	
	function do_js(){
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$('body .prex_block:first').delegate( 'strong.prex_key', 'click', function(){
				$(this).parent().find('div.group:first').toggle();
			});
			$('body .prex_block:first').delegate( 'small.prex_toggle_all', 'click', function(){
				if( $(this).parent().find('div.group:first').is(':visible') )
					$(this).parent().find('div.group').hide();
				else
					$(this).parent().find('div.group').show();
			});
		});
		</script>
		<?php
	}
	
	function do_styles(){
		echo "
		<style type='text/css'>
		.prex_key {
			cursor:pointer;background:#FFEFEF;
		}
		.prex_td_span {
			font-size:12px;
		}
		.prex_key_num {
			font-weight:normal;
		}
		.prex_table {
			width:99%;word-wrap:break-word;background:#FFF;text-align:left;font-size:11px;border:1px dotted {$this->cores['group']};color:{$this->cores['group']};margin:0;margin:2px 0 2px 2%;border-collapse:collapse;
		}
		.prex_toggle_all {
			color:{$this->cores['vazio']};float:right;cursor:pointer;
		}
		.prex_td_vazio {
			border:1px dotted {$this->cores['group']};font-size:11px;padding:2px;color: {$this->cores['vazio']};
		}
		.prex_var {
			background:{$this->cores['valor_bg']};
			border:1px dotted #3C9F3B;padding:2px;
			font-size:11px;
		}
		.prex_var_key {width:30%;}
		.prex_var_val {width:70%;}
		
		</style>
		";
	}
	
	function print_first_level_variables( $var ){
		echo '<ul id="json_list">';
		foreach( $var AS $key => $val ){
			echo "<li><strong class='var_title'>{$key}</strong></li>";
		}
		echo '</ul>';
	}
	
	function print_one_level_variables( $var, $subvariable = false ){
		echo '<ul id="json_list">';
		foreach( $var AS $key => $val ){
			$subvariables = htmlspecialchars(json_encode($val), ENT_NOQUOTES);
			$subvariables = str_replace( "'", "&#039;", $subvariables );
			echo "<li data-json='[{$subvariables}]'><strong class='var_title'>{$key}</strong> <ul class='var_data'></ul></li>";
		}
		echo '</ul>';
		?>
		<style type="text/css">
		.var_title {font-size:14px;font-weight:bold;}
		.var_data {font-size:12px;padding:0 0 0 10px;}
		</style>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$('#json_list li').click(function(){
				var $li = $(this);
				var $values = $li.find('ul.var_data:first');
				var data = eval($(this).attr('data-json'));
				if(typeof(data) == 'object'){
					$.each(data, function(key, val){
						loop_obj( key, val, $values );
					});
				}
			});
			function loop_obj( key, val, $values ){
				if(typeof(val) == 'object'){
					$.each(val, function(u, foo){
						var novo = '<li><strong>'+ u +'</strong> : <em>'+ foo +'</em></li>';
						$values.append( novo );
					})
				}
				else{
					var novo = '<li><em>'+ val +'</em></li>';
					$values.append( novo );
				}
			}
		});
		</script>
		<?php
	}
	
	function print_all_variables($var, $collapse = true, $level = 0){
		extract( $this->styles, EXTR_PREFIX_ALL, "style" );
		extract( $this->cores, EXTR_PREFIX_ALL, "cor" );

		$group_display = ($this->next == 1) ? 'display:block;' : 'display:none;';
		$this->next = intval($this->next);
		
		if($collapse == false)
			$group_display = 'display:block;';
		
		$level++;
		
		$rgb = (255 - ($level * 10));
		$cor_level = "rgb($rgb,$rgb,$rgb);";
		
		echo '<div style="width:99%;'.$group_display.'" id="group_'.$this->next.'" class="group">';
		if( is_object($var) || is_array($var) ){
			echo "<table class='group prex_table'>";
			$i = 0;
			foreach( $var AS $key => $val ){
				$counter = ( $i == $key ) ? '' : '<span class="prex_key_num">['.$i.']</span>';
				
				echo '<tr>';
				if( is_object($val) || is_array($val) ){
					
					if( empty($val)){
						echo "<td colspan='2' class='prex_td_vazio'>{$counter} <strong id='key_{$this->next}' class='key'>[{$key}]</strong> (VAZIO)</td>";
					}
					else{
						$this->next++;
						
						echo "<td colspan='2' style='background:{$cor_level};' class='prex_td_span'>
								{$counter} 
								<strong id='key_{$this->next}' class='prex_key'>[{$key}]</strong>
								<small class='prex_toggle_all'>abrir/fechar todos</small>";
						
						$this->print_all_variables($val, $collapse, $level);
						echo '</td>';
					}
				}
				else{
					if( empty($val)){
						$val = '(VAZIO)';
						$txt_valor = $cor_vazio;
					}
					else{
						$txt_valor = $cor_valor_txt;
					}
					echo "	
						<th class='prex_var prex_var_key' style='color:{$txt_valor};'>{$counter} [{$key}]</th>
						<td class='prex_var prex_var_val' style='color:{$txt_valor};'> <em>{$val}</em></td>
						";
				}
				echo '</tr>';
				$this->next++;
				$i++;
			}
			echo '</table>';
		}
		else{
			echo 'É preciso que a variável seja um array ou object';
		}
		echo '</div>';
	}
}


