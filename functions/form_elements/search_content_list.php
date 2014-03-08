<?php


/**
 * @TODO adicionar options para
 quantidade de palavras no excerpt
 
 revisar html/css
 * 
 * @todo @bug adicionar dois sortables numa mesma admin_page gera erro no segundo bloco. <<< ??? ainda existe o bug?
 * @todo possibilitar escolher apenas um post ou uma quantidade determinada
 * @todo permitir layout em grid
 */

class BFE_search_content_list extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => 'search_content_ids',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
	);
	
	var $enqueues = array(
		'js' => 'search_content_list',
		'css' => 'search_content_list',
	);
	
	function add_defaults(){
		$options = array(
			'show_thumbnails' => true,
			'show_excerpt' => true,
			'excerpt_length' => 55,
		);
		$this->defaults['options'] = $options;
	}
	
	function set_label(){
		if( !empty($this->data['label']) )
			$this->label = "<span class='non_click_label'>{$this->data['label']}{$this->label_helper}</span>";
	}
	
	function set_input( $value = null ){
		if( !isset($this->data['options']['query_search']) or !isset($this->data['options']['query_selecteds']) ){
			return '<div class="form_element_error">Não foram definidas as querys de busca e conteúdos selecionados: <br />
			<code>data[options][query_search]</code> e <code>data[options][query_selecteds]</code></div>';
		}
		
		
		// começar a guardar o output do script js em buffer
		ob_start();
		
		$attrs = make_attributes($this->data['attr']);
		?>
		<div class="search_content_box">
			<input type="hidden" value="<?php echo $value; ?>" <?php echo $attrs; ?> />
			
			<div class="search_content_list search_content_list_selected">
			<?php if( empty( $this->data_value ) ){ ?>
				<p class="no_results_h">Sem conteúdos selecionados</p>
				<ul class="related_item_list"></ul>
			<?php } else { ?>
				<p class="results_h">Contéudos selecionados:</p>
				<ul class="related_item_list">
					<?php
					//pre($data_value, '$data_value');
					$related_item = explode( ',', $this->data_value ); // gera o $related_item
					$related_item = array_unique($related_item);
					//pre($related_item, 'related_item');
					
					$query_related = $this->data['options']['query_selecteds'];
					$query_related['post__in'] = $related_item;
					$query_related['posts_per_page'] = count($related_item);
					//pre($query_related, 'query_related');
					
					$relateds = new WP_Query();
					$relateds->query($query_related);
					//pre( $relateds->posts );
					
					if( $relateds->posts ){
						$ordered_contents = sort_wp_objects( $relateds->posts, $related_item, 'ID' );
						$index = 1;
						foreach( $ordered_contents as $post ){
							setup_postdata( $post );
							related_content_output( $post, $this->data['options'], $index );
							$index++;
						}
					}
					wp_reset_query();
					/**
					// deixar na ordem desejada
					$ordered_contents = array();
					foreach( $related_item as $item ){
						foreach( $relateds->posts as $related ){
							if( $related->ID == $item ){
								$ordered_contents[] = $related;
							}
						}
					}
					
					$t = sort_wp_objects( $relateds->posts, $related_item, 'ID' );
					
					foreach( $ordered_contents as $post ){
						setup_postdata( $post );
						related_content_output( $post, $this->data['options'] );
					}
					wp_reset_query();
					/**/
					?>
				</ul>
			<?php } ?>
			</div>
			
			<hr />
			
			<?php
			// Search config
			$query_search = $this->data['options']['query_search'];
			$query_search['suppress_filters'] = true;
			$json_query_search = json_encode($query_search);
			?>
			<div class="search_content_inputs">
				<p class="results_h">Buscar:</p>
				<p>
					<input type='hidden' name='search_content_query' value='<?php echo $json_query_search; ?>' />
					<input type='hidden' name='show_post_type' value='<?php echo $this->data['options']['show_post_type']; ?>' />
					<input type='hidden' name='show_thumbnails' value='<?php echo $this->data['options']['show_thumbnails']; ?>' />
					<input type='hidden' name='show_excerpt' value='<?php echo $this->data['options']['show_excerpt']; ?>' />
					<input type='hidden' name='excerpt_length' value='<?php echo $this->data['options']['excerpt_length']; ?>' />
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
		return $input;
	}
}



