<?php
/**
 * ==================================================
 * ADICIONAR ELEMENTOS DE FORMULÁRIO ================
 * ==================================================
 * Ler todos os arquivos da pasta form_elements e fazer loop de todos
 * Desconsidera arquivos com underscore no começo do nome
 * 
 * 
 * @TODO: adicionar filters 'before', 'after' e 'wrap' element
 */



/**
 * Adicionar um dialog hidden no rodape do admin
 * 
 */
add_action( 'admin_footer', 'add_admin_footer_dialog' );
function add_admin_footer_dialog(){
	echo '<div id="admin_footer_dialog" style="display:none;">';
	
	if ( ! class_exists( '_WP_Editors' ) )
		require( ABSPATH . WPINC . '/class-wp-editor.php' );

	$set = _WP_Editors::parse_settings('admin_footer_dialog', array());
	_WP_Editors::editor_settings('admin_footer_dialog', $set);
	
	echo '</div>';
}



/**
 * ==================================================
 * RENDERIZAR ELEMENTOS DE FORMULÁRIO ===============
 * ==================================================
 * Primeiro verifica se a classe do elemento existe, caso contrário exibe uma mensagem de erro com as instruções de como resolver o problema.
 * 
 * @param array $context - ambiente em que o elemento será usado: option_page, post_meta, termmeta, usermeta, widget. Em cada caso os valores de $context mudam
 * @param array $data - array estático de configuração, com name, label, type, options, etc
 * @data_value mix - valor do campo
 */
function create_form_elements( $context, $data, $data_value ){
	global $post;
	
	$classname = 'BFE_' . $data['type'];
	if( class_exists($classname) and is_subclass_of($classname, 'BorosFormElement') ){
		$element = new $classname( $context, $data, $data_value );
		$element->output();
	}
	else{
		?>
		<div class="alert_box updated" id="form_element_error<?php echo $data['type']; ?>">
			<p class="error">A class "<code><strong><?php echo $classname; ?></strong></code>" não existe ou não é subclasse de <code>BorosFormElement</code></p>
			<p>Arquivos para o form element requerido:</p>
			<ul>
				<li><?php echo BOROS_ELEMENTS . "<code><strong>{$data['type']}.php</strong></code>" ; ?> - <em>obrigatório</em></li>
				<li><?php echo "Classe <code><strong>{$classname}</strong></code>, no arquivo php" ; ?> - <em>obrigatório</em></li>
				<li><?php echo "Método <code><strong>set_input()</strong></code>, na class <code><strong>{$classname}</strong></code>" ; ?> - <em>obrigatório</em></li>
				<li><?php echo "<code>" . BOROS_ELEMENTS . "js/<strong>{$data['type']}.js</strong></code>" ; ?> - <em>opcional</em></li>
				<li><?php echo "<code>" . BOROS_ELEMENTS . "css/<strong>{$data['type']}.css</strong></code>" ; ?> - <em>opcional</em></li>
			</ul>
			<p>Modelo de dados:</p>
			<pre><?php print_r($data); ?></pre>
		</div>
		<?php
	}
}



/**
 * ==================================================
 * INCLUDES DOS FORM ELEMENTS =======================
 * ==================================================
 * 
 * Action definida em 'init', para ser disponível em qualquer contexto, principalmente devido aos callbacks atrelados a alguns elementos, como {taxonomy|content}_order, que rodam 
 * no contexto da página 'options.php', onde não é instanciado nenhum element, apenas as configs gerais, sendo chamdos os méthodos estáticos para retornar oa nomes certos dos
 * callbacks.
 */
add_action( 'init', 'add_form_elements' );
function add_form_elements(){
	/**
	 * Adicionar elementos padrão
	 * 
	 */
	$glob = false;
	foreach( glob( BOROS_ELEMENTS . "*.php" ) as $filename ){
		$path = pathinfo( $filename );
		if( !preg_match( "/^_/", $path['filename'] ) ){
			include_once $filename;
			$glob = true;
		}
	}
	
	/**
	 * Adicionar elementos customizados para o projeto.
	 * 
	 * @todo verificar a existência da funciton glob, da pasta e se está vazia, pois qualquer uma das 3 situações causam erro.
	 */
	$extra_elements = apply_filters( 'boros_extra_form_elements_folder', array() );
	//pre($extra_elements);
	if( !empty($extra_elements) ){
		foreach( $extra_elements as $folder ){
			foreach( glob( $folder . "/*.php" ) as $filename ){
				$path = pathinfo( $filename );
				if( !preg_match( "/^_/", $path['filename'] ) ){
					include_once $filename;
				}
			}
		}
	}
	
	/**
	 * Fallback para servidores onde a função blob() não está disponível.
	 * 
	 */
	if( $glob == false ){
		$files = array(
			'attach_select', 
			'checkbox', 
			'checkbox_group', 
			'content_order', 
			'duplicable_group', 
			'factory_options', 
			'hidden', 
			'html', 
			'password', 
			'radio', 
			'search_content_list', 
			'select', 
			'select_query_posts', 
			'separator', 
			'special_image', 
			'submit', 
			'taxonomy_checkbox', 
			'taxonomy_radio', 
			'text', 
			'textarea', 
			'wp_editor',
		);
		foreach( $files as $file ){
			include_once BOROS_ELEMENTS . "/{$file}.php";
		}
		
		// fallback para custom elements - é preciso que o array venha com o caminho completo
		$extra_elements = apply_filters( 'boros_extra_form_elements_folder', array() );
		if( !empty($extra_elements) ){
			foreach( $extra_elements as $file ){
				include_once $file;
			}
		}
	}
}



