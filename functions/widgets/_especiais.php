<?php
/**
 * SIDEBAR POSTS
 * 
 * 
 * 
 * 
 */

register_widget('especiais');
class especiais extends WP_Widget {
	function especiais(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array(
			'classname' => 'especiais',
			'description' => 'Especiais'
		);
		
		// opções do controle
		$control_ops = array();
		
		// registrar o widget
		$this->WP_Widget( 'especiais', 'Especiais', $widget_ops, $control_ops );
	}
	function widget($args, $instance){
		global $wp_query;
		extract($args);
		$active = false;
		
		// remover filtros de pre get
		remove_filter( 'pre_get_posts', 'filter_pre_get_posts' );
		
		if( is_tax( 'editoria' ) and $active == false ){
			echo $before_widget;
			$term_id = $wp_query->get_queried_object_id();
			//pre($term_id);
			boros_slider( "especiais_{$term_id}", 'single', false, $term_id );
			echo $after_widget;
			$active = true;
		}
		elseif( !is_front_page() ){
			echo $before_widget;
			boros_slider( 'especiais_home', 'single', false, 'home' );
			echo $after_widget;
			$active = true;
		}
		
		// verificar listagens de posts
		// 'posts' >>> está rodando em cima da page com nome 'posts'
		if( is_home() and get_query_var('paged') and $active == false ){
			echo $before_widget;
			boros_slider( 'especiais_home', 'single', false, 'home' );
			echo $after_widget;
			$active = true;
		}
		
		// verificar listagens de posts_types: especiais, infograficos
		if( is_post_type_archive( array('especial', 'infografico') ) and $active == false ){
			echo $before_widget;
			boros_slider( 'especiais_home', 'single', false, 'home' );
			echo $after_widget;
			$active = true;
		}
		
		// reaplicar filtros de pre get
		add_filter( 'pre_get_posts', 'filter_pre_get_posts' );
	}
}