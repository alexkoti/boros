<?php
/**
 * ==================================================
 * OBETER CÓDIGO MINIFICADO =========================
 * ==================================================
 * 
 * Na linha de comando, na pasta deste arquivo, executar:
 * <code>
 * php -w debug.php
 * </code>
 * 
 */

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

    $ret = [];
    foreach( $wp_filter[$hook] as $priority => $realhook ){
        foreach( $realhook as $hook_k => $hook_v ){
            if( is_array($hook_v['function']) ){
                $name = get_class($hook_v['function'][0]);
                $ret[$priority][$name] = array(
                    'priority' => $priority,
                    'object'   => $hook_v['function'][0],
                    'method'   => $hook_v['function'][1],
                );
            }
            else{
                $name = $hook_v['function'];
                $ret[$priority][$name] = array(
                    'priority' => $priority,
                    'function' => $name,
                );
            }
        }

    }
    return $ret;
}



/**
 * Remover filter/action declarados por class
 * Esta função consegue remover hooks aplicados por classes que foram declaradas sem variável acessível.
 * 
 * @link https://wordpress.stackexchange.com/a/304861
 * 
 */
function boros_remove_filters_with_method_name( $hook_name = '', $method_name = '', $priority = 0 ) {
    global $wp_filter;
    // Take only filters on right hook name and priority
    if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) || ! is_array( $wp_filter[ $hook_name ][ $priority ] ) ) {
        return false;
    }
    // Loop on filters registered
    foreach ( (array) $wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {
        // Test if filter is an array ! (always for class/method)
        if ( isset( $filter_array['function'] ) && is_array( $filter_array['function'] ) ) {
            // Test if object is a class and method is equal to param !
            if ( is_object( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) && $filter_array['function'][1] == $method_name ) {
                // Test for WordPress >= 4.7 WP_Hook class (https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/)
                if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {
                    unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
                } else {
                    unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
                }
            }
        }
    }
    return false;
}



/**
 * ==================================================
 * CURRENT ADMIN SCREEN INFO ========================
 * ==================================================
 * 
 */
//add_action( 'admin_footer', 'boros_current_screen_info' );
function boros_current_screen_info() {
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
    pre( $vars, 'debug:variáveis de admin', $opened = false );
    echo '</div>';
}



/**
 * ==================================================
 * PRE ==============================================
 * ==================================================
 * Listar valores de um array ou objetct com <pre>
 *
 * @param mix    $var       Qualquer tipo de variável.
 * @param string $legend    Legenda para identificar a saida.
 * @param bool   $opened    Determinar se o box iniciará aberto ou fechado.
 */
if( !function_exists('pre') ){
    function pre( $var = false, $legend = '', $opened = true ){
        $pre = PRE::init();
        $pre->pre( $var, $legend, $opened );
    }
}

/**
 * ==================================================
 * PAL ==============================================
 * ==================================================
 * {P}rint {AL}ert
 * Exibir string mensagem na página.
 *
 * @param string $var         String que deseja exibir.
 * @param string $var_name    Exibir prefixo com o nome da variável, ou texto de introdução.
 * @param int    $pad         Padding para alinhar as strings. Será preenchido com traço formando seta.
 * @param string $level       Nível da mensagem, determina as cores de exibição. Padrão 'warning'.
 *                            Valores: ('none','danger','success','info','warning','neutral')
 */
if( !function_exists('pal') ){
    function pal( $var = false, $legend = '', $level = 'warning', $pad = 0 ){
        $pre = PRE::init();
        $pre->pal( $var, $legend, $level, $pad );
    }
}

/**
 * ==================================================
 * PCM ==============================================
 * ==================================================
 * {P}rint {C}o{M}ment
 * Exibir string em comentário HTML
 *
 * @param mix    $var        Mensagem que deseja exibir.
 * @param string $legend     Exibir prefixo com o nome da variável, ou texto de introdução.
 * @param int    $pad        Padding para alinhar as strings. Será preenchido com traço formando seta ou :, conforme o
 *                           valor positivo ou negativo. Positivo o espaço restante será preenchido com ->, negativo 
 *                           onde será prefixado com espaços e dividido com ':'.
 * 
 * @example
 * <code>
 * pcm('ipsum', 'lorem', 10);
 * <!-- lorem ----˃ ipsum -->
 * 
 * pcm('ipsum', 'lorem', 10);
 * <!--     lorem : ipsum -->
 * </code>
 * 
 */
