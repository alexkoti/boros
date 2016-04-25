<?php
/**
 * 
 * 
 * IMPORTANTE:  a diferença entre validate e sanitize é que o primeiro irá retornar false, cancelando assim a gravação do valor.
 * 
 * @todo criar um modo de usar as validações em métodos estáticos.
 * @todo separar sanitize de validate
 * @todo testar em duplicate
 * @todo possibilitar ampliação
 */
class BorosValidation {
	
	/**
	 * O contexto é necessário caso alguma das validações necessitem de alguma informação que não estará disponível no momento da configuração.
	 * 
	 * option, post, taxonomy
	 * $context = array( 'type' => 'option', 'option' => 'admin_page' );
	 * $context = array( 'type' => 'post_meta', 'post_type' => 'post', 'post_id' => 1 );
	 * $context = array( 'type' => 'taxonomy', 'taxonomy' => 'tax_name', 'term' => 'term_name' );
	 * $context = array( 'type' => 'frontend', 'post_id' => 1, 'slug' => 'slug' );
	 * $context = array( 'type' => 'user', 'user_id' => 1 );
	 * 
	 * 
	 * @TODO doc
	 */
	var $context;
	
	var $validations = array();
	
	var $meta_errors = array(); // post_meta only
	var $data_errors = array(); // frontend
	var $user_errors = array(); // user_meta
	
	/**
	 * Durante alguns loops, será preciso manter uma variável acessível e que esteja fora dos argumentos da function.Por exemplo nos call_user_func para acionar os métoos de validação, não é enviado os dados
	 * do elemento, por conta da estrutura de validação padrão do wordpress, Por isso é preciso manter essa informação no atributo de classe
	 */
	var $current_element;
	
	function __construct( $context = false ){
		$this->context = $context;
	}
	
	function add( $element ){
		$this->current_element = $element;
		$vals = array();
		// adicionar validação fixa do elemento
		$vals[] = array(
			'rule' => "validate_{$element['type']}",
			'args' => false,
			'message' => false,
		);
		
		// adicionar validação de config
		if( isset($element['validate']) )
			$vals = array_merge( $vals, $element['validate'] );
		
		if( isset($element['name']) ){
			$this->validations[$element['name']]['element'] = $element;
			$this->validations[$element['name']]['rules'] = $vals;
			$this->validations[$element['name']]['remaining_rules'] = $vals;
		}
	}
	
	function verify_post_meta( $post_id, $element, $value ){
		$this->current_post = $post_id;
		//pre($this->validations[$element['name']]);
		//pre($value, "input name:{$element['name']} PRE validation");
		$newval = $value;
		if( isset($element['name']) and isset($this->validations[ $element['name'] ]) ){
			foreach( $this->validations[ $element['name'] ]['rules'] as $validation ){
				if( isset($element['duplicable']) and $element['duplicable'] == true and is_array($value) ){
					//pal('duplicable input');
					$newval = array();
					foreach( $value as $subval ){
						//pal($subval, "subval pre {$validation['rule']}");
						if( method_exists( $this, $validation['rule'] ) ){
							$subval = call_user_func( array( $this, $validation['rule']), $element['name'], $subval, $validation['args'], $validation['message'] );
						}
						//testar user function. Aceita uma chamada de classe
						elseif( function_exists( $validation['rule'] ) ){
							$subval = call_user_func( $validation['rule'], $element['name'], $subval, $validation['args'], $validation['message'] );
						}
						//pal($subval, "subval pos {$validation['rule']}");
						if( $subval != false )
							$newval[] = $subval;
					}
				}
				else{
					//pal('NOT duplicable input');
					$newval = $value;
					if( method_exists( $this, $validation['rule'] ) ){
						//pal("Método da class BorosValidation: {$validation['rule']}");
						$newval = call_user_func( array( $this, $validation['rule']), $element['name'], $value, $validation['args'], $validation['message'] );
					}
					//testar user function. Aceita uma chamada de classe
					elseif( function_exists( $validation['rule'] ) ){
						//pal("Function: {$validation['rule']}");
						$newval = call_user_func( $validation['rule'], $element['name'], $value, $validation['args'], $validation['message'] );
					}
				}
			}
		}
		//pre($newval, "input name:{$element['name']} POS validation");sep('=======================');
		return $newval;
	}
	
