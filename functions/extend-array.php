<?php
/**
 * ==================================================
 * PHP EXTENDED :: ARRAYS ===========================
 * ==================================================
 * Apenas functions relacionadas a arrays
 * 
 * 
 * @todo revisar quais são mais eficientes e remover as desnecessárias >>>> BUSCAR NOS TRABALHOS ANTIGOS SE ESTÃO EM USO!!!! e passar para deprecated >>>> array_non_empty_items() é mais usado, criar novo wrapper como boros_trim_array()
 */



/**
 * PARSE ARGS
 * Clone da função core, porém permitindo o uso de arrays multidimensionais, e permitindo a junção de mais de um array.
 * IMPORTANTE: a declaração de argumentos é inversa da função original
 * 
 */
function boros_parse_args( $defaults = '', $args ){
	if ( is_object( $args ) )
		$r = get_object_vars( $args );
	elseif ( is_array( $args ) )
		$r =& $args;
	else
		wp_parse_str( $args, $r );
	
	if ( is_array( $defaults ) ){
		return boros_array_merge_recursive( $defaults, $r );
	}
	return $r;
}



/**
 * ==================================================
 * ARRAY COLUMN =====================================
 * ==================================================
 * Pegar apenas uma key de um array associativo. Apenas para array simples.
 * 
 * @link http://stackoverflow.com/a/163491
 * @todo ampliar para array multidimensional
 */
if( !function_exists('array_column') ){
function array_column( $array, $column ){
	$ret = array();
	foreach ($array as $row) $ret[] = $row[$column];
	return $ret;
}
}



/**
 * ==================================================
 * INTERVALO NUMÉRICO COM VÍRGULA E TRAÇO ===========
 * ==================================================
 * Calcular intervalos semelhannte aos de impressoras.
 * 
 * @link http://stackoverflow.com/questions/7698664/converting-a-range-or-partial-array-in-the-form-3-6-or-3-6-12-into-an-arra
 * 
 */
function boros_number_range( $list ){
	$array = explode(',', $list);
	$return = array();
	foreach ($array as $value) {
		$explode2 = explode('-', $value);
		if (count($explode2) > 1) {
			$range = range($explode2[0], $explode2[1]); 
			$return = array_merge($return, $range);
		} else {
			$return[] = (int) $value;
		}
	}
	return $return;
}



/**
 * IMPLODE WITH KEY
 * Gerar uma string a partir de array, com divisor de key:value e itens
 * 
 * @link http://www.php.net/manual/pt_BR/function.implode.php#55994
 */
function implode_with_key($assoc, $inglue = '=', $outglue = '&'){
	$return = null;
	foreach ($assoc as $tk => $tv) $return .= $outglue.$tk.$inglue.$tv;
	return substr($return,1);
}

/**
 * IMPLODE/JOIN COM ASPAS
 * Transformar array em lista separada por vírgula, com cada item fechado em aspas.
 * 
 */
function boros_quoted_list( $array, $single_quotes = true ){
	if( $single_quotes ){
		$string = implode( "', '", $array );
		return "'{$string}'";
	}
	else{
		$string = implode( '", "', $array );
		return '"'.$string.'"';
	}
}

/**
 * É UM ARRAY ASSOCIATIVO?
 * 
 * @param array $array
 * @param bool
 * @link http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential/4254008#4254008
 */
function is_assoc_array( $array ){
	if( !is_array( $array ) )
		return false;
	return (bool)count(array_filter(array_keys($array), 'is_string'));
}

/**
 * Converter array numperico para associativo
 * 
 */
function numeric_array_to_assoc( $array ){
	$new = array();
	foreach( $array as $k ){
		$new[$k] = '';
	}
	return $new;
}

/**
 * ==================================================
 * TRIM ARRAY =======================================
 * ==================================================
 * Diversas funções para limpar arrays.
 * 
 * @param array $array - array a ser limpa
 * @link	http://www.jonasjohn.de/snippets/php/trim-array.htm
 * @link	http://rogerpadilla.wordpress.com/2009/08/21/remove-empty-items-from-array/
 */
 
/**
 * TRIM ARRAY VALUES ================================
 * Remover espaços nos valores apenas.
 * 
 * @param array $array
 * @link	http://www.jonasjohn.de/snippets/php/trim-array.htm
 */
function trim_array_values( $array ){
	if ( !is_array( $array ) )
		return trim( $array );
	return array_map( 'trim_array', $array );
}