/**
 * (re)carregar um element diretamente. USado para inserção em widget ou relaod/duplicate via ajax
 * 
 * @todo adicionar os contextos termmeta, usermeta, widget
 */
add_action('wp_ajax_duplicate_element', 'ajax_duplicate_element');
function ajax_duplicate_element(){
	$context = $_POST['context'];
	//pre($context, 'context');
	//pre($group_id, 'group_id');
	//pre($element_name, 'element_name');
	//return;
	
	do_action( 'ajax_duplicate_element', $context );
	die();
}



/**
 * Carrega as configurações de um elemento com base no seu contexto e name.
 * 'load_element_config' é um filter que precisa estar presente em todos os contextos(option, post_meta, termmeta, usermeta, widget), que irá buscar em todas as configs de todas as instâncias do
 * contexto. Por exemplo, caso tenham sido criadas duas instâncias de 'BorosMetaBoxes', ambos possuem o filter 'load_element_config', portanto serão feitas duas verificações, em ambas as configs.
 * Espera-se que só exista uma instância que corresponda ao mesmo contexto( type, group e name )
 * 
 * As configs de pages podem ser acessadas em qualquer contexto via 'call_user_func($page_name)', porém as configs de metaboxes só estão acessíveis dentro de cada instância de 'BorosMetaBoxes', por 
 * isso foi deixado a manipulação desses dados via filter.
 * 
 * 
 * @modify_date 2013.02.02 - removido "Call-time pass-by-reference" --> em &$_config foi removido o "&" por conta das mudanças do PHP 5.4
 * 
 * 
 */
function load_element_config( $context ){
	$_config = array();
	$config = apply_filters_ref_array( 'load_element_config', array(&$_config, $context) );
	return $config;
}



/**
 * ==================================================
 * TRATAMENTO DAS ARRAYS DE CONFIG ==================
 * ==================================================
 * Normaliza os arrays de configuração de elementos, tratando tanto o modelo novo, que usa apenas arrays associativos(com chaves), quanto o modelo antigo, que usava apenas arrays comuns(numéricos)
 * Nos arrays atuais, adiciona os itens 'id', que agora estão como chaves de cada item.
 * No arrays antigas, onde o 'id' do bloco e os 'names' dos elementos são definidos dentro dos arrays, são recriados para adicionar as chaves.
 * 
 * @bug como as configurações de usermeta já estão em array associativo, é interpretado que este não precisa da coreção de chaves, gerando erro. É preciso atualizar todos as configs de usermeta.
 * @todo permitir elements sem name dentro de duplicates, copiando o type para o name, como já está sendo feito para os elements comuns
 */
function update_element_config( $raw_config ){
	//pre($raw_config, '$raw_config');
	// tratamento de arrays numéricos para transformar em associativo
	if( isset($raw_config[0]) ){
		$config = array();
		// loop nos grupos
		foreach( $raw_config as $i => $group ){
			// modificar os elements primeiro
			if( isset($group['itens']) ){
				$itens = array();
				foreach( $group['itens'] as $item ){
					// duplicable group
					if( isset($item['group_itens']) ){
						$subitens = array();
						foreach( $item['group_itens'] as $subitem ){
							$subitens[$subitem['name']] = $subitem;
							$subitens[$subitem['name']]['parent'] = $item['name'];
						}
						$item['group_itens'] = $subitens;
					}
					
					// item normal, mas verificar antes caso seja um element sem name, como o 'separator'
					if( isset($item['name']) ){
						$itens[$item['name']] = $item;
					}
					else{
						$itens[$item['type']] = $item;
						$itens[$item['type']]['name'] = $item['type'];
					}
				}
				$group['itens'] = $itens;
			}
			
			// no caso dos page header, dificilmente foi definido um 'id' para o bloco
			if( isset($group['id']) ){
				$config[$group['id']] = $group;
			}
			else{
				$config[$i] = $group;
			}
		}
		return $config;
	}
	else{
		$config = array();
		// loop nos grupos
		foreach( $raw_config as $id => $group ){
			// modificar os elements primeiro
			if( isset($group['itens']) ){
				$itens = array();
				foreach( $group['itens'] as $name => $item ){
					// duplicable group
					if( isset($item['group_itens']) ){
						$subitens = array();
						foreach( $item['group_itens'] as $subname => $subitem ){
							$names = array(
								'name' => $subname,
								'parent' => $name,
							);
							$subitens[$subname] = array_merge( $names, $subitem );
						}
						$item['group_itens'] = $subitens;
					}
					// adicionar item com o name em primeiro
					$itens[$name] = array_merge( array('name' => $name), $item );
				}
				$group['itens'] = $itens;
			}
			// adicionar o grupo atualizado com o id no começo
			$config[$id] = array_merge( array('id' => $id), $group );
		}
		//pre($config);return array();
		return $config;
	}
}



/**
 * Duplicar OU Reacrregar elemento
 * 
 * @todo URGENTE em recarregar elemento, verificar o trecho para recuperar user_meta, talvez não esteja funcionando! testar!!!
 */