	/**
	 * Ao aplicar a validação, selecionar o método apropriado conforme o type($option).
	 * 
	 */
	function verify_option( $value, $option = 'test' ){
		//pre($this->validations[$option], "Validação para {$option}");
		//pre($value, "input nam:{$option} PRE validation");
		if( isset($this->validations[$option]['rules']) ){
			foreach( $this->validations[ $option ]['rules'] as $validation ){
				if( !isset($validation['args']) ){
					$validation['args'] = false;
				}
				$validation['args']['options'] = issetor($element['options'], false);
				if( isset($this->validations[ $option ]['element']['duplicable']) and $this->validations[ $option ]['element']['duplicable'] == true and is_array($value) ){
					$newval = array();
					foreach( $value as $subval ){
						//pal($subval, '$subval');
						// testar método da classe
						if( method_exists( $this, $validation['rule'] ) ){
							$result = call_user_func( array( $this, $validation['rule']), $option, $subval, $validation['args'], $validation['message'] );
						}
						//testar user function. Aceita uma chamada de classe
						elseif( function_exists( $validation['rule'] ) ){
							$result = call_user_func( $validation['rule'], $option, $subval, $validation['args'], $validation['message'] );
						}
						//pre($result, '$result');
						if( $result != false )
							$newval[] = $result;
					}
				}
				else{
					$newval = $value;
					// testar método da classe
					if( method_exists( $this, $validation['rule'] ) ){
						$newval = call_user_func( array( $this, $validation['rule']), $option, $value, $validation['args'], $validation['message'] );
					}
					//testar user function. Aceita uma chamada de classe
					elseif( function_exists( $validation['rule'] ) ){
						$newval = call_user_func( $validation['rule'], $option, $value, $validation['args'], $validation['message'] );
					}
				}
			}
		}
		//pre($newval, "input name:{$option} POS validation");sep('=======================');
		return $newval;
	}
	
	function verify_data( $element, $value ){
		//pre($element, 'ELEMENT');
		$newval = $value;
		//pal($newval, "pre validate ({$element['name']})");
		if( isset($this->validations[ $element['name'] ]) ){
			//pre($this->validations, 'VALIDATIONS');
			$this->current_element = $element;
			foreach( $this->validations[$element['name']]['rules'] as $rule => $validation ){
				if( !isset($validation['args']) ){
					$validation['args'] = false;
				}
				if( !isset($validation['message']) ){
					$validation['message'] = false;
				}
				$validation['args']['options'] = issetor($element['options'], false);
				
				/**
				 * Gambiarra para enviar argumentos sem interferir nos callbacks. Como as functions de verificação já possuem numero de argumentos fixos e estão sendo usados por 'option' e
				 * 'meta_boxes', é preciso muito cuiddado antes de modificar esse comportamento.
				 * 
				 */
				//pal($rule, 'RULE');
				//pre($validation, 'VALIDATION ' . $element['name']);
				$validation['args']['rule'] = $rule;
				
				if( method_exists( $this, $validation['rule'] ) ){
					//pal("Método da class BorosValidation: {$validation['rule']}");
					$validation['args']['object'] = $this;
					$newval = call_user_func( array( $this, $validation['rule']), $element['name'], $newval, $validation['args'], $validation['message'] );
				}
				//testar user function. Aceita uma chamada de classe
				elseif( function_exists( $validation['rule'] ) ){
					//pal("Function: {$validation['rule']}");
					$validation['args']['object'] = $this;
					$newval = call_user_func( $validation['rule'], $element['name'], $newval, $validation['args'], $validation['message'] );
				}
                unset( $this->validations[$element['name']]['remaining_rules'][$rule]);
			}
		}
		//pal($newval, "pos validate ({$element['name']})");
		return $newval;
	}
	
