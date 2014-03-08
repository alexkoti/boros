<?php
/**
 * LEITOR RSS
 * 'Semi-clone' do widget core
 * 
 * 
 * 
 * 
 */

register_widget('artigos_lista');
class artigos_lista extends WP_Widget {
	function artigos_lista(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array( 'classname' => 'artigos_lista', 'description' => 'Lista de artigos' );
		
		// opções do controle
		$control_ops = array();
		
		// registrar o widget
		$this->WP_Widget( 'artigos_lista', 'Lista de artigos', $widget_ops, $control_ops );
	}
	function widget($args, $instance){
		extract($args);
		$instance = array_filter($instance);
		
		// preparar dados
		$query = array(
			'post_type' => 'artigo',
			'post_status' => 'publish',
			'posts_per_page' => $instance['number'],
		);
		$artigos = new WP_Query();
		$artigos->query($query); // posts habilitados
		
		
		// exibir dados
		echo $before_widget;
		echo '<div class="agenda_box">';
		if( $artigos->posts ){
		?>
			<h2><a href="<?php echo get_the_post_type_permalink( 'artigo' ); ?>">Artigos</a></h2>
			<?php
			foreach( $artigos->posts as $post ){
				setup_postdata($post);
			?>
				<article class="agenda_item">
					<h3><a href="<?php echo get_permalink($post->ID); ?>"><?php echo get_the_title($post->ID); ?></a></h3>
					
					<?php
					$resume = get_post_meta($post->ID, 'artigo_resume', true);
					if( $resume ){
						echo $resume;
					}
					?>
				</article>
			<?php 
			}
		}
		else{
			echo '<h2>Sem posts para exibir</h2>';
		}
		echo '</div>';
		echo $after_widget;
	}
	function form($instance){
		// sempre limpar os valores vazios
		$instance = array_filter($instance);
		// defaults
		$defaults = array(
			'number' => '5',
		);
		// mesclar dados
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
			<p>
				<label for="<?php echo $this->get_field_id('number'); ?>">Quantidade:</label>
				<input type="text" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo $instance['number']; ?>" />
			</p>
		<?php
	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['number'] = 	$new_instance['number'];
		return $instance;
	}
}