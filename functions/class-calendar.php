<?php
/**
 * Calendar
 * 
 * WARNINGS!
 * 1) devido ao limite de 64 caracteres no 'option_name' na tabela '_options', o total de caracteres usados no 'post_meta' 
 *    não poderá ultrapassar o limite de 30, segundo a seguinte fórmula: post_meta_length = (30 - post_type_length)
 * 
 */


class Boros_Calendar {
	
	protected $post_type = 'post';
	
	protected $post_status = 'publish';
	
	/**
	 * 
	 * 
	 */
	protected $post_meta = false;
	
	/**
	 * Armazena o array com todos os posts no formato anos/meses/posts, que poderá estar salvo em transient
	 * 
	 */
	protected $all_posts = false;
	
	/**
	 * Duração dos transients: 'boros_cldr_{post_type}', 'boros_cldr_{post_type}_{month}', 'boros_cldr_{post_type}_{post_meta}', 'boros_cldr_{post_type}_{post_meta}_{month}'
	 * O tamanho do name é limitado a 64 carcteres, portante é preciso manter o name conciso
	 * 
	 */
	protected $transient_expiration = 3600;
	
	protected $timezone = 'America/Sao_Paulo';
	
	protected $day = 0;
	
	protected $month = 0;
	
	protected $month_number = 0;
	
	protected $month_name = '';
	
	protected $pmonth = 0;
	
	protected $year = 0;
	
	protected $days_in_month = 0;
	
	protected $first_day = 0;
	
	protected $mont_first_day_of_week = '';
	
	protected $month_start = 0;
	
	protected $month_end = 0;
	
	protected $accepted_metas = array();
	
	protected $taxonomies = array();
	
	protected $posts_table_query;
	
	protected $posts_table = false;
	
	protected $query_list_events;
	
	protected $list_events = array();
	
	protected $query_month_events = array();
	
	protected $month_events = array();
	
	protected $weedays = array(
		1 => 'sunday',
		2 => 'monday',
		3 => 'tuesday',
		4 => 'wednesday',
		5 => 'thursday',
		6 => 'friday',
		7 => 'saturday',
	);
	
	/**
	 * Construct
	 * 
	 * $config
	 *     ['timezone']       string
	 *     ['post_type']      string Default 'post'
	 *     ['post_status']    string Default 'publish' 
	 *     ['post_meta']      string|mixed Default false, define o post_meta que armazena a informação das datas do evento.
	 *                                     É necessário que o limite de caracteres desse post_meta seja:
	 *                                     post_meta_length = (30 - post_type_length); ver o Warning 1), na descrição da classe.
	 *     ['day']            string Default dia atual via time()
	 *     ['month']          string Default mês atual via time() 
	 *     ['year']           string Default ano atual via time()
	 *     ['accepted_metas'] array Array de meta_keys que os posts serão incorporados ao objeto post. Caso não declarado, 
	 *                              será retornado todos os post_metas
	 *     ['taxonomies']     array|string Taxonomias que deverão ser incorporados ao objeto post. Default nenhum
	 * 
	 * @param array $config (ver acima)
	 * 
	 * @ver 0.1.0
	 */
	function __construct( $config = array() ){
		global $wp_locale; //pre($wp_locale);
		
		$vars = array(
			'post_type',
			'post_status',
			'post_meta',
			'posts_in_years_option',
			'timezone',
			'accepted_metas',
			'taxonomies',
		);
		foreach( $vars as $v ){
			if( isset($config[$v]) ){
				$this->$v = $config[$v];
			}
		}
		
		// Definir timezone
		date_default_timezone_set( $this->timezone );
		
		// Definir a data de referência para o mês a ser exibido. Padrão para o dia atual
		$today = time();
		$this->day   = isset($config['day'])   ? $config['day']   : date('d', $today); 
		$this->month = isset($config['month']) ? $config['month'] : date('m', $today); 
		$this->year  = isset($config['year'])  ? $config['year']  : date('Y', $today); 
		
		// variáveis de url
		if( isset($_GET['cm']) ){
			$this->month = (int) $_GET['cm'];
		}
		if( isset($_GET['ca']) ){
			$this->year = (int) $_GET['ca'];
		}
		
		// primeiro dia do mês
		$this->first_day = mktime(0,0,0,$this->month, 1, $this->year) ; 
		
		// dia da semana do primeiro dia
		$this->mont_first_day_of_week = date('D', $this->first_day) ; 
		
		// quantos dias existem neste mês
		$this->days_in_month = cal_days_in_month(0, $this->month, $this->year);
		
		// @todo remover pmonth e month_number, e usar apenas month
		$this->pmonth       = sprintf('%02d', $this->month); // format de mês com leading-zero
		$this->month_number = date('m', $this->first_day);
		$this->month_name   = $wp_locale->month[$this->month_number];
		
		// início e fim do mês
		$this->month_start = "{$this->year}-{$this->pmonth}-01";
		$this->month_end   = "{$this->year}-{$this->pmonth}-{$this->days_in_month}";
	}
	
