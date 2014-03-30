<?php
/**
 * ==================================================
 * FUNCTIONS PARA TESTES AUTOMATIZADOS ==============
 * ==================================================
 * 
 */


/**
 * ==================================================
 * GENERATES ========================================
 * ==================================================
 * Function simples para gerar dados aleatórios, podendo ser usadas diretamente sem problemas
 * 
 */
function _rand_string( $length = 10, $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' ){
	$str = '';
	$count = strlen($charset);
	while( $length-- ){
		$str .= $charset[mt_rand(0, $count-1)];
	}
	return $str;
}

function _rand_email( $pre = '', $pos = '' ){
	$pre = ( $pre == '' ) ? _rand_string( mt_rand(2, 10), 'abcdefghijklmnopqrstuvwxyz' ) : $pre;
	$pos = ( $pos == '' ) ? _rand_string( mt_rand(2, 10), 'abcdefghijklmnopqrstuvwxyz' ) : $pos;
	return "{$pre}@{$pos}.com";
}

function _rand_tel( $with_ddd = true, $with_symbols = true, $format = 'string' ){
	$ddd = '';
	if( $with_ddd == true ){
		if( is_int($with_ddd) ){
			$dddn = $with_ddd;
		}
		else{
			$ddds = array( 11, 12, 13, 14, 15, 16, 17, 18, 19, 21, 22, 24, 27, 28, 31, 32, 33, 34, 35, 37, 38, 41, 42, 43, 44, 45, 46, 47, 48, 49, 51, 53, 54, 55, 61, 61, 62, 63, 64, 65, 66, 67, 68, 69, 71, 73, 74, 75, 77, 79, 81, 82, 83, 84, 85, 86, 87, 88, 89, 91, 92, 93, 94, 95, 96, 97, 98, 99 );
			$ddd_rand = mt_rand( 0, count($ddds) - 1 );
			$dddn = $ddds[$ddd_rand];
		}
		$ddd = ($with_symbols == true) ? "({$dddn}) " : "{$dddn} ";
	}
	
	$number_1 = mt_rand( 1000, 9999 );
	$number_2 = mt_rand( 1000, 9999 );
	if( $with_symbols == true )
		$number = "{$number_1}-{$number_2}";
	else
		$number = "{$number_1}{$number_2}";
	
	if( $format == 'string' ){
		return "{$ddd}{$number}";
	}
	else {
		return array($dddn, $number);
	}
	
}

function _rand_cep( $with_sep = true ){
	$number_1 = mt_rand( 10000, 99999 );
	$number_2 = mt_rand( 100, 999 );
	if( $with_sep == true )
		return "{$number_1}-{$number_2}";
	else
		return "{$number_1}{$number_2}";
}

function _rand_estado( $type = 'sigla' ){
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
	$states_rand = mt_rand( 0, count($siglas) - 1 );
	if( $type == 'sigla' ){
		$estado = $siglas[$states_rand];
	}
	elseif( $type == 'extenso' ){
		$estado = $extenso[$states_rand];
	}
	elseif( $type == 'ambos' ){
		$estado = array(
			$siglas[$states_rand],
			$extenso[$states_rand],
		);
	}
	return $estado;
}

function _rand_cidade( $uf = 'AC' ){
	include_once('populate_cities.php');
	$ufs = load_uf_list();
	if( array_key_exists($uf, $ufs) ){
		$municipios = load_cities_list( $uf );
		$rand_mun = array_rand($municipios);
		return $municipios[$rand_mun];
	}
	else{
		return "O estado '{$uf}' não existe";
	}
}

function _rand_profissao(){
	include_once('populate_occupation.php');
	return load_occupations( $format = 'unique' );
}

function _rand_companies(){
	include_once('populate_occupation.php');
	return load_companies();
}

function _rand_cpf(){
	$cpf = new RandCpfCnpj();
	return $cpf->cpf();
}