if( !function_exists('pcm') ){
    function pcm( $var = false, $legend = '', $pad = 0 ){
        $pre = PRE::init();
        $pre->pcm( $var, $legend, $pad );
    }
}

/**
 * ==================================================
 * ERROR LOG ========================================
 * ==================================================
 * {P}rint {E}rror {L}og
 * Adicionar error_log, ajustando arrays e objects para string.
 * 
 * @param mix    $var        Mensagem que deseja salvar.
 * @param string $legend     Exibir prefixo com o nome da variável, ou texto de introdução.
 * @param string $level      Nível da mensagem que será prefixado. Opcional.
 *                           Valores: ('danger','error','warn','warning','info','debug')
 * @param int    $pad        Padding para alinhar as strings. Será preenchido com traço formando seta ou :, conforme o
 *                           valor positivo ou negativo. Positivo o espaço restante será preenchido com ->, negativo 
 *                           onde será prefixado com espaços e dividido com ':'.
 * 
 * @example
 * <code>
 * pel('ipsum', 'lorem', '', 20);
 * [13-Jul-2021 23:39:13 UTC]     lorem  : ipsum
 * 
 * pel('ipsum', 'lorem', '', 10);
 * [13-Jul-2021 23:39:45 UTC] lorem -----> ipsum
 * 
 * pel('ipsum', 'lorem', 'warn', 10);
 * [13-Jul-2021 23:39:45 UTC]  WARN lorem -> ipsum
 * 
 * pel('ipsum', 'lorem', 'error', 10);
 * [13-Jul-2021 23:39:45 UTC] ERROR lorem -> ipsum
 * </code>
 * 
 */
if( !function_exists('pel') ){
    function pel( $var = false, $legend = '', $level = '', $pad = 0, $pad_pos = 'left' ){
        $pre = PRE::init();
        $pre->pel( $var, $legend, $level, $pad, $pad_pos );
    }
}

/**
 * ==================================================
 * MENSAGEM HTML ====================================
 * ==================================================
 * {P}rint {A}lert {M}essage
 * Caixa colorida semelhante ao alert bootstrap, porém sem dependência do css do bootstrap
 * Aceita tags HTML
 * 
 * @param mix    $message    Mensagem que deseja exibir.
 * @param string $type       Nível da mensagem. Padrão 'info'.
 *                           Valores: ('none','neutral','danger','success','info','warning')
 * @param string $legend     Exibir title com texto de introdução.
 * 
 */
if( !function_exists('pam') ){
    function pam( $message = '', $type = 'info', $legend = '' ){
        $pre = PRE::init();
        $pre->pam( $message, $type, $legend );
    }
}

/**
 * ==================================================
 * SEPARADOR ========================================
 * ==================================================
 * Separador simples, usar apenas para melhorar a legibilidade em debugs complexos.
 * 
 * @param mix    $message         Mensagem que deseja exibir, aceita HTML. Opcional
 * @param string $level           Nível da mensagem. Determina as cores do separador. Padrão 'danger'.
 *                                Valores: ('none','danger','success','info','warning','neutral')
 * 
 */
if( !function_exists('sep') ){
    function sep( $message = '', $level = 'danger' ){
        $pre = PRE::init();
        $pre->sep( $message, $level );
    }
}

/**
 * ==================================================
 * CLASS PRE ========================================
 * ==================================================
 * Classe construtora para debug de variáveis.
 * Usar as functions wrapper: pre(), pal(), pel(), pcm(), pam(), sep()
 * 
 * 
 */