/**
 * ==================================================
 * TRIM ARRAY =======================================
 * Remover itens vazios em arrays e também espaços nos valores scalares.
 * A function trim_array() é apenas um wrapper para a chamada da class.
 * 
 * @param array $array
 * @todo Pesquisar se é possível melhorar e remover ::array_has_empty_value()
 * @link	http://www.jonasjohn.de/snippets/php/trim-array.htm
 */
function trim_array( $array ){
	$tarray = new boros_trim_array( $array );
	return $tarray->value();
}
class boros_trim_array {
	var $arr = array();
	
	function __construct( $arr ){
		$this->arr = $arr;
		// É necessário essa verificação, pois num primeiro momento em que ::trim_array() é executado, ele poderá limpar um item que já foi registrado em $arr
		// Por isso é feito um verificação constante em $arr até não seja encontrado nenhum item vazio.
		while( $this->array_has_empty_value( $this->arr ) == true ){
			$this->arr = $this->trim_array( $this->arr );
		}
	}
	
	/**
	 * Retornar a array limpa
	 * 
	 */
	function value(){
		return $this->arr;
	}
	
	/**
	 * Verificar se o array possui valor vazio em qualquer nível.
	 * 
	 */
	function array_has_empty_value( $arr ){
		if ( !is_array( $arr ) )
			return $arr;
		$empty_items = false;
		
		foreach( $arr as $key => $value ){
			if( is_array($value) ){
				if( count($value) > 0 )
					$empty_items = $this->array_has_empty_value( $value );
				else
					return true;
			}
		}
		return $empty_items;
	}
	
	/**
	 * Limpar array.
	 * 
	 */
	function trim_array( $arr ){
		if ( !is_array( $arr ) )
			return $arr;
		
		$non_empty_items = array();
		
		foreach( $arr as $key => $value ){
			if( is_scalar($value) ){
				$value = trim($value);
				if(!empty($value))
					$non_empty_items[$key] = $this->trim_array( $value );
			}
			else{
				if( count($value) > 0 )
					$non_empty_items[$key] = $this->trim_array( $value );
			}
		}
		return $non_empty_items;
	}
}
/**
 * 
 */
function boros_trim_array( $arr ){
	return array_non_empty_items( $arr );
}
function array_non_empty_items( $arr ){
	if( !is_array( $arr) ){
		return;
	}
	foreach( $arr as $k => $v ){
		if( empty($v) ){
			unset( $arr[$k] );
		}
		else{
			if( is_array( $v ) ){
				$arr[$k] = array_non_empty_items( $v );
			}
		}
		if( empty($arr[$k]) )
			unset( $arr[$k] );
	}
	return $arr;
}
/**
class array_non_empty_items {
	var $raw = array();
	var $non_empty_items = array();
	
	function __construct( $input ){
		pre($input, 1);
		$this->raw = $input;
		$a = $this->trim( $this->raw );
		pre($a, 'AAAA');
	}
	
	function trim( $arr ){
		if( !is_array( $this->raw ) ){
			return;
		}
		foreach( $arr as $k => $v ){
			if( empty($v) ){
				unset( $arr[$k] );
			}
			else{
				if( is_array( $v ) ){
					$arr[$k] = $this->trim( $v );
				}
			}
			if( empty($arr[$k]) )
				unset( $arr[$k] );
		}
		return $arr;
	}
}
/**/


/**
 * ==================================================
 * ARRAY INSERT =====================================
 * ==================================================
 * Inserir item(s) em qualquer posição do array
 * 
 * 
 * @param    array         $array       array a modificar
 * @param    string        $insert      item para incluir
 * @param    int/string    $position    posição do elemento, integer para indexed e string para associative
 * @link http://www.mrleong.net/143/php-array-union/ indexed
 * @link http://stackoverflow.com/a/1783125 associative
 */
function array_insert( $array, $insert, $position ){
	if( is_assoc_array($array) ){
		$index = array_search($position, array_keys($array)) + 1;
		return array_slice($array, 0, $index, true) + $insert + array_slice($array, $index, NULL, true);
	}
	else{
		$first = array_slice( $array, 0, $position );
		$second = array_slice( $array, $position );
		return array_unique( array_merge($first, $insert, $second) );
	}
}

/**
 * ==================================================
 * IN ARRAY RECURSIVE ===============================
 * ==================================================
 * in_array() em array multidimensional
 * 
 * @link http://stackoverflow.com/questions/4128323/in-array-and-multidimensional-array
 * @return bool true|false
 */
