<?php
/**
 * MODELO DE REFERÊNCIA
 * Possui exemplos de aplicação, não necessários todos serão usados. Documentar aqui todas as possibilidades que poderão 
 * ser usadas ao extender a class BorosFormElement.
 * 
 * 
 * Name: BFE_{type}, ex: BFE_text para 'text', BFE_custom_element para 'custom_element'
 * 
 * 
 */

class BFE_form_element_model extends BorosFormElement {
	/**
	 * Lista de atributos aceitos pelo elemento, e seus respectivos valores padrão.
	 * Caso seja definido qualquer outro atributo no array de configuração ele será ignorado.
	 * Definir qualquer valor padrão ou string vazia(''), irá obrigatoriamente renderizar o atributo, independente do valor. 
	 * Valor padrão 'false' só irá renderizar o atributo caso ele seja definido no array de configuração.
	 * 
	 * Atenção: NÃO INCLUIR dataset - este atributo será adicionado em set_elements(), que irá separar os diversos datasets necessários
	 */
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'placeholder' => '',
		'size' => false,
		'disabled' => false,
		'readonly' => false,
		'maxlength' => false,
	);
	
	/**
	 * Adicionar enqueues: css e javascript
	 * Geralmente é necessário apenas adicionar javascript do core e não css
	 * 
	 * ['folder'] string Caminho do diretório onde estarão as pastas /js e /css, normalmente a pasta do plugin.
	 *                   Por padrão a pasta usada é as BOROS_JS e BOROS_CSS
	 * ['js'] array Lista de names dos arquivos js customizados a serem adicionados. EM caso de js absoluto, utilizar 
	 *              array com dois itens: name e url
	 * ['css'] array Lista de names dos arquivos css customizados a serem adicionados
	 * ['core']
	 *     ['js'] array Lista de names dos javascripts já registrados do core
	 *     ['css'] array Lista de names dos css já registrados do core
	 * 
	 */
	var $enqueues = array(
		'folder' => 'url_to_plugin_directory',
		'js' => array(
			'my_custom_js_name',
			'my_second_custom_js',
			array('google_grecaptcha', 'https://www.google.com/recaptcha/api.js?onload=boros_grecaptcha_onload&render=explicit'),
		),
		'css' => array( 'my_custom_css_name', 'my_second_custom_css' ),
		'core' => array(
			'js' => array('jquery-ui-draggable', 'admin-tags'),
			'css' => array('wp-pointer', 'wp-mediaelement'),
		),
	);
	
	/**
	 * Atributo compartilhado entre todas as instâncias do elemento.
	 * Caso ele seja modificado dentro de uma instância, esse valor modificado será acessado por quaisquer instâncias subsequentes.
	 * 
	 */
	private static $counter = 1;
	
	/**
	 * Caso o elemento precise realizar alguma operação antes de qualquer método padrão, utilizar o init()
	 * Neste exemplo, a propriedade da class $counter é verificada, caso seja o valor inciial, é adicionado uma action 
	 * em wp_footer e a function one_time(), em seguida é modificado com +1. A próxima instância terá o $counter = 2, a seguinte = 3, etc.
	 * 
	 */
	function init(){
		// acionar apenas na primeira instância
		if( self::$counter == 1 ){
			$this->one_time();
			add_action( 'wp_footer', array($this, 'footer') );
		}
		self::$counter++;
	}
	
	function footer(){
		echo '<!-- ' . self::$counter . '-->';
	}
	
	/**
	 * Executar este método apenas uma vez
	 * 
	 */
	function one_time(){
		// $this->data['attr']['id'] = 'primeiro-elemento-lorem-ipsum';
		// echo 'ONE TIME!!!';
	}
	
	/**
	 * Includes adicionais
	 * 
	 */
	function includes(){
		require_once( BOROS_LIBS . DIRECTORY_SEPARATOR . 'grecaptcha/recaptchalib.php' );
	}
	
	/**
	 * Adicionar valores default
	 * Serão usados quando determinada chave não for definida na configuração individual do elemento.
	 * É utilizada por $this->set_defaults(), que define os valores que serão usados por $this->set_attributes()
	 * 
	 * 
	 */
	function add_defaults(){
		$this->defaults['label']                        = 'Label padrão';
		$this->defaults['label_helper']                 = 'texto de ajuda padronizado';
		$this->defaults['options']['show_option_none']  = false;
		$this->defaults['options']['option_none_value'] = 0;
		$this->defaults['options']['checked_ontop']     = false;
		$this->defaults['options']['other_field']       = 'Lore ipsum';
	}
	
	/**
	 * Filtrar $this->data antes que seja manipulado
	 * É aplicado ao final de $this->set_defaults(), portanto ainda será modificado pelos métodos subsequentes. Este filtro
	 * deverá ser usado para adicionar/normalizar/corrigir/alterar valores à $this->data
	 * 
	 */
	function filter_data(){
		$this->data['attr']['maxlength']                 = 120;
		$this->data['attr']['dataset']['my-custom-data'] = 'lorem ipsum';
		$this->data['attr']['dataset']['my-second-data'] = $this->defaults['options']['image_size'];
	}
	
	/**
	 * Sobrepor o método para criar o label do elemento
	 * Caso o label precise ser diferente do padrão
	 * 
	 */
	function set_label(){
		if( !empty($this->data['label']) )
			$this->label = "<span class='non_click_label'>{$this->data['label']}{$this->label_helper}</span>";
	}
	
	/**
	 * Adicionar variaveis de javascript
	 * É o último método a ser acionado, antes de $this->output()
	 * É possível adicionar váriáveis para cada instância do elemento ou uma única para todos, no caso de variáveis de,
	 * tradução, nesse caso é preciso utilizar uma propriedade estática da class para sinalizar
	 * 
	 */
	private static $localized = 0;
	
	function localize_script(){
		add_filter( 'admin_footer', array($this, 'localize') );
	}
	
	function localize(){
		// Para cada uma das instâncias do elemento. É possível diferenciar as instâncias utilizando $localized
		$array = array(
			'lorem'
		);
		wp_localize_script( 'name_of_enqueued_script', 'global_var_name', $array );
		
		// Apenas uma vez
		if( self::$localized == false ){
			global $wp_locale;
			$locale_array = array(
				'monthNames'        => $locale_strings['month'],
				'monthNamesShort'   => $locale_strings['month_abbrev'],
				'dayNamesShort'     => $locale_strings['weekday_abbrev'],
				'dayNamesMin'       => $locale_strings['weekday_initial'],
			);
			wp_localize_script( 'unique_name_of_enqueued_script', 'unique_global_var_name', $locale_array );
			// sinalizar que o script já foi localizado
			self::$localized = 1;
		}
		else{
			self::$localized++;
		}
	}
	
	/**
	 * Definir um array de callbacks a serem registrados no elemento
	 * 
	 */
	static function set_callback_functions(){
		$callbacks = array(
			'taxonomy_term_order',         // function
			array('MyClass', 'my_method'), // static class method
			array($obj, 'obj_method'),     // class instance method
		);
		return $callbacks;
	}
	
	/**
	 * Saída final do input
	 * ATENÇÃO: utilizar o $value e não $this->data_value como valor do input, pois nos casos de 
	 * elemento duplicável(não confundir com o grupo duplicável), o valor em $this->data_value será um array, 
	 * e em $value já estará separado o valor do elemento individual.
	 * 
	 */
	function set_input( $value = null ){
		/**
		 * $post só está disponível nas páginas de postagem de post type('post', 'page', 'cpt')
		 * Caso seja preciso utilizar $post, é melhor certificar do acesso à variável verificando $this->context['type']
		 * 
		 */
		global $post;
		
		/**
		 * HTML com todos os atributos do elemento, incluindo 'name' e 'id', mas sem 'value', que ainda poderá ser manipulado pela class
		 * 
		 */
		$attrs = $this->make_attributes($this->data['attr']);
		
		/**
		 * Todas as configurações específicas do elemento devem estar em $this->data['options']. A partir delas, set_input()
		 * poderá fazer as modificações apropriadas conforme as opções
		 * 
		 */
		//$this->data['options']
		
		/**
		 * Separar os contextos.
		 * Caso o elemento possua comportamentos diferenciados conforme o contexto, utilizar esse switch para separar os
		 * as operações. Por exemplo, em taxonomy_radio, o option selecionado em post_meta, será os termos atribuidos ao
		 * post, nos outros contextos, será o valor gravado como 'option|user_meta|term_meta|post_meta', e o name também
		 * precisará ser diferente, para que funcione como um substituto do metabox de taxonomia padrão.
		 * 
		 */
		switch( $this->context['type'] ){
			case 'option':
				// operações caso seja uma página de opções
				break;
			
			case 'user_meta':
				// operações caso seja o profile de usuário
				break;
			
			case 'termmeta':
				// operações caso seja uma página de edição de termo
				break;
			
			case 'frontend':
				// operações caso seja uma frontend
				break;
			
			case 'post_meta':
			default:
				// operações caso seja uma página de post type
				break;
		}
		
		/**
		 * HTML final do input a ser retornado. deverá possuir o name e value para serem enviados via POST
		 * 
		 */
		$input = "<input type='text' value='{$value}' {$attrs} />";
		
		/**
		 * Subitem
		 * Em casos onde o elemento é composto de diversos inputs, por exemplo hora|minuto ou input:text + select, esses
		 * inputs precisam ter o name no modelo name="lorem[ipsum]"
		 * É necessário enviar ao menos o 'key' e 'id' para o make_attributes() para que o name e id sejam corrretamente
		 * definidos.
		 * 
		 */
		// verificar se existe o subvalor
		$subitem_value = isset($value['subname']) ? $value['subname'] : '';
		// é possível definir attrs que serão diferentes do parent aqui
		$sub_attr = array();
		// atribuir os attrs do parent aonde não foi definido para subitem
		$subitem_attr = boros_parse_args($this->data['attr'], $sub_attr);
		// definir a chave do subitem, *obrigatório para subitens
		$subitem_attr['dataset']['key'] = $name;
		// definir a id do subitem, *obrigatório para subitens
		$subitem_attr['id']             = "{$this->data['name']}_{$name}";
		// definir a class
		$subitem_attr['class']          = "{$this->data['attr']['class']} {$item_attr['class']} sub-item";
		// criar a string de atributos
		$sattr = $this->make_attributes($item_attr);
		// adicionar o subitem ao HTML final
		$input .= "<input type='text' value='{$item_value}' {$sattr} /> ";
		
		
		
		/**
		 * Modelo utilizando output buffer
		 * 
		 */
		ob_start();
		echo 'OUTPUT HTML NORMAL dentro da tag "?php"';
		?>
		OUTPUT HTML NORMAL fora de "?php"
		<?php
		$input = ob_get_contents();
		ob_end_clean();
		
		return $input;
	}
}