if( !class_exists('PRE') ){

class PRE {

    private $css = [
        'pal'       => 'border:1px solid #666;border-left:10px groove;clear:both;color:#666;font:normal 12px fira code,monospace;line-height:130%;margin:5px;padding:5px;text-align:left;',
        'pre'       => 'background:#f4f4f4;border:1px solid #dfdfdf;border-radius:3px;clear:both;color:#333;font:10px fira code,monospace;line-height:130%;margin:5px;position:relative;text-align:left;',
        'pre_head'  => 'background:#ededed;color:#da1c23;cursor:pointer;font-size:12px;font-weight:normal;margin:0 !important;padding:5px;',
        'pre_value' => 'font-size:12px;color:#333;margin:0;padding:5px;',
        'pre_tag'   => 'border:none;color:#6B7688;font-size:12px;white-space:pre-wrap !important;margin:0;padding:5px;',
        'pre_foot'  => 'font-size:11px;background:#ededed;color:#999;margin:0;padding:5px;',
        'pam'       => 'border-radius:4px;border:1px solid;font-size:12px;font-family:sans-serif;line-height:24px;margin:5px;position:relative;padding:12px 18px;',
        'sep'       => 'border-radius:3px;box-sizing:content-box;color:#fff;font:12px fira code, monospace;min-height:11px;line-height:13px;margin:50px 5px;padding:10px 0 8px;text-align:center;',
    ];
    
    private static $instance;

    private static $index = 1;
    
    public static function init(){
        if( empty( self::$instance ) ){
            self::$instance = new PRE();
        }
        return self::$instance;
    }
    
    private function __construct(){

    }
    
    /**
     * Mensagem simples com formatação de cores indicativas
     * 
     */
    public function pal( $message, $var_name = false, $level = 'warning', $pad = 0 ){
        $legend = '';
        $color = [
            'none'    => ['#fff','#ccc'],
            'danger'  => ['#f8d7da','#d65661'],
            'error'   => ['#f8d7da','#d65661'],
            'success' => ['#d4edda','#6ac580'],
            'info'    => ['#d1ecf1','#59b6c7'],
            'warning' => ['#f0f0bb','#e08b8b'],
            'neutral' => ['#e2e3e5','#a5a5a5'],
        ];
        if( !isset($color[$level]) ){
            $level = 'warning';
        }
        if( !empty($var_name) ){
            $legend = $this->mb_str_pad("{$var_name} ", $pad, '-', STR_PAD_RIGHT);
            $legend = "<strong style='font-weight:600;'>{$legend}-&gt;</strong> ";
        }
        $style = "{$this->css['pal']};background-color:{$color[$level][0]};border-color:{$color[$level][1]}";
        echo PHP_EOL . "<div style='{$style}'>" . $legend . $this->esc_html($message) . PHP_EOL . '</div>' . PHP_EOL;
    }
    
    /**
     * Mensagem com toggle de exibição
     * 
     */
    public function pre( $var = false, $legend = '', $opened = true ){
        self::$index++;
        $id            = 'd' . self::$index;
        $js            = "var div=document.getElementById('{$id}');div.style.display=div.style.display=='none'?'block':'none';";
        $click         = 'onclick="' . $js . '";';
        $content_class = ($opened === true) ? 'block' :'none';
        
        echo PHP_EOL . "<div style='{$this->css['pre']}'>" . PHP_EOL;
        echo ( $legend == '' ) ? '' :"<div style='{$this->css['pre_head']}' {$click}>{$legend}</div>" . PHP_EOL;
        echo "<div id='{$id}' style='display:{$content_class};'>" . PHP_EOL;
        if( is_object($var) || is_array($var) ){
            echo "<pre style='{$this->css['pre_tag']}'>" . PHP_EOL;
            $v = print_r( $var, true );
            echo str_replace('  ', ' ', $this->esc_html($v));
            echo  "</pre>" . PHP_EOL;
            if( is_array($var) ){
                echo "<div style='{$this->css['pre_foot']}'>TOTAL:<strong style='font-weight:600;'>" . count($var) . '</strong></div>';
            }
        }
        else{
            $size = '';
            $type = gettype($var);
            if( $type == 'boolean' ){
                $var = ($var == false) ? 'FALSE' :'TRUE';
            }
            if( $type == 'string' ){
                $len = strlen($var);
                $size = " ({$len})";
            }
            
            /* verificar se a variável é multilinha e trocar para <pre>*/
            if(strpos($var, "\n") !== FALSE) {
                $var = "<pre style='{$this->css['pre_tag']}'>" . $this->esc_html($var) . "</pre>";
            }
            else {
                $var = "<span style='color:#6B7688;'>" . $this->esc_html($var) . "</span>";
            }
            echo "<div style='{$this->css['pre_value']}'><em>{$type}</em> :{$var}{$size}</div>" . PHP_EOL;
        }
        echo "</div></div>" . PHP_EOL;
    }

    /**
     * Error Log
     * 
     */
    public function pel( $var = false, $legend = '', $level = '', $pad = 0 ){
        $levels = [
            'danger'  => 'ERROR ',
            'error'   => 'ERROR ',
            'warn'    => ' WARN ',
            'warning' => ' WARN ',
            'info'    => ' INFO ',
            'debug'   => 'DEBUG ',
        ];
        // permitir level vazio
        $lvl = '';
        if( !empty($level) && array_key_exists($level, $levels) ){
            $lvl = $levels[$level];
        }

        // determinar o padding right ou left, compensar comprimento do level
        if( !empty($legend) ){
            if( $pad < 0 ){
                $pad += 6;
                if( $pad > 0 ){$pad = 0;}
                $legend = str_pad("{$legend}", -$pad, ' ', STR_PAD_LEFT);
                $legend = "{$legend} : ";
            }
            else{
                $pad -= 6;
                if( $pad < 0 ){$pad = 0;}
                $legend = str_pad("{$legend} ", $pad, '-', STR_PAD_RIGHT);
                $legend = "{$legend}-> ";
            }
        }

        $var = print_r($var, true);
        error_log( "{$lvl}{$legend}{$var}" );
    }
    
    /**
     * Comentário HTML
     * 
     */
    public function pcm( $var = false, $legend = '', $pad = 0 ){
        $var = (string)$var;

        if( !empty($legend) ){
            if( $pad < 0 ){
                $legend = str_pad("{$legend} ", -$pad, ' ', STR_PAD_LEFT);
                $legend = "{$legend}: ";
            }
            else{
                $legend = str_pad("{$legend} ", $pad, '-', STR_PAD_RIGHT);
                $legend = "{$legend}˃ "; /* caractere "Modifier Letter Right Arrowhead" U+02C3 para não fechar o cometário */
            }
        }

        echo PHP_EOL . "<!-- {$legend}{$var} -->";
    }

    /**
     * Alert HTML
     * 
     */
    public function pam( $message = '', $legend = '', $level = 'info' ){
        $color = [
            'none'    => ['#888','#fff'],
            'neutral' => ['#383d41','#e2e3e5'],
            'danger'  => ['#721c24','#f8d7da'],
            'error'   => ['#721c24','#f8d7da'],
            'success' => ['#155724','#d4edda'],
            'info'    => ['#004085','#cce5ff'],
            'warning' => ['#856404','#fff3cd'],
        ];
        if( !isset($color[$level]) ){
            $level = 'info';
        }
        if( !empty($legend) ){
            $legend = "<strong style='clear:both;display:block;'>{$legend}</strong> ";
        }
        $style = "{$this->css['pam']};background-color:{$color[$level][1]};border-color:{$color[$level][0]}30;color:{$color[$level][0]};";
        echo PHP_EOL . "<div style='{$style}'>{$legend}{$message}</div>" . PHP_EOL;
    }

    /**
     * Separador
     * 
     */
    public function sep( $message = '', $level = 'danger' ){
        $color = [
            'none'    => '#ddd',
            'danger'  => '#dc3545',
            'error'   => '#dc3545',
            'success' => '#28a745',
            'info'    => '#17a2b8',
            'warning' => '#ffae00',
            'neutral' => '#6c757d',
        ];
        if( !isset($color[$level]) ){
            $level = 'danger';
        }
        echo PHP_EOL . "<div style='background:{$color[$level]};{$this->css['sep']}'>{$message}</div>" . PHP_EOL;
    }
    
    public function esc_html( $var ){
        if( function_exists('esc_html') ){
            return esc_html( $var );
        }
        else{
            return htmlentities( $var, ENT_QUOTES, 'UTF-8', false );
        }
    }
    
    /**
     * String Pad str_pad() para multi-byte characters
     * 
     */
    public function mb_str_pad( $input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT){
        $diff = strlen( $input ) - mb_strlen( $input );
        return str_pad( $input, $pad_length + $diff, $pad_string, $pad_type );
    }
}

}


