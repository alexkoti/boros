<?php
/**
 * ==================================================
 * FUNCTIONS EXTRAS PARA O WORDPRESS ================
 * ==================================================
 * Functions relativas ao WordPress, corrigindo ou ampliando funções do core.
 * 
 */



/**
 * ==================================================
 * CLASS LOADER =====================================
 * ==================================================
 * Loader simples de classes
 * 
 * @todo: melhorar para um autolad PSR
 * 
 */
function boros_calendar( $config ){
	require_once('class-calendar.php');
	return new Boros_Calendar( $config );
}



/**
 * ==================================================
 * ADICIONAR NOTIFICAÇÃO DE ALERTA DE ADMIN =========
 * ==================================================
 * Adicionar mensagem de notificação de alerta no widget "Mensagens e alertas"
 * 
 * 
 */
function boros_add_dashboard_notification( $key = 'alert', $message = 'Mensagem de alerta' ){
    $alerts = get_option('boros_dashboard_notifications');
    if( !isset( $alerts[$key] ) ){
        update_option( 'boros_dashboard_notifications', $alerts );
    }
}

function boros_remove_dashboard_notification( $key = 'alert' ){
    $alerts = get_option('boros_dashboard_notifications');
    if( isset( $alerts[$key] ) ){
        unset( $alerts[$key] );
        update_option( 'boros_dashboard_notifications', $alerts );
    }
}



/**
 * ==================================================
 * CUSTOM EXCERPT ===================================
 * ==================================================
 * Criar um excerpt com base em qualquer string. Baseado no código core do wp, removendo tags, shortcodes, porém mais simplificado, podendo ser aplicado em qulauer tipo de string.
 * 
 */
function boros_excerpt( $content, $excerpt_length = 55 ){
	// Versão 1
	//$raw_content = wp_trim_excerpt( $content );
	//$text = strip_shortcodes( $raw_content );
	
	// Versão 2
	$text = strip_shortcodes( $content );
	
	$text = str_replace(']]>', ']]&gt;', $text);
	$text = strip_tags($text);
	$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
	if ( count($words) > $excerpt_length ) {
		array_pop($words);
	}
	return wptexturize(implode(' ', $words));
}

function boros_excerpt_letters( $content, $excerpt_length = 55 ){
	$text = strip_shortcodes( $content );
	$text = str_replace(']]>', ']]&gt;', $text);
	$text = strip_tags($text);
	$letters = substr($text, 0, $excerpt_length);
	return wptexturize($letters);
}

/**
 * ==================================================
 * GET POST META SINGLE =============================
 * ==================================================
 * Pegar post_meta de forma simplificada, sempre quando o valor é único e o post seja o post corrente. Precisa ser dentro do loop por conta 
 * da global $post.
 * 
 * @param   string         $meta              Nome do meta a ser recuperado
 * @param   bool|string    $filter_or_echo    Caso seja false, apenas retorna o meta, caso seja true, faz o echo() do meta, e caso seja
 *                                            string, imprime o meta aplicado o filtro com nome no valor de $filter_echo, ex. 'the_content'
 *                                            (default true, imprime o valor)
 * @parnm   string         $wrapper           String padrão que acompanha o valor, pode ser html, ex '<div class='wrapper'>%s</div>'
 *                                            (default '%s')
 * 
 * @return  string  $meta_value valor gravado
 * @uses    get_post_meta() function core
 */
function pmeta( $meta, $filter_or_echo = true, $wrapper = '%s' ){
    global $post;
    $meta_value = get_post_meta($post->ID, $meta, true);
    
    if( !empty($meta_value) ){
        $meta_value = sprintf( $wrapper . "\n", $meta_value );
        if( $filter_or_echo === false ){
            return $meta_value;
        }
        else{
            if( $filter_or_echo === true ){
                echo $meta_value;
            }
            else{
                echo apply_filters( $filter_or_echo, $meta_value );
            }
        }
    }
    return $meta_value;
}