	function verify_user_meta( $user_id, $element, $value ){
		//pre($value, "{$element['name']} PRE");
		$newval = $value;
		if( isset($this->validations[ $element['name'] ]) ){
			$this->current_element = $element;
			foreach( $this->validations[ $element['name'] ]['rules'] as $rule => $validation ){
				if( !isset($validation['args']) ){
					$validation['args'] = false;
				}
				if( !isset($validation['message']) ){
					$validation['message'] = false;
				}
				$validation['args']['options'] = issetor($element['options'], false);
				
				/**
				 * Gambiarra para enviar argumentos sem interferir nos callbacks. Como as functions de verificação já possuem numero de argumentos fixos e estão sendo usados por 'option' e
				 * 'meta_boxes', é preciso muito cuiddado antes de modificar esse comportamento.
				 * 
				 */
				//pal($rule);
				$validation['args']['rule'] = $rule;
				//pre($validation['args'], 'Validation ARGS');
				
				if( method_exists( $this, $validation['rule'] ) ){
					//pal("Método da class BorosValidation: {$validation['rule']}");
					//pre( $newval, "{$validation['rule']} PRE" );
					$validation['args']['object'] = $this;
					$newval = call_user_func( array( $this, $validation['rule']), $element['name'], $newval, $validation['args'], $validation['message'] );
				}
				//testar user function. Aceita uma chamada de classe
				elseif( function_exists( $validation['rule'] ) ){
					//pal("Function: {$validation['rule']}");
					//pre( $newval, "{$validation['rule']} PRE" );
					$validation['args']['object'] = $this;
					$newval = call_user_func( $validation['rule'], $element['name'], $newval, $validation['args'], $validation['message'] );
				}
			}
		}
		//pre( $newval, "{$element['name']} FINAL");
		return $newval;
	}
	
	/**
	 * Caso seja split text, é preciso manter os valores em array
	 * 
	 * @todo: validar os valores do split
	 */
	function validate_text( $option, $value, $args, $message ){
		//pal($option, '$option');
		//pre($this->current_element, 'CURRENT_ELEMENT');
		//pal( 'VALIDATE TEXT METHOD CALL' );
		//pre($option, '$option');
		//pre($value, '$value');
		//pre($args, '$args');
		//pre($message, '$message');
		//pre(func_get_args(), 'validate_text');
		
		// não é campo split, validar normalmente
		if( !isset($this->current_element['options']['split']) ){
			if( !is_string( $value ) ){
				$value = '';
			}
		}
		return $value;
	}
	
	/**
	 * É preciso verificar caso o campo possua a extensão outros
	 * 
	 */
	function other_field( $name, $value, $args, $message ){
		if( $args['rule'] == 'other_field' and !empty($value) ){
			if( $value[0] == 'other' and !isset($value[1]) ){
				$error = array(
					'name' => $name,
					'message' => $message,
					'type' => 'error',
				);
				$this->data_errors[$name][$args['rule']] = $error;
			}
		}
		return $value;
	}
	
	/**
	 * Filtro parar remover todas as tags de html
	 * 
	 */
	function strip_tags( $name, $value, $args, $message ){
		return wp_strip_all_tags( $value );
	}
	
	
	function required( $name, $value, $args, $message ){
		$error = false;
		
		// limpar array com itens em branco
		if( is_array( $value ) ){
			$value = boros_trim_array( $value );
			
			// caso seja um campo de texto split, verificar se cada pedaço é requerido
			if( isset($this->current_element['options']['split']) ){
				foreach( $this->current_element['options']['split'] as $sub_name => $sub_config ){
					if( isset($sub_config['required']) or isset($this->current_element['validate']['required']) and (!isset($value[$sub_name]) or !boros_check_empty_var($value[$sub_name])) ){
						$error = true;
					}
				}
			}
		}
		
		if( !boros_check_empty_var( $value ) ){
			$error = true;
		}
		
		if( $error == true ){
			if( $this->context['type'] == 'frontend' ){
				$error = array(
					'name' => $name,
					'message' => $message,
					'type' => 'error'
				);
				$this->data_errors[$name][$args['rule']] = $error;
			}
		}
		return $value;
	}
	