/**
 * Recuperar posts selecionados, a partir de variável. Aceita string separada vírgulas ou array
 * 
 */
function get_search_content_list( $selecteds, $query = array('post_type' => 'post') ){
	if( !is_array($selecteds) ){
		$selected_itens = explode( ',', $selecteds );
		$selected_itens = array_unique($selected_itens);
	}
	$query['post__in'] = $selected_itens;
	$query['posts_per_page'] = count($selected_itens);
	$relateds = new WP_Query();
	$relateds->query($query);
	wp_reset_query();
	return sort_wp_objects( $relateds->posts, $selected_itens, 'ID' );
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
	
	$search_result = new WP_Query();
	$search_result->query($query);
	// Check if any posts were found.
	if ( !$search_result->post_count ){
		echo '<p class="results_h">Sem resultados. <strong>Atenção: os resultados excluem conteúdos já adicionados.</strong></p>';
		die();
	}
	if( $search_result->posts ){
		echo '<p class="results_h">Resultados:</p>';
		echo '<ul>';
		$index = 1;
		foreach($search_result->posts as $post){
			setup_postdata($post);
			$args = array(
				'show_post_type' => $_POST['show_post_type'], 
				'show_thumbnails' => $_POST['show_thumbnails'], 
				'show_excerpt' => $_POST['show_excerpt'], 
				'excerpt_length' => $_POST['excerpt_length']
			);
			related_content_output( $post, $args );
			$index++;
		}
		echo '</ul>';
	}
	wp_reset_query();
	die();
}



function related_content_output( $post, $options = array(), $index = '' ){
	// Show thumbnails
	$show_thumbnails = ( isset($options['show_thumbnails']) and $options['show_thumbnails'] == true ) ? true : false;
	$show_excerpt = ( isset($options['show_excerpt']) and $options['show_excerpt'] == true ) ? true : false;
	$excerpt_length = ( isset($options['excerpt_length']) ) ? $options['excerpt_length'] : 30;
	$post_type_name = '';
	if( isset($options['show_post_type']) and $options['show_post_type'] == true ){
		$pt = get_post_type_object($post->post_type);
		$post_type_name = "<em>({$pt->labels->singular_name})</em> :";
	}
	?>
	<li id="related_item_<?php echo $post->ID; ?>" class="related_item">
		<div class="related_content">
			<div class="result_head">
				<strong><?php echo "{$post_type_name} {$post->post_title} <small>[post_id: {$post->ID}]</small>"; ?></strong>
				<input type="button" value="remover este post" class="button result_deselect" />
				<input type="button" value="selecionar este post" class="button-primary result_select" />
			</div>
			
			<?php
			echo "<div class='related_index'>{$index}</div>";
			
			if( $show_thumbnails == true ){
				$_thumbnail_id = get_post_meta($post->ID, '_thumbnail_id', true);
				if( !empty($_thumbnail_id) ){
					$thumb = wp_get_attachment_image_src($_thumbnail_id, array(100,100));
					echo '<div class="result_thumbnail">';
					echo "<img src='{$thumb[0]}' alt='' width='100' />";
					echo '</div>';
				}
			}
			
			if( $show_excerpt == true ){
				echo '<div class="result_excerpt">';
				if( strlen($post->post_excerpt) > 1 ){
					$text = apply_filters('the_content', $post->post_excerpt);
					echo wp_trim_words( $text, $excerpt_length );
				}
				else{
					$text = $post->post_content;
					/**
					$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
					if ( count($words) > $excerpt_length ) {
						array_pop($words);
						$text = implode(' ', $words);
					} else {
						$text = implode(' ', $words);
					}
					echo apply_filters('the_content', $text);
					/**/
					$text = strip_shortcodes( $post->post_content );
					$text = apply_filters('the_content', $text);
					$text = str_replace(']]>', ']]&gt;', $text);
					echo wp_trim_words( $text, $excerpt_length );
				}
				echo '</div>';
			}
			?>
		</div>
	</li>
	<?php
}