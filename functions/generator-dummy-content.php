<?php
/**
 * ==================================================
 * GERADOR DE CONTEÚDO DUMMY ========================
 * ==================================================
 * Conteúdo a ser usado para posts de testes
 * 
 * 
 */


class BorosDummyContent {
	
	private $lipsum_size = 10;
	
	private $lipsum_vars = array(
		'decorate',
		'link',
		'ul',
		'ol',
		'dl',
		'bq',
		'code',
		'headers',
	);
	
	private $lipsum_length = array(
		'short', 
		'medium', 
		'long', 
		'verylong',
	);
	
	private $post_title = 'Lorem ipsum dolor sit amet';
	
	private $post_categories = array();
	
	private $post_terms = array();
	
	private $lipsum = '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec odio enim, molestie non, pretium ut, fringilla vel, libero. Nam adipiscing ultricies nisl. Sed ornare. Vivamus sodales congue ligula. Nunc purus nulla, consectetuer non, sollicitudin a, tincidunt non, arcu. Nam purus urna, consequat eu, mattis scelerisque, facilisis eu, leo. Nulla facilisi. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Integer libero diam, eleifend et, vestibulum non, scelerisque ut, ipsum. Ut dictum mattis libero. Duis quis arcu vel elit ultrices ornare. Donec neque dui, auctor eget, aliquet quis, tempus id, sem. Fusce sapien.</p><p>Duis lectus eros, elementum vitae, egestas eu, pharetra quis, nisl. Pellentesque viverra. Pellentesque id mauris gravida metus ultricies tincidunt. Mauris adipiscing ante eu nisl. Nullam placerat pede id sem. Fusce nonummy accumsan urna. Nunc sodales tristique diam. Morbi mollis. Nam eget nisl vel ante iaculis nonummy. Praesent ultricies consequat purus. Integer eu felis. Donec id pede. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Fusce non libero.</p>';
	
	private function __construct(){
		
	}
	
	private function define_lipsum_vars(){
		
	}
	
	private function request_lipsum(){
		$lipsum_page = wp_remote_get( 'http://loripsum.net/api/14/short/headers/medium/decorate/ul/ol/bq/' );
		$lipsum = wp_remote_retrieve_body($lipsum_page);
	}
}



/**
 * ==================================================
 * ADICIONAR PAGES/POSTS/CPT ========================
 * ==================================================
 * Adicionar conteúdo dummy
 * 
 * 
 * @todo adicionar categorias/tags/termos
 * @todo adicionar imagem _thumbnail_id
 * @todo adicionar imagens no corpo
 * @todo adicionar subpages para um determinado item
 */
class BorosQuickPages {
	
	private $pages_string = '';
	
	private $pages_array = array();
	
	private $multilevel_pages_array = array();
	
	function __construct( $pages_raw_string ){
		$this->pages_string = $pages_raw_string;
		$this->pages_array = explode( "\n", $this->pages_string );
	}
	
	function get_pages_array(){
		return $this->pages_array;
	}
	
	/**
	 * Criar um array hierárquico a partir da string com dashes('-', '--'), criando um array com a chave 'parent_index',
	 * que indica o índice do parent no mesmo array.
	 * 
	 * Baseado no plugin Quick Pages @link https://github.com/snaptortoise/wp-quick-pages
	 * 
	 */
	function create_multilevel_pages_array(){
		foreach ($this->pages_array as $key => $page) {
			$page = trim($page);
			$parent = 0;
			$parent_index = 0;
			preg_match("/^[\-]+/", $page, $child);
			
			if(@$child[0]){
				$depth = strlen($child[0]) - 1;
				$page = trim(substr($page, $depth + 1));
				
				// cycle through and find parent
				for($i = $key; $i--; $i >= 0){
					// if we find it...
					$pattern = "/^[\-]{".$depth."}[^\-]/";
					
					if( (preg_match($pattern, $this->pages_array[$i], $test) && $depth > 0) || ($depth== 0 && substr($this->pages_array[$i], 0, 1) != "-") ){
						$parent = $this->multilevel_pages_array[$i]['post_title'];
						$parent_index = $i;
						$i = false;
					}
				}
			}
			
			$page_array = array(
				'post_title' => $page,
			);
			$page_array['parent_index'] = $parent_index;
			$this->multilevel_pages_array[$key] = $page_array;
		}
		$this->create_wp_multilevel_pages();
	}
	
	/**
	 * Criar as páginas/posts/cpt no wordpress.
	 * 
	 */
	function create_wp_multilevel_pages(){
		$t = array();
		foreach( $this->multilevel_pages_array as $index => $page ){
			// criar post
			$new_post_id = rand(100, 999);
			
			// associar id criada
			$this->multilevel_pages_array[$index]['id'] = $new_post_id;
			
			
			if( $page['parent_index'] > 0 ){
				$this->multilevel_pages_array[$index]['parent_id'] = $this->multilevel_pages_array[$page['parent_index']]['id'];
			}
			else{
				$this->multilevel_pages_array[$index]['parent_id'] = 0;
			}
		}
		pre($this->multilevel_pages_array);
	}
	
}






















