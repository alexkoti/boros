<?php
/**
 * Calendar
 * 
 * WARNINGS!
 * 1) devido ao limite de 64 caracteres no 'option_name' na tabela '_options', o total de caracteres usados no 'post_meta' 
 *    não poderá ultrapassar o limite de 30, segundo a seguinte fórmula: post_meta_length = (30 - post_type_length)
 * 
 * 
 * 
 * Modelo de styles para exibição da tabela em xs:
 
    table.calendar , 
    table.calendar thead, 
    table.calendar tbody, 
    table.calendar th, 
    table.calendar td, 
    table.calendar tr {
        display:block;
    }
    table.calendar .has-events {
        display:block;
    }
    table.calendar td.cell-events {
        height:auto;
    }
    table.calendar {
        border:0;
        border-bottom:1px solid #fdc222;
    }
    table.calendar th,
    table.calendar td,
    table.calendar td.cell-header,
    table.calendar .event-btn-ovelay {
        display:none;
        border:none;
    }
    table.calendar tr{
        border:none;
    }
    table.calendar td.cell-events {
        border:1px solid #fdc222;
        border-bottom:none;
        padding:0;
    }
    table.calendar td.cell-events .events-list {
        display: block;
    }
    table.calendar tr.week-extra td {
        border:0;
    }
    .agenda-no-posts {
        background-color: #fff4d4;
        border: 2px solid #fff;
        padding:10px;
        text-align:center;
    }
    table.empty-calendar {
        display:none;
    }
 * 
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
    
    protected $month_slug = '';
    
    protected $month_abbrev = '';
    
    protected $pmonth = 0;
    
    protected $year = 0;
    
    protected $days_in_month = 0;
    
    protected $first_day = 0;
    
    protected $mont_first_day_of_week = '';
    
    protected $month_start = 0;
    
    protected $month_end = 0;
    
    protected $accepted_metas = array();
    
    /**
     * Lista de taxonomias permitidas
     * 
     */
    protected $taxonomies = array();
    
    /**
     * WP_Query da requisição de posts
     * 
     */
    protected $posts_query;
    
    /**
     * @todo remover?
     * 
     */
    protected $query_list_events;
    
    /**
     * @todo remover?
     * 
     */
    protected $list_events = array();
    
    /**
     * @todo remover?
     * 
     */
    protected $query_month_events = array();
    
    /**
     * @todo remover?
     * 
     */
    protected $month_events = array();
    
    /**
     * Slugs dos dias da semana
     * 
     */
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
     * Strings do head da tabela
     * 
     */
    protected $weekdays_head = false;
    
    /**
     * Cópia do $wp_locale
     * 
     */
    protected $locale = array();
    
    /**
     * Mostrar linha própria para os números de dias separados da linha de evento
     * 
     */
    protected $number_heads = true;
    
    /**
     * Adicionar row extra para slideDown e exibição de dados
     * 
     */
    protected $extra_row = false;
    
    /**
     * Variável de url para o reset do transient(cache)
     * 
     */
    protected $delete_cache_var = false;
    protected $delete_cache = false;
    
    /**
     * Posts através de wp_query
     * 
     */
    protected $posts = false;
    
    /**
     * Posts formatado no padrão month > week[header|events] > day > [day_number|day_events]
     * 
     */
    protected $posts_table = false;
    
    /**
     * Construct
     * 
     * $config
     *     ['timezone']         string
     *     ['post_type']        string Default 'post'
     *     ['post_status']      string Default 'publish' 
     *     ['post_meta']        string|mixed Default false, define o post_meta que armazena a informação das datas do evento.
     *                                       É necessário que o limite de caracteres desse post_meta seja:
     *                                       post_meta_length = (30 - post_type_length); ver o Warning 1), na descrição da classe.
     *     ['day']              string Default dia atual via time()
     *     ['month']            string Default mês atual via time() 
     *     ['year']             string Default ano atual via time()
     *     ['accepted_metas']   array Array de meta_keys que os posts serão incorporados ao objeto post. Caso não declarado, 
     *                                será retornado todos os post_metas
     *     ['taxonomies']       array|string Taxonomias que deverão ser incorporados ao objeto post. Default nenhum
     *     ['number_heads']     bool Mostrar linha própria para os números de dias separados da linha de evento. Default true
     *     ['weekdays_head']    array Definir as strings dos dias da semana, mostrados no head da tabela. Default empty, será usado 'weekday_initial'
     *     ['extra_row']        bool Mostrar <row> extra para slideDown e exibição de dados. Default false
     *     ['delete_cache_var'] string Parâmetro de url para apagar o cache. Default false
     * 
     * @param array $config (ver acima)
     * 
     * @ver 0.1.0
     */
    function __construct( $config = array() ){
        global $wp_locale;
        $this->locale = $wp_locale; pre($this->locale);
        
        $vars = array(
            'post_type',
            'post_status',
            'post_meta',
            'posts_in_years_option',
            'timezone',
            'accepted_metas',
            'taxonomies',
            'number_heads',
            'weekdays_head',
            'extra_row',
            'delete_cache_var',
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
        
        // primeiro dia do mês
        $this->first_day = mktime(0,0,0,$this->month, 1, $this->year) ; 
        
        // dia da semana do primeiro dia
        $this->mont_first_day_of_week = date('D', $this->first_day) ; 
        
        // quantos dias existem neste mês
        $this->days_in_month = cal_days_in_month(0, $this->month, $this->year);
        
        // @todo remover pmonth e month_number, e usar apenas month
        $this->pmonth       = sprintf('%02d', $this->month); // format de mês com leading-zero
        $this->month_number = date('m', $this->first_day);
        $this->month_name   = $this->locale->month_genitive[$this->month_number];
        $this->month_slug   = $this->locale->month[$this->month_number];
        $this->month_abbrev = $this->locale->month_abbrev[$this->month_slug];
        
        // head com os dias da semana
        if( $this->weekdays_head )
        
        // início e fim do mês
        $this->month_start = "{$this->year}-{$this->pmonth}-00";
        $this->month_end   = "{$this->year}-{$this->pmonth}-{$this->days_in_month}";
        
        // adicionar javascript
        if( $this->extra_row == true ){
            add_action( 'wp_footer', array($this, 'extra_row_javascript'), 99 );
        }
        
        // sinalizar se precisa remover o cache(transient)
        if( $this->delete_cache_var != false and isset($_GET[$this->delete_cache_var]) ){
            $this->delete_cache = true;
        }
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
    
    function get_transient( $transient_name ){
        if( $this->delete_cache == true ){
            return false;
        }
        else{
            return get_transient($transient_name);
        }
    }
    
    /**
     * Iniciar a exibição da tabela do calendário
     * 
     * @ver 0.1.0
     */
    function get_posts(){
        if( $this->post_meta === false ){
            $this->get_posts_by_date();
        }
        else{
            $this->get_posts_by_post_meta();
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
            $transient = $this->get_transient($transient_name);
            
            // verifica o transient
            if( false !== $transient ){
                $this->all_posts = apply_filters( 'boros_calendar_all_posts', $transient );
            }
            else{
                delete_transient($transient_name);
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
        }
    }
    
    /**
     * Busca os posts do mês, baseado na data
     * 
     * @ver 0.1.0
     */
    function get_posts_by_date(){
        $transient_name = "boros_cldr_{$this->post_type}_{$this->pmonth}_{$this->year}";
        $transient = $this->get_transient($transient_name);
        
        if( false !== $transient ){
            $this->posts = apply_filters( 'boros_calendar_posts', $transient );
        }
        else{
            delete_transient($transient_name);
            $query = apply_filters('boros_calendar_posts_by_date_query', array(
                'post_type' => $this->post_type,
                'post_status' => $this->post_status,
                'posts_per_page' => -1,
                'date_query' => array(
                    'after'  => $this->month_start,
                    'before' => $this->month_end,
                ),
            ));
            $this->posts_query = new WP_Query();
            $this->posts_query->query($query);
            if( $this->posts_query->posts ){
                //pre($this->posts_query->posts);
                foreach( $this->posts_query->posts as $post ){
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
                    
                    $this->posts[] = $post;
                }
            }
            wp_reset_query();
            set_transient( $transient_name, $this->posts, $this->transient_expiration );
            //pal("set transient {$transient_name} POSTS BY DATE");
        }
        
        $this->add_events_to_month();
    }
    
    /**
     * Buscar posts do mês, baseado em post_meta
     * O post_meta precisa ser uma data no formato 'Y-m-d', e precisa de uma entrada de post_meta para cada dia de ocorrência.
     * 
     * 
     * @ver 0.1.0
     */
    function get_posts_by_post_meta(){
        $transient_name = "boros_cldr_{$this->post_type}_{$this->post_meta}_{$this->pmonth}_{$this->year}";
        $transient = $this->get_transient($transient_name);
        
        if( false !== $transient ){
            $this->posts = apply_filters( 'boros_calendar_posts', $transient );
        }
        else{
            delete_transient($transient_name);
            $query = apply_filters('boros_calendar_posts_by_post_meta_query', array(
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
            $this->posts_query = new WP_Query();
            $this->posts_query->query($query);
            if( $this->posts_query->posts ){
                foreach( $this->posts_query->posts as $post ){
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
                    
                    $this->posts[] = $post;
                }
                
                $this->posts = apply_filters( 'boros_calendar_posts_transient', $this->posts );
            }
            wp_reset_query();
            set_transient( $transient_name, $this->posts, $this->transient_expiration );
        }
        
        $this->add_events_to_month();
    }
    
    /**
     * Adicionar os post_metas ao $post, filtrando por 'accepted_metas', caso definido
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
     * Criar array do calendário no formato month > week[header|events] > day > [day_number|day_events]
     * 
     * @ver 0.1.0
     */
    function add_events_to_month(){
        
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
        
        // criar array de mês > semanas > dias, para que possa ser duplicado, a primeira é para os cabeçalhos 
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
                'mday'    => $day_num,
                'class'   => $class,
                'attr'    => '',
                'active'  => $active,
                'gmt'     => "{$this->year}-{$this->month}-{$day_pad} 00:00:00",
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
        $this->posts_table = array();
        foreach( $month_table as $windex => $week ){
            // primeiro loop, head de dias
            $i = 1;
            foreach( $week as $hday ){
                $hday['class'] .= " mday-{$hday['mday']} wday-{$i} {$this->weedays[$i]} cell-header";
                // verificar se este dia possui eventos
                $hday = $this->add_events_to_day( $hday, false );
                $this->posts_table[$windex]['header'][] = $hday;
                
                $i++;
            }
            
            // segundo loop, posts
            $i = 1;
            foreach( $week as $cday ){
                $cday['class'] .= " mday-{$cday['mday']} wday-{$i} {$this->weedays[$i]} cell-events";
                $cday['attr'] = " data-day='{$cday['mday']}' data-weekday='{$this->weedays[$i]}' data-month='{$this->month}' data-year='{$this->year}'";
                // verificar se este dia possui eventos
                $cday = $this->add_events_to_day( $cday, true );
                $this->posts_table[$windex]['events'][] = $cday;
                $i++;
            }
        }
        //pre($this->posts_table, 'output_table', false);
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
        
        if( !empty($this->posts) ){
            foreach( $this->posts as $evt ){
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
     * Output da tabela do calendário
     * 
     * @todo - aplicar tags de tradução no <th>
     * @todo - criar row opcional de output completo com slideDown, semelhante ao http://bootstrap-calendar.azurewebsites.net/
     * 
     * @ver 0.1.0
     */
    function show_calendar_table(){
        if( $this->posts == false ){
            do_action('boros_calendar_no_posts', get_object_vars($this));
        }
        
        $table_class = 'calendar';
        if( $this->posts == false ){
            $table_class .= ' empty-calendar no-posts';
        }
        
        // iniciar output tabela
        echo "\n<table class='{$table_class}' cellspacing='0' cellpadding='0'>\n";
        
        // table header com os dias da semana
        $this->table_weekdays_head();
        
        // loop
        foreach( $this->posts_table as $windex => $week ){
            // primeiro loop, head de dias
            if( $this->number_heads == true ){
                echo "\t<tr class='week-{$windex} week-heads' data-row='{$windex}'>\n";
                foreach( $week['header'] as $day ){
                    echo apply_filters( 'boros_calendar_number_head', "\t\t<td class='{$day['class']}'><div class='day-number'>{$day['day_pad']}</div></td>\n", $day );
                }
                echo "\t</tr>\n";
            }
            
            // segundo loop, posts
            echo "\t<tr class='week-{$windex} week-events' data-row='{$windex}'>\n";
            foreach( $week['events'] as $day ){
                echo "\t\t<td class='cell-events {$day['class']}' {$day['attr']}>\n\t\t";
                $this->show_day_posts($day);
                echo "</td>\n";
            }
            echo "\t</tr>\n";
            
            // terceiro loop, row extra para slideDown e exibição de dados
            if( $this->extra_row == true ){
                echo "\t<tr class='week-{$windex} week-extra' data-row='{$windex}'>\n";
                echo "\t\t<td colspan='7' class='week-extra-cell'></td>";
                echo "\t</tr>\n";
            }
        }
        
        echo "\t</tr>\n</table>";
    }
    
    /**
     * Cabeçalho dos dias da semana
     * 
     */
    function table_weekdays_head(){
        // padrão para iniciais
        $w = array_values($this->locale->weekday_initial);
        
        // customizado?
        if( !empty($this->weekdays_head) ){
            // array completo declarado
            if( is_array($this->weekdays_head) ){
                $w = $this->weekdays_head;
            }
            // usar opções do locale
            elseif( in_array( $this->weekdays_head, array('weekday', 'weekday_initial', 'weekday_abbrev')) ){
                $w = array_values($this->locale->{$this->weekdays_head});
            }
        }
        $w = apply_filters( 'boros_calendar_weekdays_head', $w );
        
        echo "\t<tr>";
            echo "\n\t\t<th class='wday-1 {$this->weedays[1]}'>{$w[0]}</th>";
            echo "<th class='wday-2 {$this->weedays[2]}'>{$w[1]}</th>";
            echo "<th class='wday-3 {$this->weedays[3]}'>{$w[2]}</th>";
            echo "<th class='wday-4 {$this->weedays[4]}'>{$w[3]}</th>";
            echo "<th class='wday-5 {$this->weedays[5]}'>{$w[4]}</th>";
            echo "<th class='wday-6 {$this->weedays[6]}'>{$w[5]}</th>";
            echo "<th class='wday-7 {$this->weedays[7]}'>{$w[6]}</th>";
        echo "\n\t</tr>\n";
    }
    
    function calendar_table_nav( $context = 'head', $dropdown = false ){
        $center = '';
        if( $dropdown == true ){
            $center = $this->posts_dropdown();
        }
        $calendar_head = sprintf(
            '<div class="calendar-nav row"><div class="col-md-4 col-sm-4 col-xs-6 prev-month">%s</div><div class="col-md-4 col-sm-4 month-dropdown">%s</div><div class="col-md-4 col-sm-4 col-xs-6 next-month">%s</div></div>', 
            $this->prev_next_month_link('prev'), 
            apply_filters( 'calendar_table_nav_center', $center ), 
            $this->prev_next_month_link()
        );
        // filtros: boros_calendar_header ou boros_calendar_footer
        echo apply_filters( "boros_calendar_{$context}", $calendar_head, $this->prev_next_month_link('prev'), $this->prev_next_month_link(), $center );
    }
    
    /**
     * Cabeçalho da tabela
     * 
     * @ver 0.1.0
     */
    function show_calendar_head( $dropdown = false ){
        $this->calendar_table_nav( 'head', $dropdown );
    }
    
    function show_calendar_footer( $dropdown = false ){
        $this->calendar_table_nav( 'footer', $dropdown );
    }
    
    /**
     * Output dos eventos do dia.
     * Cada evento passa pelo filtro 'boros_calendar_event_day_item_output'
     * 
     * @ver 0.1.0
     */
    function show_day_posts( $day ){
        $day_index = "{$this->year}-{$this->pmonth}-{$day['day_pad']} 00:00:00";
        $blank_day = true;
        
        if( !empty($this->posts) ){
            $list_class = 'events-list';
            $show_events_button = '';
            $events_list = array();
            $events_available = array();
            $output = array();
            
            if( $this->extra_row == true ){
                $list_class = 'events-list hidden';
            }
            foreach( $this->posts as $evt ){
                if( in_array($day_index, $evt->post_days) ){
                    $evt->url = get_permalink($evt->ID);
                    $evt->title = apply_filters('the_title', $evt->post_title);
                    $item = sprintf('<li><a href="%s">%s</a></li>', $evt->url, $evt->title);
                    $events_available[] = $evt;
                    $events_list[] = apply_filters( 'boros_calendar_event_day_item_output', $item, array('post' => $evt, 'day' => $day) );
                }
            }
            
            // mostrar dia dentro da célula caso a linha própria de dias esteja desabilitada
            $day_args = array('year' => $this->year, 'month' => $this->pmonth, 'day' => $day );
            if( $this->number_heads == false ){
                echo apply_filters( 'boros_calendar_cell_day_number', "<div class='day-number' data-date='{$day['day_pad']}'>{$day['day_pad']}</div>", $day_args );
            }
            
            $filter_args = array('year' => $this->year, 'month' => $this->pmonth, 'day' => $day, 'events_list' => $events_list, 'events_available' => $events_available);
            if( !empty($events_list) and $this->extra_row == true ){
                $show_events_button = apply_filters( 'boros_calendar_show_events_button', "<div class='show-events-btn hidden-xs'>&#x26AB;</div>", $filter_args );
            }
            
            if( !empty($events_list) ){
                $output[] = "<span class='visible-xs day-number' data-date='{$day_index}'>{$day['day_pad']}</span>";
                $output[] = $show_events_button;
                $output[] = "<ul class='{$list_class}'>";
                $output[] = implode('', $events_list);
                $output[] = '</ul>';
                $output = apply_filters( 'boros_calendar_event_day_output', $output, $filter_args );
                echo implode('', $output);
            }
            else{
                echo apply_filters( 'boros_calendar_cell_empty', '', $day_args );
            }
        }
    }
    
    /**
     * Link para mês anterior ou posterior
     * 
     * @ver 0.1.0
     */
    function prev_next_month_link( $direction = 'next' ){
        
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
        $link = $this->month_url( $direction, $date_obj );
        
        $html = "<a href='{$link}' class='{$class}'>{$this->locale->month_genitive[$date_obj->format('m')]}</a>";
        
        return apply_filters( 'boros_calendar_prev_next_month_link', $html, $direction, $date_obj, $link, $class, $this->locale->month[$date_obj->format('m')] );
    }
    
    function month_url( $direction = 'next', $date_obj = false ){
        
        if( $direction == 'next' ){
            $modifier = '+1 month';
        }
        else{
            $modifier = '-1 month';
        }
        
        if( $date_obj == false ){
            $date_obj = new DateTime("{$this->year}-{$this->month}");
            $date_obj->modify($modifier);
        }
        
        $ca = $date_obj->format('Y');
        $cm = $date_obj->format('n');
        if( $ca == date('Y') and $cm == date('n') ){
            return esc_url(remove_query_arg( array('ca', 'cm') ));
        }
        else{
            return esc_url(add_query_arg( array('ca' => $ca, 'cm' => $cm) ));
        }
    }
    
    /**
     * Dropdown apenas com os meses que possuem posts
     * 
     * @ver 0.1.0
     */
    function posts_dropdown( $echo = true ){
        
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
                    $month_name = $this->locale->month_genitive[$month];
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
     * Javascript para extra_row
     * 
     * @ver 0.1.1
     */
    function extra_row_javascript(){
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            var boros_calendar_extra_row = {day: 0, content: ''};
            
            $('table.calendar .show-events-btn').on('click', function(e){
                var btn = $(this);
                var table = $(this).closest('table.calendar');
                var td = $(this).closest('td');
                var day = td.attr('data-day');
                var month = td.attr('data-month');
                var year = td.attr('data-year');
                var row = $(this).closest('tr').attr('data-row');
                var today = day + month + year;
                var extra_row = $(this).closest('table.calendar').find('.week-' + row + '.week-extra .week-extra-cell');
                
                if( boros_calendar_extra_row.day != today ){
                    $('table.calendar .show-events-btn').removeClass('opened');
                    extra_row.removeClass('opened');
                    boros_calendar_extra_row.day = today;
                    boros_calendar_extra_row.content = td.find('.events-list').clone();
                    boros_calendar_extra_row.content.removeClass('hidden').hide();
                    if( extra_row.find('.events-list').length ){
                        extra_row.find('.events-list').slideUp(400, function(){
                            extra_row.html( boros_calendar_extra_row.content );
                            table.find('.events-list').slideUp();
                            extra_row.find('.events-list').slideDown();
                        });
                    }
                    else{
                        extra_row.html( boros_calendar_extra_row.content );
                        table.find('.events-list').slideUp();
                        extra_row.find('.events-list').slideDown();
                    }
                    extra_row.addClass('opened');
                    btn.addClass('opened');
                }
                else{
                    extra_row.find('.events-list').slideToggle();
                    extra_row.toggleClass('opened');
                    btn.toggleClass('opened');
                }
                
                
            });
        });
        </script>
        <?php
    }
}












