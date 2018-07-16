<?php
/**
 * ==================================================
 * ADD ADMIN PAGES ==================================
 * ==================================================
 *
 * Adicionar todas as páginas de admin customizadas. Os includes dos arquivos são globais(admin e frontend), para que functions presentes nos arquivos 
 * possam ser acessados no frontend.
 * 
 * Timeline:
 * Ao executar new BorosAdminPages(), __construct() - esse trecho configura todas as páginas, fazendo o correto registro das opções para serem verificadas e gravadas no contexto de wp-admin/options.php
 * __construct() > loop em todas as pages da configuração > add_menu_page()
 * __construct() > loop > include do arquivo
 * __construct() > loop > set_elements() - guarda o array de $views, separa as abas se houver(includes) e executa o register_settings()
 *		register_settings() > loop > regiter_settings(core)
 *		register_settings() > loop > adiciona callbacks
 *		register_settings() > loop > adiciona validation/sanitize
 * __construct() > loop > subpages - adiciona as subpages, em processo muito semelhante ao anterior, porém adaptado às subpages
 * 
 * Ao exibir o conteúdo da página, é executado $this->output(), que foi registrado em add_menu_page() e add_submenu_page()
 * $this->output() > define $this->current_page
 * $this->output() > define $this->current_tab
 * $this->output() > define $this->settings_name - pode variar caso seja uma aba
 * $this->output() > define $this->elements - pode variar caso seja uma aba
 * $this->output() > $this->enqueues() - enfileira os enqueues para a página corrente
 * 		$this->enqueues() > $this->enqueue_css() e $this->enqueue_js()
 * $this->output() > loop > output_page_header() ou output_page_section() - essas duas funções são bem semelhantes à do_settings_sections() do core
 * 
 * 
 * @TODO adicionar mais opções ao array de config, que no momento só configura os $elements, para permitir configurar botão de submit, textos, mensagens de callback da página, etc >>> SUGESTÃO: fazer uma function nova para que não quebre os sites antigos
 * @TODO talvez colocar os includes in 'init', para as functions estejam disponíveis em qualquer contexto, inclusive ajax
 */

class BorosAdminPages {
	/**
	 * Armazena as configurações das páginas/sub-páginas declaradas.
	 * É definido no construct e guarda a configuração original simples.
	 */
	var $pages;
	
	/**
	 * Diretório do servidor aonde estão as admin_pages a serem incluídas.
	 * Como cada plugin poderá fazer sua chamada de BorosAdminPages, é preciso declarar o local correto dos includes
	 * 
	 */
	var $folder_base;
	
	/**
	 * URL base do plugin, que irá criar as chamadas de js|css específicos de cada página.
	 * Cada plugin poderá ter scripts próprios.
	 * 
	 */
	var $url_base;
	
	/**
	 * Guarda o slug das páginas no formato $page_action => $page_slug
	 * Dessa forma quando executar o output(), podemos verificar a action atual e identificar o slug da página e assim a config correta a exibir
	 * 
	 */
	protected $views = array();
	
	/**
	 * Guarda as informações dos includes não encontrados, para posterior mensagem de erro.
	 * Como as definições dos elementos estão nesses arquivos, é preciso interromper a execução do loop de exibição.
	 * 
	 */
	protected $not_founds = array();
	
	/**
	 * Informações da página corrente.
	 * 
	 * Embora seja preciso registrar as opções para todas as páginas, ainda existe o contexto da página que está sendo exibida. Nesse contexto será executado o output dos elementos com 
	 * base na config declarada, assim como os enqueues necessários.
	 * 
	 * @var $current_page	guarda o name da página corrente, ou seja a chave no array de configuração, que corresponde também ao, slug da página, nome de arquivo do include, e 
	 * 					função de configuração. Definido em add_help(), pois é a primeira action a rodar, em load-$page_name. Usado por output() e enqueues(), enqueues_css() e enqueue_js()
	 * @var $tabs			guarda o array de abas da página corrente. Usado por set_elements() e output() e output_page_header() - inicia vazio(zero abas)
	 * @var $current_tab	guarda a aba corrente, usado por output() e output_page_header()
	 * @var $elements		armazena a configuração dos elementos da página corrente, usado por output()
	 */
	var $current_page;
	var $tabs = array();
	var $current_tab;
	var $elements;
	
