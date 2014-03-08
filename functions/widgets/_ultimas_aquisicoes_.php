<?php
/**
 * LEITOR RSS
 * 'Semi-clone' do widget core
 * 
 * 
 * 
 * 
 */

register_widget('ultimas_aquisicoes');
class ultimas_aquisicoes extends WP_Widget {
	function ultimas_aquisicoes(){
		// opções do widget que será aplicado no frontend
		$widget_ops = array(
			'classname' => 'aquisicoes_box', 
			'description' => 'Últimas aquisições - escolher entre livros e revistas.',
		);
		
		// opções do controle
		$control_ops = array(
			'width' => 400,
		);
		
		// registrar o widget
		$this->WP_Widget( 'ultimas_aquisicoes', 'Últimas aquisições', $widget_ops, $control_ops );
	}
	function widget($args, $instance){
		extract($args);
		$instance = array_filter($instance);
		
		// preparar dados
		$query = array(
			'post_type' => 'aquisicao',
			'post_status' => 'publish',
			'posts_per_page' => $instance['number'],
			'aquisicao_tipo' => $instance['tipo'],
		);
		if( $instance['idioma'] != 'any' )
			$query['aquisicao_idioma'] = $instance['idioma'];
		$aquisicoes = new WP_Query();
		$aquisicoes->query($query); // posts habilitados
		
		// exibir dados
		echo $before_widget;
		if( $aquisicoes->posts ){
		?>
		<div class="sidebar_box">
			<?php if( isset($instance['titulo']) ) echo "<h2><a href='" . get_term_link( $instance['tipo'], 'aquisicao_tipo' )  . "' class='bg_color_{$instance['cor']}'>{$instance['titulo']}</a></h2>"; ?>
			<?php if( isset($instance['desc']) ) echo "<p>{$instance['desc']}</p>"; ?>
			
			<?php
			foreach( $aquisicoes->posts as $post ){
				setup_postdata($post);
				$aquisicao_resume = get_post_meta($post->ID, 'aquisicao_resume', true);
					$aquisicao_idioma = wp_get_object_terms($post->ID, 'aquisicao_idioma');
					$ver_resenha = ( $aquisicao_idioma[0]->slug == 'pt' ) ? '<a href="#resenha_' . $post->ID . '" class="ver_resenha">ver resenha</a>' : '<a href="#resenha_' . $post->ID . '" class="ver_resenha">内容紹介</a>';
					$lista_completa = ( $aquisicao_idioma[0]->slug == 'pt' ) ? 'ver lista completa' : '一覧を見る';
			?>
			<article class="aquisicao_item">
				<div class="aquisicao_resume">
					<figure>
						<?php if( has_post_thumbnail($post->ID) ){ ?>
						<a href="#resenha_<?php echo $post->ID; ?>" title="<?php the_title(); ?>" class="aquisicao_single_thumbnail ver_resenha">
							<?php
							$title_attr = the_title_attribute('echo=0');
							echo get_the_post_thumbnail( $post->ID, 'aquisicao', array('title' => $title_attr) );
							?>
						</a>
						<?php } else { ?>
						<a href="#resenha_<?php echo $post->ID; ?>" title="<?php the_title(); ?>" class="aquisicao_single_thumbnail ver_resenha">
							<img src="<?php bloginfo('template_url'); ?>/css/img/aquisicao_default.jpg" alt="<?php the_title(); ?>" />
						</a>
						<?php } ?>
					</figure>
					
					<h3><?php echo get_the_title($post->ID); ?></h3>
					<?php echo apply_filters( 'the_content', "$aquisicao_resume <br /> $ver_resenha" ); ?>
				</div>
				
				<div class="aquisicao_resenha" id="resenha_<?php echo $post->ID; ?>">
					<div class="btn"></div>
					<?php the_content(); ?>
				</div>
			</article>
			<?php } ?>
			<p class="ver_todas_aquisicoes"><a href="<?php echo get_term_link( $instance['tipo'], 'aquisicao_tipo' ); ?>"><?php echo $lista_completa; ?></a></p>
		</div>
		<?php
		}
		else{
			echo '<h2>Sem posts para exibir</h2>';
		}
		echo $after_widget;
		
	}
	function form($instance){
		// sempre limpar valores vazios
		$instance = array_filter($instance);
		//defaults
		$defaults = array(
			'titulo' => '',
			'desc' => '',
			'cor' => 'd81920',
			'tipo' => 'livro',
			'number' => 2,
			'idioma' => 'jp',
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
				<label for="<?php echo $this->get_field_id('titulo'); ?>">Título do Box(opcional):</label><br />
				<input type="text" id="<?php echo $this->get_field_id('titulo'); ?>" name="<?php echo $this->get_field_name('titulo'); ?>" value="<?php echo $instance['titulo']; ?>" class="ipt_size_full" />
			</p>
			<div>
				<span class="label">Cor do chapéu do título(opcional):</span><br />
				<?php radio_colors($color_args); ?>
			</div>
			<p>
				<label for="<?php echo $this->get_field_id('desc'); ?>">Texto de introdução(opcional):</label><br />
				<textarea id="<?php echo $this->get_field_id('desc'); ?>" class="simple_textarea simple_textarea_small" name="<?php echo $this->get_field_name('desc'); ?>"><?php echo format_to_edit($instance['desc']); ?></textarea>
			</p>
			<p>
				Exibir: <br />
				<label for="<?php echo $this->get_field_id('tipo'); ?>_simples" class="label_radio">
					<input type="radio" name="<?php echo $this->get_field_name('tipo'); ?>" <?php checked('aquisicoes-biblioteca', $instance['tipo']); ?> value="aquisicoes-biblioteca" id="<?php echo $this->get_field_id('tipo'); ?>_simples" class="ipt_radio" /> Aquisições da Biblioteca
				</label>
				<label for="<?php echo $this->get_field_id('tipo'); ?>_box" class="label_radio">
					<input type="radio" name="<?php echo $this->get_field_name('tipo'); ?>"<?php checked('revistas', $instance['tipo']); ?>  value="revistas" id="<?php echo $this->get_field_id('tipo'); ?>_box" class="ipt_radio" /> Revistas
				</label> 
			</p>
			<p>
				Conteúdo em: <br />
				<label for="<?php echo $this->get_field_id('idioma'); ?>_jp" class="label_radio">
					<input type="radio" name="<?php echo $this->get_field_name('idioma'); ?>"<?php checked('jp', $instance['idioma']); ?>  value="jp" id="<?php echo $this->get_field_id('idioma'); ?>_jp" class="ipt_radio" /> Japonês
				</label>
				<label for="<?php echo $this->get_field_id('idioma'); ?>_pt" class="label_radio">
					<input type="radio" name="<?php echo $this->get_field_name('idioma'); ?>" <?php checked('pt', $instance['idioma']); ?> value="pt" id="<?php echo $this->get_field_id('idioma'); ?>_pt" class="ipt_radio" /> Português
				</label>
				<label for="<?php echo $this->get_field_id('idioma'); ?>_any" class="label_radio">
					<input type="radio" name="<?php echo $this->get_field_name('idioma'); ?>" <?php checked('any', $instance['idioma']); ?> value="any" id="<?php echo $this->get_field_id('idioma'); ?>_any" class="ipt_radio" /> Ambos
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('number'); ?>">Quantidade:</label><br />
				<input type="text" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo $instance['number']; ?>" class="ipt_size_tiny" />
			</p>
		<?php
	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['titulo'] = 	$new_instance['titulo'];
		$instance['cor'] = 		$new_instance['cor'];
		$instance['desc'] = 	$new_instance['desc'];
		$instance['tipo'] = 	$new_instance['tipo'];
		$instance['number'] = 	$new_instance['number'];
		$instance['idioma'] = 	$new_instance['idioma'];
		return $instance;
	}
}