<?php
/**
 * ==================================================
 * AJAX QUERY LOOP ==================================
 * ==================================================
 * Classe base para criar páginas de admin que utilizam loop para realizar tarefas em um grupo de posts através de 
 * queries complexas.
 * 

$args = array(
	'page_args' => array(
		'page_title' => 'Título da página',
		'menu_title' => 'Título no menu',
		'menu_slug'  => 'slug-da-pagina',
		'intro_html' => 'HTML de introdução da página',
	),
	'ajax_args' => array(
		'action' => 'boros_ajax_query_loop',
		'initial_offset' => 0,
	),
	'query_args' => array(
		// query WP_Query
	),
);

 * 
 */

abstract class Boros_Ajax_Query_Loop {
	
	protected $page_args = array(
		'page_title' => 'Page title',
		'menu_title' => 'Menu title',
		'menu_slug'  => 'page-slug',
		'intro_html' => '<p>Page intro</p>',
	);
	
	protected $ajax_args = array(
		'action' => 'boros_ajax_query_loop',
		'initial_offset' => 0,
	);
	
	protected $query_args = array();
	
	final public function __construct( $args ){
		$this->page_args  = boros_parse_args( $this->page_args, $args['page_args'] );
		$this->ajax_args  = boros_parse_args( $this->ajax_args, $args['ajax_args'] );
		$this->query_args = $args['query_args'];
		
		add_action( 'admin_menu', array($this, 'add_menu_page') );
		add_action( "wp_ajax_{$this->ajax_args['action']}", array($this, 'ajax') );
	}
	
	/**
	 * Registrar página do admin
	 * 
	 */
	final public function add_menu_page(){
		add_menu_page( $this->page_args['page_title'], $this->page_args['menu_title'], 'activate_plugins', $this->page_args['menu_slug'], array($this, 'output') );
		add_action( 'admin_print_footer_scripts', array($this, 'footer') );
	}
	
	/**
	 * Adicionar scripts inline
	 * 
	 */
	final public function footer(){
		global $hook_suffix;
		if( $hook_suffix == "toplevel_page_{$this->page_args['menu_slug']}" ){
			?>
			<script type="text/javascript">
			var boros_ajax_query_loop_offset = <?php echo $this->ajax_args['initial_offset']; ?>;
			jQuery(document).ready(function($){
				var boros_ajax_query_loop = {
					btn : false,
					init : function(){
						$('#boros-ajax-query-loop-submit').on('click', function(){
							boros_ajax_query_loop.btn = $(this);
							boros_ajax_query_loop.btn.prop('disabled', true);
							var results = $('#boros-ajax-query-loop-results');
							results.html('');
							
							// verificar se existe algum offset definido por campo de texto
							if( $('#boros_ajax_query_loop_initial_offset').length && $('#boros_ajax_query_loop_initial_offset').val() > 0 ){
								boros_ajax_query_loop_offset = $('#boros_ajax_query_loop_initial_offset').val();
								$('#boros-ajax-query-loop-results').attr('start', Number(boros_ajax_query_loop_offset) + 1);
							}
							boros_ajax_query_loop.proccess_item();
						});
					},
					proccess_item : function(){
						var data = {
							action : '<?php echo $this->ajax_args['action']; ?>',
							offset : boros_ajax_query_loop_offset,
						};
						
						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: data,
							success: function(response){
								var resp = JSON.parse(response);
								if( typeof resp !== 'object' ){
									var n = boros_ajax_query_loop_offset + 1;
									$('#boros-ajax-query-loop-results').append('<li class="text_error">Ocorreu um problema com a requisição. Utilize o offset ' + n + '</li>');
									boros_ajax_query_loop.btn.prop('disabled', false);
								} else {
									console.log(resp);
									// append do resultado independente se irá continuar o loop
									$('#boros-ajax-query-loop-results').append(resp.html);
									// continuar o loop
									if( resp.offset > 0 ){
										boros_ajax_query_loop_offset = resp.offset;
										boros_ajax_query_loop.proccess_item();
									}
									// reabilitar o botão
									else if( resp.post_id == 0 ){
										boros_ajax_query_loop.btn.prop('disabled', false);
									}
								}
							},
							error: function(XMLHttpRequest, textStatus, errorThrown) {
								alert('Ocorreu um erro na requisição, consultar o console.');
								console.log('erro');
								console.log(textStatus);
								console.log(errorThrown);
								boros_ajax_query_loop.btn.prop('disabled', false);
							}
						});
					}
				};
				boros_ajax_query_loop.init();
			});
			</script>
			<?php
		}
	}
	
	final public function output(){
		?>
		<div class="wrap">
			<h1><?php echo $this->page_args['page_title']; ?></h1>
			<?php echo $this->page_args['intro_html']; ?>
			<ol id="boros-ajax-query-loop-results" start="1"></ol>
			<p class="submit">
				<button id="boros-ajax-query-loop-submit" class="button button-primary">Iniciar</button>
			</p>
		</div>
		<?php
	}
	
	/**
	 * A classe estendida precisa declarar este método para realizar as tarefas específicas do projeto.
	 * 
	 */
	abstract public function ajax();
	
}