	/**
	 * Configurações carregadas pelos callbacks
	 * 
	 */
	var $loaded_configs = array();
	
	/**
	 * Contexto
	 * 
	 * $type
	 * $admin_page
	 * $group
	 */
	var $context = array(
		'type' => 'option',
	);
	
	
	/**
	 * Registrar os actions e filters
	 * 
	 * 1* a prioridade 9 é necessária ao 'admin_menu' para as situações onde um post_type ficará como sub-menu de uma options-page. Assim a page será registrada antes do post_type, fazendo com que
	 * o primeiro level do menu aponte para a option-page, caso contrário será apontado para o post_type
	 */
	function __construct( $config, $folder_base, $url_base ){
		//if( isset($_POST) ){ pre($_POST); die(); }
		
		$this->pages = $config;
		$this->folder_base = $folder_base;
		$this->url_base = $url_base;
		
		//add_action( 'init', array($this, 'includes') );
		add_action( 'admin_init', array($this, 'frontend') );
		add_action( 'admin_menu', array($this, 'admin'), 9 ); // ver 1*
		add_action( 'template_redirect', array($this, 'frontend') );
		add_filter( 'load_element_config', array($this, 'load_element_config'), 10, 2 );
	}
    
	/**
	 * Adicionar as page no admin.
	 * Registro das pages e subpages, assim como os hooks, que farão os includes quando necessário, assim como a fila de js e css
	 * 
	 */
	function admin(){
		foreach( $this->pages as $page_name => $attr ){
			// pular caso seja página do core
			if( isset($attr['type']) and $attr['type'] == 'core' ){
				
			}
			else{
				$admin_page = add_menu_page(
					$page_title	= $attr['page_title'], 
					$menu_title	= $attr['menu_title'], 
					$capability	= isset($attr['capability']) ? $attr['capability'] : 'manage_options', 
					$menu_slug	= apply_filters('boros_menu_page_slug', $page_name, $attr), 
					$function	= array( $this, 'output' ),
					$icon_url	= isset($attr['icon_url']) ? $attr['icon_url'] : '',
					$position	= isset($attr['position']) ? $attr['position'] : null
				);
				
				// include do arquivo ou armazena as informações de arquivo não encontrado
				$admin_page_file = $this->folder_base . "admin_pages/{$page_name}.php";
				if( file_exists($admin_page_file) ){
					include_once( $admin_page_file );
					// Separar as abas, register settings e armazenar os names($views) das páginas
					$this->set_elements( $admin_page, $page_name );
				}
				else{
					$this->not_founds[$admin_page] = array('name' => $page_name, 'attr' => $attr, 'file' => $admin_page_file);
				}
				add_action( 'load-'.$admin_page, array( $this, 'add_help' ) );
			}
			
			// Caso existam subpages registradas, adicionar, herdando a 'capability', caso não declarada.
			// caso seja uma subpage do core, declarar o $capability
			if( isset($attr['subpages']) ){
				foreach( $attr['subpages'] as $subpage_name => $subattr ){
					$capability = isset($subattr['capability']) ? $subattr['capability'] : $attr['capability'];
					$admin_sub_page = add_submenu_page(
						$parent_slug	= $page_name, 
						$page_title		= $subattr['page_title'], 
						$menu_title		= $subattr['menu_title'], 
						$capability		= $capability, 
						$menu_slug		= apply_filters('boros_menu_page_slug', $subpage_name, $subattr), 
						$function		= array( $this, 'output' )
					);
					
					// include do arquivo ou armazena as informações de arquivo não encontrado
					$sub_page_file = $this->folder_base . "admin_pages/{$subpage_name}.php";
					if( file_exists($sub_page_file) ){
						include_once( $sub_page_file );
						// Separar as abas, register settings e aramazenar os names($views) das páginas
						$this->set_elements( $admin_sub_page, $subpage_name );
					}
					else{
						$this->not_founds[$admin_sub_page] = array('name' => $subpage_name, 'attr' => $subattr, 'file' => $sub_page_file);
					}
				}
			}
		}
	}
	