add_action( 'ajax_duplicate_element', 'boros_duplicate_element', 10, 3 );
function boros_duplicate_element( $context ){
	/**
	 * Carregar configuração do element baseado no $context.
	 * O contexto precisa possuir o método 'load_element_config' para retornar a configuração correta
	 * 
	 */
	$config = load_element_config( $context );
	//pre($config, 'load_element_config');//return $context;
	
	if( !isset($config) or empty($config) )
		return $context;
	
	$item = $config;
	
	/**
	 * O primeiro bloco somente será executado caso a requisição seja um conjunto de duplicate elements, que serão adicionados à uma li.duplicate_element nova.
	 * Para reload de elementos dependentes, é usado o segundo bloco.
	 * 
	 */
	if( $item['type'] == 'duplicate_group' ){
		//pal(1);
		foreach( $item['group_itens'] as $element ){
			if( isset($element['options']) )
				$element['options'] = array_merge( $element['options'], $_POST['args'] );
			else
				$element['options'] = $_POST['args'];
			
			if( isset($_POST['args']['index']) )
				$element['index'] = $_POST['args']['index'];
			
			// armazenar o 'name' original em 'data-name'
			$element['attr']['dataset']['name'] = $element['name'];
			
			// modificar o 'id' e 'name' para o formato aninhado
			$element['attr']['id'] = "{$item['name']}_{$element['index']}_{$element['name']}";
			$element['name'] = "{$item['name']}[{$element['index']}][{$element['name']}]";
			
			// SEMPRE sinalizar que é um duplicate
			$element['in_duplicate_group'] = true;
			
			create_form_elements( $context, $element, false );
		}
	}
	/**
	 * Recarregar elemento dependente sempre será nesse trecho
	 * 
	 */
	else{
		//pal(2);
		if( isset($item['options']) )
			$item['options'] = array_merge( $item['options'], $_POST['args'] );
		else
			$item['options'] = $_POST['args'];
		
		if( isset($_POST['args']['index']) )
			$item['index'] = $_POST['args']['index'];
		
		//pre($context);
		$item['in_duplicate_group'] = $context['in_duplicate_group'];
		
		// armazenar o 'name' original em 'data-name'
		$item['attr']['dataset']['name'] = $item['name'];
		
		$item['attr']['dataset'] = array_merge( $context, $item['attr']['dataset'] );
		
		if( $item['in_duplicate_group'] == true ){
			// modificar o 'id' e 'name' para o formato aninhado
			$item['attr']['id'] = "{$item['parent']}_{$item['index']}_{$item['name']}";
			$item['name'] = "{$item['parent']}[{$item['index']}][{$item['name']}]";
		}
		
		/**
		 * É preciso recuperar o valor do input conforme o contexto
		 * 
		 */
		switch( $context['type'] ){
			case 'post_meta':
				$data_value = get_post_meta( $context['post_id'], $item['name'], true );
				break;
			case 'user_meta':
				$data_value = get_user_meta( $context['user_id'], $item['name'], true );
				break;
			case 'option':
				$data_value = get_option( $item['name'] );
				break;
			default:
				break;
		}
		create_form_elements( $context, $item, $data_value );
		//create_form_elements( $context, $item, false );
	}
}



/**
 * ==================================================
 * BOROS FORM ELEMENTS ==============================
 * ==================================================
 * Classe para criação dos elementos
 * 
 * @todo revisar o set_attributes(), para evitar incompatibilidades de classes extendidas que não usem atributos(ex HTML)
 * @todo revisar o $parent - talvez usar/mudar para controle de unique name(multiplos controles com um único name, para gravar o option em array)
 * 
 */
class BorosFormElement {
	/**
	 * Contexto
	 * 
	 */
	var $context;
	var $parent_elem = 0;
	
	/**
	 * caso seja postpage
	 * 
	 */
	var $post;
	var $post_id = 0;
	
	/**
	 * Armazena o array com dados pós-processados
	 * 
	 */
	var $data = array();
	
	/**
	 * Lista de atributos HTML permitidos. Será usado por $this->set_attributes() para criar o output correto de atributos.
	 * As classes extendidas sempre deverão declarar essa variável
	 * 
	 * @todo verificar o uso de abstract 
	 */
	var $valid_attrs = array();
	
	/**
	 * String HTML com os atributos renderizados, no formato <code> atrr1="value1" attr2="value2" attr3="value3"</code>
	 * Para acessar o array com os atributos com chave=>valor, usar $this->data['attr']
	 * Ver $this->set_attributes()
	 * 
	 */
	var $attrs = '';
	
	var $dataset = '';
	
	/**
	 * String HTML com o input pós-processado, incluindo o <INPUT> corrreto, assim como HTML extra necessário para os controles.
	 * 
	 */
	var $input = '';
	var $final_input = '';
	
	/**
	 * String HTML com o label pós-processado, ou seja, incluindo o label + label_helper
	 * Em alguns casos o label não conterá a tag <LABEL>, como por exemplo radio e checkbox_group
	 * 
	 */
	var $label = '';
	
	/**
	 * Valor pós-processado, após aplicação de filtros e correções
	 * Ver $this->set_data_value()
	 * 
	 */
	var $data_value = null;
	
	/**
	 * String HTML auxiliar do input, pós-processado. Será exibido junto com o input.
	 * 
	 */
	var $input_helper = '';
	
	/**
	 * String HTML auxiliar do input, será exibido antes do input.
	 * 
	 */
	var $input_helper_pre = '';
	
	/**
	 * String HTML auxiliar do label, pós-processado. Será exibido junto com o label.
	 * 
	 */
	var $label_helper = '';
	
	/**
	 * Valores padrão, será mesclado com dados enviados na chamada para guardar em $this->data
	 * Poderá ser ampliado via $this->add_defaults()
	 * Para deixar o o atributo opcional, deixar o valor padrão como false
	 * 
	 */
	var $defaults = array(
		'std' => '',
		'attr' => array(
			'name' => '',
			'id' => '',
			'class' => '',
			'value' => '',
			'rel' => false,
			'disabled' => false,
			'readonly' => false,
		),
		'size' => '',
		'layout' => 'table',
		'input_helper' => '',
		'label_helper' => '',
		'index' => 0,
		'in_duplicate_group' => false,
		//'errors' => array(),
	);
	
