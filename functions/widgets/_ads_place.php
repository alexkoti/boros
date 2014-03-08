<?php
/**
 * ADS PLACE
 * 
 * 
 * 
 * 
 */

register_widget('ads_place');
class ads_place extends WP_Widget {
	function ads_place(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array(
			'classname' => 'simple_ads_manager_widget',
			'description' => 'Ads Place Dinânico'
		);
		
		// opções do controle
		$control_ops = array();
		
		// registrar o widget
		$this->WP_Widget( 'ads_place', 'Ads Place Dinânico', $widget_ops, $control_ops );
	}
	
	
	function widget($args, $instance){
		ad_place_widget_frontend( $args, $instance );
	}
	
	
	function form($instance){
		// sempre limpar valores vazios
		$instance = array_filter($instance);
		//defaults
		$defaults = array(
			'ad_place_prefix' => 'Sidebar Topo',
		);
		// mesclar dados
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
			<p>
				<label for="<?php echo $this->get_field_id('ad_place_prefix'); ?>">Place Padrão:</label><br />
				<select id="<?php echo $this->get_field_id('ad_place_prefix'); ?>" name="<?php echo $this->get_field_name('ad_place_prefix'); ?>">
					<option <?php selected( $instance['ad_place_prefix'], 'Sidebar Topo' ); ?>>Sidebar Topo</option>
					<option <?php selected( $instance['ad_place_prefix'], 'Sidebar Rodapé' ); ?>>Sidebar Rodapé</option>
					<option <?php selected( $instance['ad_place_prefix'], 'Selo Editoria' ); ?>>Selo Editoria</option>
				</select>
			</p>
		<?php
	}
	
	
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['ad_place_prefix'] = 	$new_instance['ad_place_prefix'];
		return $instance;
	}
}

function ad_place_widget_frontend( $args, $instance ){
	global $wp_query, $wpdb, $post;
	extract($args);
	
	//pre($args, 'args');
	//pre($instance, 'instance');
	
	// status do usuário(logado/desloagado)
	if( is_user_logged_in() )
		$ad_place_status = 'Logado';
	else
		$ad_place_status = 'Deslogado';
		
	// nome prefixo base (ex Sidebar Topo)
	if( is_array($instance) )
		$ad_place_prefix = "{$instance['ad_place_prefix']} {$ad_place_status}";
	else
		$ad_place_prefix = "{$instance} {$ad_place_status}";
	
	// separar formatação do title
	if( $instance['ad_place_prefix'] == 'Selo Editoria' ){
		$title = 'patrocinado por:';
		$banner_default = CSS_IMG . '/selo_patrocinio.jpg';
	}
	else{
		$title = 'PUBLICIDADE';
		$banner_default = CSS_IMG . '/banner_padrao.jpg';
	}
	
	// nenhum ad exibido inicialmente, status false
	$ad_placed = false;
	
	// mensagem de alerta caso não exista nenhuma propaganda definida
	$msg = 'Possíveis nomes de Ads Places para este local: <ul>';
	
	echo $before_widget;
	echo $before_title . $title . $after_title;
	
	if( is_home() and $ad_placed == false ){
		// nome prefixo base (ex Sidebar Topo)
		$ad_place_name = "{$ad_place_prefix} Capa";
		
		// buscar ad_place no banco
		$related_ad_place = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sam_places WHERE name = '%s'", $ad_place_name) );
		
		if( $related_ad_place ){
			if(function_exists('drawAdsPlace')) drawAdsPlace(array('id' => $related_ad_place->id), true);
			
			// marcar como já exibido
			$ad_placed = true;
		}
		$msg .= "<li><strong>{$ad_place_name}</strong></li>";
	}
	if( is_single() and $ad_placed == false ){
		$ad_place_name = str_replace( '-', '_', sanitize_title($ad_place_prefix) );
		$single_ad = get_post_meta( $post->ID, $ad_place_name, true );
		
		if( $single_ad ){
			$related_ad = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sam_ads WHERE name = '%s'", $single_ad) );
			
			if(function_exists('drawAd')) drawAd(array('id' => $related_ad->id), true);
			
			// marcar como já exibido
			$ad_placed = true;
		}
		$msg .= "<li>campo <strong>Ads Place({$ad_place_prefix})</strong> no post com <strong>ID #{$post->ID}</strong></li>";
	}
	if( is_tax( 'editoria' ) and $ad_placed == false ){
		$term = $wp_query->get_queried_object();
		
		// possível nome para o ad_place
		$ad_place_name = "{$ad_place_prefix} {$term->name}";
		
		// buscar ad_place no banco
		$related_ad_place = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sam_places WHERE name = '%s'", $ad_place_name) );
		
		if( $related_ad_place ){
			if(function_exists('drawAdsPlace')) drawAdsPlace(array('id' => $related_ad_place->id), true);
			
			// marcar como já exibido
			$ad_placed = true;
		}
		$msg .= "<li><strong>{$ad_place_name}</strong></li>";
	}
	if( is_search() and $ad_placed == false ){
		// possível nome para o ad_place
		$ad_place_name = "{$ad_place_prefix} Busca";
		
		// buscar ad_place no banco
		$related_ad_place = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sam_places WHERE name = '%s'", $ad_place_name) );
		
		if( $related_ad_place ){
			if(function_exists('drawAdsPlace')) drawAdsPlace(array('id' => $related_ad_place->id), true);
			
			// marcar como já exibido
			$ad_placed = true;
		}
		$msg .= "<li><strong>{$ad_place_name}</strong></li>";
	}
	
	// exibir ad place padrão caso não encontre um personalizado
	if( $ad_placed == false ){
		// buscar ad_place PADRÃO no banco
		$related_ad_place = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sam_places WHERE name = '%s'", $ad_place_prefix) );
		
		if( $related_ad_place ){
			if(function_exists('drawAdsPlace')) drawAdsPlace(array('id' => $related_ad_place->id), true);
		}
		else{
			$home = home_url('/');
			echo "<a href='{$home}'><img src='{$banner_default}' alt='quotidiem.org' /></a>";
		}
	}
	if( is_user_logged_in() and current_user_can('manage_options') ){
		$msg .= "<li><strong>{$ad_place_prefix}</strong>(padrão)</li></ul>";
		echo "<div class='ad_admin_help'>{$msg}</div>";
	}
	
	echo $after_widget;
	
	//pre($ad_place_name, '$ad_place_name');
	//pre($related_ad_place, '$related_ad_place');
}