	/**
	 * Adicionar pages no frontend.
	 * Na prática apenas fazer os includes no frontend, para disponibilizar as functions desses arquivos.
	 * É necessário também para o reload de elementos e duplicates
	 * 
	 */
	function frontend(){
		foreach( $this->pages as $page_name => $attr ){
			// includes de toplevel pages e tabs
			$this->load_frontend_files( $page_name );
			
			// includes de subpages e tabs
			if( isset($attr['subpages']) ){
				foreach( $attr['subpages'] as $subpage_name => $subattr ){
					$this->load_frontend_files( $subpage_name );
				}
			}
		}
	}
	
	function load_frontend_files( $page_name ){
		$page_or_sub_page = array_key_search_r( $page_name, $this->pages );
		if( isset($page_or_sub_page['tabs']) ){
			// include arquivo principal
			include_once( $this->folder_base . "admin_pages/{$page_name}.php" );
			// include tabs
			foreach( $page_or_sub_page['tabs'] as $tab => $title ){
				$tab_file = $this->folder_base . "admin_pages/{$page_name}_{$tab}.php";
				if( file_exists($tab_file) )
					include_once( $tab_file );
			}
		}
		// include arquivo principal
		else{
			$admin_page_file = $this->folder_base . "admin_pages/{$page_name}.php";
			if( file_exists($admin_page_file) )
				include_once( $admin_page_file );
		}
	}
	
	function error_file_not_exists( $page ){
		?>
		<div class="alert_box updated admin_page_error" id="admin_page_error_<?php echo $page['name']; ?>">
			<p class="error">O arquivo <code><strong><?php echo $page['file']; ?></strong></code> não existe.</p>
			<p>Ele é necessário para a página <strong><?php echo $page['attr']['page_title']; ?></strong></p>
			<p>Requisitos:</p>
			<ul>
				<li>Arquivo <code><strong><?php echo $page['name']; ?>.php</strong></code></li>
				<li>No arquivo acima, a function <code><strong><?php echo $page['name']; ?>()</strong></code></li>
			</ul>
		</div>
		<?php
	}
	
	function error_function_not_exists( $wp_error ){
		//pre($wp_error);
		$errors = $wp_error->get_error_codes();
		foreach( $errors as $code ){
			$message = $wp_error->get_error_message($code);
			$function_name = $wp_error->get_error_data($code);
			?>
			<div class="alert_box updated admin_page_error" id="admin_page_error_<?php echo $code; ?>">
				<p class="error"><?php echo $message; ?></p>
				<p>Requisitos:</p>
				<ul>
					<li>Function <code><strong><?php echo $function_name; ?>()</strong></code> no arquivo <code><strong><?php echo $function_name; ?>.php</strong></code></li>
				</ul>
			</div>
			<?php
		}
	}
	
	/**
	 * Carrega um array de configuração executando uma chamada de função, retornando uma mensagem de erro caso essa função não exista.
	 * 
	 * 
	 */
	function load_config( $function_name ){
		if( function_exists( $function_name ) ){
			if( !array_key_exists($function_name, $this->loaded_configs) ){
				$config = call_user_func( $function_name );
				$updated_config = update_element_config( $config );
				$this->loaded_configs[$function_name] = $updated_config;
				return $updated_config;
			}
			else{
				return $this->loaded_configs[$function_name];
			}
		}
		else{
			$error = new WP_Error();
			$error->add( 'no_config_function', "A function <code><strong>{$function_name}()</strong></code> não existe." );
			$error->add_data( $function_name, 'no_config_function' );
			return $error;
		}
	}
	