	/**
	 * js e css requeridos, modelo:
	  <code>
	 	var $enqueues = array(
	 		'css' => array( 'css1', 'css2', 'css3' ),		// valores array
	 		'js' => array( 'js1', 'js2', 'js3' ),		// valores array
	 		'css' => 'css1',							// valor string
	 		'js' => 'js1',							// valor string
	 	);
	  </code>
	 * 
	 * @todo fazer uma opção $enqueues = 'css'|'js'|'both', que tenta buscar um único js|css com o mesmo nome do element
	 */
	var $enqueues;
	
	/**
	 * 
	 * @TODO
	 */
	var $errors;
	var $error_messages = '';
	
	/**
	 * @TODO
	 * 
	 */
	static $callbacks = false;
	
	/**
	 * Cada classe extendida irá possuir o método set_input, mas mesmo assim, após a criação do input ele poderá ser filtrado pelo hook "BFE_{TYPE}_input"
	 * 
	 */
	function __construct( $context, $data, $data_value ){
		$this->includes();
		$this->context = $context;
		if( isset($context['parent']) ) $this->parent_elem = $context['parent'];
		$this->data = $data;
		$this->set_defaults();
		$this->set_data_value( $data_value );
		$this->set_attributes();
		$this->set_dependent_options();
		$this->set_label_helper();
		$this->set_input_helper();
		$this->set_label();
		$this->dataset();
		//$this->set_input();
		$this->enqueues();
		$this->error_messages();
		$this->final_input();
		$this->input = apply_filters( "BFE_{$this->data['type']}_input", $this->input );
	}
	
	/**
	 * Includes adicionais, caso necessário
	 * 
	 */
	function includes(){}
	
	/**
	 * Para modificar os defaults, mudar a var $defaults na classe extendida(requer declarar os defaults completos). Para apenas adicionar, usar add_defaults(), que roda no início ou
	 * filter_data(), que roda ao final
	 * 
	 */
	function set_defaults(){
		$this->add_defaults();
		//$this->defaults['attr'] = $this->valid_attrs;
		$this->data = boros_parse_args( $this->defaults, $this->data );
		if( !isset($this->data['name']) and isset($this->defaults['attr']['name']) )
			$this->data['name'] = $this->defaults['attr']['name'];
		
		//if( isset($this->data['is_duplicate_child']) )
		//	$this->data['name'] = $this->defaults['attr']['name'];
		
		/**
		global $post;
		if( $post ){
			$this->post = $post;
			$this->post_id = $post->ID;
		}
		if( isset($this->data['options']['post_id']) ){
			$this->post_id = $this->data['options']['post_id'];
		}
		/**/
		
		$this->set_callback_options();
		
		// recuperar os errors
		if( isset($this->data['errors']) ){
			$this->errors = $this->data['errors'];
		}
		
		$this->filter_data();
		//pre($this->data);
	}
	
	/**
	 * Permite que seja adicionando itens ao array $this->defaults, assim não é preciso redeclarar o array todo
	 * 
	 */
	function add_defaults(){}
	
	/**
	 * Filtrar $this->data
	 * Necessário caso seja preciso pós-processar algum valor depois que recebe a configuração. Ex adicionar dataset em special_image
	 *
	 * ATENçÃO: é preciso editar $this->data e não $this->defaults
	 */
	function filter_data(){}
	
	/**
	 * Setar options por callbacks
	 * define qualquer variável a ser guardada em $this->data['options'], mas que seja preciso a execução de algum callback. É necessário caso seja preciso
	 * utilizar alguma variável presente apenas no momento de execução, como a global $post, e a partir desta mais alguma informação, como categorias, termos e
	 * post_metas aplicados ao conteúdo(post) atualmente em edição.
	 * 
	 * IMPORTANTE!!! caso o valor de $this->data['options'][$value] já esteja setado, o callback não será aplicado, NÃO irá sobreescrever o valor 
	 * original. Isso permite que o reload direto de cada elemento por ajax possa setar esses valores, pois executam em outro contexto, onde as variáveis 
	 * que não estão disponíveis no momento da configuração do metaboxes_config() poderão ser setadas.
	 * 
	 */
	function set_callback_options(){
		if( isset($this->data['options']['callback_values']) ){
			foreach( $this->data['options']['callback_values'] as $value => $callback ){
				if( !isset( $this->data['options'][$value]) ){
					$this->data['options'][$value] = call_user_func( $callback );
				}
			}
		}
	}
	
	/**
	 * @TODO
	 * callbacks fixos do elemento
	 */
	static function set_callback_functions(){
		return self::$callbacks;
	}
	
	/**
	 * Armazenar opções que dependem de valores de outros controles
	 * 
	 * @TODO fazer código para post_meta!!!
	 */
	function set_dependent_options(){
		/**
		 * Carregar value do elemento provider gravado, a menos que seja uma requisição ajax, quando deverá usar o novo valor
		 * 
		 */
		if( isset($this->data['dependencies']) ){
			if( defined('DOING_AJAX') ){
				//pal(1);
				if( isset($this->data['options']) ){
					$this->data['options']['provider_value'] = array_key_search_r( 'provider_value', $this->data['options'] );
				}
			}
			else{
				//pal(2);
				if( $this->data['dependencies']['dependent'] === true ){
					if( $this->context['type'] == 'option' ){
						$config = call_user_func( $this->context['option_page'] );
						if( $this->data['in_duplicate_group'] == true ){
							$parent = array_parent_k( $config, 'name', $this->data['dependencies']['provider'], 'name' );
							$parent_value = get_option($parent['name']);
							$this->data['options']['provider_value'] = $parent_value[$this->data['index']][$this->data['dependencies']['provider']];
						}
						else{
							$this->data['options']['provider_value'] = get_option($this->data['dependencies']['provider']);
						}
					}
					elseif( $this->context['type'] == 'post_meta' ){
						pal('REVISAR ESTE CÓDIGO: form_elements.php - set_dependent_options()');
					}
				}
			}
		}
	}
	
