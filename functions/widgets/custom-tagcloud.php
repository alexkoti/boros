<?php
/**
 * LEITOR RSS
 * 'Semi-clone' do widget core
 * 
 * 
 * 
 * 
 */
 
register_widget('custom_tagcloud');
class custom_tagcloud extends WP_Widget {
	function custom_tagcloud(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array( 'classname' => 'widget_tagcloud', 'description' => 'Custom TagCloud, com mais opções de exibição.' );
		
		// opções do controle, apenas widht é oferecido por enquanto. Usar caso seja maior que 250px(padrão)
		$control_ops = array('width' => 350);
		
		// registrar o widget
		$this->WP_Widget( 'custom_tagcloud', 'Custom TagCloud', $widget_ops, $control_ops );
	}
	function widget($args, $instance){
		extract($args);
		
		echo $before_widget;
		if ( $instance['title'] )
			echo $before_title . $instance['title'] . $after_title;
		$defaults = array(
			'smallest'	=> 8, 
			'largest'	=> 22,
			'unit'		=> 'px', 
			'number'	=> 0, 
			'format'	=> 'flat',
			'separator'	=> "\n",
			'orderby'	=> 'count', 
			'order'		=> 'ASC',
			'taxonomy'	=> 'post_tag', 
			'echo'		=> true 
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		echo "<p class='tag_cloud {$instance['taxonomy']}_tag_cloud'>";
		wp_tag_cloud( $instance );
		echo '</p>';
		echo $after_widget;
	}
	function form($instance){
		// sempre limpar os valores vazios
		$instance = array_filter($instance);
		// defaults
		$defaults = array(
			'title'		=> 'Nuvem de Tags', 
			'smallest'	=> 8, 
			'largest'	=> 22, 
			'unit'		=> 'px', 
			'number'	=> 0, 
			'format'	=> 'flat',
			'separator'	=> "\n",
			'orderby'	=> 'count', 
			'order'		=> 'ASC',
			'taxonomy'	=> 'post_tag', 
		);
		// mesclar dados
		$instance = wp_parse_args( (array) $instance, $defaults );
		//pre($instance);
		?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>">Titulo:</label>
				<input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('taxonomy'); ?>">Taxonomia:</label>
				<select id="<?php echo $this->get_field_id('taxonomy'); ?>" name="<?php echo $this->get_field_name('taxonomy'); ?>">
					<?php
					$all_taxonomies = get_taxonomies();
					$excludes = array('nav_menu', 'link_category', 'post_format');
					$taxonomies = array_diff( $all_taxonomies, $excludes );
					foreach( $taxonomies as  $taxonomy ){
						$tax = get_taxonomy($taxonomy);
						$selected = selected( $instance['taxonomy'], $tax->name, false );
						echo "<option value='{$tax->name}'{$selected}>{$tax->labels->singular_name}</option>";
					}
					?>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('smallest'); ?>">Tamanho da fonte:</label> 
				<label for="<?php echo $this->get_field_id('smallest'); ?>">Mínimo:</label>
				<input type="text" id="<?php echo $this->get_field_id('smallest'); ?>" name="<?php echo $this->get_field_name('smallest'); ?>" value="<?php echo $instance['smallest']; ?>" class="ipt_size_30" />
				<label for="<?php echo $this->get_field_id('largest'); ?>">Máximo:</label>
				<input type="text" id="<?php echo $this->get_field_id('largest'); ?>" name="<?php echo $this->get_field_name('largest'); ?>" value="<?php echo $instance['largest']; ?>" class="ipt_size_30" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('number'); ?>">Quantidade de tags para exibir:</label>
				<input type="text" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo $instance['number']; ?>" class="ipt_size_30" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('orderby'); ?>">Ordernar por:</label>
				<select id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>">
					<option value="count"<?php selected( 'count', $instance['orderby'] ); ?>>Contagem</option>
					<option value="name"<?php selected( 'name', $instance['orderby'] ); ?>>Nome</option>
				</select>, 
				<label for="<?php echo $this->get_field_id('order'); ?>">em ordem:</label>
				<select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
					<option value="ASC"<?php selected( 'ASC', $instance['order'] ); ?>>Crescente</option>
					<option value="DESC"<?php selected( 'DESC', $instance['order'] ); ?>>Decrescente</option>
				</select>
			</p>
		<?php
	}
	function update($new_instance, $old_instance) {
		$instance = array_merge($old_instance, $new_instance);
		return $instance;
	}
}