	/**
	 * 
	 * 
	 */
	function set_elements( $admin_page, $page_name ){
		// armazenar os names das pages
		$this->views[$admin_page] = $page_name;
		
		$page_or_sub_page = array_key_search_r( $page_name, $this->pages );
		
		$config = $this->load_config( $page_name );
		// registrar as opções na whitelist do wordpress
		$this->register_settings( $page_name, $config );
		//return;
			
		// Caso tenha sido configuradas abas
		if( isset($page_or_sub_page['tabs']) ){
			$tabs = $page_or_sub_page['tabs'];
			
			// include de todas as abas
			foreach( $tabs as $tab => $title ){
				// considerar apenas a partir da segunda aba, pois a primeira já carregada antes
				if( $tab != key($tabs) ){
					//pre($this->folder_base . "admin_pages/{$page_name}_{$tab}.php");
					if( array_key_exists( $tab, $tabs ) ){
						$filename = $this->folder_base . "admin_pages/{$page_name}_{$tab}.php";
						if( file_exists( $filename ) ){
							include_once( $filename );
							$config = $this->load_config( "{$page_name}_{$tab}" );
							// registrar as opções na whitelist do wordpress
							$this->register_settings( "{$page_name}_{$tab}", $config );
						}
						else{
							pre( "É necessário a criação de arquivo com o este nome e caminho: {$filename}", 'Arquivo da página de opções não encontrado!' );
						}
					}
				}
				else{
					$config = $this->load_config( $page_name );
					$this->register_settings( $page_name, $config );
				}
			}
		}
		// Não possui abas
		else{
			$config = $this->load_config( $page_name );
			// registrar as opções na whitelist do wordpress
			$this->register_settings( $page_name, $config );
		}
	}
	
	/**
	 * Register settings precisa rodar em contexto global, ou seja, o resultado precisa estar acessível em qualquer contexto, pois será verificado 
	 * apenas em /wp-admin/options.php. Por isso é declarado fora do __call(), assim todas as opções serão registradas como válidas
	 * 
	 * $page_name será usado para agrupar as opções no registro( global $whitelist_options ) e também será usado em settings_fields() no output do form
	 * 
	 * 
	 */
	function register_settings( $page_name, $config ){
		// interrompe a execução caso a configuração seja um erro(não existe a function que retorna a config)
		if( is_wp_error($config) ){
			return false;
		}
		
		$context = array(
			'type' => 'option',
			'admin_page' => $page_name,
		);
		$validation = new BorosValidation( $context );
		
		$settings_name = $page_name;
		
		foreach( $config as $block ){
			
			if( isset( $block['itens'] ) ){
				foreach( $block['itens'] as $element ){
					// apenas registrar elements com name declarado
					if( isset( $element['name'] ) ){
						/**
						 * Registrar callbacks da config e callbacks fixos do element
						 * Alguns elements, como {taxonomy|content}_order possuem um callback padronizado.
						 * 
						 */
						// configs
						if( isset( $element['callback'] ) ){
							$this->callbacks[ $element['name'] ][] = $element['callback'];
						}
						// fixos do elemento
						if( method_exists( "BFE_{$element['type']}", 'set_callback_functions' ) ){
							$callbacks = call_user_func( "BFE_{$element['type']}::set_callback_functions" );
							if( $callbacks !== false ){
								foreach( $callbacks as $callback ){
									$this->callbacks[ $element['name'] ][] = $callback;
								}
							}
						}
						
						/**
						 * SKIP SAVE
						 * Pular o salvamento caso seja configurado, por exemplo, caso a admin_page sirva apenas para ativar algum callback.
						 * ATENÇÃO: É PRECISO PELO MENOS UM CAMPO(pode ser hidden) SEM 'skip_save' PARA QUE O BLOCO SEJA REGISTRADO
						 * 
						 */
						if( isset($element['skip_save']) and $element['skip_save'] == true ){
							continue;
						}
						else{
							// sanitize separado de register_setting()
							register_setting( $settings_name, $element['name'] );
						}
						
						// enfileirar validação - ambas validações(fixo do elemento e config) serão setadas apartir do array $element, que irá buscar o 'type' e o 'validate'
						$validation->add( $element );
						
						// as duas validações(fixa e config) irão rodar em sequência nesse filtro, mas apenas no contexto de options.php, com $_POST
						// ATENÇÂO: sanitize_option_$option_name é um filtro padrão do WordPress
						add_filter( "sanitize_option_{$element['name']}", array( $validation, 'verify_option' ), 10, 2 );
						
						/**
						 * Adicionar callbacks de sanitize - prioridade tardia para rodar por último.
						 * Caso não queira que o valor seja gravado, adicione return false no callback.
						 * ATENÇÂO: sanitize_option_$option_name é um filtro padrão do WordPress
						 */
						add_filter( "sanitize_option_{$element['name']}", array( $this, 'callbacks' ), 999, 2 );
					}
				}
			}
		}
	}
	
