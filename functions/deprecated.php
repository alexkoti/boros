<?php
/**
 * ==================================================
 * DEPRECATED =======================================
 * ==================================================
 * 
 * Functions e classes que devem ser substituídas por versões atualizadas
 * 
 * Todas as funcões devem mostrar um alerta no output e/ou registrar um admin_notice
 * 
 * 
 */

function make_attributes( $args = array(), $prefix = '' ){
    pal('Deprecated function: make_attributes()');
    boros_make_attributes( $args = array(), $prefix = '' );
}



/**
 * PAGENAVI LIST
 * Transformar o pagenavi em ul>li
 * 
 */
function boros_pagenavi( $args = array() ){
    pal('Deprecated function: boros_pagenavi()');
	$pagenavi = new PageNaviList();
	$pagenavi->config($args);
	$pagenavi->output();
}

/**
 * @deprecated
 * 
 */
function pagenavi_filtered( $args ){
    pal('Deprecated function: pagenavi_filtered()');
	boros_pagenavi($args);
}

/**
 * @deprecated
 * pagenavi_list() é usada pelo 3m inovação
 */
function pagenavi_list( $query = false, $number = 10, $sep = '' ){
    pal('Deprecated function: pagenavi_list()');
	$pagenavi = new PageNaviList();
	$pagenavi->config( array() );
	$pagenavi->tresm_pagenavi($query, $number, $sep);
}

class PageNaviList {
	var $sep = '';
	
	function __construct(){
		/**
		$pagination = array(
			'before' => '',
			'after' => '',
			'type' => 'posts',
			'options' => array(
				'use_pagenavi_css' => false,
				'always_show' => true,
				'num_pages' => $number,
				'pages_text' => false,
				'first_text' => false,
				'dotleft_text' => false,
				'last_text' => false,
				'dotright_text' => false,
				'prev_text' => '« anterior',
				'next_text' => 'próximo »',
				'page_text' => '%PAGE_NUMBER%',
				'current_text' => '%PAGE_NUMBER%',
				//'num_larger_page_numbers' => true,
				//'larger_page_numbers_multiple' => 3,
			),
		);
		if( $query != false )
			$pagination['query'] = $query;
		
		add_filter( 'wp_pagenavi', array($this, 'filter_pagenavi') );
		wp_pagenavi( $pagination );
		/**/
	}
	
	function config( $args ){
		//defaults
		$defaults = array(
			'query' => false,
			'number' => 9,
			'sep' => '',
			'ul_class' => '',
			'li_class' => ' ',
			'link_class' => 'btn',
			'pagenavi' => array(
				'before' => '',
				'after' => '',
				'type' => 'posts',
				'options' => array(
					'use_pagenavi_css' => false,
					'always_show' => false,
					'num_pages' => 9,
					'pages_text' => false, //'Página %CURRENT_PAGE% de %TOTAL_PAGES%'
					'first_text' => '« Primeira',
					'dotleft_text' => false,
					'last_text' => 'Última »',
					'dotright_text' => false,
					'prev_text' => '« anterior',
					'next_text' => 'próximo »',
					'page_text' => '%PAGE_NUMBER%',
					'current_text' => '%PAGE_NUMBER%',
					'num_larger_page_numbers' => true,
					'larger_page_numbers_multiple' => 10,
				),
			),
		);
		$this->options = boros_parse_args( $defaults, $args );
		$this->sep = $this->options['sep'];
	}
	
	function output(){
		if( $this->options['query'] != false ){
			$this->options['pagenavi']['query'] = $this->options['query'];
		}
		add_filter( 'wp_pagenavi', array($this, 'filter_pagenavi') );
		wp_pagenavi($this->options['pagenavi']);
	}
	
	function output_bootstrap(){
		if( $this->options['query'] != false ){
			$this->options['pagenavi']['query'] = $this->options['query'];
		}
		add_filter( 'wp_pagenavi', array($this, 'filter_pagenavi_bootstrap') );
		wp_pagenavi($this->options['pagenavi']);
	}
	
	function filter_pagenavi( $out ){
		/**
		 * REGEX DESC
		 * 1 - tags completas
		 * 2 - tag name open <(a|span)
		 * 3 - tag attributes ([^`]*?)>
		 * 4 - tag content ([^`]*?)
		 * 5 - tag name close <\/(a|span)>
		 * 
		 */
		preg_match_all('/<(a|span)([^`]*?)>([^`]*?)<\/(a|span)>/', $out, $matches);
		//pre($matches[0]);
		
		$i = 1;
		$last = count($matches[0]);
		$ul = "<ul class='pagenavi_list {$this->options['ul_class']}'>\n";
		// não possui prev(primeira página)
		if(strpos($out, 'previouspostslink') === false)
			$ul .= "<li class='first {$this->options['li_class']}'>
			<span class='previouspostslink'>{$this->options['pagenavi']['options']['prev_text']}</span></li>";
		
		foreach( $matches[0] as $li ){
			$class = '';
			if( ($i == 1) and (strpos($li, 'previouspostslink') !== false) )
				$class = ' first';
			elseif( ($i == 1) and (strpos($li, 'previouspostslink') === false) )
				$class = ' first_number';
			
			if( ($i == $last) and (strpos($li, 'nextpostslink') !== false) )
				$class = ' last';
			elseif( ($i == $last ) and (strpos($li, 'nextpostslink') === false) )
				$class = ' last_number';
			elseif( ($i == ($last - 1)) and (strpos($out, 'nextpostslink') !== false) )
				$class = ' last_number';
			
			$class .= " {$this->options['li_class']}";
			
			// aplicar class .btn aos links apenas
			if( strpos($li, '</span>') === false ){
				$li = str_replace("class='", "class='{$this->options['link_class']} ", $li);
			}
			
			$ul .= "<li class='item_{$i}{$class}'>{$li}{$this->sep}</li>\n";
			$i++;
		}
		// não possui next(última página)
		if(strpos($out, 'nextpostslink') === false)
			$ul .= "<li class='last {$this->options['li_class']}'><span class='nextpostslink'>{$this->options['pagenavi']['options']['next_text']}</span></li>";
		$ul .= "</ul>\n";
		return $ul;
	}
}