/**
 * ==================================================
 * OPTIONAL POST META ===============================
 * ==================================================
 * Verificar se o post_meta existe gravada no banco e não vazia, e imprimir. Se for declarado o wrapper, o option será inserido dentro do bloco.
 * 
 * @param	string	$post_meta	nome do post_meta
 * @param	string	$wrapper		modelo de retorno em formato printf
 * @uses		get_option()
 */
function opt_post_meta( $post_id, $post_meta, $wrapper = '%s', $echo = true, $filters = false ){
	$meta = get_post_meta( $post_id, $post_meta, true );
	if( $meta != '' ){
		if( $wrapper != '' ){
			$meta = sprintf( $wrapper . "\n", $meta );
		}
		
		if( $filters !== false ){
			foreach( (array)$filters as $filter ){
				$meta = apply_filters( $filter, $meta );
			}
		}
		
		if($echo == true)
			echo $meta;
		else
			return $meta;
	}
}

function get_post_meta_or( $post_id, $meta, $alt = '', $wrapper = '%s', $echo = false, $filters = false ){
	$meta = get_post_meta( $post_id, $meta, true );
	if( empty($meta) ){
		$meta = $alt;
	}
	
	if( $wrapper != '' ){
		$meta = sprintf( $wrapper, $meta );
	}
	
	if( $filters !== false ){
		foreach( (array)$filters as $filter ){
			$meta = apply_filters( $filter, $meta );
		}
	}
	
	if($echo == true)
		echo $meta;
	else
		return $meta;
}

/**
 * ==================================================
 * OPTIONAL OPTION ==================================
 * ==================================================
 * Verificar se a opção existe gravada no banco e não vazia, e imprimir. Se for declarado o wrapper, o option será inserido dentro do bloco.
 * 
 * @param	string	$option		nome do option
 * @param	string	$wrapper		modelo de retorno em formato printf
 * @param	bool		$echo
 * @param	string	$filters		aplicar filtros, como the_content ou the_title
 * @uses		get_option()			function core
 */
function opt_option( $option, $wrapper = '%s', $echo = true, $filters = false ){
	$opt = get_option($option);
	if( $opt != '' ){
		if( $wrapper != '' ){
			$opt = sprintf( $wrapper . "\n", $opt );
		}
		
		if( $filters !== false ){
			foreach( (array)$filters as $filter ){
				$opt = apply_filters( $filter, $opt );
			}
		}
		
		if($echo == true)
			echo $opt;
		else
			return $opt;
	}
}

/**
 * ==================================================
 * GET OPTION OR ====================================
 * ==================================================
 * Buscar uma option ou aplicar o valor alternativo
 * 
 * @todo mudar essa function que faz o echo diretamente para opt_option_or() e deixar esta co get_option_or() com return
 * 
 * @param	string	$option		nome do option
 * @param	string	$option		valor alternativo a ser aplicado caso não exista o option ou seja vazio
 * @param	string	$wrapper		modelo de retorno em formato printf
 * @param	bool		$echo
 * @param	string	$filters		aplicar filtros, como the_content ou the_title
 * @uses		get_option()			function core
 */
function get_option_or( $option, $alt = '', $wrapper = '%s', $echo = true, $filters = false ){
	$opt = get_option($option);
	if( empty($opt) ){
		$opt = $alt;
	}
	
	if( $wrapper != '' ){
		$opt = sprintf( $wrapper . "\n", $opt );
	}
	
	if( $filters !== false ){
		foreach( (array)$filters as $filter ){
			$opt = apply_filters( $filter, $opt );
		}
	}
	
	if($echo == true)
		echo $opt;
	else
		return $opt;
}

/**
 * ==================================================
 * FORMATTED NOW ====================================
 * ==================================================
 * Retornar a data atual, considerando o timezone definido no WordPress, e aplicando 
 * traduções nas strings, caso necessário.
 * 
 */