	/**
	 * 
	 * 
	 * @ver 0.1.0
	 */
	function __get( $var ){
		return $this->$var;
	}
	
	/**
	 * 
	 * 
	 * @ver 0.1.0
	 */
	function __set( $var, $val ){
		
	}
	
	
	
	/**
	 * MODO TABELA
	 * 
	 */
	
	
	
	/**
	 * Iniciar a exibição da tabela do calendário
	 * 
	 * @ver 0.1.0
	 */
	function get_posts_table(){
		if( $this->post_meta === false ){
			$this->get_posts_table_by_date();
		}
		else{
			$this->get_posts_table_by_post_meta();
		}
	}
	
	/**
	 * Buscar uma lista completa de todos os posts, no formato anos/meses/posts
	 * Salva o resultado em transient, que deverá ser deletado em caso de 'save_post', 'trashed_post', 'untrashed_post'
	 * 
	 * @ver 0.1.0
	 */
	function get_all_posts(){
		// verificar se já foi buscado
		if( empty($this->all_posts) ){
			$transient_name = ( $this->post_meta === false ) ? "boros_cldr_{$this->post_type}" : "boros_cldr_{$this->post_type}_{$this->post_meta}";
			
			// verifica o transient
			if( false === ( $this->all_posts = get_transient($transient_name) ) ){
				// todos os posts, sem post_meta
				$args = array(
					'post_type' => $this->post_type,
					'posts_per_page' => -1,
					'order' => 'ASC',
				);
				
				// verificar se está usando post_meta
				if( $this->post_meta != false ){
					$args['meta_key'] = $this->post_meta;
				}
				
				$this->all_posts = array();
				$all_posts_query = new WP_Query($args);
				
				foreach( $all_posts_query->posts as $post ){
					$y = date('Y', strtotime($post->post_date));
					$m = date('m', strtotime($post->post_date));
					$d = date('d', strtotime($post->post_date));
					
					// adicionar o post apenas no dia de publicação
					if( $this->post_meta === false ){
						$this->all_posts[$y][$m][$d] = $post->ID;
					}
					// ou pegar todos os post_metas e adicionar um dia para cada meta
					else{
						$post_days = get_post_meta($post->ID, $this->post_meta);
						foreach( $post_days as $day ){
							$sy = date('Y', strtotime($day));
							$sm = date('m', strtotime($day));
							$sd = date('d', strtotime($day));
							$this->all_posts[$sy][$sm][$sd] = $post->ID;
						}
					}
				}
				ksortRecursive($this->all_posts);
				set_transient( $transient_name, $this->all_posts, $this->transient_expiration );
				//pal("set transient {$transient_name} ALL POSTS");
			}
			//pre($this->all_posts, 'a');
		}
	}
	
	/**
	 * Busca os posts do mês, baseado na data
	 * 
	 * @ver 0.1.0
	 */
	function get_posts_table_by_date(){
		$transient_name = "boros_cldr_{$this->post_type}_{$this->pmonth}";
		if( false === ( $this->posts_table = get_transient($transient_name) ) ){
			$query = apply_filters('boros_calendar_posts_table_by_date_query', array(
				'post_type' => $this->post_type,
				'post_status' => $this->post_status,
				'posts_per_page' => -1,
				'date_query' => array(
					'after'  => $this->month_start,
					'before' => $this->month_end,
				),
			));
			$this->posts_table_query = new WP_Query();
			$this->posts_table_query->query($query);
			if( $this->posts_table_query->posts ){
				//pre($this->posts_table_query->posts);
				foreach( $this->posts_table_query->posts as $post ){
					setup_postdata($post);
					
					// Definir os metas
					$post->metas = $this->set_metas($post->ID);
					
					// Marcar os dias que acontece
					$post->post_days = array( date('Y-m-d 00:00:00', strtotime($post->post_date)) );
					
					// Taxonomias
					if( !empty($this->taxonomies) ){
						foreach( (array)$this->taxonomies as $tax ){
							$terms = wp_get_post_terms( $post->ID, $tax );
							$post_terms = array();
							if( !empty($terms) ){
								foreach( $terms as $t ){
									$post_terms[] = $t;
								}
							}
							$post->$tax = $post_terms;
						}
					}
					
					$this->posts_table[] = $post;
				}
			}
			wp_reset_query();
			set_transient( $transient_name, $this->posts_table, $this->transient_expiration );
			//pal("set transient {$transient_name} POSTS BY DATE");
		}
	}
	
