<?php
/**
 * SIDEBAR POSTS
 * 
 * 
 * 
 * 
 */

register_widget('infograficos');
class infograficos extends WP_Widget {
	function infograficos(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array(
			'classname' => 'infograficos',
			'description' => 'Infográficos'
		);
		
		// opções do controle
		$control_ops = array();
		
		// registrar o widget
		$this->WP_Widget( 'infograficos', 'Infográficos', $widget_ops, $control_ops );
	}
	function widget($args, $instance){
		global $wp_query;
		extract($args);
		
		// remover filtros de pre get
		remove_filter( 'pre_get_posts', 'filter_pre_get_posts' );
		
		if( is_tax( 'editoria' ) ){
			echo $before_widget;
			$term_id = $wp_query->get_queried_object_id();
			infograficos_box( $term_id );
			echo $after_widget;
		}
		elseif( !is_front_page() ){
			echo $before_widget;
			infograficos_box( 'home' );
			echo $after_widget;
		}
		
		// verificar listagens de post_types: posts, especiais, infograficos
		// 'posts' >>> está rodando em cima da page com nome 'posts'
		if( is_home() and get_query_var('paged') ){
			echo $before_widget;
			infograficos_box( 'home' );
			echo $after_widget;
		}
		
		// reaplicar filtros de pre get
		add_filter( 'pre_get_posts', 'filter_pre_get_posts' );
	}
}