	/**
	 * Define o valor a ser impresso na tela/input. É filtrado para impedir conflitos com aspas duplas e simples, aplicando esc_html() em todos os valores, incluindo arrays.
	 * Possibilidade de modificar o valor via filter, com 'BFE_{TYPE}_value' ou método de classe extendida.
	 * 
	 * @TODO: adicionar 'use_core_value' para termos, links, comments, etc
	 * TODO: adicionar filter para o $data_value, passando como argumentos $post, $term, etc conforme o contexto - OU - criar filtro para o 'std'
	 */
	function set_data_value( $data_value ){
		// aplicar valor de campo core, como post_content, post_title, post_excerpt, etc
		if( isset($this->data['use_core_value']) ){
			if( $this->data['use_core_value']['object'] == 'post' ){
				global $post;
				$field = $this->data['use_core_value']['field'];
				$data_value = $post->$field;
			}
		}
		
		//echo $data_value;
		//pre($data_value, $this->data['name']);
		if( isset($this->data['options']['raw']) and $this->data['options']['raw'] == true ){
			// not changes :P
		}
		else{
			if( is_array($data_value) ){
				//pal('ARRAY! 90000!!!');
				multidimensional_array_map('esc_html', $data_value);
			}
			else{
				//pal('normal :3');
				$data_value = esc_html($data_value);
			}
		}
		$value = apply_filters( "BFE_{$this->data['type']}_value", $data_value ); // filtro do type
		$value = apply_filters( "BFE_{$this->data['name']}_value", $data_value ); // filtro do name
		$this->data_value = $value;
		//pal($this->data_value, "{$this->data['name']} A");
	}
	
	/**
	 * Cria o label helper, caso seja requrido.
	 * Possibilidade de modificar o valor via filter, com 'BFE_{TYPE}_label_helper' ou método de classe extendida.
	 * 
	 * Aplicar filtros mesmo que o label esteja vazio, para que possa retornar valores de filtros
	 */
	function set_label_helper(){
		$this->label_helper = apply_filters( "BFE_{$this->data['type']}_label_helper", " <span class='label_helper'>{$this->data['label_helper']}</span>", $this->data_value, $this->context );
		$this->label_helper = apply_filters( "BFE_{$this->data['name']}_label_helper", $this->label_helper, $this->data_value, $this->context );
		// resetar caso esteja vazio
		if( $this->label_helper == " <span class='label_helper'></span>" )
			$this->label_helper = '';
	}
	
	/**
	 * Cria o input helper, caso seja requrido.
	 * Possibilidade de modificar o valor via filter, com 'BFE_{TYPE}_input_helper' ou método de classe extendida.
	 * 
	 * Aplicar filtros mesmo que o input_helper esteja vazio, para que possa retornar valores de filtros
	 * 
	 * @todo estudar a necessidade de filtros, melhorar a parte do 'input_helper_pre'
	 */
	function set_input_helper(){
		//if( !empty($this->data['input_helper']) ){
			$this->input_helper = apply_filters( "BFE_{$this->data['type']}_input_helper", " <span class='description'>{$this->data['input_helper']}</span>", $this->data_value, $this->context );
			$this->input_helper = apply_filters( "BFE_{$this->data['name']}_input_helper", $this->input_helper, $this->data_value, $this->context );
		//}
		// resetar caso esteja vazio
		if( $this->input_helper == " <span class='description'></span>" ){
			$this->input_helper = '';
		}
		
		// input helper prepend
		if( $this->input_helper_pre == '' ){
			$this->input_helper_pre = " <span class='description'>{$this->data['input_helper_pre']}</span>";
		}
	}
	
	/**
	 * Cria o label, caso seja requrido.
	 * O padrão é label(com a tag <LABEL>) + label_helper. Existe a possibilidade de modificar o valor via filter, com 'BFE_{TYPE}_input_helper' ou método de classe extendida.
	 * 
	 * Em caso de duplicable, modificar o $for para apontar para o primeiro elemento
	 * 
	 * Aplicar filtros mesmo que o label esteja vazi, para que possa retornar valores de filtros e o label_helper
	 */
	function set_label(){
		if( !isset($this->data['label']) ) $this->data['label'] = '';
		$for = ( isset($this->data['duplicable']) and $this->data['duplicable'] == true ) ? $this->data['attr']['id'] . '_0' : $this->data['attr']['id'];
		
		// separar os layouts :: bootstrap
		if( $this->data['layout'] == 'bootstrap' ){
			$this->label = "<label class='control-label' for='{$for}'>{$this->data['label']}{$this->label_helper}</label>";
		}
		elseif( $this->data['layout'] == 'bootstrap3' ){
			if(!empty($this->data['label'])){
				$this->label = "<label class='control-label' for='{$for}'>{$this->data['label']}{$this->label_helper}</label>";
			}
			else{
				$this->label = '';
			}
		}
		else{
			$this->label = "<label for='{$for}'>{$this->data['label']}</label>{$this->label_helper}<br class='label_divider' />";
		}
		
		// aplicar filtros
		$this->label = apply_filters( "BFE_{$this->data['type']}_label", $this->label, $this->data );
		$this->label = apply_filters( "BFE_{$this->data['name']}_label", $this->label, $this->data );
		
		// remover caso o label resultante seja vazio
		if( $this->label == "<label for='{$for}'></label><br class='label_divider' />" )
			$this->label = '';
	}
	