function boros_formatted_now( $format = 'd\/m\/Y' ){
	$timezone_string = get_option( 'timezone_string' );
	if( $timezone_string ){
		date_default_timezone_set($timezone_string);
		$date_i18n = date_i18n( $format, time() );
	}
	else{
		$date_i18n = date_i18n( $format, time() );
	}
	return $date_i18n;
}

/**
 * ==================================================
 * WP-OPTIONS =======================================
 * ==================================================
 * Gravar opções diretamente na tabela wp_options, caso não existam ainda.
 * A principal utilidade dessa função é setar defaults para opções que são necessárias para o funcionamento do tema, mas que ao ativar o mesmo ainda não foram setadas.
 * 
 * @param array $options_to_save Array de $key => $value com as opções que deseja gravar. Só serão gravadas caso não existam ainda na tablea wp_options.
 */
function direct_insert_options( $options_to_save ){
	foreach( $options_to_save as $name => $value ){
		if( !get_option( $name ) ){
			add_option( $name, $value );
		}
	}
}

/**
 * ==================================================
 * IS PLUGIN ACTIVE? ================================
 * ==================================================
 * Verificar se um plugin está ativo. Semelhante ao 'is_plugin_active()' do core, porém pode ser usado no frontend.
 * 
 */
function plugin_is_active( $plugin_path ) {
	$return_var = in_array( $plugin_path, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	return $return_var;
}



/**
 * ==================================================
 * SORT OBJECTS BY ARRAY ============================
 * ==================================================
 * Reorganizar um array baseado em valores de outro, normalmente o objeto será um conjunto de posts do wordpress
 * 
 * 
 * @link http://wordpress.mfields.org/2011/rekey-an-indexed-array-of-post-objects-by-post-id/
 */
class sort_wp_objects {
	var $sortorder = array();
	var $field = 'ID';
	var $ordered = array();
	
	function __construct( $objects, $sortorder, $field = 'ID' ){
		$this->sortorder = $sortorder;
		$this->field = $field;
		usort($objects, array( $this , 'compare' ) );
		$this->ordered = $objects;
	}
	
	function compare( $a, $b ){
		$field = $this->field;
		$cmpa = array_search( $a->$field, $this->sortorder );
		$cmpb = array_search( $b->$field, $this->sortorder );
		return ( $cmpa > $cmpb ) ? 1 : -1;
	}
}
// helper function
function sort_wp_objects( $objects, $sortorder, $field = 'ID' ){
	$obj = new sort_wp_objects( $objects, $sortorder, $field );
	return $obj->ordered;
}



/**
 * ==================================================
 * GET ARCHIVES =====================================
 * ==================================================
 * 
 * 
 */
function lastday( $month = '', $year = '', $format = 'Y-m-d' ){
	if(empty($month)){
		$month = date('m');
	}
	if(empty($year)){
		$year = date('Y');
	}
	$result = strtotime("{$year}-{$month}-01");
	$result = strtotime('-1 second', strtotime('+1 month', $result));
	return date($format, $result);
}
function boros_year_months_archive( $args = '' ){
	global $wpdb, $wp_locale;
	
	$defaults = array(
		'year' => date( 'Y', strtotime('now') ),
		'limit' => '',
		'format' => 'html',
		'before' => '',
		'after' => '',
		'show_post_count' => false,
		'order' => 'ASC',
		'echo' => 1,
		'term_id' => false
	);
	
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	//$year = 2011;
	//$term_id = 4;
	$first_day = "$year-01-01 00:00:00";
	$last_day = lastday( 12, $year );
	$join = "
		 LEFT JOIN $wpdb->term_relationships
				ON $wpdb->posts.ID = $wpdb->term_relationships.object_id
		 LEFT JOIN $wpdb->term_taxonomy
				ON $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id
		 LEFT JOIN $wpdb->terms
				ON $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id
	";
	$where = "
		WHERE post_type   = 'post' 
		  AND post_status = 'publish' 
		  AND post_date  >= '$first_day' 
		  AND post_date   < '$last_day 23:59:59'
	";
	if( $term_id != false )
		$where .= " AND $wpdb->term_taxonomy.term_id = $term_id ";
	
	$query = "
		SELECT 
		YEAR(post_date) AS `year`, 
		MONTH(post_date) AS `month`, 
		count(ID) as posts 
		FROM $wpdb->posts 
		$join 
		$where 
		GROUP BY YEAR(post_date), 
		MONTH(post_date) 
		ORDER BY post_date 
		$order 
		$limit
	";
	
	$output = '';
	$key = md5($query);
	$cache = wp_cache_get( 'wp_get_archives' , 'general');
	if ( !isset( $cache[ $key ] ) ) {
		$arcresults = $wpdb->get_results($query);
		$cache[ $key ] = $arcresults;
		wp_cache_set( 'wp_get_archives', $cache, 'general' );
	} else {
		$arcresults = $cache[ $key ];
	}
	if ( $arcresults ) {
		$afterafter = $after;
		foreach ( (array) $arcresults as $arcresult ) {
			$url = get_month_link( $arcresult->year, $arcresult->month );
			if( $term_id != false )
				$url = add_query_arg( 'cid', $term_id, $url );
			
			/* translators: 1: month name, 2: 4-digit year */
			$text = sprintf(__('%1$s %2$d'), $wp_locale->get_month($arcresult->month), $arcresult->year);
			if ( $show_post_count )
				$after = '&nbsp;('.$arcresult->posts.')' . $afterafter;
			$output .= get_archives_link($url, $text, $format, $before, $after);
		}
	}
	if ( $echo )
		echo $output;
	else
		return $output;
}
function _boros_get_archives( $args = '' ) {
	global $wpdb, $wp_locale;

	$defaults = array(
		'type' => 'monthly',
		'limit' => '',
		'format' => 'html',
		'before' => '',
		'after' => '',
		'show_post_count' => false,
		'echo' => 1
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( '' == $type )
		$type = 'monthly';

	if ( '' != $limit ) {
		$limit = absint($limit);
		$limit = ' LIMIT '.$limit;
	}

	// this is what will separate dates on weekly archive links
	$archive_week_separator = '&#8211;';

	// over-ride general date format ? 0 = no: use the date format set in Options, 1 = yes: over-ride
	$archive_date_format_over_ride = 0;

	// options for daily archive (only if you over-ride the general date format)
	$archive_day_date_format = 'Y/m/d';

	// options for weekly archive (only if you over-ride the general date format)
	$archive_week_start_date_format = 'Y/m/d';
	$archive_week_end_date_format	= 'Y/m/d';

	if ( !$archive_date_format_over_ride ) {
		$archive_day_date_format = get_option('date_format');
		$archive_week_start_date_format = get_option('date_format');
		$archive_week_end_date_format = get_option('date_format');
	}

	//filters
	$where = apply_filters( 'getarchives_where', "WHERE post_type = 'post' AND post_status = 'publish'", $r );
	$join = apply_filters( 'getarchives_join', '', $r );

	$output = '';

	if ( 'monthly' == $type ) {
		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC $limit";
		$key = md5($query);
		$cache = wp_cache_get( 'wp_get_archives' , 'general');
		if ( !isset( $cache[ $key ] ) ) {
			$arcresults = $wpdb->get_results($query);
			$cache[ $key ] = $arcresults;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$arcresults = $cache[ $key ];
		}
		if ( $arcresults ) {
			$afterafter = $after;
			foreach ( (array) $arcresults as $arcresult ) {
				$url = get_month_link( $arcresult->year, $arcresult->month );
				/* translators: 1: month name, 2: 4-digit year */
				$text = sprintf(__('%1$s %2$d'), $wp_locale->get_month($arcresult->month), $arcresult->year);
				if ( $show_post_count )
					$after = '&nbsp;('.$arcresult->posts.')' . $afterafter;
				$output .= get_archives_link($url, $text, $format, $before, $after);
			}
		}
	} elseif ('yearly' == $type) {
		$query = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) ORDER BY post_date DESC $limit";
		$key = md5($query);
		$cache = wp_cache_get( 'wp_get_archives' , 'general');
		if ( !isset( $cache[ $key ] ) ) {
			$arcresults = $wpdb->get_results($query);
			$cache[ $key ] = $arcresults;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$arcresults = $cache[ $key ];
		}
		if ($arcresults) {
			$afterafter = $after;
			foreach ( (array) $arcresults as $arcresult) {
				$url = get_year_link($arcresult->year);
				$text = sprintf('%d', $arcresult->year);
				if ($show_post_count)
					$after = '&nbsp;('.$arcresult->posts.')' . $afterafter;
				$output .= get_archives_link($url, $text, $format, $before, $after);
			}
		}
	} elseif ( 'daily' == $type ) {
		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, DAYOFMONTH(post_date) AS `dayofmonth`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date), DAYOFMONTH(post_date) ORDER BY post_date DESC $limit";
		$key = md5($query);
		$cache = wp_cache_get( 'wp_get_archives' , 'general');
		if ( !isset( $cache[ $key ] ) ) {
			$arcresults = $wpdb->get_results($query);
			$cache[ $key ] = $arcresults;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$arcresults = $cache[ $key ];
		}
		if ( $arcresults ) {
			$afterafter = $after;
			foreach ( (array) $arcresults as $arcresult ) {
				$url	= get_day_link($arcresult->year, $arcresult->month, $arcresult->dayofmonth);
				$date = sprintf('%1$d-%2$02d-%3$02d 00:00:00', $arcresult->year, $arcresult->month, $arcresult->dayofmonth);
				$text = mysql2date($archive_day_date_format, $date);
				if ($show_post_count)
					$after = '&nbsp;('.$arcresult->posts.')'.$afterafter;
				$output .= get_archives_link($url, $text, $format, $before, $after);
			}
		}
	} elseif ( 'weekly' == $type ) {
		$week = _wp_mysql_week( '`post_date`' );
		$query = "SELECT DISTINCT $week AS `week`, YEAR( `post_date` ) AS `yr`, DATE_FORMAT( `post_date`, '%Y-%m-%d' ) AS `yyyymmdd`, count( `ID` ) AS `posts` FROM `$wpdb->posts` $join $where GROUP BY $week, YEAR( `post_date` ) ORDER BY `post_date` DESC $limit";
		$key = md5($query);
		$cache = wp_cache_get( 'wp_get_archives' , 'general');
		if ( !isset( $cache[ $key ] ) ) {
			$arcresults = $wpdb->get_results($query);
			$cache[ $key ] = $arcresults;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$arcresults = $cache[ $key ];
		}
		$arc_w_last = '';
		$afterafter = $after;
		if ( $arcresults ) {
				foreach ( (array) $arcresults as $arcresult ) {
					if ( $arcresult->week != $arc_w_last ) {
						$arc_year = $arcresult->yr;
						$arc_w_last = $arcresult->week;
						$arc_week = get_weekstartend($arcresult->yyyymmdd, get_option('start_of_week'));
						$arc_week_start = date_i18n($archive_week_start_date_format, $arc_week['start']);
						$arc_week_end = date_i18n($archive_week_end_date_format, $arc_week['end']);
						$url  = sprintf('%1$s/%2$s%3$sm%4$s%5$s%6$sw%7$s%8$d', home_url(), '', '?', '=', $arc_year, '&amp;', '=', $arcresult->week);
						$text = $arc_week_start . $archive_week_separator . $arc_week_end;
						if ($show_post_count)
							$after = '&nbsp;('.$arcresult->posts.')'.$afterafter;
						$output .= get_archives_link($url, $text, $format, $before, $after);
					}
				}
		}
	} elseif ( ( 'postbypost' == $type ) || ('alpha' == $type) ) {
		$orderby = ('alpha' == $type) ? 'post_title ASC ' : 'post_date DESC ';
		$query = "SELECT * FROM $wpdb->posts $join $where ORDER BY $orderby $limit";
		$key = md5($query);
		$cache = wp_cache_get( 'wp_get_archives' , 'general');
		if ( !isset( $cache[ $key ] ) ) {
			$arcresults = $wpdb->get_results($query);
			$cache[ $key ] = $arcresults;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$arcresults = $cache[ $key ];
		}
		if ( $arcresults ) {
			foreach ( (array) $arcresults as $arcresult ) {
				if ( $arcresult->post_date != '0000-00-00 00:00:00' ) {
					$url  = get_permalink( $arcresult );
					if ( $arcresult->post_title )
						$text = strip_tags( apply_filters( 'the_title', $arcresult->post_title, $arcresult->ID ) );
					else
						$text = $arcresult->ID;
					$output .= get_archives_link($url, $text, $format, $before, $after);
				}
			}
		}
	}
	if ( $echo )
		echo $output;
	else
		return $output;
}



