<?php
/**
 * ==================================================
 * PHP EXTENDED =====================================
 * ==================================================
 * Funções auxiliares que extendem funcionalidades geraisdo PHP
 * A princípio apenas funções que possam ser aplicadas ao PHP puro, sem qualquer dependência do WordPress. Caso a função dependa ou extenda alguma funcionalidade 
 * do WP, deixar no arquivo 'functions/extended_wp.php'.
 * 
 * 
 */


/**
 * ==================================================
 * ISSETOR ==========================================
 * ==================================================
 * Aplicar valor ou NULL em variável, para comparação de valores de variáveis que possam não existir
 *
 * Exemplos:
<code>
// 1)  $foo não está setado, gerando erro em um uma comparação if( $foo == 'teste' ), então usar issetor():
if( issetor($foo) == 'test' ){
	echo 'its alive!';
}

// 2) aplicar valor padrão caso a variável pedida não exista
$bar = issetor( $foo, 'blah' ); // caso $foo NÃO esteja definido, será aplicado o valor 'blah' à variável $bar;
</code>
 * 
 * @link http://www.php.net/manual/pt_BR/function.isset.php#108768
 */
function issetor(&$variable, $or = NULL) {
	return $variable === NULL ? $or : $variable;
}



/**
 * ==================================================
 * CHECK IS-SET-EMPTY ===============================
 * ==================================================
 * Verificar se o valor de uma variável existe. Pode diferenciar de valores empty(), 0(numeral zero) e espaço em branco. 
 * Indicado para usar em forms e conferir valores de $_POST, mas pode verificar qualquer variável, incluindo em arrays associativos, por exemplo $foo['bar'], onde é preciso testar a existência da chave $bar
 * 
 * 
 * @param string $var variável a ser verificada
 * @param string|array $index caso queira verificar um array com um index de existência não confirmada. Se for declarado um array, será criado uma árvore dentro do $var declarado, exemplo:
 <code>
 $var = 'foo',
 $index = array('lorem', 'ipsum', 'dolor', 'sit')
 
 // será verificado o index:
 $foo['lorem']['ipsum']['dolor']['sit'];
 </code>
 */
function boros_check_empty_var( $var, $index = false ){
	//pre($var, '$var');
	if( is_array($index) ){
		$var = new BorosCheckArrayTree($var, $index);
		if( $var === false ){
			return false;
		}
	}
	
	if( isset($var) ){
		// verificar vazio //pal("1 check_posted_value: SET");
		if( empty($var) ){
			// verificar zero //pal("2 check_posted_value: EMPTY");
			if( ctype_digit($var) ){
				//pal("3 check_posted_value: NUMERIC ZERO");
				return true;
			}
			else{
				//pal("3 check_posted_value: TRUE EMPTY");
				return false;
			}
		}
		else{
			//pal("2 check_posted_value: NOT EMPTY");
			return true;
		}
	}
	else{
		//pal("check_posted_value: NOT SET");
		return false;
	}
}

class BorosCheckArrayTree {
	private $variable;
	private $index;
	
	function __construct( $variable, $index ){
		$this->variable = $variable;
		$this->index = $index;
		return $this->check_tree();
	}
	
	function check_tree(){
		if( is_array($this->index) ){
			return $this->loop( $this->variable, $this->index );
		}
		else{
			return isset( $this->variable[$this->index] );
		}
	}
	
	function loop( $variable, $index ){
		$i = array_shift($index);
		if( isset($variable[$i]) ){
			if( count($index) > 0 ){
				return $this->loop($variable[$i], $index);
			}
			else{
				return $variable[$i];
			}
		}
		else{
			return false;
		}
	}
}



/**
 * IMAGE_SRC
 * Retornar o caminho da imagem para uso em SRC, sempre em relação à pasta css
 * 
 */
function IMAGE_SRC( $file_name, $echo = true ){
	$src = CSS_IMG . '/' . $file_name;
	if( $echo == false  )
		return $src;
	echo $src;
}

/**
 * ==================================================
 * IMAGE TAG ========================================
 * ==================================================
 * Criar uma tag <img> conforme os argumentos. Caso seja uma string, será considerado que se queira uma imagem da pasta CSS_IMG. Caso seja um array, será feito o parse dos attributes, e caso o
 * 'src' não tenha sido declarado, será considerado uma imagem da pasta CSS_IMG
 * 
 * @param    string|array    $args caso seja string, será interpretado apenas como $file_name, caso seja array será usado para criar os attributes da imagem
 * @return tag <img> monstada, com echo opcional
 */
