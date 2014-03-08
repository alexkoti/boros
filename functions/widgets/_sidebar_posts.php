<?php
/**
 * SIDEBAR POSTS
 * 
 * 
 * 
 * 
 */

register_widget('sidebar_posts');
class sidebar_posts extends WP_Widget {
	function sidebar_posts(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array(
			'classname' => 'sidebar_posts',
			'description' => 'Sidebar Mais Acessados'
		);
		
		// opções do controle
		$control_ops = array();
		
		// registrar o widget
		$this->WP_Widget( 'sidebar_posts', 'Sidebar Mais Acessados', $widget_ops, $control_ops );
	}
	function widget($args, $instance){
		global $post;
		extract($args);
		
		// remover filtros de pre get
		remove_filter( 'pre_get_posts', 'filter_pre_get_posts' );
		
		echo $before_widget;
		echo '<div>';
		echo '<h2 class="chapeu">Mais acessados</h2>';
			$query = array(
				'post_type' => array('post', 'separador'),
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'meta_key' => 'featured_post_sidebar',
			);
			$sidebar_posts = new WP_Query();
			$sidebar_posts->query($query);	// posts habilitados
			
			$destaques_order = get_option('sidebar_posts');
			$ordered_destaques = array();
			$destaques_itens = explode(',', $destaques_order);
			//pre($destaques_itens);
			
			foreach( $destaques_itens as $destaque ){
				foreach( $sidebar_posts->posts as $post ){
					if( $post->ID == $destaque ){
						$ordered_destaques[] = $post;
					}
				}
			}
			
			//pre($ordered_destaques);
			if( $ordered_destaques ){
				$i = 1;
				foreach($ordered_destaques as $post){
					setup_postdata($post);
					
					if( $post->post_type == 'separador' ){
						$editoria = get_term( get_post_meta($post->ID, 'separador_link', true), 'editoria' );
						if( is_wp_error($editoria) )
							$editoria = $post->post_name;
						
						$editoria_link = get_term_link( $editoria, 'editoria' );
						if( is_wp_error( $editoria_link ) ){
							echo "<span class='chapeu_2 error'>Ocorreu um erro na exibição desse separador. Verifique se está relacionado a alguma editoria.</span><ul class='lista_post_ultimas'>\n";
						}
						else{
							if( $i == 1 )
								echo "<a class='chapeu_2' href='{$editoria_link}'>{$post->post_title}</a><ul class='lista_post_ultimas'>\n";
							else
								echo "</ul>\n<a class='chapeu_2' href='{$editoria_link}'>{$post->post_title}</a>\n<ul class='lista_post_ultimas'>\n";
						}
					}
					else{
						$link = get_permalink( $post->ID );
						$date = get_the_time('d\/m\/Y');
						echo "<li>
							<a class='data' href='{$link}'>{$date}</a>
							<h4><a href='{$link}'>{$post->post_title}</a></h4>
							<p>&rarr; <a href='{$link}'>ler mais</a></p>
						</li>";
					}
					$i++;
				}
				echo "</ul>";
			}
		echo '</div>';
		echo $after_widget;
		
		// reaplicar filtros de pre get
		add_filter( 'pre_get_posts', 'filter_pre_get_posts' );
	}
}