	/**
	 * Teoricamente este método é obrigatório nas classes estendidas, onde deverá ser criado o input conforme a necessidade.
	 * 
	 * @todo verificar o uso de abstract
	 */
	function set_input( $value = null ){
		$this->input = "{$this->input_helper}";
	}
	
	/**
	 * Verificar se é preciso duplicar elementos
	 * 
	 */
	function final_input(){
		if( !boros_check_empty_var($this->data_value) ){
			$this->data_value = '';
		}
		
		/**
		 * A segunda execução de set_attributes(), irá modificar o name e a id, porém irá manter o data-name
		 * 
		 */
		if( isset($this->data['duplicable']) and $this->data['duplicable'] == true ){
			$i = 0;
			$name = $this->data['name'];
			
			// caso possua apenas um valor, transformar em array
			if( !is_array($this->data_value) ){
				$this->data_value = array($this->data_value);
			}
			
			$this->final_input .= '<ul class="duplicable_input_box">';
			foreach($this->data_value as $data_value){
				$this->data['attr']['id'] = "{$name}_{$i}";
				$this->data['attr']['name'] = $name . '[]';
				//pal($this->data['attr']['id'], 'ID');
				
				$this->set_attributes();
				//pre($this->data['attr'], 'multiple');
				$input = $this->set_input($data_value);
				$size = $this->data['size'];
				$this->final_input .= "<li class='duplicable_input_item'><div class='duplicable_move' title='Arraste para organizar'></div> <div class='duplicable_input iptw_{$size}'>{$input}</div><span class='duplicable_add' title='Adicionar'></span><span class='duplicable_remove' title='Remover'></span></li>";
				$i++;
			}
			$this->final_input .= '</ul>';
		}
		else{
			//$this->set_attributes();
			//pre($this->data['attr'], 'single');
			$this->final_input = $this->set_input($this->data_value);
		}
	}
	
	/**
	 * Processa $this->data['attr'] e $this->attrs
	 * 
	 * @todo testar a o foreach $merge
	 * @todo revisar este método para possibilitar que qualquer attribute possa ser osbreescrito no array de configuração
	 * @see dataset();
	 */
	function set_attributes(){
		// Adicionar valores NAME, ID, REL
		// ATENÇÃO: para TEXTAREAs, é preciso remover o value do array, pois ele não possui este atributo
		
		// 'name' - apenas herda o name da raiz
		if( empty($this->data['attr']['name']) ){
			//pre($this->data['name']);
			$this->data['attr']['name'] = $this->data['name'];
		}
		
		// caso seja duplicável, adicionar modificador []
		//if( isset($this->data['duplicable']) and $this->data['duplicable'] == true ){
		//	$this->data['attr']['name'] .= '[]';
		//}
		
		// 'id' - caso não tenha siso definido, usa o valor de 'name'. Em geral virá definido em duplicable
		$this->data['attr']['id'] = empty($this->data['attr']['id']) ? $this->data['name'] : $this->data['attr']['id'];
		
		// 'rel' - caso não tenha sido definido, usa o valor de 'id'
		$this->data['attr']['rel'] = empty($this->data['attr']['rel']) ? $this->data['attr']['id'] : $this->data['attr']['rel'];
		
		// 'size' - aqui na prática será a largura do element
		$size = !empty($this->data['size']) ? "iptw_{$this->data['size']}" : '';
		
		/**
		 * Irá mesclar os seguintes dados:
		 * 'boros_form_input' 	- em todos os elementos
		 * 'input_{type}' 		- para cada tipo de elemento
		 * $size 			- definido anteriormente
		 * $class 			- string de class da configuração
		 * $dependent 		- definição que possui um campo dependente
		 * 
		 * E por último irá adicionar a string definida no elemento em $valid_attrs['class']. Diferente dos atributos definidos em add_defaults(), que apenas definem valores a serem
		 * usados em caso de valores não definidos, a string de $valid_attrs['class'] será adicionada. Considera-se que todos os elementos deverão habilitar o atributo 'class'.
		 * 
		 * IMPORTANTE: aqui é configurado apenas o array com os attrs pós processados, e cada element deverá manipular(se necessário) esse array fazer o output 
		 * com make_attributes(), que é semelhante à function maek_dataset()
		 * 
		 */
		if( !empty($this->data['attr']['class']) )
			$class = "{$this->data['attr']['class']} {$this->valid_attrs['class']}";
		else
			$class = isset($this->valid_attrs['class']) ? $this->valid_attrs['class'] : '';
		
		$dependency = '';
		if( isset($this->data['dependencies']) ){
			if( $this->data['dependencies']['provider'] === true ){
				$dependency = 'provider_input';
			}
			else{
				$dependency = 'dependent_input';
			}
		}
		
		$this->data['attr']['class'] = "boros_form_input input_{$this->data['type']} {$size} {$class} {$dependency}";
		
		// BACKUP!!! if( isset($this->data['attr']['value']) ) $this->data['attr']['value'] = $this->data_value;
		unset($this->data['attr']['value']);
		
		//pre($this->data['attr'], 1);
		// atributos comuns, valores padrão como false serão ignorados caso não tenham sido definidos na config.
		//$this->attrs = '';
		//foreach( $this->data['attr'] as $attr => $val ){
		//	if( $val !== false and array_key_exists($attr, $this->valid_attrs) ){
		//		$this->attrs .= " {$attr}='{$val}'";
		//	}
		//}
		//pal($this->data['attr']['name']);
	}
	