function IMAGE( $args, $echo = true ){
	$defaults = array(
		'src' => false,
		'title' => false,
		'alt' => '',
		'width' => false,
		'height' => false,
	);
	
	if( is_array($args) ){
		$attrs = boros_parse_args( $defaults, $args );
		if( $attrs['src'] == false ){
			$attr = 'src="' . CSS_IMG . '/' . $attrs['file_name'] . '"';
		}
		else{
			$attr = '';
		}
	}
	else{
		$attrs = $defaults;
		$attr = 'src="' . CSS_IMG . '/' . $attrs['file_name'] . '"';
	}
	
	foreach( $attrs as $k => $v ){
		if( $v !== false and $k != 'file_name' )
			$attr .= " {$k}='{$v}'";
	}
	
	$tag = "<img {$attr} />";
	
	if( $echo == false )
		return $tag;
	echo $tag;
}



/**
 * ==================================================
 * SELF URL =========================================
 * ==================================================
 * Retornar a url atual. Ideal para actions de forms, e retornos.
 * 
 * @return string - a url atual pedida no navegador
 */
function self_url( $args = array() ){
	$url = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	$url = apply_filters( 'boros_self_url', $url, $args );
	return $url;
}


/**
 * ==================================================
 * AUTO LINK ========================================
 * ==================================================
 * Usar para criar um link
 * @link http://stackoverflow.com/a/1945957
 * 
 */