function in_array_r( $needle, $array ){
	foreach( $array as $item ) {
		if ( $item === $needle || ( is_array($item) && in_array_r($needle, $item) ) ){
			return true;
		}
	}
	return false;
}

/**
 * ==================================================
 * ARRAY KEY EXISTS MULTIDIMENSIONAL ================
 * ==================================================
 * array_key_exists() em array multidimensional
 * 
 * @return bool true|false
 * @link http://www.php.net/manual/en/function.array-key-exists.php#85184
 */
function array_key_exists_r($needle, $haystack)
{
    $result = array_key_exists($needle, $haystack);
    if ($result)
        return $result;
    foreach ($haystack as $v)
    {
        if (is_array($v) || is_object($v))
            $result = array_key_exists_r($needle, $v);
        if ($result)
        return $result;
    }
    return $result;
}

/**
 * ==================================================
 * ARRAY KEY SEARCH MULTIDIMENSIONAL ================
 * ==================================================
 * Retorna o sub array dentro de um array multidimensional com a chave requerida
 * 
 */
function array_key_search_r( $search, $array ){
	foreach( $array as $key => $value ){
		if( $key === $search ){
			$find = $array[$key];
			return $find;
		}
		if( is_array($value) ){
			$find = array_key_search_r( $search, $value );
			if( $find )
				return $find;
		}
	}
	return false;
}
function array_search_r( $search, $array ){
	foreach( $array as $key => $value ){
		if( $value === $search ){
			$find = $array;
			return $find;
		}
		if( is_array($value) ){
			$find = array_search_r( $search, $value );
			if( $find )
				return $find;
		}
	}
}


/**
 * Busca em array associativo multidimensional, buscando ambos key e value
 * 
 * @link http://stackoverflow.com/a/1019126
 */
function array_search_kv( $key, $value, $array ){
	if( is_array($array) ){
		if( isset($array[$key]) && $array[$key] == $value ){
			return $array;
		}
		foreach( $array as $subarray ){
			$find = array_search_kv( $key, $value, $subarray );
			if( $find )
				return $find;
		}
	}
}

function array_parent_k( $array, $need_k, $need_v, $parent_k ){
	$parent = new array_parent_k( $array, $need_k, $need_v, $parent_k );
	return $parent->array_parent_k;
}
class array_parent_k {
	// todos que tiverem $parent_k
	var $itens = array();
	var $array_parent_k = false;
	
	function __construct( $array, $need_k, $need_v, $parent_k ){
		$this->set_itens($array, $parent_k);
		$this->find( $need_k, $need_v );
	}
	
	function set_itens( $array, $parent_k ){
		if( is_array($array) ){
			foreach( $array as $key => $value ){
				if( is_array($value) ){
					if( isset($value[$parent_k]) )
						$this->itens[] = $value;
					$this->set_itens($value, $parent_k);
				}
			}
		}
	}
	
	function find( $need_k, $need_v ){
		foreach( $this->itens as $item ){
			$find = array_search_kv( $need_k, $need_v, $item );
			if( !is_null($find) and $find != $item ){
				$this->array_parent_k = $item;
			}
		}
	}
}
function array_parent_kv( $array, $need_k, $need_v, $parent_k, $parent_v ){
	$parent = new array_parent_kv( $array, $need_k, $need_v, $parent_k, $parent_v );
	return $parent->array_parent;
}
class array_parent_kv {
	// todos que tiverem $parent_k
	var $itens = array();
	var $array_parent = false;
	
	function __construct( $array, $need_k, $need_v, $parent_k, $parent_v ){
		$this->set_itens($array, $parent_k, $parent_v);
		$this->find( $need_k, $need_v );
	}
	
	function find( $need_k, $need_v ){
		foreach( $this->itens as $item ){
			$find = array_search_kv( $need_k, $need_v, $item );
			if( !is_null($find) and $find != $item ){
				$this->array_parent = $item;
			}
		}
	}
	
	function set_itens( $array, $parent_k, $parent_v ){
		if( is_array($array) ){
			foreach( $array as $key => $value ){
				if( is_array($value) ){
					if( isset($value[$parent_k]) and $value[$parent_k] == $parent_v ) 
						$this->itens[] = $value;
					
					$this->set_itens($value, $parent_k, $parent_v);
				}
			}
		}
	}
}

