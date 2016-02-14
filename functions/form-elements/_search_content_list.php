<?php
/**
 * FORM ELEMENT: RELATED_HISTORIES
 * 
 * 
 * 
 */

function form_element_search_content_list( $data, $data_value, $parent ){
	global $post;
	
	// mostrar thumbnails?
	$show_thumbnails = ( isset($data['options']['show_thumbnails']) and $data['options']['show_thumbnails'] == true ) ? 1 : 0;
	$limit = ( isset($data['options']['limit']) and $data['options']['limit'] == true ) ? 1 : 0;
	
	// começar a guardar o output do script js em buffer
	ob_start();
	
	$selected_list_class = ( $disabled == true ) ? 'search_content_list search_content_list_selected disabled' : 'search_content_list search_content_list_selected';
	$insert_type = ( $limit == true ) ? 'replace_itens' : 'add_itens' ;
	?>
	<div class="search_content_box <?php echo $insert_type; ?>">
		<input type="hidden" class="search_content_ids ipt_size_large" name="<?php echo $data['name']; ?>" value="<?php echo $data_value; ?>" />
		
		<div class="search_content_list search_content_list_selected">
		<?php if( empty( $data_value ) ){ ?>
			<p class="no_results_h">Sem conteúdos selecionados</p>
			<ul class="related_item_list"></ul>
		<?php } else { ?>
			<p class="results_h">Contéudos selecionados:</p>
			<ul class="related_item_list">
				<?php
				//pre($data_value, '$data_value');
				$related_item = explode( ',', $data_value ); // gera o $related_item
				$related_item = array_unique($related_item);
				//pre($related_item, 'related_item');
				
				$query_related = $data['options']['query_selected'];
				$query_related['post__in'] = $related_item;
				//pre($query_related, 'query_related');
				
				$temp = $post; // armazenar $post original
				$relateds = new WP_Query();
				$relateds->query($query_related);
				//pre( $relateds->posts );
				
				// deixar na ordem desejada
				$ordered_contents = array();
				foreach( $related_item as $item ){
					foreach( $relateds->posts as $related ){
						if( $related->ID == $item ){
							$ordered_contents[] = $related;
						}
					}
				}
				
				foreach( $ordered_contents as $post ){
					setup_postdata( $post );
					related_content_output( $post, array( 'show_thumbnails' => $show_thumbnails ) );
				}
				$post = $temp; // recuperar $post original
				?>
			</ul>
		<?php } ?>
		</div>
		
		<hr />
		
		<?php
		// Search config
		$query_search = $data['options']['query_search'];
		$query_search['suppress_filters'] = true;
		$json_query_search = json_encode($query_search);
		?>
		<div class="search_content_inputs">
			<p class="results_h">Buscar:</p>
			<p>
				<input type='hidden' name='search_content_query' value='<?php echo $json_query_search; ?>' />
				<input type='hidden' name='show_thumbnails' value='<?php echo $show_thumbnails; ?>' />
				<input type="text" name="search_content_text" class="ipt_text" />
				<span class="search_content_clear"></span>
				<span class="button search_content_submit">buscar</span>
				<img class="waiting" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" />
			</p>
		</div>
		
		<div class="search_content_list search_content_list_results"></div>
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
					<p class="form_ipt_text"><label for="<?php echo $data['name']; ?>"><?php echo $data['label']; ?></label></p>
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
add_action('wp_ajax_search_content', 'search_content');
function search_content(){
		
	/* Primeiro remover os slashes que o wordpress adicona por padrão em todos os dados
	 * Não esquecer de setar o segundo argumento $assoc como true, para que retorne um array no lugar de object
	 */
	$query = json_decode( stripslashes($_POST['query']), true );
	
	if ( isset( $_POST['search_text'] ) )
		$query['s'] = stripslashes( $_POST['search_text'] );
	
	if ( isset( $_POST['remove'] ) ){
		$query['post__not_in'] = array_map( 'absint', explode(',', $_POST['remove']) );
	}
	
	// Do main query.
	$temp = $post; // armazenar $post original
	$get_posts = new WP_Query;
	$posts = $get_posts->query( $query );
	// Check if any posts were found.
	if ( !$get_posts->post_count ){
		echo '<p class="results_h">Sem resultados. <strong>Atenção: os resultados excluem conteúdos já adicionados.</strong></p>';
		die();
	}

	// Build results.
	
	$results = array();
	echo '<p class="results_h">Resultados:</p>';
	echo '<ul>';
	foreach ( $posts as $post ){
		setup_postdata( $post );
		related_content_output( $post, array('show_thumbnails' => $_POST['show_thumbnails']) );
	}
	echo '</ul>';
	$post = $temp; // recuperar $post original
	
	die();
}



function related_content_output( $post, $options = array() ){
	
	// Show thumbnails
	$show_thumbnails = ( isset($options['show_thumbnails']) and $options['show_thumbnails'] == true ) ? true : false;
	
	?>
	<li id="related_item_<?php echo $post->ID; ?>" class="related_item">
		<div class="related_content">
			<p class='result_title'><strong><?php echo "{$post->post_title} [{$post->ID}]"; ?></strong></p>
			<div class="result_excerpt">
				<div class="result_thumbnail"><?php if( $show_thumbnails == true ) echo get_the_post_thumbnail($post->ID, array(100,100) ); ?></div>
				<?php the_excerpt(); ?>
			</div>
		</div>
		<p class="result_actions">
			<input type="button" value="remover este post" class="button result_deselect" />
			<input type="button" value="selecionar este post" class="button-primary result_select" />
		</p>
	</li>
	<?php
}