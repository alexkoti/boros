<?php
/**
 * LEITOR RSS
 * 'Semi-clone' do widget core
 * 
 * 
 * 
 * 
 */
 
register_widget('Widget_Custom_RSS_Reader');
class Widget_Custom_RSS_Reader extends WP_Widget {
	function Widget_Custom_RSS_Reader(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array(
			'classname' => 'widget_custom_rss_reader', 
			'description' => 'Leitor de Feeds do Blog Nihongo(pode ser configurado para exibir feeds de outro site)', 
		);
		
		// opções do controle
		$control_ops = array(
			'width' => 500,
		);
		
		// registrar o widget
		$this->WP_Widget( 'Widget_Custom_RSS_Reader', 'Últimas do Blog Nihongo', $widget_ops, $control_ops );
	}
	function widget($args, $instance){
		extract($args);
		//pre($args);
		//pre($instance);
		/**
		 * Preparar dados
		 * 
		 */
		$rss = fetch_feed($instance['feed_url']);
		if ( is_wp_error($rss) ) {
			if ( is_admin() || current_user_can('manage_options') )
				echo '<p>' . sprintf( __('<strong>RSS Error</strong>: %s'), $rss->get_error_message() ) . '</p>';
			return;
		}
		//verificar se retornou dados
		if ( !$rss->get_item_quantity() ) {
			echo '<ul><li>' . __( 'An error has occurred; the feed is probably down. Try again later.' ) . '</li></ul>';
			$rss->__destruct();
			unset($rss);
			return;
		}
		
		echo $before_widget;
		?>
		<div class="sidebar_box rss_box">
			<h2>
				<a href="<?php echo $instance['feed_url']; ?>" class="bg_color_<?php echo $instance['cor']; ?>" target="_blank"><?php echo $instance['title']; ?></a>
			</h2>
			<div class="sidebar_box_desc">
				<?php echo $instance['html']; ?>
			</div>
			<section>
				<?php
				foreach ( $rss->get_items(0, $instance['number']) as $item ) {
					$link = $item->get_link();
					while ( stristr($link, 'http') != $link )
						$link = substr($link, 1);
					$link = esc_url(strip_tags($link));
					$title = esc_attr(strip_tags($item->get_title()));
					if ( empty($title) )
						$title = __('Untitled');
					
					$date = $item->get_date( 'U' );
					if( $date ){
						$date = '<span class="rss-date-day">' . date_i18n( 'd', $date ) . '</span><span class="rss-date-month">' . date_i18n( 'M', $date ) . '</span>';
					}
					
					/**
					if ( $link == '' ) {
						echo "<li>$title {$date}</li>";
					} else {
						echo "<li><a class='rsswidget' href='$link' title='$title'>$title</a>{$date}</li>";
					}
					/**/
					?>
					<article class="rss_item">
						<h3>
							<span class="rss-date"><?php echo $date; ?></span>
							<a href="<?php echo $link; ?>" target="_blank"><?php echo $title; ?></a>
						</h3>
					</article>
					<?php } ?>
				</section>
			</div>
		<?php
		echo $after_widget;
	}
	function form($instance){
		// sempre limpar os valores vazios
		$instance = array_filter($instance);
		// defaults
		$defaults = array(
			'title' => '',
			'cor' => 'd81920',
			'feed_url' => '',
			'number' => '1',
			'html' => '',
		);
		// mesclar dados
		$instance = wp_parse_args( (array) $instance, $defaults );
		// config color radios
		$color_args = array(
			'name' => $this->get_field_name('cor'),
			'checked' => $instance['cor'],
		);
		?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>">Titulo do Box:</label>
				<input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
			</p>
			<div>
				<span class="label">Cor do chapéu do título:</span><br />
				<?php radio_colors($color_args); ?>
			</div>
			<hr />
			<p>
				<label for="<?php echo $this->get_field_id('feed_url'); ?>">Endereço do Feed:</label>
				<input type="text" id="<?php echo $this->get_field_id('feed_url'); ?>" name="<?php echo $this->get_field_name('feed_url'); ?>" value="<?php echo $instance['feed_url']; ?>" class="widefat" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('number'); ?>">Quantidade de posts para exibir:</label>
				<input type="text" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo $instance['number']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('html'); ?>">Texto de introdução:</label><br />
				<textarea id="<?php echo $this->get_field_id('html'); ?>" class="simple_textarea simple_textarea_small" name="<?php echo $this->get_field_name('html'); ?>"><?php echo format_to_edit($instance['html']); ?></textarea>
			</p>
		<?php
	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['cor'] = $new_instance['cor'];
		$instance['feed_url'] = $new_instance['feed_url'];
		$instance['number'] = $new_instance['number'];
		$instance['html'] = $new_instance['html'];
		return $instance;
	}
}