function _rand_cnpj(){
	$cnpj = new RandCpfCnpj();
	return $cnpj->cnpj();
}

function _rand_birth_date( $start_date = '1940-01-01 00:00:00', $end_date = '1994-01-01 00:00:00' ){
	$min = strtotime($start_date);
    $max = strtotime($end_date);
	$val = rand($min, $max);
	$birth_date = array(
		'Y' => date('Y', $val),
		'm' => date('m', $val),
		'd' => date('d', $val),
	);
	return $birth_date;
}

function rand_lipsum( $size = 100, $mode = 'plain' ){
	if( !class_exists('LoremIpsumGenerator') ){
		require_once('libs/LoremIpsum.class.php');
	}
	$lipsum = new LoremIpsumGenerator;
	return $lipsum->getContent($size, $mode, false);
}

/**
 * GENERATE CPF
 * @link http://www.dcon.com.br/jd.comment/cpf_cnpf.php
 * 
 */
class RandCpfCnpj {
	var $cpf = '';
	var $cnpj = '';
	
	function __construct( $type = 'cpf' ){
		if( $type == 'cpf' ){
			$this->cpf = $this->cpf();
		}
		else{
			$this->cnpj = $this->cnpj();
		}
	}
	
	function mod($dividendo,$divisor){
		return round($dividendo - (floor($dividendo/$divisor)*$divisor));
	}
	
	function cpf($compontos = false){
		$n1 = mt_rand(0,9);
		$n2 = mt_rand(0,9);
		$n3 = mt_rand(0,9);
		$n4 = mt_rand(0,9);
		$n5 = mt_rand(0,9);
		$n6 = mt_rand(0,9);
		$n7 = mt_rand(0,9);
		$n8 = mt_rand(0,9);
		$n9 = mt_rand(0,9);
		$d1 = $n9*2+$n8*3+$n7*4+$n6*5+$n5*6+$n4*7+$n3*8+$n2*9+$n1*10;
		$d1 = 11 - ( $this->mod($d1,11) );
		if ( $d1 >= 10 )
		{ $d1 = 0 ;
		}
		$d2 = $d1*2+$n9*3+$n8*4+$n7*5+$n6*6+$n5*7+$n4*8+$n3*9+$n2*10+$n1*11;
		$d2 = 11 - ( $this->mod($d2,11) );
		if ($d2>=10) { $d2 = 0 ;}
		$retorno = '';
		if ($compontos==1) {$retorno = ''.$n1.$n2.$n3.".".$n4.$n5.$n6.".".$n7.$n8.$n9."-".$d1.$d2;}
		else {$retorno = ''.$n1.$n2.$n3.$n4.$n5.$n6.$n7.$n8.$n9.$d1.$d2;}
		return $retorno;
	}

	function cnpj($compontos = true){
		$n1 = mt_rand(0,9);
		$n2 = mt_rand(0,9);
		$n3 = mt_rand(0,9);
		$n4 = mt_rand(0,9);
		$n5 = mt_rand(0,9);
		$n6 = mt_rand(0,9);
		$n7 = mt_rand(0,9);
		$n8 = mt_rand(0,9);
		$n9 = 0;
		$n10= 0;
		$n11= 0;
		$n12= 1;
		$d1 = $n12*2+$n11*3+$n10*4+$n9*5+$n8*6+$n7*7+$n6*8+$n5*9+$n4*2+$n3*3+$n2*4+$n1*5;
		$d1 = 11 - ( $this->mod($d1,11) );
		if ( $d1 >= 10 )
		{ $d1 = 0 ;
		}
		$d2 = $d1*2+$n12*3+$n11*4+$n10*5+$n9*6+$n8*7+$n7*8+$n6*9+$n5*2+$n4*3+$n3*4+$n2*5+$n1*6;
		$d2 = 11 - ( $this->mod($d2,11) );
		if ($d2>=10) { $d2 = 0 ;}
		$retorno = '';
		if ($compontos==1) {$retorno = ''.$n1.$n2.".".$n3.$n4.$n5.".".$n6.$n7.$n8."/".$n9.$n10.$n11.$n12."-".$d1.$d2;}
		else {$retorno = ''.$n1.$n2.$n3.$n4.$n5.$n6.$n7.$n8.$n9.$n10.$n11.$n12.$d1.$d2;}
		return $retorno;
	}
}