	/**
	 * @TODO
	 * 
	 * Diferente do contexto dos meta_boxes, os valores já corrigidos só estão acessíveis nesse momento, após o envio para options.php, e portanto os callbacks devem ser realizados aqui. Por isso todos
	 * os options registrados precisam passar por $this->validation.
	 * 
	 * ATENÇÂO: para passar um callback, a config precisa de um name. Caso não seja registrado um name, não será gravado o option nem rodarão os callbacks e validation
	 */
	function callbacks( $value, $option = 'test' ){
		/**
		 * Acionar callbacks de 'on_save'
		 * Passar esse bloco como uma validação, assim será possível retornar false caso não queira salvar o option
		 * 
		 */
		if( isset($this->callbacks[$option]) ){
			foreach( $this->callbacks[$option] as $function ){
				$value = call_user_func( $function, $option, $value );
			}
		}
		return $value;
	}
	
	/**
	 * 
	 * 
	 * Em settings_fields() é usado o 'name' da página já setado para o nome de arquivo/slug/function de configuração/agrupamento de settings, que neste caso 
	 * será a página corrente. Nesse ponto, register_settings() já terá registrado todas as opções na whitelist no grupo de mesmo name
	 * 
	 * http://wp.smashingmagazine.com/2011/10/20/create-tabs-wordpress-settings-pages/
	 * http://theme.fm/2011/10/how-to-create-tabs-with-the-settings-api-in-wordpress-2590/
	 */
	function output(){
		$action = current_filter();
		/**
		 * Verificar se o arquivo do include existe
		 * 
		 */
		if( array_key_exists( $action, $this->not_founds ) ){
			?>
			<div class="wrap">
				<h2>Erro</h2>
				<?php 
				$this->error_file_not_exists( $this->not_founds[$action] );
				?>
			</div>
			<?php
			return;
		}
		
		/**
		 * Identificar a página corrente. Será usado por outros métodos.
		 * 
		 */
		$this->current_page = $this->views[$action];
		
		// Definir abas
		$page_or_sub_page = array_key_search_r( $this->current_page, $this->pages );
		if( isset($page_or_sub_page['tabs']) ){
			$this->tabs = $page_or_sub_page['tabs'];
		}
		
		/**
		 * Aqui, call_user_func(), irá chamar a função de configuração presente no arquivo include. Por exemplo em $this->current_page = 'site_options', o include será admin_pages/site_options.php, e a função site_options()
		 * IMPORTANTE: aqui é preciso separar os elements de cada tab
		 * 
		 */
		if( isset($_GET['tab']) ){
			$this->current_tab = $_GET['tab'];
			//$this->context['option_page'] = $this->current_tab;
			$this->context['option_page'] = "{$this->current_page}_{$this->current_tab}";
			
			if( array_key_exists( $this->current_tab, $this->tabs ) ){
				$this->settings_name = "{$this->current_page}_{$this->current_tab}";
				$this->elements = $this->load_config( "{$this->current_page}_{$this->current_tab}" );
			}
		}
		else{
			// Considera-se a página inicial a primeira aba, portanto será setado existindo ou não as outras abas.
			$this->current_tab = key($this->tabs);
			$this->context['option_page'] = $this->current_page;
			$this->settings_name = $this->current_page;
			$this->elements = $this->load_config( $this->current_page );
		}
		
		// enfileirar js|css desta página
		$this->enqueues();
		
		settings_errors();
		?>
		<div class="wrap">
			<form action="options.php" method="post">
				<?php
				settings_fields( $this->settings_name );
				
				/**
				 * Quando uma configuração de admin page não possui um bloco de submit, aplicar esse modelo padrão.
				 * Configurações antigas não terão esse bloco e devem usar esse trecho.
				 * 
				 */
				$submit_block = array(
					'submit_type' => 'default',
					'text' => 'Atualizar',
					'class' => 'button-primary',
					'parent_class' => 'page-form-submit',
					'html' => false,
				);
				
				/**
				 * Verificar se os elementos estão corretos
				 * 
				 */
				if( is_wp_error( $this->elements ) ){
					$this->error_function_not_exists( $this->elements );
				}
				else{
					/**
					 * Renderizar os tipos de bloco: 'header', 'section', e pular em outros casos, evitando possíveis erros.
					 * 
					 */
					foreach( $this->elements as $block ){
						if( $block['block'] == 'header' ){
							$this->output_page_header( $block );
						}
						elseif( $block['block'] == 'section' ){
							$this->output_page_section( $block );
						}
						elseif( $block['block'] == 'submit' ){
							// mostrar submit custom no meio do formulário, mesmo que não seja o último item
							if( $block['submit_type'] == 'custom' ){
								$submit_block = array(
									'submit_type' => 'custom',
									'text' => $block['options']['text'],
									'class' => $block['options']['class'],
									'parent_class' => $block['options']['parent_class'],
									'html' => $block['options']['html'],
								);
								// exibir submit ou personalizado.
								$this->output_page_submit( $submit_block );
							}
							elseif( $block['submit_type'] == 'none' ){
								$submit_block['submit_type'] = 'none';
								continue;
							}
						}
					}
				}
				
				// submit padrão
				if( $submit_block['submit_type'] == 'default' ){
					$this->output_page_submit( $submit_block );
				}
				?>
			</form>
		</div><!-- /wrap -->
		<?php
	}
	
