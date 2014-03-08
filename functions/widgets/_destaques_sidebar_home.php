<?php
/**
 * LEITOR RSS
 * 'Semi-clone' do widget core
 * 
 * 
 * 
 * 
 */
 
register_widget('destaques_sidebar_home');
class destaques_sidebar_home extends WP_Widget {
	function destaques_sidebar_home(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array(
			'classname' => 'destaques_sidebar_home',
			'description' => 'Destaques Sidebar Home'
		);
		
		// opções do controle
		$control_ops = array();
		
		// registrar o widget
		$this->WP_Widget( 'destaques_sidebar_home', 'Destaques Sidebar Home', $widget_ops, $control_ops );
	}
	function widget($args, $instance){
		extract($args);
		$number = get_option('home_destaques_sidebar_number');
		$selected_posts = json_decode( get_option('home_destaques_sidebar'), true );
		$selected_posts_order = array_keys($selected_posts);
		
		if( !$number )
			$number = 2;
		
		echo $before_widget;
		$args = array(
			'post_type' => 'any',
			'post_status' => 'publish',
			'posts_per_page' => $number,
			'post__in' => $selected_posts_order,
		);
		$destaques_home_sidebar = new WP_Query();
		$destaques_home_sidebar->query($args);	// posts habilitados
		
		$ordered_destaques = array();
		foreach( $selected_posts_order as $order ){
			foreach( $destaques_home_sidebar->posts as $destaque ){
				if( $destaque->ID == $order ){
					$ordered_destaques[] = $destaque;
				}
			}
		}
		
		if($ordered_destaques){
			$i = 1;
			foreach($ordered_destaques as $post){
				setup_postdata($post);
				$feature_resume = get_post_meta($post->ID, 'content_resume', true);
		?>
		<article id="featured_sidebar_<?php echo $i; ?>">
			<h2>
				<a href="<?php echo get_permalink($post->ID); ?>" class="bg_color_<?php echo $selected_posts[$post->ID];?>"><?php echo get_the_title($post->ID); ?></a>
			</h2>
			<div class="featured_content">
				<?php
				$feature_resume = get_post_meta($post->ID, 'content_resume', true);
				if( $feature_resume ){
				?>
				<div class="content_resume">
					<?php echo apply_filters('the_content', get_post_meta($post->ID, 'content_resume', true)); ?>
				</div>
				<?php } ?>
			</div>
		</article>
		<?php
			$i++;
			}
		}
		echo $after_widget;
	}
}