	/**
	 * DATASET
	 * Parâmetros adicionais que poderão ser usados pelo javascript.
	 * Um deles é o 'data-name', que armazena o 'flat name', ou seja o name original definido no array de config, pois em duplicate element por exemplo, o 'name' é modificado
	 * para aninhar os inputs sub um mesmo parent -> parent_name[name], parent_name[name][subname], etc
	 * 
	 * @todo revisar este método para possibilitar que qualquer attribute possa ser osbreescrito no array de configuração
	 * @see set_attributes();
	 */
	function dataset(){
		if( $this->data['in_duplicate_group'] == true )
			$this->data['attr']['dataset']['in_duplicate_group'] = 1;
		else
			$this->data['attr']['dataset']['in_duplicate_group'] = 0;
		
		/**
		 * IMPORTANTE!!! >>> Apenas adicionar o data-name caso não já não tenha sido definido. Duplicate elements já enviam a requisição de create_form_elements() com o data-name definido.
		 * Indicador de parent - caso seja um element simples, será zero, caso seja um duplicate_group, irá herdar o parent setado em duplicate_group.php - create_form_elements()
		 */
		if( !isset($this->data['attr']['dataset']['name']) )
			$this->data['attr']['dataset']['name'] = $this->data['attr']['name'];
		$this->data['attr']['dataset']['parent'] = $this->parent_elem;
		
		// 'provider' não é usado no momento pelo javascript, apenas para o load inicial do elemento, em $this->set_data_value
		if( isset($this->data['dependencies']) ){
			if( $this->data['dependencies']['provider'] === true ){
				$this->data['attr']['dataset']['dependency_dependent'] = $this->data['dependencies']['dependent'];
			}
			else{
				$this->data['attr']['dataset']['dependency_provider'] = $this->data['dependencies']['provider'];
			}
		}
		$this->data['attr']['dataset'] = array_merge( $this->context, $this->data['attr']['dataset'] );
		
		//$this->dataset .= make_dataset($this->data['attr']['dataset']);
		//$this->attrs .= $this->dataset;
	}
	
	/**
	 * Exibir o output final, conforme o layout provavelmente adicionar 'frontend' e 'widget'.
	 * 
	 * @uses $this->input - html do input, já processado pela class extendida
	 * @uses $this->label - html do label, já processado pela class extendida
	 * @uses $this->this->data['type'] - tipo do input
	 * Resolver se este método será final ou poderá ser sobreescrito nas classes extendidas, pois a alteração do html poderá quebrar as tabelas do admin.
	 * 
	 */
	function output(){
		//pre($this);
		//pre($this->attrs);
		//pre($this->context, 'context - form_elements');
		//pre($this->data_value, 'data_value');
		//pre($this->data['attr']['dataset'], $this->data['type']);
		//pre($this->data['index'], 'index');
		//pre($this->data['options']);
		
		/**
		 * Definir wp_nonce_field(), para verificação ao salvar meta_boxes. Não é preciso definir o terceiro parâmetro $referer como true, pois não será executado check_admin_referer()
		 * NÃO DEFINIR NONCE EM DUPLICATES!!!
		 * 
		 */
		//pre($this->in_duplicate_group);
		if( ($this->data['in_duplicate_group'] == false) and !empty($this->data['name']) and ($this->data['type'] != 'html') and ($this->data['type'] != 'separator') ){
			//pal('nonce');
			$nonce = wp_nonce_field( $this->data['name'], "{$this->data['name']}_nonce", false, false );
		}
		else{
			$nonce = '';
		}
		
		/**
		 * HTML de start|end input e start|end label
		 * 
		 */
		$label_start = isset($this->data['label_start']) ? "<div class='form_element_label_start'>{$this->data['label_start']}</div>" : '';
		$label_end = isset($this->data['label_end']) ? "<div class='form_element_label_end'>{$this->data['label_end']}</div>" : '';
		$input_start = isset($this->data['input_start']) ? "<div class='form_element_input_start'>{$this->data['input_start']}</div>" : '';
		$input_end = isset($this->data['input_end']) ? "<div class='form_element_input_end'>{$this->data['input_end']}</div>" : '';
		
		// ATENÇÃO: nunca enviar o output dos elementos sem identificar com as classes corretas, pois alguns controles de javascript dependem destes identificadores
		$error_class = !empty($this->errors) ? ' error has-error' : '';
		$holder_class = isset($this->data['attr']['elem_class']) ? $this->data['attr']['elem_class'] : '';
		$elem_class = "boros_form_element boros_element_{$this->data['type']} layout_{$this->data['layout']} {$holder_class}{$error_class}";
		
		// Definir id do box. Caso não tenha sido declarado(provavelmente será um box tax_input), tentar definir um nome através dos options
		if( !empty($this->data['name']) ){
			$box_name = "box_{$this->data['name']}";
		}
		else{
			// O valor padrão é o type do elemento. Espera-se que não existam dois elements sem name do mesmo tipo sem options
			$box_name = $this->data['type'];
			// tax_input
			if( isset( $this->data['options']['taxonomy'] ) ){
				$box_name = "box_{$this->data['options']['taxonomy']}";
			}
		}
		
		switch( $this->data['layout'] ){
			// atualmente sem uso
			case 'simple':
				echo "<div class='{$elem_class}' id='box_{$this->data['name']}'>{$input_start}{$nonce}{$this->final_input}{$this->error_messages}{$input_end}</div>";
				break;
				
			case 'frontend':
				echo "<div class='{$elem_class}' id='box_{$this->data['name']}'>{$input_start}\n{$nonce}\n{$label_start}\n{$this->label}\n{$label_end}\n{$this->final_input}\n{$this->error_messages}{$input_end}</div>";
				break;
				
			case 'frontend_verbose':
				echo "<div class='{$elem_class}' id='box_{$this->data['name']}'><div class='boros_form_element_inner'>{$input_start}\n{$nonce}\n{$label_start}\n{$this->label}\n{$label_end}\n{$this->final_input}\n{$this->error_messages}{$input_end}</div></div>";
				break;
			
			case 'bootstrap':
				if( $this->data['type'] == 'hidden' ){
					echo $this->final_input;
				}
				else{
					?>
					<div class="control-group <?php echo $elem_class; ?>" id="<?php echo $this->data['attr']['id'];?>_control">
						<?php echo $this->label; ?>
						<div class="controls">
							<?php echo $this->final_input; ?>
							<?php echo $this->error_messages; ?>
						</div>
					</div>
					<?php
				}
				break;
			
			case 'bootstrap_actions':
				?>
				<div class="form-actions">
					<?php echo $this->final_input; ?>
				</div>
				<?php
				break;
			
			case 'bootstrap3':
				if( $this->data['type'] == 'hidden' ){
					echo $this->final_input;
				}
				else{
					?>
					<div class="form-group <?php echo $elem_class; ?>" id="<?php echo $this->data['attr']['id'];?>_control">
						<?php echo $this->label; ?>
						<?php echo $this->final_input; ?>
						<?php echo $this->error_messages; ?>
					</div>
					<?php
				}
				break;
			
			// admin
			case 'block':
				$id = isset($this->data['name']) ? " id='box_{$this->data['name']}'" : '';
				?>
				<tr valign="top">
					<td class="<?php echo $elem_class; ?>" colspan="2" id="<?php echo $box_name; ?>">
						<?php echo $input_start; ?>
						<?php echo $label_start; ?>
						<?php echo (empty($this->label)) ? '' : $this->label; ?>
						<?php echo $label_end; ?>
						<?php echo $nonce; ?>
						<?php echo $this->final_input; ?>
						<?php echo $this->error_messages; ?>
						<?php echo $input_end; ?>
					</td>
				</tr>
				<?php
				break;
			
			// admin
			case 'table':
			default:
				?>
				<tr valign="top">
					<th class="<?php echo $elem_class; ?> boros_form_element_th" scope="row">
						<?php echo $label_start; ?>
						<?php echo $this->label; ?>
						<?php echo $label_end; ?>
					</th>
					<td class="<?php echo $elem_class; ?> " id="<?php echo $box_name; ?>">
						<?php echo $input_start; ?>
						<?php echo $nonce; ?>
						<?php echo $this->final_input; ?>
						<?php echo $this->error_messages; ?>
						<?php echo $input_end; ?>
					</td>
				</tr>
				<?php
				break;
		}
	}
	