/**
 * ==================================================
 * GERADOR DE NOMES E SOBRENOMES RANDÔNMICOS ========
 * ==================================================
 * 
 * 
 * 
 * @TODO: separar os prenomes em masculinos e femininos para melhorar a criação de nomes compostos
 * @TODO: possibilitar aplicar o DDD correspondente à cidade escolhida
 * @TODO: possibilitar aplicar o CEP correspondente à cidade escolhida
 * @TODO: possibilitar adicionar metas e options via methods, exemplo $profile->add_data($array);
 */
class ProfileGen {
	
	var $first_names;
	
	var $last_names;
	
	var $alpha = array( 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z' );
	
	var $mail_services = array(
		'gmail.com',
		'yahoo.com.br',
		'hotmail.com',
	);
	
	// rua e avenida duplicados para aumentar o peso
	var $adrress_prefixes = array(
		'rua',
		'rua',
		'rua',
		'rua',
		'rua',
		'avenida',
		'avenida',
		'avenida',
		'alameda',
		'travessa',
		'praça',
		'largo',
		'beco',
		'ladeira',
		'viaduto',
		'fazenda',
	);
	
	var $complements = array(
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'apt',
		'apt',
		'apt',
		'fundos',
		'sobreloja',
		'bloco',
		'vila',
	);
	
	var $localhost;
	var $localhost_email;
	var $localhost_email_prefix;
	var $localhost_service;
	
	function __construct(){
		include_once('populate_names.php');
		include_once('populate_cities.php');
		$this->first_names = load_first_names();
		$this->last_names = load_last_names();
	}
	
	/**
	 * NOMES E SOBRENOMES
	 * 
	 */
	function name( $type = 'first' ){
		$rand_alpha = array_rand( $this->alpha );
		//echo "({$this->alpha[$rand_alpha]})";
		
		if( $type == 'first' )
			$letter_group = $this->first_names[$this->alpha[$rand_alpha]];
		else
			$letter_group = $this->last_names[$this->alpha[$rand_alpha]];
		
		$rand_name = array_rand($letter_group);
		//echo "({$rand_name})";
		return $letter_group[$rand_name];
	}
	
	function first_name(){
		//echo 'first';
		return $this->name('first');
	}
	
	function last_name(){
		//echo 'last';
		return $this->name('last');
	}
	
	function full_name( $first_number = 1, $last_number = 1 ){
		$name = array();
		for( $i = 1; $first_number >= $i; $i++ ){
			$name[] = $this->first_name();
		}
		for( $i = 1; $last_number >= $i; $i++ ){
			$name[] = $this->last_name();
		}
		//print_r($name);
		
		return implode( ' ', $name );
	}
	
	function rand_number_full_name( $first_number = 2, $last_number = 2, $ease = true ){
		if( $ease == true ){
			$first = $this->distribution( $first_number );
			$last = $this->distribution( $last_number );
		}
		else{
			$first = mt_rand( 1, $first_number );
			$last = mt_rand( 1, $last_number );
		}
		return $this->full_name( $first, $last );
	}
	
	function rand_number_name( $type = 'first', $number = 2, $ease = true ){
		if( $ease == true )
			$rand_number = $this->distribution( $number );
		else
			$rand_number = mt_rand( 1, $number );
		
		$name = array();
		$function = "{$type}_name";
		for( $i = 1; $rand_number >= $i; $i++ ){
			$name[] = $this->$function();
		}
		return implode( ' ', $name );
	}
	
	/**
	 * Function de balanceamento.
	 * Irá gerar um array com mais números baixos que altos, facilitando a geração de um número randômico em uma determinada faixa de valores, 
	 * porém com maiores chances de gerar números baixos.
	 * 
	 */
	function distribution( $n ){
		$numbers = array();
		for( ; 1 <= $n; $n-- ){
			for( $i = $n; 1 <= $i; $i-- ){
				$numbers[] = $i;
			}
		}
		//echo '<pre>';
		//print_r($numbers);
		//echo '</pre>';
		$rand = array_rand($numbers);
		return $numbers[$rand];
	}
	
	/**
	 * EMAIL
	 * 
	$args = array(
		'first_name' => string opt,
		'last_name' => string opt,
		'full_name' => string opt,
		'service' => string opt,
	);
	 * 
	 */
	function email( $args ){
		//pre($args);
		// full name, apenas gera um nome emendado por pontos + lowercase
		if( isset($args['full_name']) ){
			$args['full_name'] = str_replace('  ', ' ', $args['full_name']);
			$name = explode(' ', $args['full_name']);
		}
		// nome e sobrenome separados: gera o primiero nome com abreviatura caso seja maior que 1
		else{
			$first = explode(' ', $args['first_name']);
			$first_name = $first[0];
			if( count($first) > 1 ){
				unset( $first[0] );
				$first = array_values($first);
				//pre($first, '$first');
				foreach( $first as $initial )
					$first_name .= $initial[0];
			}
			$name = str_replace( '  ', ' ', "{$first_name} {$args['last_name']}" );
			$name = explode(' ', $name);
		}
		
		// definir serviço de email
		if( isset($args['service']) ){
			$service = $args['service'];
		}
		else{
			$rand_mail = array_rand( $this->mail_services );
			$service = $this->mail_services[$rand_mail];
		}
		
		//pre($name, '$name');
		//pre($service, '$service');
		//$full_name = implode( '.', $name );
		//pre($full_name);
		//$format_name = $full_name;
		$format_name = strtolower(sanitize_user(implode( '.', $name )));
		//pre( $format_name, '$format_name' );
		
		return "{$format_name}@{$service}";
	}
	
	function rand_address(){
		$prefixes = count($this->adrress_prefixes) - 1;
		$name = $this->rand_number_full_name();
		
		$r = mt_rand(0, mt_rand(0, $prefixes));
		$prefix = $this->adrress_prefixes[$r];
		
		return "{$prefix} {$name}";
	}
	
	function rand_complement( $number = 100 ){
		$complements = count($this->complements) - 1;
		$r = mt_rand(0, mt_rand(0, $complements));
		$complement = $this->complements[$r];
		if( !empty($complement) ){
			$n = mt_rand(1, $number);
			$complement .= " {$n}";
		}
		
		return $complement;
	}
	
	function set_localhost_config( $full_email = false, $email_prefix = 'dev.alexkoti+', $service = 'gmail.com' ){
		$this->localhost = true;
		$this->localhost_email = $full_email;
		$this->localhost_email_prefix = $email_prefix;
		$this->localhost_service = $service;
	}
	
	/**
	 * Caso seja localhost, modificar todos os emails para enviar para a conta de testes do gmail
	 * 
	 * @TODO permitir configurar quais campos usar e qual function para criar os valores
	 * 
	 */
	function profile( $user_login = 'full_name' ){
		$profile = array();
		
		/**
		 * Dados básicos
		 * 
		 */
		$profile['first_name']        = $this->rand_number_name('first');
		$profile['last_name']         = $this->rand_number_name('last');
		$profile['name']              = "{$profile['first_name']} {$profile['last_name']}";
		$profile['full_name']         = $profile['name'];
		$profile['username']          = sanitize_user($profile['full_name']);
		$profile['user_nicename']     = sanitize_user($profile['full_name']);
		$profile['pass']              = '321';
		$profile['user_pass']         = '321';
		$profile['user_pass_confirm'] = '321';
		
		// email
		$email_args =  array(
			'first_name' => $profile['first_name'],
			'last_name' => $profile['last_name'],
		);
		$email = $this->email( $email_args );
		
		// definir email caso seja localhost
		if( $this->localhost == true ){
			// irá substituir o email
			if( $this->localhost_email != false ){
				$email = $this->localhost_email;
			}
			// adicionar prefixo
			else{
				$email_args['first_name'] = $this->localhost_email_prefix . $email_args['first_name'];
				$email_args['service'] = $this->localhost_service;
				$email = $this->email( $email_args );
			}
		}
		
		// criar o $user_login
		if( $user_login == 'full_name' ){
			$profile['user_login'] = "{$profile['first_name']} {$profile['last_name']}";
		}
		elseif( $user_login == 'email' ){
			$profile['user_login'] = $email;
		}
		elseif( $user_login == 'blank' ){
			$profile['user_login'] = '0';
		}
		else{
			$profile['user_login'] = $user_login;
		}
		
		// usermetas
		$profile['email']           = $email;
		$profile['user_email']      = $email;
		$profile['rg']              = mt_rand( 111111111, 999999999 );
		$cpf_cnpj                   = new RandCpfCnpj();
		$profile['cpf']             = $cpf_cnpj->cpf();
		
		$profile['sexo']            = array_rand( array('feminino' => 'feminino', 'masculino' => 'masculino') );
		$profile['nascimento']      = _rand_birth_date();
		$profile['data_nascimento'] = "{$profile['nascimento']['d']}/{$profile['nascimento']['m']}/{$profile['nascimento']['Y']}";
		
		$profile['endereco']        = $this->rand_address();
		$profile['complemento']     = $this->rand_complement();
		$profile['numero']          = mt_rand(1, 2000);
		$estado                     = _rand_estado('ambos');
		$profile['estado']          = $estado[1];
		$profile['uf']              = $estado[0];
		$profile['cidade']          = _rand_cidade( $profile['uf'] );
		$profile['bairro']          = $this->rand_number_name('last');
		$profile['cep']             = _rand_cep();
		
		$profile['telefone']        = _rand_tel( $with_ddd = true, $with_symbols = false, $format = 'array' );
		$profile['telefone_ddd']    = $profile['telefone'][0];
		$profile['telefone_numero'] = $profile['telefone'][1];
		$profile['telefone_format'] = "({$profile['telefone_ddd']}) {$profile['telefone_numero']}";
		
		$profile['celular']         = _rand_tel( $with_ddd = $profile['telefone_ddd'], $with_symbols = false, $format = 'array' );
		$profile['celular_ddd']     = $profile['celular'][0];
		$profile['celular_numero']  = $profile['celular'][1];
		$profile['celular_format'] = "({$profile['celular_ddd']}) {$profile['celular_numero']}";
		
		$profile['mensagem']        = rand_lipsum( mt_rand(80, 120), 'plain' );
		
		$profile['profissao']       = _rand_profissao();
		$profile['empresa']         = _rand_companies();
		$profile['cnpj']            = $cpf_cnpj->cnpj();
		return $profile;
	}
}

//add_action( 'wp_footer', 'test_profile_gen' );
function test_profile_gen(){
	$profile = new ProfileGen();
	
	pre( $profile->profile() );
	
	return;
	
	for( $i = 1; $i < 500; $i++ ){
		$uf = _rand_estado( 'sigla' );
		pre( _rand_cidade( $uf ) );
	}
	
	return;
	// rand simples
	$args = array(
		'full_name' => $profile->rand_number_full_name(),
	);
	pre( $profile->email($args) );
	
	for( $i = 1; $i < 500; $i++ ){
		// nome e sobrenome separados
		$args = array(
			'first_name' => $profile->rand_number_name('first'),
			'last_name' => $profile->rand_number_name('last'),
		);
		pre( $profile->email($args) );
	}
}