/**
 * PAGENAVI LIST
 * Transformar o pagenavi em ul>li
 * 
 */
function boros_pagenavi( $args = array() ){
	$pagenavi = new PageNaviList();
	$pagenavi->config($args);
	$pagenavi->output();
}

/**
 * @deprecated
 * 
 */
function pagenavi_filtered( $args ){
	boros_pagenavi($args);
}

/**
 * @deprecated
 * pagenavi_list() é usada pelo 3m inovação
 */
function pagenavi_list( $query = false, $number = 10, $sep = '' ){
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
	
	/**
	 * @deprecated
	 * 
	 */
	function tresm_pagenavi( $query = false, $number = 10, $sep = '' ){
		$this->sep = $sep;
		$pagination = array(
			'before' => '',
			'after' => '',
			'options' => array(
				'use_pagenavi_css' => false,
				'always_show' => true,
				'num_pages' => $number,
				'pages_text' => false,
				'first_text' => '« Primeira',
				'dotleft_text' => false,
				'last_text' => 'Última »',
				'dotright_text' => false,
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
				'page_text' => '%PAGE_NUMBER%',
				'current_text' => '%PAGE_NUMBER%',
				//'num_larger_page_numbers' => true,
				//'larger_page_numbers_multiple' => 3,
			),
			'type' => 'posts'
		);
		
		if( $query != false )
			$pagination['query'] = $query;
		
		add_filter( 'wp_pagenavi', array($this, 'filter_pagenavi') );
		wp_pagenavi( $pagination );
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



/**
 * ==================================================
 * PAGINAÇÂO ========================================
 * ==================================================
 * Paginação criada a partir do plugin wp_pagenavi, mas que permite uma maior customização do output
 * 
 */
function boros_pagination( $args ){
	$pagination = new Boros_Pagination( $args );
	$pagination->create_items();
	$pagination->output();
}

/**
 * Classe de paginação, baseado no pagenavi
 * 
 * @todo 'pages_text'
 * 
 */
class Boros_Pagination {
	private $current        = 1;
	private $total          = 1;
	private $posts_per_page = 10;
	private $total_pages    = 1;
	private $query_type     = 'normal';
	private $page_query_arg = 'pg';
	
	/**
	 * Array com as páginas numéricas
	 * 
	 */
	private $pages;
	
	/**
	 * Array com todos os itens da paginação
	 * 
	 */
	private $items;
	
	private $options = array(
		'query_type'     => 'normal',
		'page_query_arg' => 'pg',
		'always_show'    => false,
		'num_pages'      => 5,
		'ul_class'       => '',
		'li_class'       => ' ',
		'link_class'     => 'btn',
		'pages_text'     => '',
		'first_text'     => '«',
		'dotleft_text'   => '',
		'last_text'      => '»',
		'dotright_text'  => '',
		'prev_text'      => '‹',
		'next_text'      => '›',
		'page_text'      => '%PAGE_NUMBER%',
		'current_text'   => '%PAGE_NUMBER%',
	);
	private $output = '';
	
    /**
     * Aplicar defaults via boros_defaults
     * 
     */
	function __construct( $args ){
		$this->current        = (int)$args['current'];
		$this->total          = (int)$args['total'];
		$this->posts_per_page = (int)$args['posts_per_page'];
		$this->total_pages    = ceil( $this->total / $this->posts_per_page );

		if( isset($args['query_type']) ){
			$this->query_type = $args['query_type'];
		}

		if( isset($args['page_query_arg']) ){
			$this->page_query_arg = $args['page_query_arg'];
		}

		if( !isset($args['options']) ){
			$args['options'] = array();
		}
		$this->options = boros_parse_args( $this->options, $args['options'] );
	}
	
	function create_items(){
		$pages_to_show_minus_1 = $this->options['num_pages'] - 1;
		$half_page_start = floor( $pages_to_show_minus_1/2 );
		$half_page_end = ceil( $pages_to_show_minus_1/2 );
		$start_page = $this->current - $half_page_start;
		
		if( $start_page <= 0 ){
			$start_page = 1;
		}
		
		$end_page = $this->current + $half_page_end;
		
		if( ( $end_page - $start_page ) != $pages_to_show_minus_1 ){
			$end_page = $start_page + $pages_to_show_minus_1;
		}
		
		if( $end_page > $this->total_pages ){
			$start_page = $this->total_pages - $pages_to_show_minus_1;
			$end_page = $this->total_pages;
		}
		
		if( $start_page < 1 ){
			$start_page = 1;
		}
		
		//pal($start_page, '$start_page');
		//pal($end_page, '$end_page');
		
		// First
		$previous_class = array('first_item');
		if( $start_page >= 2 && $this->options['num_pages'] < $this->total_pages && $this->options['first_text'] !== false ){
			unset($previous_class[0]);
			$this->items[] = $this->set_item( 1, 'page', array('firstpostslink', 'first_item'), $this->options['first_text'], true, '%TOTAL_PAGES%' );
		}
		
		// Previous
		if( $this->current > 1 ){
			$previous_class[] = 'previouspostslink';
			$this->items[] = $this->set_item( ($this->current - 1), 'page', $previous_class, $this->options['prev_text'], true, '%PAGE_NUMBER%' );
		}
		
		// Page numbers
		$timeline = 'smaller';
		$u = 1;
		foreach ( range( $start_page, $end_page ) as $i ) {
			$class = array();
			if( $u == 1 ){
				$class[] = 'first_number';
			}
			
			if( $i == $end_page ){
				$class[] = 'last_number';
			}
			
			if( ($i == $start_page) AND ($i == $this->current) ){
				$class[] = 'first_item';
			}
			
			if( ($i == $this->total_pages) AND ($this->total_pages == $this->current) ){
				$class[] = 'last_item';
			}
			
			if( $i == $this->current ){
				$class[] = 'current active';
				$this->pages[] = $this->items[] = $this->set_item( $i, 'span', $class, $this->options['current_text'], false, '%PAGE_NUMBER%' );
				$timeline = 'larger';
			}
			else{
				$class[] = 'page';
				$class[] = $timeline;
				$this->pages[] = $this->items[] = $this->set_item( $i, 'page', $class, $this->options['page_text'], true, '%PAGE_NUMBER%' );
			}
			$u++;
		}
		
		// Next
		if( $this->current < $this->total_pages ){
			$class = array('nextpostslink');
			if( $end_page == $this->total_pages ){
				$class = 'last_item';
			}
			$this->items[] = $this->set_item( ($this->current + 1), 'page', $class, $this->options['next_text'], true, '%PAGE_NUMBER%' );
		}
		
		// Last
		if( $end_page < $this->total_pages && $this->options['last_text'] !== false ){
			$this->items[] = $this->set_item( $this->total_pages, 'page', array('lastpostslink', 'last_item'), $this->options['last_text'], true, '%PAGE_NUMBER%' );
		}
	}
	
	function get_items(){
		return $this->items;
	}
	
	/**
	 * não utilizado
	 * 
	 */
	function set_text( $tag, $value, $raw_text ){
		$new_text = str_replace( $tag, $value, $raw_text );
		return $new_text;
	}
	
	function set_item( $page, $type, $class, $text, $link, $tag, $attr = array() ){
		if( $this->query_type == 'wpdb' ){
			$url = ($link == true) ? add_query_arg( $this->page_query_arg, $page ) : false;
		}
		elseif( $this->query_type == 'normal' ){
			$url = ($link == true) ? get_pagenum_link($page) : false;
		}
		$item = array(
			'page' => $page,
			'type' => $type,
			'class' => implode(' ', (array)$class),
			'text' => str_replace( $tag, $page, $text ),
			'link' => $url,
		);
		return $item;
	}
	
	function output( $echo = true ){
		if( count($this->items) < 3 and $this->options['always_show'] == false ){
			return '';
		}
		$li_class = empty($this->options['li_class']) ? $this->options['li_class'] : '';
		echo "<ul class='pagenavi_list pagination {$this->options['ul_class']}'>\n";
		foreach( $this->items as $item ){
			if( $item['page'] > 0 ){
				if( $item['type'] == 'page' ){
					echo "<li class='{$li_class} {$item['class']}'><a href='{$item['link']}' class='{$item['class']} {$this->options['link_class']}'>{$item['text']}</a></li>";
				}
				else{
					echo "<li class='{$li_class} {$item['class']}'><span class='{$item['class']}'>{$item['text']}</span></li>";
				}
			}
		}
		echo '</ul>';
	}
}



/**    
 * ==================================================
 * Remover hooks sem instância ======================
 * ==================================================
 * 
 * @link https://github.com/herewithme/wp-filters-extras/
 * 
 */

/**
 * Allow to remove method for an hook when, it's a class method used and class don't have global for instanciation !
 */
function remove_filters_with_method_name( $hook_name = '', $method_name = '', $priority = 0 ) {
    global $wp_filter;
    // Take only filters on right hook name and priority
    if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) || ! is_array( $wp_filter[ $hook_name ][ $priority ] ) ) {
        return false;
    }
    // Loop on filters registered
    foreach ( (array) $wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {
        // Test if filter is an array ! (always for class/method)
        if ( isset( $filter_array['function'] ) && is_array( $filter_array['function'] ) ) {
            // Test if object is a class and method is equal to param !
            if ( is_object( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) && $filter_array['function'][1] == $method_name ) {
                // Test for WordPress >= 4.7 WP_Hook class (https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/)
                if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {
                    unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
                } else {
                    unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
                }
            }
        }
    }
    return false;
}

/**
 * Allow to remove method for an hook when, it's a class method used and class don't have variable, but you know the class name :)
 */
function remove_filters_for_anonymous_class( $hook_name = '', $class_name = '', $method_name = '', $priority = 0 ) {
    global $wp_filter;
    // Take only filters on right hook name and priority
    if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) || ! is_array( $wp_filter[ $hook_name ][ $priority ] ) ) {
        return false;
    }
    // Loop on filters registered
    foreach ( (array) $wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {
        // Test if filter is an array ! (always for class/method)
        if ( isset( $filter_array['function'] ) && is_array( $filter_array['function'] ) ) {
            // Test if object is a class, class and method is equal to param !
            if ( is_object( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) == $class_name && $filter_array['function'][1] == $method_name ) {
                // Test for WordPress >= 4.7 WP_Hook class (https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/)
                if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {
                    unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
                } else {
                    unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
                }
            }
        }
    }
    return false;
}



