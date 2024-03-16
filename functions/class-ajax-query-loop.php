<?php
/**
 * ==================================================
 * AJAX QUERY LOOP ==================================
 * ==================================================
 * Classe base para criar páginas de admin que utilizam loop para realizar tarefas em um grupo de posts através de 
 * queries complexas.
 * 
 * @todo registrar um exemplo mais completo com campos extras e retorno
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


Exemplo completo
add_action( 'init', function(){

    $args = array(
        'page_args' => array(
            'page_title' => 'Gerar Inscrições',
            'menu_title' => 'Gerar Inscrições',
            'menu_slug'  => 'generate-subs',
            'parent'     => false,
            'intro_html' => '<p>Arrumar o campo.</p><p>Offset: <input type="text" id="boros_ajax_query_loop_initial_offset" /></p>',
        ),
        'ajax_args' => array(
            'action'         => 'jlpt_gen_subs',
            'initial_offset' => 0,
        ),
        'query_args' => array(
            'post_type'      => 'post',
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ),
    );
    
    $gensubs = new JLPT_Generate_Subs( $args );

} );

class JLPT_Generate_Subs extends Boros_Ajax_Query_Loop {
    
    public function ajax(){

        $offset = ($_POST['offset'] < 4 ) ? ($_POST['offset'] + 1) : 0;
        $post_id = 1;


        echo json_encode(array(
            'offset'  => $offset,
            'post_id' => $post_id,
            'html'    => '<li>qwe qwe qweqwe</li>',
        ));
        die();
    }
}


 * 
 */

abstract class Boros_Ajax_Query_Loop {
	
	protected $page_args = array(
		'page_title'   => 'Page title',
		'menu_title'   => 'Menu title',
		'menu_slug'    => 'page-slug',
		'parent'       => false,
        'intro_html'   => '<p>Page intro</p>',
        'offset_field' => true,
	);
	
	protected $ajax_args = array(
		'action'         => 'boros_ajax_query_loop',
		'interval'       => 0,
		'initial_offset' => 0,
	);
	
    protected $query_args = array();
    
    protected $offset_field = 'boros_ajax_query_loop_initial_offset';
	
	final public function __construct( $args ){
		$this->page_args  = boros_parse_args( $this->page_args, $args['page_args'] );
		$this->ajax_args  = boros_parse_args( $this->ajax_args, $args['ajax_args'] );
		$this->query_args = $args['query_args'];
		
		if( $this->page_args['parent'] == false ){
			add_action( 'admin_menu', array($this, 'add_menu_page') );
		}
		else{
			add_action( 'admin_menu', array($this, 'add_submenu_page') );
		}
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
	 * Registrar subpágina do admin
	 * 
	 */
	final public function add_submenu_page(){
		add_submenu_page( $this->page_args['parent'], $this->page_args['page_title'], $this->page_args['menu_title'], 'activate_plugins', $this->page_args['menu_slug'], array($this, 'output') );
		add_action( 'admin_print_footer_scripts', array($this, 'footer') );
    }
    
    /**
     * Formulário com campo de offset e campos adicionais
     * 
     */
    final public function form(){
        echo '<form onsubmit="return false;" id="boros-ajax-loop-form">';
        $this->form_start();
        $this->offset_field();
        $this->form_end();
        echo '</form>';
    }

    protected function offset_field(){
        if( $this->page_args['offset_field'] == true ){
            echo "<p>Offset: <input type='text' id='{$this->offset_field}' /></p>";
        }
    }

    public function form_start(){
        
    }

    public function form_end(){
        
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
			var boros_ajax_query_loop_offset  = <?php echo $this->ajax_args['initial_offset']; ?>;
			var boros_ajax_query_loop_timeout = <?php echo $this->ajax_args['interval']; ?>;
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
							if( $('#<?php echo $this->offset_field; ?>').length && $('#<?php echo $this->offset_field; ?>').val() > 0 ){
								boros_ajax_query_loop_offset = $('#<?php echo $this->offset_field; ?>').val();
								$('#boros-ajax-query-loop-results').attr('start', Number(boros_ajax_query_loop_offset) + 1);
							}
							boros_ajax_query_loop.proccess_item();
						});
					},
					proccess_item : function(){

                        var form_data = $('#boros-ajax-loop-form').serializeArray();

						var data = {
							action : '<?php echo $this->ajax_args['action']; ?>',
							offset : boros_ajax_query_loop_offset,
						};

                        $(form_data).each(function(i, field){
                            data[field.name] = field.value;
                        });
                        console.log(data);
						
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
                                        setTimeout(function(){boros_ajax_query_loop.proccess_item()}, boros_ajax_query_loop_timeout);
									}
									// reabilitar o botão
									else if( resp.post_id == 0 ){
										boros_ajax_query_loop_offset = 0;
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
            <style type="text/css">
            #boros-ajax-query-loop-results li {
                background-color: #fff;
                border: 1px dotted #ccc;
                margin: 0 0 10px;
                padding: 10px;
            }
            #boros-ajax-query-loop-results li .pre_box {
                margin: 0 0 10px;
            }
            #boros-ajax-query-loop-results li .message_title {
                border: 1px dotted #ccc;
                font-size: 16px;
                font-weight: bold;
                margin: 0 0 10px;
                padding: 10px;
            }
            #boros-ajax-query-loop-results li .message {
                border: 1px dotted #ccc;
                margin: 0 0 10px;
                padding: 10px;
            }
            #boros-ajax-query-loop-results li .alert {
                border-color: red;
                color: red;
            }
            #boros-ajax-query-loop-results li .success {
                border-color: green;
                color: green;
            }
            #boros-ajax-query-loop-results li .divider {
                border-top: 1px dotted #ccc;
                margin: 20px 0;
            }
            </style>
			<?php
		}
	}
    
    final public function ajax_response( $offset, $post_id, $status = 'success', $message ){
        echo json_encode(array(
            'offset'  => $offset,
            'post_id' => $post_id,
            'html'    => "<li class='text_{$status}'>{$message}</li>",
        ));
        die();
    }
    
	final public function output(){
		?>
		<div class="wrap">
			<h1><?php echo $this->page_args['page_title']; ?></h1>
			<?php echo $this->page_args['intro_html']; ?>
            <?php $this->form(); ?>
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