	/**
	 * Not default
	 * Não permitir o valor default
	 * 
	 */
	function not_default( $name, $value, $args, $message ){
		//pre( $name, 'name' );
		//pre( $value, 'value' );
		//pre( $args, 'args' );
		//pre( $message, 'message' );
		if( $value == $args['default'] ){
			$error = array(
				'name' => $name,
				'message' => $message,
				'type' => 'error'
			);
			$this->data_errors[$name][$args['rule']] = $error;
		}
		return $value;
	}
	
	/**
	 * Recaptcha
	 * 
	 */
	function validate_recaptcha( $name, $value, $args, $message ){
		//pre( $name, 'name' );
		//pre( $value, 'value' );
		//pre( $args, 'args' );
		//pre( $message, 'message' );
		require_once( BOROS_LIBS . DIRECTORY_SEPARATOR . 'recaptcha/recaptchalib.php' );
		$publickey = get_option('recaptcha_publickey');
		$privatekey = get_option('recaptcha_privatekey');
		$resp = null;
		$error = null;
		
		if( isset($_POST["recaptcha_response_field"])){
			$resp = recaptcha_check_answer ($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
			$message = issetor($args['options']['error_message'], 'O captcha está incorreto');
			if( $resp->is_valid ){
				
			}
			else{
				$error = array(
					'name' => $name,
					'message' => $message,
					'type' => 'error'
				);
				$this->data_errors[$name][$args['rule']] = $error;
			}
		}
	}
	
	/**
	 * G-Recaptcha
	 * 
	 */
	function validate_grecaptcha( $name, $value, $args, $message ){
		require_once( BOROS_LIBS . DIRECTORY_SEPARATOR . 'grecaptcha/recaptchalib.php' );
		$publickey = get_option('recaptcha_publickey');
		$privatekey = get_option('recaptcha_privatekey');
		$resp = null;
		$error = null;
		
		if( isset($_POST["g-recaptcha-response"]) ){
			if( empty($_POST["g-recaptcha-response"]) ){
				$error = array(
					'name' => $name,
					'message' => 'É preciso preencher o reCAPTCHA',
					'type' => 'error'
				);
				$this->data_errors[$name][$args['rule']] = $error;
			}
			else{
				$reCaptcha = new ReCaptcha($privatekey);
				$resp = $reCaptcha->verifyResponse($_SERVER["REMOTE_ADDR"], $_POST["g-recaptcha-response"]);
				$message = issetor($args['options']['error_message'], 'O captcha está incorreto');
				if($resp != null && $resp->success){
					
				}
				else{
					$error = array(
						'name' => $name,
						'message' => $message,
						'type' => 'error'
					);
					$this->data_errors[$name][$args['rule']] = $error;
				}
			}
		}
	}
	
    /**
     * File
     * 
     * @ATENÇÃO : A presença desta function é obrigatória para não entrar em conflito com a function do core de mesmo nome.
     *            É feita a tentativa de utilizar um metodo da classe BorosValidation, e caso contrário, tenta utilizar
     *            uma function de mesmo nome, que no caso já existe no core, mas não serve para esta validação.
     */
    function validate_file( $name, $value, $args, $message ){
        //pre($this);
        if( isset($value['error']) and $value['error'] > 0 ){
            // caso existe apenas uma regra de validação(ou seja a corrente), retornar vazio, 
            // caso contrário retorna o valor original para as outras validações
            if( count($this->validations[$name]['remaining_rules']) > 1 ){
                return $value;
            }
            else{
                return '';
            }
        }
        return $value;
    }
    
    /**
     * Validar tamanho
     * 
     */
    function validate_file_size( $name, $value, $args, $message ){
        
        //pre($value['size']);
        //pre($args['size_bytes']);
        //die('validate_file_size');
        
        if( isset($value['size']) and $value['size'] > $args['size_bytes'] ){
            $error = array(
                'name' => $name,
                'message' => $message,
                'type' => 'error'
            );
            $this->data_errors[$name][$args['rule']] = $error;
        }
        
        // Caso seja 4(não enviado), retornar vazio. A verificação de campo obrigatório já é feito em BorosFrontendForm:required()
        if( isset($value['error']) and $value['error'] == 4 ){
            
            // caso existe apenas uma regra de validação(ou seja a corrente), retornar vazio, 
            // caso contrário retorna o valor original para as outras validações
            if( count($this->validations[$name]['remaining_rules']) > 1 ){
                return $value;
            }
            else{
                return '';
            }
        }
        
        //$limit = $args['size_bytes'];
        //pre(func_get_args());
        //die();
        return $value;
    }
    
    /**
     * Validar MIME type
     * 
     */
    function validate_file_mime_type( $name, $value, $args, $message ){
        
        //pre($value);
        //pre($args);
        //pre($this);
        //die('validate_file');
        
        // não gerar erro caso esteja vazio
        if( empty($value) ){
            return $value;
        }
        
        // validar mime
        if( isset($args['mimes']) and !empty($value['name']) ){
            require_once( BOROS_LIBS . '/mime_type_lib.php' );
            $mime = get_file_mime_type($value['name']);
            //pre($mime);
            //pre($args['mimes']);
            //pre($this);
            //die();
            if( !in_array($mime, $args['mimes']) ){
                $error = array(
                    'name' => $name,
                    'message' => $message,
                    'type' => 'error'
                );
                $this->data_errors[$name][$args['rule']] = $error;
            }
        }
        
        // Caso seja 4(não enviado), retornar vazio. A verificação de campo obrigatório já é feito em BorosFrontendForm:required()
        if( isset($value['error']) and $value['error'] == 4 ){
            
            // caso existe apenas uma regra de validação(ou seja a corrente), retornar vazio, 
            // caso contrário retorna o valor original para as outras validações
            if( count($this->validations[$name]['remaining_rules']) > 1 ){
                return $value;
            }
            else{
                return '';
            }
        }
        return $value;
    }
    
	/**
	 * Image file
	 * 
	 */
	function validate_image_file( $name, $value, $args, $message ){
		//pre($name, 'name');
		//pre($value, "value {$name}");
		//pre($args, 'args');
		//pre($message, 'message');
		
		// não gerar erro caso esteja vazio
		if( empty($value) ){
			return $value;
		}
		
		$error = array(
			'name' => $name,
			'message' => $message,
			'type' => 'error'
		);
        
		/**
		 * Caso não tenha sido feito o upload, será gerado o valor 4 em 'error'
		 * @http://php.net/manual/pt_BR/features.file-upload.errors.php
		 * 
		 */
		if( is_array($value) ){
			if( isset($value['error']) and $value['error'] == 4 ){
                // caso existe apenas uma regra de validação(ou seja a corrente), retornar vazio, 
                // caso contrário retorna o valor original para as outras validações
                if( count($this->validations[$name]['rules']) > 1 ){
                    return $value;
                }
                else{
                    return '';
                }
			}
			else{
				// Corrigir imagem caso ela esteja com extensão errada. Apenas para arquivos de imagem reais
				$file_info = wp_check_filetype_and_ext( $value['tmp_name'], $value['name'] );
				//pre($file_info, '$file_info');
				$value = array_merge($value, $file_info);
				
				// Certificar que é um arquivo de imagem
				$imgstats = @getimagesize( $value['tmp_name'] );
				//pre($imgstats);
				if( $imgstats === false ){
					//pal('não é imagem!');
					$this->data_errors[$name][$args['rule']] = $error;
				}
			}
		}
		else{
			$this->data_errors[$name][$args['rule']] = $error;
		}
		return $value;
	}
	
	/**
	 * NÃO TESTADO!!!
	 * 
	 */
	function email( $name, $value, $args, $message ){
		if( !empty($value) ){
			if( !filter_var( $value, FILTER_VALIDATE_EMAIL) ){
				if( $this->context['type'] == 'frontend' ){
					$error = array(
						'name' => $name,
						'message' => $message,
						'type' => 'error'
					);
					$this->data_errors[$name][$args['rule']] = $error;
				}
			}
		}
		return $value;
	}
	
	function email_unique( $name, $value, $args, $message ){
		//pre($name, 'name');
		//pre($value, 'value');
		//pre($args, 'args');
		//pre($message, 'message');
		if( !empty($value) ){
			$query_args = array(
				'search' => $value,
			);
			$users_with_email = new WP_User_Query( $query_args );
			$count = count($users_with_email->results);
			if( $count > 0 ){
				if( $this->context['type'] == 'frontend' ){
					$error = array(
						'name' => $name,
						'message' => $message,
						'type' => 'error'
					);
					$this->data_errors[$name][$args['rule']] = $error;
				}
			}
		}
		return $value;
	}
	
	/**
	 * Na edição de dados é permitido retornar um único registro, o dó próprio usuário
	 * 
	 */
	function email_unique_edit_user( $name, $value, $args, $message ){
		if( !empty($value) ){
			$query_args = array(
				'search' => $value,
			);
			$users_with_email = new WP_User_Query( $query_args );
			$count = count($users_with_email->results);
			if( $count > 1 ){
				if( $this->context['type'] == 'frontend' ){
					$error = array(
						'name' => $name,
						'message' => $message,
						'type' => 'error'
					);
					$this->data_errors[$name][$args['rule']] = $error;
				}
			}
		}
		return $value;
	}
	
	function max_length( $check, $max ) {
		return mb_strlen( $check ) <= $max;
	}
	
    /**
     * Apenas verificar o limite máximo da string, sem interferir no valor
     * 
     */
	function string_limit( $name, $value, $args, $message ){
		if( mb_strlen($value) > $args['max'] ){
			// adicionar box de erro apenas em option_page
			if( $this->context['type'] == 'option' ){
				add_settings_error(
					$setting 	= $name,
					$code 		= "{$name}_error",
					$message 	= $message,
					$type 		= 'error'
				);
			}
			// adiciona transient para este post
			elseif( $this->context['type'] == 'post_meta' ){
				$error = array(
					'name' => $name,
					'message' => $message,
					'type' => 'error'
				);
				$this->meta_errors[$name][] = $error;
			}
			// frontend
			elseif( $this->context['type'] == 'frontend' ){
				$error = array(
					'name' => $name,
					'message' => $message,
					'type' => 'error'
				);
				$this->data_errors[$name][] = $error;
			}
		}
		return $value;
	}
	
    /**
     * Limpar string além do limite
     * 
     */
	function sanitize_string_limit( $name, $value, $args, $message ){
		return mb_substr( $value, 0, $args['max'] );
	}
	
	function string_min_required( $name, $value, $args, $message ){
		if( mb_strlen($value) < $args['min'] ){
			if( $this->context['type'] == 'frontend' ){
				$error = array(
					'name' => $name,
					'message' => $message,
					'type' => 'error'
				);
				$this->data_errors[$name][$args['rule']] = $error;
			}
		}
		return $value;
	}
	
	function cep( $name, $value, $args, $message ){
		$regex = "/^([0-9]{2})\.?([0-9]{3})-?([0-9]{3})$/";
		if ( preg_match( $regex, $value ) )
			$cep_valid = true;
		else
			$cep_valid = false;
		
		if( $cep_valid == false ){
			if( $this->context['type'] == 'frontend' ){
				$error = array(
					'name' => $name,
					'message' => $message,
					'type' => 'error'
				);
				$this->data_errors[$name][$args['rule']] = $error;
			}
		}
		return $value;
	}
	
	 function cpf( $name, $value, $args, $message ){
		//pre($name, 'name');
		//pre($value, 'value');
		//pre($args, 'args');
		//pre($message, 'message');
		
		// permitir apenas números
		$value = preg_replace( '/[^0-9]/', '', $value );
		
		$isCpfValid = boros_validation_is_cpf_valid( $value );
		
		// boros
		if( $isCpfValid == false ){
			if( $this->context['type'] == 'frontend' ){
				$error = array(
					'name' => $name,
					'message' => $message,
					'type' => 'error'
				);
				$this->data_errors[$name][$args['rule']] = $error;
			}
			elseif( $this->context['type'] == 'user_meta' ){
				$error = array(
					'name' => $name,
					'message' => $message,
					'type' => 'error'
				);
				$this->user_errors[$name][$args['rule']] = $error;
				// RESETAR VALOR PARA QUE NÃO SEJA SALVO
				$value = false;
			}
		}
		return $value;
	}
	
	function validate_cpf( $name, $value, $args, $message ){
		return $this->cpf( $name, $value, $args, $message );
	}
	
	function cpf_unique( $name, $value, $args, $message ){
		// Se a verificação for feita em um cadastro já realizado, irá acusar não-único. Fazer uma segunda verificação de usuário
		$user_id = isset($args['user_id']) ? $args['user_id'] : false;
		$cpf_count = $this->cpf_count( $value, $user_id );
		
		// Não deve ser encontrado nenhum OUTRO usuário com esse CPF
		if( !empty($value) and $cpf_count > 0 ){
			if( $this->context['type'] == 'frontend' ){
				$error = array(
					'name' => $name,
					'message' => $message,
					'type' => 'error'
				);
				$this->data_errors[$name][$args['rule']] = $error;
			}
			elseif( $this->context['type'] == 'user_meta' ){
				$error = array(
					'name' => $name,
					'message' => $message,
					'type' => 'error'
				);
				$this->user_errors[$name][$args['rule']] = $error;
				// RESETAR VALOR PARA QUE NÃO SEJA SALVO
				$value = false;
			}
		}
		return $value;
	}
	
	function validate_cpf_unique( $name, $value, $args, $message ){
		return $this->cpf_unique( $name, $value, $args, $message );
	}
	
	function cpf_count( $cpf, $user_id = false ){
		$query_args = array(
			'meta_key' => 'cpf',
			'meta_value' => $cpf,
			'meta_compare' => '=',
			'exclude' => $user_id, // remover usuário corrente, caso determinado, por exemplo na edição de profile
		);
		$users_with_cpf = new WP_User_Query( $query_args );
		$count = count($users_with_cpf->results);
		return $count;
	}
    
    /**
     * Validate a date
     * 
     * 
     * @link https://gist.github.com/TiuTalk/0effe55821e82eb0f745
     * @link http://pt.stackoverflow.com/questions/14560/como-validar-data-de-nascimento-entre-o-ano-de-1900-e-hoje
     * 
     * @param    string    $data
     * @param    string    formato
     * @return    bool
     */
    function valid_birth_date( $data, $formato = 'DD/MM/AAAA' ){
        
        switch($formato) {
            case 'DD-MM-AAAA':
            case 'DD/MM/AAAA':
                $parts = preg_split('~[-./ ]~', $data);
                if( count($parts) != 3 ){
                    return false;
                }
                list($d, $m, $a) = $parts;
                break;

            case 'AAAA/MM/DD':
            case 'AAAA-MM-DD':
                list($a, $m, $d) = preg_split('~[-./ ]~', $data);
                if( count($parts) != 3 ){
                    return false;
                }
                break;

            case 'AAAA/DD/MM':
            case 'AAAA-DD-MM':
                list($a, $d, $m) = preg_split('~[-./ ]~', $data);
                break;

            case 'MM-DD-AAAA':
            case 'MM/DD/AAAA':
                list($m, $d, $a) = preg_split('~[-./ ]~', $data);
                break;

            case 'AAAAMMDD':
                $a = substr($data, 0, 4);
                $m = substr($data, 4, 2);
                $d = substr($data, 6, 2);
                break;

            case 'AAAADDMM':
                $a = substr($data, 0, 4);
                $d = substr($data, 4, 2);
                $m = substr($data, 6, 2);
                break;

            default:
                throw new Exception( "Formato de data inválido");
                break;
        }
        
        if( !checkdate( $m , $d , $a ) || $a < 1900 || mktime( 0, 0, 0, $m, $d, $a ) > time() ){
            return false;
        }
        return true;
    }
    
    function validate_birth_date( $name, $value, $args, $message ){
        $valid_birth_date = $this->valid_birth_date( $value, $args['format'] );
        
        // boros
        if( $valid_birth_date == false ){
            if( $this->context['type'] == 'frontend' ){
                $error = array(
                    'name' => $name,
                    'message' => $message,
                    'type' => 'error'
                );
                $this->data_errors[$name][$args['rule']] = $error;
            }
            elseif( $this->context['type'] == 'user_meta' ){
                $error = array(
                    'name' => $name,
                    'message' => $message,
                    'type' => 'error'
                );
                $this->user_errors[$name][$args['rule']] = $error;
                // RESETAR VALOR PARA QUE NÃO SEJA SALVO
                $value = false;
            }
        }
        return $value;
    }
    
    function sanitize_wp_kses( $name, $value, $args, $message ){
        return wp_kses($value);
    }
    
    function sanitize_wp_kses_post( $name, $value, $args, $message ){
        return wp_kses_post($value);
    }
    
    function sanitize_strip_all_tags( $name, $value, $args, $message ){
        if( is_array($value) ){
            foreach( $value as $k => $v ){
                $value[$k] = $this->sanitize_strip_all_tags( false, $v, false, false );
            }
        }
        else {
            $value = wp_strip_all_tags($value);
        }
        return $value;
    }
	
}

/**
 * isCpfValid
 *
 * Esta função testa se um cpf é valido ou não. 
 *
 * @author	Raoni Botelho Sporteman <raonibs@gmail.com>
 * @version	1.0 Debugada em 26/09/2011 no PHP 5.3.8
 * @param	string		$cpf			Guarda o cpf como ele foi digitado pelo cliente
 * @param	array		$num			Guarda apenas os números do cpf
 * @param	boolean		$isCpfValid		Guarda o retorno da função
 * @param	int			$multiplica 	Auxilia no Calculo dos Dígitos verificadores
 * @param	int			$soma			Auxilia no Calculo dos Dígitos verificadores
 * @param	int			$resto			Auxilia no Calculo dos Dígitos verificadores
 * @param	int			$dg				Dígito verificador
 * @return	boolean						"true" se o cpf é válido ou "false" caso o contrário
 * 
 * @link http://codigofonte.uol.com.br/codigo/php/validacao/funcao-php-para-validar-cpf
 */
function boros_validation_is_cpf_valid( $cpf ){
	//Etapa 1: Cria um array com apenas os digitos numéricos, isso permite receber o cpf em diferentes formatos como "000.000.000-00", "00000000000", "000 000 000 00" etc...
	$j = 0;
	$num = array();
	for($i=0; $i<(strlen($cpf)); $i++){
		if(is_numeric($cpf[$i])){
			$num[$j]=$cpf[$i];
			$j++;
		}
	}
	//Etapa 2: Conta os dígitos, um cpf válido possui 11 dígitos numéricos.
	if(count($num)!=11){
		$isCpfValid=false;
	}
	//Etapa 3: Combinações como 00000000000 e 22222222222 embora não sejam cpfs reais resultariam em cpfs válidos após o calculo dos dígitos verificares e por isso precisam ser filtradas nesta parte.
	else{
		for($i=0; $i<10; $i++){
			if ($num[0]==$i && $num[1]==$i && $num[2]==$i && $num[3]==$i && $num[4]==$i && $num[5]==$i && $num[6]==$i && $num[7]==$i && $num[8]==$i){
				$isCpfValid = false;
				break;
			}
		}
	}
	//Etapa 4: Calcula e compara o primeiro dígito verificador.
	if(!isset($isCpfValid)){
		$j=10;
		for($i=0; $i<9; $i++){
			$multiplica[$i]=$num[$i]*$j;
			$j--;
		}
		$soma = array_sum($multiplica);	
		$resto = $soma % 11;
		if($resto<2){
			$dg = 0;
		}
		else{
			$dg = 11 - $resto;
		}
		if($dg != $num[9]){
			$isCpfValid=false;
		}
	}
	//Etapa 5: Calcula e compara o segundo dígito verificador.
	if(!isset($isCpfValid)){
		$j=11;
		for($i=0; $i<10; $i++){
			$multiplica[$i]=$num[$i]*$j;
			$j--;
		}
		$soma = array_sum($multiplica);
		$resto = $soma%11;
		if($resto<2){
			$dg=0;
		}
		else{
			$dg=11-$resto;
		}
		if($dg!=$num[10]){
			$isCpfValid=false;
		}
		else{
			$isCpfValid = true;
		}
	}
	
	return $isCpfValid;
}




