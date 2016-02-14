<?php
/**
 * FORM ELEMENT: RELATED_HISTORIES
 * 
 * 
 * 
 */

function form_element_related_histories( $data, $data_value, $parent ){
	global $post;
	
	// começar a guardar o output do script js em buffer
	ob_start();
	?>
	<div class="excs_related_histories_box">
		<input type="hidden" id="excs_related_histories_list" class="ids_list ipt_size_large" name="<?php echo $data['name']; ?>" id="<?php echo $data['name']; ?>_values" value="<?php echo $data_value; ?>" />
		<!-- <p><span class="btn_excs btn_excs_add_history"><span class="btn_fam btn_add"></span> adicionar história</span></p> -->
		
		<div class="excs_related_histories_list excs_related_histories_selected">
		<?php if( empty( $data_value ) ){ ?>
			<p class="no_results_h">Sem histórias selecionadas</p>
			<ul class="related_item_list"></ul>
		<?php } else { ?>
			<p class="results_h">Histórias selecionadas:</p>
			<ul class="related_item_list">
				<?php
				$related_item = explode( ',', $data_value ); // gera o $related_item
				$related_item = array_unique($related_item);
				//pre($related_item);
				$args = array(
					'post_type' => 'historia',
					'post_status' => 'publish',
					'post__in' => $related_item,
				);
				$historias = new WP_Query();
				$historias->query($args);
				//pre( $historias->posts );
				
				// deixar na ordem desejada
				$ordered_historias = array();
				foreach( $related_item as $item ){
					foreach( $historias->posts as $historia ){
						if( $historia->ID == $item ){
							$ordered_historias[] = $historia;
						}
					}
				}
				
				foreach( $ordered_historias as $historia ){
					related_history_output( $historia, $post->ID );
				}
				?>
			</ul>
		<?php } ?>
		</div>
		
		<hr />
		
		<div class="excs_search_inputs">
			<p class="results_h">Buscar pelo título:</p>
			<p>
				<input type="text" name="excs_search_text" class="ipt_text" />
				<span class="excs_clear_search"></span>
				<span class="button excs_search_submit">buscar</span>
				<img class="waiting" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" />
				
				<span id="add_new_history_control">
					ou
					<?php
					/**
					 * Este link será dinamicamente modificado pelo thickbox.js e pelo admin_footer_scripts.js, que irá adicionar/trocar a variável 'serie', conforme o 
					 * checkbox correspondente.
					 */
					$url = 'edit.php';
					$url_args = array(
						'post_type' => 'historia',
						'page' => 'add_single_history',
						'post_id' => '0',
						'edit_from' => $post->ID,
						'serie' => '0',
						'inline' => '1',
					);
					// existe uma série, buscar dados da série
					$serie = wp_get_post_terms( $post->ID, 'serie' );
					if( count($serie) > 0 ){
						$url_args['serie'] = $serie[0]->term_id;
					}
					// SEMPRE ADICIONAR TB_iframe por último
					$url_args['TB_iframe'] = '1';
					$url_args['width'] = '750';
					$new_history_link = add_query_arg( $url_args, $url );
					//$new_history_link = "edit.php?post_type=historia&page=add_single_history&post_id=0&edit_from={$post->ID}&inline=1&TB_iframe=1";
					?>
					<a href="<?php echo $new_history_link; ?>" class="button-primary thickbox add_single_history_button" target="_blank">adicionar nova história</a>
				</span>
			</p>
		</div>
		
		<div class="excs_related_histories_list excs_search_results"></div>
	</div>
	<?php
	// guardar o output em variável
	$input = ob_get_contents();
	ob_end_clean();
	
	// verificar o tipo de layout
	if( !isset($data['layout']) )
		$data['layout'] = 'table';
	
	// exibir conforme o layout
	switch( $data['layout'] ){
		case 'block':
			?>
			<tr>
				<td class="boros_form_element boros_element_text" colspan="2">
					<?php echo $input; ?>
				</td>
			</tr>
			<?php
			break;
		
		
		case 'table':
		default:
			?>
			<tr>
				<th><label for="<?php echo $data['name']; ?>"><?php echo $data['label']; ?></label></th>
				<td><?php echo $input; ?></td>
			</tr>
			<?php
			break;
	}
}