	/**
	 * Adicionar os js|css necessários à fila de registro.
	 * Como estes pedidos são realizado após o output do <HEAD>, ambos os scripts(js E css) serão renderizados no final do HTML.
	 * 
	 * Cada form element precisa declarar a variável $enqueues com os valores desejados. Aceita string simples ou array numerico
	 * Existe o modificador $this->enqueues['folder'], para modificar o caminho de arquivo
	 * 
	 * @TODO: registrar o caminho de pasta na classe, para esse caminho seja declarado para o elemento extendido.
	 * 
	 */
	function enqueues(){
		if( isset( $this->enqueues['css'] ) ){
			$files = $this->enqueues['css'];
			$files_array = is_array($files) ? $files : array($files);
			$folder = isset($this->enqueues['folder']) ? $this->enqueues['folder'] . 'css/' : BOROS_CSS;
			foreach( $files_array as $css ){
				$css_url = $folder . $css . '.css';
				wp_enqueue_style( "form_element_{$this->data['type']}_{$css}", $css_url, false, version_id(), 'screen' );
			}
		}
		
		if( isset( $this->enqueues['js'] ) ){
			$files = $this->enqueues['js'];
			$files_array = is_array($files) ? $files : array($files);
			$folder = isset($this->enqueues['folder']) ? $this->enqueues['folder'] . 'js/' : BOROS_JS;
			foreach( $files_array as $js ){
				// emqueue absoluto
				if( is_array($js) ){
					wp_enqueue_script( $js[0], $js[1], NULL, NULL );
				}
				else{
					$js_url = $folder . $js . '.js';
					wp_enqueue_script( "form_element_{$this->data['type']}_{$js}", $js_url, array('jquery'), NULL );
				}
			}
		}
	}
	
	function error_messages(){
		if( !empty($this->errors) ){
			foreach( $this->data['errors'] as $error ){
				if( $this->data['layout'] == 'bootstrap' ){
					$this->error_messages .= "<span id='{$error['name']}_error_message' class='help-inline message_type_{$error['type']} help-block'>{$error['message']}</span>";
				}
				else{
					$this->error_messages .= "<div id='{$error['name']}_error_message' class='form_element_message message_type_{$error['type']} help-block'>{$error['message']}</div>";
				}
			}
		}
	}
}



/**
 * Criar o output de attributes a partir de um array
 * 
 */
function make_attributes( $args = array(), $prefix = '' ){
	$attrs = '';
	foreach( $args as $k => $v ){
		if( $v !== false and ($k != 'dataset' and $k != 'elem_class') ){
			$attrs .= " {$prefix}{$k}='{$v}'";
		}
		elseif( $k == 'dataset' ){
			$attrs .= make_attributes( $v, 'data-' );
		}
	}
	return $attrs;
}


