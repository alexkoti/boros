<?php
/**
 * LEITOR RSS
 * 'Semi-clone' do widget core
 * 
 * 
 * 
 * 
 */

register_widget('box_calendario');
class box_calendario extends WP_Widget {
	function box_calendario(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array( 'classname' => 'calendario_box', 'description' => 'Box Calendário' );
		
		// opções do controle
		$control_ops = array();
		
		// registrar o widget
		$this->WP_Widget( 'box_calendario', 'Box Calendário', $widget_ops, $control_ops );
	}
	function widget($args, $instance){
		extract($args);
		$instance = array_filter($instance);
		
		echo $before_widget;
		
		if ( isset($instance['title']) ) echo "<h2>{$instance['title']}</h2>";
		
		$calendarios = array();
		for( $i = 1; $i <= 6; $i++ ){
			$calendario_img = get_option('calendario_' . $i);
			if($calendario_img)
				$calendarios[] = $calendario_img;
		}
		if( $calendarios ){
		?>
			<div class="boros_slider calendario_slider">
				<div class="boros_slider_holder">
					<ul class="boros_slider_strip">
						<?php foreach($calendarios as $counter => $img){ ?>
						<li class="slide">
							<div class="slide_inner">
								<img src="<?php echo $img; ?>" alt="<?php echo $counter; ?>" />
							</div>
						</li>
						<?php } ?>
					</ul>
				</div>
				<?php if( count($calendarios) > 1 ) { ?>
				<div class="boros_slider_nav">
					<a rel="prev" class="btn_nav btn_prev btn_prev_next" title="anterior">&larr; </a> 
					<a rel="next" class="btn_nav btn_next btn_prev_next" title="próximo">&rarr; </a>
				</div>
				<?php } ?>
			</div><!-- .slider -->
		<?php
		} 
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
		<?php
	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		return $instance;
	}
}