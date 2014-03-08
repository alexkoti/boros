<?php
/**
 * LEITOR RSS
 * 'Semi-clone' do widget core
 * 
 * 
 * 
 * 
 */

register_widget('section_agenda');
class section_agenda extends WP_Widget {
	function section_agenda(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array(
			'classname' => 'section_agenda', 
			'description' => 'Agenda da Seção',
		);
		
		// opções do controle
		$control_ops = array();
		/** MODELO
		$control_ops = array(
			'width' => 300,
			'height' => 350,
			'id_base' => 'sidebar_home'
		);/**/
		
		// registrar o widget
		$this->WP_Widget( 'section_agenda', 'Agenda da Seção', $widget_ops, $control_ops );
	}
	function widget($args, $instance){
		extract($args);
		
		// preparar dados
		$query = array(
			'post_type' => 'agenda',
			'post_status' => 'publish',
			'posts_per_page' => $instance['number'],
			'cat' => $instance['category']
		);
		$agendas = new WP_Query();
		$agendas->query($query); // posts habilitados
		
		// exibir dados
		echo $before_widget;
		echo '<div class="agenda_box">';
		if( $agendas->posts ){
		?>
			<h2><a href="<?php echo get_category_link( $instance['category'] ); ?>">Agenda <?php echo get_cat_name( $instance['category'] ); ?></a></h2>
			<?php
			foreach( $agendas->posts as $post ){
				setup_postdata($post);
				$content_subtitle = get_post_meta($post->ID, 'content_subtitle', true);
			?>
				<article class="agenda_item">
					<h3><a href="<?php echo get_permalink($post->ID); ?>"><?php echo get_the_title($post->ID); ?></a></h3>
					
					<?php if( $content_subtitle ){ ?>
					<p><?php echo $content_subtitle; ?></p>
					<?php } ?>
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
		// sempre limpar valores vazios
		$instance = array_filter($instance);
		//defaults
		$defaults = array(
			'number' => '',
			'category' => '',
		);
		// mesclar dados
		$instance = wp_parse_args( (array) $instance, $defaults );
		$cat_name = $this->get_field_name('category');
		?>
			<p>
				Exibir a agenda de: <br />
				<?php wp_dropdown_categories("class=ipt_size_full&hide_empty=false&show_option_all=Todas as Agendas&orderby=name&selected={$instance['category']}&name={$cat_name}"); ?>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('number'); ?>">Quantidade:</label><br />
				<input type="text" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo $instance['number']; ?>" class="ipt_size_tiny" />
				<br />
				Serão exibidos os últimos X itens da agenda marcada
			</p>
		<?php
	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['number'] = $new_instance['number'];
		$instance['category'] = $new_instance['category'];
		return $instance;
	}
}