	/**
	 * Buscar posts do mês, baseado em post_meta
	 * 
	 * @ver 0.1.0
	 */
	function get_posts_table_by_post_meta(){
		$transient_name = "boros_cldr_{$this->post_type}_{$this->post_meta}_{$this->pmonth}";
		if( false === ( $this->posts_table = get_transient($transient_name) ) ){
			$query = apply_filters('boros_calendar_posts_table_by_post_meta_query', array(
				'post_type' => $this->post_type,
				'post_status' => $this->post_status,
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => $this->post_meta,
						'value' => $this->month_start,
						'compare' => '>=',
						'type' => 'DATE',
					),
					array(
						'key' => $this->post_meta,
						'value' => $this->month_end,
						'compare' => '<=',
						'type' => 'DATE',
					),
				),
			));
			$this->posts_table_query = new WP_Query();
			$this->posts_table_query->query($query);
			if( $this->posts_table_query->posts ){
				//pre($this->posts_table_query->posts);
				foreach( $this->posts_table_query->posts as $post ){
					setup_postdata($post);
					
					// Definir os metas
					$post->metas = $this->set_metas($post->ID); //pre($post->metas);
					
					// Marcar os dias que acontece
					$days = array();
					foreach( $post->metas[$this->post_meta] as $day ){
						$days[] = $day;
					}
					$post->post_days = $days;
					
					// Taxonomias
					if( !empty($this->taxonomies) ){
						foreach( (array)$this->taxonomies as $tax ){
							$terms = wp_get_post_terms( $post->ID, $tax );
							$post_terms = array();
							if( !empty($terms) ){
								foreach( $terms as $t ){
									$post_terms[] = $t;
								}
							}
							$post->$tax = $post_terms;
						}
					}
					
					$this->posts_table[] = $post;
				}
			}
			wp_reset_query();
			set_transient( $transient_name, $this->posts_table, $this->transient_expiration );
			//pal("set transient {$transient_name} POSTS BY META");
		}
	}
	
	/**
	 * Output da tabela do calendário
	 * 
	 * @ver 0.1.0
	 */
	function set_metas( $post_id ){
		$meta_values = get_post_custom($post_id);
		$metas = array();
		
		if( !empty($this->accepted_metas) ){
			foreach( $this->accepted_metas as $key ){
				if( isset($meta_values[$key]) ){
					if( is_array($meta_values[$key]) ){
						foreach( $meta_values[$key] as $v ){
							$metas[$key][] = maybe_unserialize($v);
						}
					}
					else{
						$metas[$key] = maybe_unserialize($meta_values[$key]);
					}
				}
				else{
					$metas[$key] = '';
				}
			}
		}
		else{
			foreach( $meta_values as $key => $val ){
				if( is_array($val) ){
					foreach( $val as $v ){
						$metas[$key][] = maybe_unserialize($v);
					}
				}
				else{
					$metas[$key] = maybe_unserialize($val);
				}
			}
		}
		return $metas;
	}
	
	/**
	 * 
	 * 
	 * @ver 0.1.0
	 */
	function show_posts_table(){
		global $wp_locale; //pre($wp_locale);
		
		// dias do mês anterior
		switch($this->mont_first_day_of_week){
			case 'Sun': $blank = 0; break;
			case 'Mon': $blank = 1; break;
			case 'Tue': $blank = 2; break;
			case 'Wed': $blank = 3; break;
			case 'Thu': $blank = 4; break;
			case 'Fri': $blank = 5; break;
			case 'Sat': $blank = 6; break;
			default : $blank = 0;
		}
		
		echo '<div id="calendar-table-box">';
		
		// nav head
		$this->show_calendar_head();
		
		// criar array de mes > semanas > dias, para que possa ser duplicado, a primeira é para os cabeçalhos 
		// com dia e o segundo para os posts do dia
		$month_table = array();
		$week_count = 1;
		$month_table[$week_count] = array();

		// contador dia da semana
		$day_count = 1;

		// espaço dos dias do mês anterior
		while( $blank > 0 ){
			//echo "\t\t<td class='blank-day'><div class='cell-header'></div></td>\n";
			$month_table[$week_count][] = array(
				'day_num' => ' &nbsp; ',
				'day_pad' => ' &nbsp; ',
				'mday' => 'prev',
				'class' => 'blank-day',
				'active' => false,
			);
			$blank = ($blank - 1);
			$day_count++;
		}
		
		// primeiro dia do mês
		$day_num = 1;
		
		while ( $day_num <= $this->days_in_month ){
			// Definir a class do dia, verificando se o mesmo está no presente ou passado
			$today = date('Ymd');
			$day_pad = sprintf('%02d', $day_num);
			
			if( "{$this->year}{$this->pmonth}{$day_pad}" < $today ){
				$active = false;
				$class = 'past-day';
			}
			elseif( "{$this->year}{$this->pmonth}{$day_pad}" == $today ){
				$active = true;
				$class = 'today';
			}
			else{
				$active = true;
				$class = 'future-day';
			}
			
			// identificar se é sexta ou sábado - precisam de class para o posicionamento do popup
			if( $day_count >= 5 ){
				$class .= ' row-last-days';
			}
			
			$month_table[$week_count][] = array(
				'day_num' => $day_num,
				'day_pad' => $day_pad,
				'mday' => $day_num,
				'class' => $class,
				'active' => $active,
			);
			
			$day_num++;
			$day_count++;
			
			// uma linha por semana
			if ($day_count > 7){
				$day_count = 1;
				$week_count++;
			}
		}
		
		// dias do próximo mês
		while( $day_count > 1 && $day_count <= 7 ){ 
			$month_table[$week_count][] = array(
				'day_num' => ' &nbsp; ',
				'day_pad' => ' &nbsp; ',
				'mday' => 'next',
				'class' => 'blank-day',
				'active' => false,
			);
			$day_count++; 
		}
		
		// associar eventos ao $month_table
		$output_table = array();
		foreach( $month_table as $windex => $week ){
			// primeiro loop, head de dias
			$i = 1;
			foreach( $week as $hday ){
				$hday['class'] .= " mday-{$hday['mday']} wday-{$i} {$this->weedays[$i]} cell-header";
				// verificar se este dia possui eventos
				$hday = $this->add_events_to_day( $hday, false );
				$output_table[$windex]['header'][] = $hday;
				
				$i++;
			}
			
			// segundo loop, posts
			$i = 1;
			foreach( $week as $cday ){
				$cday['class'] .= " mday-{$cday['mday']} wday-{$i} {$this->weedays[$i]} cell-events";
				// verificar se este dia possui eventos
				$cday = $this->add_events_to_day( $cday, true );
				$output_table[$windex]['events'][] = $cday;
				$i++;
			}
		}
		//pre($output_table, 'output_table', false);
		
		// iniciar output tabela
		echo "\n<table class='calendar' cellspacing='0' cellpadding='0'>\n";
		echo "\t<tr>\n\t\t<th>Domingo</th><th>Segunda</th><th>Terça</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sábado</th>\n\t</tr>\n";
		
		// loop
		foreach( $output_table as $windex => $week ){
			// primeiro loop, head de dias
			echo "\t<tr class='week-{$windex}'>\n";
			$i = 1;
			foreach( $week['header'] as $day ){
				echo "\t\t<td class='{$day['class']}'><div class='day-number'>{$day['day_pad']}</div></td>\n";
				$i++;
			}
			echo "\t</tr>\n";
			
			// segundo loop, posts
			echo "\t<tr class='week-{$windex}'>\n";
			$i = 1;
			foreach( $week['events'] as $day ){
				echo "\t\t<td class='cell-events {$day['class']}'>\n\t\t";
				$this->show_day_posts($day);
				echo "</td>\n";
				$i++;
			}
			echo "\t</tr>\n";
		}
		
		echo "\t</tr>\n</table>";
		
		// calendar footer
		$this->show_calendar_footer();
		
		echo '</div>';
	}
	
	function calendar_table_nav( $context = 'head' ){
		$calendar_head = sprintf(
			'<div class="calendar-nav row"><div class="col-md-4 col-sm-4">%s</div><div class="col-md-4 col-sm-4">%s</div><div class="col-md-4 col-sm-4">%s</div></div>', 
			$this->prev_next_month_link('prev'), 
			$this->posts_table_dropdown(), 
			$this->prev_next_month_link()
		);
		// filtros: boros_calendar_header ou boros_calendar_footer
		echo apply_filters( "boros_calendar_{$context}", $calendar_head, $this->prev_next_month_link('prev'), $this->prev_next_month_link(), $this->posts_table_dropdown() );
	}
	
	/**
	 * Cabeçalho da tabela
	 * 
	 * @ver 0.1.0
	 */
	function show_calendar_head(){
		$this->calendar_table_nav( 'head' );
	}
	
	function show_calendar_footer(){
		$this->calendar_table_nav( 'footer' );
	}
	
	/**
	 * Output dos eventos do dia.
	 * Cadda evento passa pelo filtro 'boros_calendar_event_day_output'
	 * 
	 * @ver 0.1.0
	 */
	function show_day_posts( $day ){
		$d = sprintf('%02d', $day['day_num']);
		$day_index = "{$this->year}-{$this->pmonth}-{$d} 00:00:00";
		$blank_day = true;
		
		if( !empty($this->posts_table) ){
			foreach( $this->posts_table as $evt ){
				if( in_array($day_index, $evt->post_days) ){
					$link = get_permalink($evt->ID);
					$title = apply_filters('the_title', $evt->post_title);
					$output = sprintf('<p><a href="%s">%s</a></p>', $link, $title);
					echo apply_filters( 'boros_calendar_event_day_output', $output, $evt, $day, $link, $title );
				}
			}
		}
	}
	
	/**
	 * Verificar se determinado dia possui eventos e adicionar
	 * 
	 * @ver 0.1.0
	 */
	function add_events_to_day( $day, $full = true ){
		$day['events'] = array();
		
		$d = sprintf('%02d', $day['day_num']);
		$day_index = "{$this->year}-{$this->pmonth}-{$d} 00:00:00";
		$blank_day = true;
		
		if( !empty($this->posts_table) ){
			foreach( $this->posts_table as $evt ){
				if( in_array($day_index, $evt->post_days) ){
					if( $full == true ){
						$day['events'][] = $evt;
					} else {
						$day['events'][] = $evt->ID;
					}
					$blank_day = false;
				}
			}
		}
		if( $blank_day == false ){
			$day['class'] .= ' has-events';
		}
		
		return $day;
	}
	
	/**
	 * Link para mês anterior ou posterior
	 * 
	 * @ver 0.1.0
	 */
	function prev_next_month_link( $direction = 'next' ){
		global $wp_locale;
		
		if( $direction == 'next' ){
			$modifier = '+1 month';
			$class = 'prev-next-month next';
		}
		else{
			$modifier = '-1 month';
			$class = 'prev-next-month prev';
		}
		$date_obj = new DateTime("{$this->year}-{$this->month}");
		$date_obj->modify($modifier);
		
		$ca = $date_obj->format('Y');
		$cm = $date_obj->format('n');
		if( $ca == date('Y') and $cm == date('n') ){
			$link = esc_url(remove_query_arg( array('ca', 'cm') ));
		}
		else{
			$link = esc_url(add_query_arg( array('ca' => $ca, 'cm' => $cm) ));
		}
		
		$html = "<a href='{$link}' class='{$class}'>{$wp_locale->month[$date_obj->format('m')]}</a>";
		
		return apply_filters( 'boros_calendar_prev_next_month_link', $html, $direction, $date_obj, $link, $class, $wp_locale->month[$date_obj->format('m')] );
	}
	
	/**
	 * Dropdown apenas com os meses que possuem posts
	 * 
	 * @ver 0.1.0
	 */
	function posts_table_dropdown( $echo = true ){
		global $wp_locale; //pre($wp_locale, 'wp_locale', false);
		
		// buscar todos os posts
		$this->get_all_posts();
		
		$dropdown = '';
		$dropdown_opts = array();
		$class = 'form-control table-events-dropdown';
		if( !empty($this->all_posts) ){
			$dropdown = "<select class='{$class}'><option>-</option>";
			foreach( $this->all_posts as $year => $months ){
				foreach( $months as $month => $events ){
					$selected = ($this->year == $year and $this->month == $month ) ? ' selected="selected"' : '';
					$month_name = ucfirst($wp_locale->month[$month]);
					$date = new DateTime("{$year}-{$month}");
					$link = add_query_arg( array('ca' => $date->format('Y'), 'cm' => $date->format('n')) );
					$html = "<option value='{$link}' {$selected}>{$month_name} de {$year}</option>";
					$dropdown .= $html;
					
					$dropdown_opts[] = array(
						'selected'   => $selected,
						'year'       => $year,
						'month_name' => $month_name,
						'date_obj'   => $date,
						'link'       => $link,
						'html'       => $html,
					);
				}
			}
			$dropdown .= '</select>';
		}
		
		return apply_filters( 'boros_calendar_month_dropdown', $dropdown, $class, $dropdown_opts );
	}
	
	
	
	/**
	 * MODO LISTAGEM
	 * 
	 */
	
	
	
	/**
	 * 
	 * 
	 */
	function get_list_events(){
		// pegar todos os eventos do ano
		$query = array(
			'post_type' => 'evento',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'event_end',
					'value' => "{$this->year}-01-01",
					'compare' => '>=',
					'type' => 'DATE',
				),
				array(
					'key' => 'event_start',
					'value' => "{$this->year}-12-31",
					'compare' => '<=',
					'type' => 'DATE',
				),
				
				//'relation' => 'OR',
				//array(
				//	'key' => 'event_start_year',
				//	'value' => $this->year,
				//),
				//array(
				//	'key' => 'event_end_year',
				//	'value' => $this->year,
				//),
			),
		);
		$this->query_list_events = new WP_Query();
		$this->query_list_events->query($query);
		if( $this->query_list_events->posts ){
			$year_events = array();
			foreach( $this->query_list_events->posts as $post ){
				setup_postdata($post);
				$metas = get_post_custom($post->ID);
				// definir os metas
				$post->metas = $this->set_metas($metas);
				
				/**
				 * Duração do evento em dias, mas considerando apenas o mês atual
				 * @link http://stackoverflow.com/a/3207849
				 * 
				 */
				$s = new DateTime($post->metas['event_date']['start_iso']); 
				$e = new DateTime($post->metas['event_date']['end_iso']);
				$e->modify('+1 day');
				$interval = DateInterval::createFromDateString('1 day');
				$period = new DatePeriod($s, $interval, $e);
				$post->post_days = array();
				foreach( $period as $ed ){
					if( $ed->format('Y') == $this->year ){
						$post->post_days[] = $ed->format('Y-m-d');
					}
				}
				
				// categoria
				$terms = wp_get_post_terms( $post->ID, 'evento_categoria' );
				$post->evento_categoria = array();
				if( !empty($terms) ){
					foreach( $terms as $t ){
						$post->evento_categoria[] = $t;
					}
				}
				$year_events[] = $post;
			}
			//pre($this->list_events);
			//pre($year_events);
			
			foreach( $year_events as $evt ){
				$evt_month = date('m', strtotime($evt->post_days[0]));
				$this->list_events[$evt_month][] = $evt;
			}
			ksort($this->list_events);
			//pre($this->list_events, 'list_events', false);
		}
	}
	
	function show_events_list(){
		global $wp_locale;
		?>
		<div id="events-list-box">
			<div id="events-list-header">
				Eventos em 
				<?php $this->months_dropdown(); ?>
			</div>
			<div id="events-list-months">
			<?php
			foreach( $this->list_events as $month => $events ){
				$month_name = $wp_locale->month[$month];
				echo "<div class='events-list-month' id='eventos-list-mont-{$month}'>";
				echo "<div class='events-list-month-name'>{$month_name}</div><div class='event-list-items'>";
				ksort($events);
				foreach( $events as $evt_date => $evt ){
					?>
					<div class="event-list-item">
						<div class="event-list-item-title"><div><?php echo $evt->post_title; ?></div></div>
						<div class="event-list-item-details" data-run-toggle="0">
							<div class="event-list-item-details-info">
								<div class="event-list-item-details-info-head"><?php echo $evt->metas['event_date_string']; ?></div>
								<div class="event-list-item-details-info-body">
									<p><strong><?php echo $evt->metas['event_location_name']; ?></strong> - <?php echo $evt->metas['event_location_address']; ?></p>
									<p><strong>Valor:</strong> <?php echo $evt->metas['event_price']; ?></p>
								</div>
							</div>
							<div class="event-list-item-details-description">
								<?php echo apply_filters('the_content', $evt->post_content); ?>
							</div>
							<div class="event-list-item-details-ticket">
								<a href="<?php echo $evt->metas['event_ticket_url']; ?>" target="_blank">Faça sua inscrição aqui</a>
							</div>
						</div>
					</div>
					<?php
				}
				echo '</div></div>';
			}
			?>
			</div><!-- events-list-months -->
		</div>
		<?php
	}
	
	function get_month_events(){
		// pegar todos os eventos do mes
		$query = array(
			'post_type' => 'evento',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'event_end',
					'value' => $this->month_start,
					'compare' => '>=',
					'type' => 'DATE',
				),
				array(
					'key' => 'event_start',
					'value' => $this->month_end,
					'compare' => '<=',
					'type' => 'DATE',
				),
			),
		);
		$this->query_month_events = new WP_Query();
		$this->query_month_events->query($query);
		if( $this->query_month_events->posts ){
			$month_events = array();
			foreach( $this->query_month_events->posts as $post ){
				setup_postdata($post);
				$metas = get_post_custom($post->ID);
				// definir os metas
				$post->metas = $this->set_metas($metas);
				
				/**
				 * Duração do evento em dias, mas considerando apenas o mês atual
				 * @link http://stackoverflow.com/a/3207849
				 * 
				 */
				$s = new DateTime($post->metas['event_date']['start_iso']); 
				$e = new DateTime($post->metas['event_date']['end_iso']);
				$e->modify('+1 day');
				$interval = DateInterval::createFromDateString('1 day');
				$period = new DatePeriod($s, $interval, $e);
				$post->post_days = array();
				foreach( $period as $ed ){
					if( $ed->format('m') == $this->pmonth ){
						$post->post_days[] = $ed->format('Y-m-d');
					}
				}
				
				// categoria
				$terms = wp_get_post_terms( $post->ID, 'evento_categoria' );
				$post->evento_categoria = array();
				if( !empty($terms) ){
					foreach( $terms as $t ){
						$post->evento_categoria[] = $t;
					}
				}
				$month_events[] = $post;
			}
			
			foreach( $month_events as $evt ){
				$this->month_events[$evt->post_days[0]] = $evt;
			}
			ksort($this->month_events);
			//pre($this->month_events);
		}
	}
	
	function show_month_events(){
		global $wp_locale; //pre($wp_locale->weekday);
		$events_in_years = get_option('events_in_years'); //pre($events_in_years, 'events_in_years', false);
		?>
		<div id="month-events-box" class="visible-lg-block visible-md-block">
			<div id="month-events-box-index">
				<div id="month-events-header">
					Eventos em 
					<?php $this->months_dropdown(); ?>
				</div>
				<div id="month-events-month-list">
					<ul>
						<li <?php $this->month_events_next_month_class(1); ?>><a href="<?php $this->month_events_next_month_link(1); ?>">Jan</a></li>
						<li class="separator"></li>
						<li <?php $this->month_events_next_month_class(2); ?>><a href="<?php $this->month_events_next_month_link(2); ?>">Fev</a></li>
						<li class="separator"></li>
						<li <?php $this->month_events_next_month_class(3); ?>><a href="<?php $this->month_events_next_month_link(3); ?>">Mar</a></li>
						<li class="separator three"></li>
						<li <?php $this->month_events_next_month_class(4); ?>><a href="<?php $this->month_events_next_month_link(4); ?>">Abr</a></li>
						<li class="separator four"></li>
						<li <?php $this->month_events_next_month_class(5); ?>><a href="<?php $this->month_events_next_month_link(5); ?>">Mai</a></li>
						<li class="separator"></li>
						<li <?php $this->month_events_next_month_class(6); ?>><a href="<?php $this->month_events_next_month_link(6); ?>">Jun</a></li>
						<li class="separator three"></li>
						<li <?php $this->month_events_next_month_class(7); ?>><a href="<?php $this->month_events_next_month_link(7); ?>">Jul</a></li>
						<li class="separator"></li>
						<li <?php $this->month_events_next_month_class(8); ?>><a href="<?php $this->month_events_next_month_link(8); ?>">Ago</a></li>
						<li class="separator four"></li>
						<li <?php $this->month_events_next_month_class(9); ?>><a href="<?php $this->month_events_next_month_link(9); ?>">Set</a></li>
						<li class="separator three"></li>
						<li <?php $this->month_events_next_month_class(10); ?>><a href="<?php $this->month_events_next_month_link(10); ?>">Out</a></li>
						<li class="separator"></li>
						<li <?php $this->month_events_next_month_class(11); ?>><a href="<?php $this->month_events_next_month_link(11); ?>">Nov</a></li>
						<li class="separator"></li>
						<li <?php $this->month_events_next_month_class(12); ?>><a href="<?php $this->month_events_next_month_link(12); ?>">Dez</a></li>
					</ul>
					<span></span>
				</div>
			</div>
			<div id="month-events-list">
				<?php
				if( empty($this->month_events) ){
					echo '<h2>Ainda não temos eventos programados para essa data</h2>';
					echo '<p>Mas fique ligado! Cadastre-se em nossa Newsletter e receba informações sobre nossos eventos, assim como dicas e atualizações para ajudar na sua carreira</p>';
				}
				else {
					//pre( $this->month_events, 'month_events', false );
					echo '<div class="month-events-list-items">';
					foreach( $this->month_events as $evt ){
						//pre($evt, 'evt', false);
						$weekday = str_replace('-feira', '', $wp_locale->weekday[date('w', strtotime($evt->post_days[0]))]);
						$day = date('j', strtotime($evt->post_days[0]));
						
						?>
						<div class="month-events-list-item">
							<div class="month-events-list-item-date">
								<div class="month-events-list-item-date-weekday"><?php echo $weekday; ?></div>
								<div class="month-events-list-item-date-day"><?php echo $day; ?></div>
							</div>
							<div class="month-events-list-item-desc">
								<div class="month-events-list-item-desc-title"><h2><?php echo $evt->post_title; ?> <a href="<?php echo $evt->metas['event_ticket_url']; ?>" target="_blank">Faça sua inscrição</a></h2></div>
								<div class="month-events-list-item-desc-info">
									<div class="month-events-list-item-desc-info-date"><?php echo $evt->metas['event_date_string']; ?></div>
									<div class="month-events-list-item-desc-info-location"><strong><?php echo $evt->metas['event_location_name']; ?></strong> <?php echo $evt->metas['event_location_address']; ?></div>
								</div>
								<div class="month-events-list-item-desc-text">
									<?php echo apply_filters('the_content', $evt->post_content); ?>
								</div>
							</div>
						</div>
						<?php
					}
					echo '</div>';
				}
				?>
			</div>
		</div>
		<?php
	}
	
	function month_events_next_month_link( $n ){
		$next = new DateTime("{$this->year}-{$n}");
		echo add_query_arg( array('ca' => $next->format('Y'), 'cm' => $next->format('n')) );
	}
	
	function month_events_next_month_class( $n ){
		if( $n == $this->month ){
			echo ' class="active"';
		}
	}
	
	function months_dropdown(){
		$events_in_years = get_option('events_in_years');
		if( !empty($events_in_years) ){
			echo "<select id='month-events-dropdown' class='month-events-dropdown'>";
			foreach( $events_in_years as $year => $months ){
				$selected = ($this->year == $year) ? ' selected="selected"' : '';
				$link = add_query_arg( array('ca' => $year, 'cm' => 1) );
				echo "<option value='{$link}' {$selected}>{$year}</option>";
			}
			echo '</select>';
		}
	}
	
	function show_year_events(){
		global $wp_locale; //pre($wp_locale->weekday);
		$events_in_years = get_option('events_in_years'); //pre($events_in_years, 'events_in_years', false);
		?>
		<div id="year-events">
			<div id="year-events-header">
				Eventos em 
				<?php $this->months_dropdown(); ?>
			</div>
			<div id="year-month-events-list">
				<?php
				if( isset($events_in_years[$this->year]) )
				foreach( $events_in_years[$this->year] as $month ){
					pre($month);
				}
				?>
			</div>
		</div>
		<?php
	}
}