function auto_link_text( $text ){
	$pattern  = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
	$callback = create_function('$matches', '
	$url       = array_shift($matches);
	$url_parts = parse_url($url);

	$text = parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);
	$text = preg_replace("/^www./", "", $text);

	$last = -(strlen(strrchr($text, "/"))) + 1;
	if ($last < 0) {
	$text = substr($text, 0, $last) . "&hellip;";
	}

	return sprintf(\'<a href="%s">%s</a>\', $url, $text);
	');

	return preg_replace_callback($pattern, $callback, $text);
}


/**
 * ==================================================
 * CORRECT URLS =====================================
 * ==================================================
 * Corrigir urls sem HTTP
 * 
 */
function auto_http_prefix( $link ){
	if( strpos($link,'http://') === false ){
		$link = 'http://' . $link;
	}
	return $link;
}



/**
 * ==================================================
 * UPPERCASE PORTUGUES ==============================
 * ==================================================
 * 
 * @link http://dourado.net/2007/05/15/php-converter-string-para-maiuscula-ou-minuscula-com-acentos/
 * 
 */
function uppercase_ptbr($term, $tp = 1) { 
    if ($tp == "1") $palavra = strtr(strtoupper($term),"àáâãäåæçèéêëìíîïðñòóôõö÷øùüúþÿ","ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÜÚÞß"); 
    elseif ($tp == "0") $palavra = strtr(strtolower($term),"ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÜÚÞß","àáâãäåæçèéêëìíîïðñòóôõö÷øùüúþÿ"); 
    return $palavra; 
}



/**
 * ==================================================
 * VERIFICAR SE URL EXISTE ==========================
 * ==================================================
 * Válido para arquivos de imagens, para verificar a existência antes do download
 * 
 * 
 * @link http://stackoverflow.com/a/7684862
 * 
 */
function boros_url_exists( $url ){
	$ch = curl_init($url);    
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	if($code == 200){
		$status = true;
	}
	else{
		$status = false;
	}
	curl_close($ch);
	return $status;
}



/**
 * ==================================================
 * FORMATTED LINK ===================================
 * ==================================================
 * Retorna um link em HTML com todos os atributos definidos
 * Definindo os defaults dentro da função e deixando apenas para pegar as variáveis em $args, possibilita a 
 * futura expansão do script sem comprometer as declarações já realizadas.
 * 
 * @param	string	$url		required
 * @param	string	$id 
 * @param	string	$class
 * @param	string	$text
 * @param	string	$title
 * @param	string	$list
 * @param	array	$append	adicionar query string no href do link. Usar array associativo
 * @param	array	$attr	adicionar atributos chave=valor, como data- e outros
 * @param	bool		$echo
 * 
 * @return string - link em HTML formatado
 */
function formatted_link( $args ){
	$defaults = array(
		'url'    => false,
		'id'     => false,
		'class'  => false,
		'text'   => false,
		'format' => '%s',
		'title'  => false,
		'list'   => false,
		'append' => false,
		'attr'   => false,
		'echo'   => false,
	);
	
	/** //debug $args
	$a = $args;
	$b = array_intersect_key( $a, $defaults );
	pre($b);
	/**/
	
	// processar dados enviados pela chamada da função em $args
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	// se não tiver a url encerra a função
	if( $url === false )
		return 'href inválido';
	// adicionar argumentos ao final da url
	if( $append )
		$url = add_query_arg( $param1 = $append, $old_query_or_uri = $url );
	$href = " href=\"{$url}\"";
	
	if( $id !== false ) 	$id = " id=\"{$id}\"";
	if( $class !== false ) 	$class = " class=\"{$class}\"";
	if( $text === false ) 	$text = $url;
	if( $title !== false ) 	$title = " title=\"{$title}\"";
	
	/**
	 * Definir demais atributos
	 * 
	 */
	$attrs = '';
	if( $attr !== false and is_array($attr) ){
		$attrs .= implode(' / ', array_map('boros_query_string', $attr, array_keys($attr)));
	}
	
	/**
	 * Retorno HTML
	 * 
	 */
	// montar o link no formato desejado
	$link = "<a{$href}{$id}{$class}{$title} {$attrs}>{$text}</a>";
	$link_format = sprintf( $format, $link );
	// retornar <a>
	if( $list === false ){
		$output = $link_format;
	}
	// retornar <li>
	else{
		$output = "<li{$id}{$class}>{$link_format}</li>";
	}
	
	if($echo)
		echo $output;
	return $output;
}

function boros_query_string($v, $k){
	return $k . '=' . $v;
}

/**
 * ==================================================
 * DECIMAL OPTION LIST ==============================
 * ==================================================
 * Criar uma lista de <option> com intervalo(range) numérico
 * 
 * @param	int		$start	início
 * @param	int		$end		final do range
 * @param	string	$value	valor a ser verificado para o selected="selected"
 * @param	int		$pad		leading zeros para aplicar em 'value'
 * 
 * @uses selected(), função core presente em wp-includes/formatting.php
 */
function decimal_options_list($start, $end, $value, $pad = 0){
	for( $a = $start; $a <= $end; $a++ ){
		if( $pad > 0){
			$f = '%0' . $pad . 'd';
			$a = sprintf( $f, $a );
		}
	?>
		<option value="<?php echo $a;?>" <?php selected( $value, $a, true ); ?> /><?php echo $a;?></option>
	<?php
	}
}

/**
 * ==================================================
 * PRINT CSS ========================================
 * ==================================================
 * Renderizar CSS a partir de um array.
 * Usar para o output de opções gravadas ou configs de functions.
 * 
 */
function boros_print_css( $css, $style_tag = true ){
	if( !empty( $css ) ){
		echo '<style type="text/css">';
		foreach( $css as $seletor => $declaration ){
			echo "\n{$seletor} {\n";
			foreach( $declaration as $prop => $val ){
				echo "	{$prop} : {$val};\n";
			}
			echo "\n}\n";
		}
		echo '</style>';
	}
}

/**
 * ==================================================
 * GERADOR DE ESTADOS BRASILEIROS ===================
 * ==================================================
 * 
 * 
 */
function boros_brazilian_states( $key = 'siglas', $value = 'extenso', $option_none = true, $option_none_key = '', $option_none_value = 'Estado' ){
	$siglas = array(
		'AC', 
		'AL', 
		'AM', 
		'AP', 
		'BA', 
		'CE', 
		'DF', 
		'ES', 
		'GO', 
		'MA', 
		'MG', 
		'MS', 
		'MT', 
		'PA', 
		'PB', 
		'PE', 
		'PI', 
		'PR', 
		'RJ', 
		'RN', 
		'RO', 
		'RR', 
		'RS', 
		'SC', 
		'SE', 
		'SP', 
		'TO', 
	);
	$extenso = array(
		'Acre',
		'Alagoas',
		'Amazonas',
		'Amapá',
		'Bahia',
		'Ceará',
		'Distrito Federal',
		'Espírito Santo',
		'Goiás',
		'Maranhão',
		'Minas Gerais',
		'Mato Grosso do Sul',
		'Mato Grosso',
		'Pará',
		'Paraíba',
		'Pernambuco',
		'Piauí',
		'Paraná',
		'Rio de Janeiro',
		'Rio Grande do Norte',
		'Rondônia',
		'Roraima',
		'Rio Grande do Sul',
		'Santa Catarina',
		'Sergipe',
		'São Paulo',
		'Tocantins',
	);
	
	$k = ($key == 'siglas') ? $siglas : $extenso;
	$v = ($value == 'extenso') ? $extenso : $siglas;
	
	$estados = array_combine( $k, $v );
	if( $option_none == true ){
		$pre = array( $option_none_key => $option_none_value );
		$estados = $pre + $estados;
	}
	return $estados;
}


/**
 * ==================================================
 * CONVERSOR DE TAMANHO DE ARQUIVO ==================
 * ==================================================
 * @link http://stackoverflow.com/a/2510459
 * 
 */
function boros_format_bytes($bytes, $precision = 2) { 
	$units = array('B', 'KB', 'MB', 'GB', 'TB'); 

	$bytes = max($bytes, 0); 
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
	$pow = min($pow, count($units) - 1); 

	$bytes /= pow(1024, $pow);
	//$bytes /= (1 << (10 * $pow)); 

	return round($bytes, $precision) . ' ' . $units[$pow]; 
} 



/**
 * ==================================================
 * FORMAT_MONEY =====================================
 * ==================================================
 * Habilitar o money_format() para windows, pois depende do sistema operacional
 * 
 * That it is an implementation of the function money_format for the 
 * platforms that do not it bear.  
 * 
 * The function accepts to same string of format accepts for the 
 * original function of the PHP.  
 * 
 * (Sorry. my writing in English is very bad)  
 * 
 * The function is tested using PHP 5.1.4 in Windows XP 
 * and Apache WebServer. 
 */
if( !function_exists('money_format') ){
function money_format($format, $number){
	$regex  = '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?'. 
			  '(?:#([0-9]+))?(?:\.([0-9]+))?([in%])/'; 
	if (setlocale(LC_MONETARY, 0) == 'C') { 
		setlocale(LC_MONETARY, ''); 
	} 
	$locale = localeconv(); 
	preg_match_all($regex, $format, $matches, PREG_SET_ORDER); 
	foreach ($matches as $fmatch) { 
		$value = floatval($number); 
		$flags = array( 
			'fillchar'  => preg_match('/\=(.)/', $fmatch[1], $match) ? 
						   $match[1] : ' ', 
			'nogroup'   => preg_match('/\^/', $fmatch[1]) > 0, 
			'usesignal' => preg_match('/\+|\(/', $fmatch[1], $match) ? 
						   $match[0] : '+', 
			'nosimbol'  => preg_match('/\!/', $fmatch[1]) > 0, 
			'isleft'    => preg_match('/\-/', $fmatch[1]) > 0 
		); 
		$width      = trim($fmatch[2]) ? (int)$fmatch[2] : 0; 
		$left       = trim($fmatch[3]) ? (int)$fmatch[3] : 0; 
		$right      = trim($fmatch[4]) ? (int)$fmatch[4] : $locale['int_frac_digits']; 
		$conversion = $fmatch[5]; 

		$positive = true; 
		if ($value < 0) { 
			$positive = false; 
			$value  *= -1; 
		} 
		$letter = $positive ? 'p' : 'n'; 

		$prefix = $suffix = $cprefix = $csuffix = $signal = ''; 

		$signal = $positive ? $locale['positive_sign'] : $locale['negative_sign']; 
		switch (true) { 
			case $locale["{$letter}_sign_posn"] == 1 && $flags['usesignal'] == '+': 
				$prefix = $signal; 
				break; 
			case $locale["{$letter}_sign_posn"] == 2 && $flags['usesignal'] == '+': 
				$suffix = $signal; 
				break; 
			case $locale["{$letter}_sign_posn"] == 3 && $flags['usesignal'] == '+': 
				$cprefix = $signal; 
				break; 
			case $locale["{$letter}_sign_posn"] == 4 && $flags['usesignal'] == '+': 
				$csuffix = $signal; 
				break; 
			case $flags['usesignal'] == '(': 
			case $locale["{$letter}_sign_posn"] == 0: 
				$prefix = '('; 
				$suffix = ')'; 
				break; 
		} 
		if (!$flags['nosimbol']) { 
			$currency = $cprefix . 
						($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']) . 
						$csuffix; 
		} else { 
			$currency = ''; 
		} 
		$space  = $locale["{$letter}_sep_by_space"] ? ' ' : ''; 

		$value = number_format($value, $right, $locale['mon_decimal_point'], 
				 $flags['nogroup'] ? '' : $locale['mon_thousands_sep']); 
		$value = @explode($locale['mon_decimal_point'], $value); 

		$n = strlen($prefix) + strlen($currency) + strlen($value[0]); 
		if ($left > 0 && $left > $n) { 
			$value[0] = str_repeat($flags['fillchar'], $left - $n) . $value[0]; 
		} 
		$value = implode($locale['mon_decimal_point'], $value); 
		if ($locale["{$letter}_cs_precedes"]) { 
			$value = $prefix . $currency . $space . $value . $suffix; 
		} else { 
			$value = $prefix . $value . $space . $currency . $suffix; 
		} 
		if ($width > 0) { 
			$value = str_pad($value, $width, $flags['fillchar'], $flags['isleft'] ? 
					 STR_PAD_RIGHT : STR_PAD_LEFT); 
		} 

		$format = str_replace($fmatch[0], $value, $format); 
	} 
	return $format; 
}
}