	function output_page_header( $block ){
		echo "<div class='icon32' id='icon-options-general'><br></div>";
		
		if( empty( $this->tabs ) ){
			echo "<h2 class='section_title'>{$block['title']}</h2>";
		}
		else{
			echo '<h2 class="nav-tab-wrapper">';
			$first_tab = key( $this->tabs );
			foreach( $this->tabs as $tab => $name ){
				$class = ( $tab == $this->current_tab ) ? ' nav-tab-active' : '';
				$url = add_query_arg( array( 'tab' => false, 'settings-updated' => false ) );
				// apenas adicionar query_arg a partir da segunda aba
				if( $tab != $first_tab ){
					$url = add_query_arg( array( 'tab' => $tab, 'settings-updated' => false ));
				}
				$url = apply_filters( 'boros_admin_page_tab_url', $url, $block, $tab, $name );
				echo "<a class='nav-tab{$class}' href='{$url}'>{$name}</a>";
			}
			echo '</h2>';
		}
		
		if( isset($block['desc']) and !empty($block['desc']) )
			echo "<div class='boros_section_desc'>{$block['desc']}</div>";
	}
	
	/**
	 * Output da seção.
	 * Consiste do loop com a exibição dos elementos, seguindo o modelo do WordPress, um <H3> seguido de um <P> descritivo opcional e uma table.form-table. Foi adicionado as classes
	 * 
	 * 
	 */
	function output_page_section( $block ){
		if( isset($block['title']) )
			echo "<h3>{$block['title']}</h3>";
		?>
		
		<!-- .form-table adicionado para herdar formatações do css do core -->
		<table class="form-table boros_form_block boros_options_block" id="<?php echo $block['id'];?>">

			<?php if( isset($block['desc']) and !empty($block['desc']) ){ ?>
			<td colspan="2" class="boros_form_desc">
				<div><?php echo $block['desc']; ?></div>
			</td>
			<?php } ?>
			
			<?php
			foreach( $block['itens'] as $element ){
				$data_value = null;
				// chamar o valor gravado para o input, caso tenha sido definido um name
				if( isset( $element['name']) ){
					// permitir que a configuração sobreponha o valor gravado no momento da exibição. É diferente de std, que é opcional para quando o valor for vazio.
					if( isset($element['value']) ){
						$data_value = $element['value'];
					}
					else{
						$data_value = get_option( $element['name'] );
					}
				}
				
				// se estiver vazio, usar o valor padrão
				if( !boros_check_empty_var($data_value) and isset($element['std']) ){
					$data_value = $element['std'];
				}
				
				// renderizar o elemento
				$this->context['group'] = $block['id'];
				create_form_elements( $this->context, $element, $data_value );
			}
			?>
			
			<?php if( isset($block['help']) and !empty($block['help']) ){ ?>
			<tr>
				<td colspan="2" class="boros_form_extra_info">
					<div>
						<span class="ico"></span> 
						<?php echo $block['help']; ?>
					</div>
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php
	}
	
	function output_page_submit( $block ){
		// separar se é submit configurado ou enviado como html puro
		if( isset($block['html']) and $block['html'] != false ){
			echo $block['html'];
		}
		else{
			echo "<p class='{$block['parent_class']}'><input type='submit' value='{$block['text']}' class='{$block['class']}'></p>";
		}
	}
	
	/**
	 * Verifica se existem js/css para serem inclusos.
	 * No array de configuração, é a chave 'enqueues':
	 <code>
	 $admin_pages['page_name']['enqueues'] = array(
		'css' => array( 'css1', 'css2', 'css3' ),		// valores array
		'js' => array( 'js1', 'js2', 'js3' ),		// valores array
		'css' => 'css1',							// valor string
		'js' => 'js1',							// valor string
	 );
	 </code>
	 * São aceitos valores em array, no caso de múltiplos arquivos ou apenas uma string simples, caso seja apenas um único arquivo.
	 * 
	 */
	function enqueues(){
		// carregar js|css apenas dessa página
		$page_config = array_key_exists_r( $this->current_page, $this->pages );
		if( isset($page_config['enqueues']) ){
			if( isset($page_config['enqueues']['css']) ){
				$this->enqueue_css( $page_config['enqueues']['css'], $this->url_base . 'css/' );
			}
			if( isset($page_config['enqueues']['js']) )
				$this->enqueue_js( $page_config['enqueues']['js'], $this->url_base . 'js/' );
		}
	}
	
	function enqueue_css( $files, $url_base ){
		foreach( $files as $css ){
			$css_url = $url_base . $css . '.css';
			wp_enqueue_style( "admin_page_{$this->current_page}_{$css}", $css_url, false, version_id(), 'screen' );
		}
	}
	
	function enqueue_js( $files, $url_base ){
		foreach( $files as $js ){
			$js_url = $url_base . $js . '.js';
			wp_enqueue_script( "admin_page_{$this->current_page}_{$js}", $js_url, array('jquery'), version_id(), true );
		}
	}
	
	/**
	 * Adicionar ajuda(aba superior direita)
	 * 
	 */
	function add_help(){
		$action = str_replace( 'load-', '', current_filter() );
		$page = $this->views[$action];
		
		$help_function = "{$page}_help";
		if( function_exists( $help_function ) ){
			$help = call_user_func($help_function);
			$screen = get_current_screen();
			
			// help sidebar
			if( isset($help['sidebar']) )
				$screen->set_help_sidebar($help['sidebar']);
			
			foreach( $help['tabs'] as $tab )
				$screen->add_help_tab($tab);
		}
	}
	
	/**
	 * >>> não é possível rodar isso apenas dentro de $this->dupicate_group por conta das várias instancias de metabox
	 * 
	 * @todo Testar e revisar este método
	 */
	function load_element_config( &$config, $context ){
		if( $context['type'] != 'option' )
			return $config;
		
		// carregar a configuração via function > $pagename(), que estão na pasta admin_pages
		$config = $this->load_config( $context['option_page'] );
		
		//pre($context, 'context');
		//pre($config, 'config');
		//pre($config[$context['group']]);
		//pre($config[$context['group']]['itens']);
		//pre($config[$context['group']]['itens'][$context['name']]);
		if( isset($context['in_duplicate_group']) and $context['in_duplicate_group'] == true ){
			$element_config = $config[$context['group']]['itens'][$context['parent']]['group_itens'][$context['name']];
		}
		else{
			$element_config = $config[$context['group']]['itens'][$context['name']];
		}
		return $element_config;
	}
}


class BorosAdminSubPages {
	function __construct(){
		
	}
}