/* ========================================================================== */
/* AJAX FUNCTIONS =========================================================== */
/* ========================================================================== */
add_action('wp_ajax_excs_search_history', 'excs_search_history');
function excs_search_history(){
	$args = array();
	if ( isset( $_POST['search_text'] ) )
		$args['s'] = stripslashes( $_POST['search_text'] );
		
	$query = array(
		'post_type' => 'historia',
		'suppress_filters' => true,
		'post_status' => 'publish',
		'order' => 'ASC',
		'orderby' => 'post_title',
		'posts_per_page' => -1,
	);
	
	if ( isset( $args['s'] ) )
		$query['s'] = $args['s'];
	
	if ( isset( $_POST['remove'] ) ){
		$query['post__not_in'] = array_map( 'absint', explode(',', $_POST['remove']) );
	}
	
	// Do main query.
	$get_posts = new WP_Query;
	$posts = $get_posts->query( $query );
	// Check if any posts were found.
	if ( !$get_posts->post_count ){
		echo '<p class="results_h">Sem resultados. <strong>Atenção: os resultados excluem as histórias já adicionadas.</strong></p>';
		die();
	}

	// Build results.
	
	$results = array();
	echo '<p class="results_h">Resultados:</p>';
	echo '<ul>';
	foreach ( $posts as $post ) {
		related_history_output( $post );
	}
	echo '</ul>';
	
	die();
}



function related_history_output( $post, $edit_from = 0 ){
	?>
	<li id="related_item_<?php echo $post->ID; ?>" class="related_item">
		<?php
		
		// este link será dinamicamente modificado pelo thickbox.js
		$url = 'edit.php';
		$url_args = array(
			'post_type' => 'historia',
			'page' => 'add_single_history',
			'post_id' => $post->ID,
			'edit_from' => $edit_from,
			'serie' => '0',
			'inline' => '1',
		);
		// existe uma série, buscar dados da série
		$serie = wp_get_post_terms( $edit_from, 'serie' );
		if( count($serie) > 0 ){
			$url_args['serie'] = $serie[0]->term_id;
		}
		// SEMPRE ADICIONAR TB_iframe por último
		$url_args['TB_iframe'] = '1';
		$url_args['width'] = '750';
		$new_history_link = add_query_arg( $url_args, $url );
		?>
		<p class='result_title'><strong><?php echo "{$post->post_title} [{$post->ID}]"; ?></strong> <a href="<?php echo $new_history_link; ?>" class="thickbox add_single_history_button">editar história</a></p>
		<div class="related_history">
			<table>
				<?php
				echo flat_terms( $post->ID, 'personagem', '<tr><th class="result_escritor">Personagem(s): </th><td>', ', ', '</td></tr>' );
				echo flat_terms( $post->ID, 'escritor', '<tr><th class="result_escritor">Escritor(es): </th><td>', ', ', '</td></tr>' );
				echo flat_terms( $post->ID, 'desenhista', '<tr><th class="result_desenhista">Desenhista(s): </th><td>', ', ', '</td></tr>' );
				echo flat_terms( $post->ID, 'arte-finalista', '<tr><th class="result_arte_finalista">Arte-Finalista(s): </th><td>', ', ', '</td></tr>' );
				echo flat_terms( $post->ID, 'colorista', '<tr><th class="result_colorista">Colorista(s): </th><td>', ', ', '</td></tr>' );
				echo flat_terms( $post->ID, 'arco-saga', '<tr><th class="result_arco_saga">Arco ou Saga: </th><td>', ', ', '</td></tr>' );
				echo flat_terms( $post->ID, 'genero', '<tr><th class="result_genero">Gênero: </th><td>', ', ', '</td></tr>' );
				?>
			</table>
			<div class="related_products">
				<?php
				$args = array(
					'post_type' => 'produto',
					'post_status' => 'publish',
					'meta_key' => 'related_history',
					'meta_value' => $post->ID,
					'meta_compare' => '=',
					'post__not_in' => array($edit_from),
				);
				$products = new WP_Query();
				$products->query($args);
				if( $products->posts ){
					echo 'Outras revistas com esta história:<ul>';
					foreach( $products->posts as $post ){
						?>
						<li>
							<?php echo edit_post_link( $post->post_title, '', '', $post->ID ); ?>
						</li>
						<?php
					}
					echo '</ul>';
				}
				?>
			</div>
		</div>
		<p class="result_actions">
			<input type="button" value="remover esta história" class="button result_deselect" />
			<input type="button" value="selecionar esta história" class="button-primary result_select" />
		</p>
	</li>
	<?php
}