/**
 * ==================================================
 * SORT ARRAY BY ARRAY ==============================
 * ==================================================
 * 
 * 
 */
class sort_array_by_array {
	var $sortorder = array();
	var $field = 'ID';
	var $sorted = array();
	
	function __construct( $array, $sortorder, $field = 'ID' ){
		$this->sortorder = $sortorder;
		$this->field = $field;
		usort($array, array( $this , 'compare' ) );
		$this->sorted = $array;
	}
	
	function compare( $a, $b ){
		$cmpa = array_search( $a[$this->field], $this->sortorder );
		$cmpb = array_search( $b[$this->field], $this->sortorder );
		return ( $cmpa > $cmpb ) ? 1 : -1;
	}
}
// helper function
function sort_array_by_array( $array, $sortorder, $field = 'ID' ){
	$arr = new sort_array_by_array( $array, $sortorder, $field );
	return $arr->sorted;
}



/**
 * ==================================================
 * KSORT RECURSIVE ==================================
 * ==================================================
 * 
 * ksort() recursive
 * @link https://gist.github.com/cdzombak/601849
 * 
 */
function ksortRecursive( &$array, $sort_flags = SORT_REGULAR ){
	if(!is_array($array)){
		return false;
	}
	ksort( $array, $sort_flags );
	foreach( $array as &$arr ){
		ksortRecursive( $arr, $sort_flags );
	}
	return true;
}



/**
 * ==================================================
 * MULTI SORT ARRAY =================================
 * ==================================================
 * Sort array por várias colunas, baseado em array associativo:
<code>
$fields = array(
	'ID' => 'ASC',
	'name' => 'DESC',
	'post_content' => 'ASC',
);
</code>
 * 
 * 
 */
function multi_sort_array( $array, $fields ){
	$arr = new multi_sort_array( $array, $fields );
	return $arr->ordered;
}
class multi_sort_array {
	public $fields;
	public $ordered;
	
	function __construct( $array, $fields ){
		$this->fields = $fields;
		usort($array, array( $this , 'compare' ) );
		$this->ordered = $array;
	}
	
	function compare( $a, $b ){
		$cmp = 0;
		foreach($this->fields as $field => $order){
			if( $order == 'ASC' ){
				if($cmp == 0) $cmp = strnatcmp($a[$field], $b[$field]);
			}
			else{
				if($cmp == 0) $cmp = strnatcmp($b[$field], $a[$field]);
			}
		}
		return $cmp;
	}
}

/**
 * MULTIDIMENSIONAL ARRAY MAP
 * Aplicar callback em array multidimensional
 * 
 * @link http://www.php.net/manual/en/function.array-map.php#70710
 */
function multidimensional_array_map( $func, $arr ){
	if( !is_array($arr) )
		return $arr;
	$newArr = array();
	foreach( $arr as $key => $value ){
		if( is_scalar( $value ) ){
			$nvalue = call_user_func( $func, $value );
		}
		else{
			$nvalue = multidimensional_array_map( $func, $value );
		}
		$newArr[ $key ] = $nvalue;
	}
	return $newArr;
}

/**
 * ARRAY MERGE RECURSIVE
 * A função core do php, não mescla corretamente os valores em sub arrays.
 * 
 * @link http://www.php.net/manual/en/function.array-merge-recursive.php#104145
 */
function boros_array_merge_recursive(){
	if(func_num_args() < 2){
		trigger_error(__FUNCTION__ .' needs two or more array arguments', E_USER_WARNING);
		return;
	}
	$arrays = func_get_args();
	$merged = array();
	while($arrays){
		$array = array_shift($arrays);
		if(!is_array($array)){
			trigger_error(__FUNCTION__ .' encountered a non array argument', E_USER_WARNING);
			return;
		}
		if(!$array){
			continue;
		}
		foreach($array as $key => $value){
			if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])){
				$merged[$key] = call_user_func(__FUNCTION__, $merged[$key], $value);
			}
			else{
				$merged[$key] = $value;
			}
			/** Versão com append nos arrays numéricos. Revisar para decidir se será mantido ou não *
			if(is_string($key)){
				if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])){
					$merged[$key] = call_user_func(__FUNCTION__, $merged[$key], $value);
				}
				else{
					$merged[$key] = $value;
				}
			}
			else{
				$merged[] = $value;
			}
			/**/
		}
	}
	return $merged;
}






