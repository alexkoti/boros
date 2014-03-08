<?php
/**
 * FORM ELEMENT: 
 * 
 * 
 * 
 */

/**
 * Para este elemento, $data_value poderá ser o valor de term_meta, no caso de edição de série(taxonomy) ou então $post, no caso de produto(posts)
 * ATENÇÃO: a ativação do javascript de autocomplete, tagBox.init(),  pode ser feito de duas formas:
 *		- chamada padrão do core, em 'wp-admin/js/post.dev.js' - isso é ativado em qualquer página de edição, e/ou onde possua <code>$('#side-sortables, #normal-sortables, #advanced-sortables').children('div.postbox')</code>
 * 		- chamada via admin_footer_scripts.js, que verifica se os inputs de autocomplete já estão com os handlers atribuídos
 * 		- também em admin_footer_scripts.js está a chamada para ativar os botões de adição de tags recentes e favoritas
 * 
 */
function form_element_excs_taxonomy_terms_non_hierachical( $data, $data_value, $parent ){
	global $post;
	
	// começar a guardar o output do script js em buffer
	ob_start();
	
	
	$taxonomy = $data['name'];
	$tax_name = esc_attr($taxonomy);
	$taxonomy = get_taxonomy($taxonomy);
	$disabled = !current_user_can($taxonomy->cap->assign_terms) ? 'disabled="disabled"' : '';
	
	//options
	$defaults = array(
		'object_type' => 'post',
		'show_popular' => true,
		'show_recent' => true,
		'show_favs' => true,
		'show_adder' => false,
	);
	$options = wp_parse_args($data['options'], $defaults);
	//pre($options);
	
	// termo selecionado - verifica se está buscando post_meta, user_meta ou taxonomy_meta
	if( $options['object_type'] == 'taxonomy' ){
		//$input_name = 'personagem';
		$input_name = $tax_name;
		if( isset($_GET['tag_ID']) ){
			$selected_terms = get_metadata( 'term', intval($_GET['tag_ID']), $tax_name, true );
			
			if( empty($selected_terms) or $selected_terms == 'Array' )
				$selected_terms = ''; // filtrar erros de gravação
		}
		else{
			$selected_terms = '';
		}
		
	}
	if( $options['object_type'] == 'user' ){
		$input_name = "fav_terms_{$tax_name}";
		global $current_user;
		$selected_terms = get_user_meta( $current_user->ID, "fav_terms_{$tax_name}", true );
	}
	elseif( $options['object_type'] == 'post' ){
		$input_name = "tax_input[$tax_name]";
		$selected_terms = get_terms_to_edit( $post->ID, $tax_name );
		
		/**
		 * Caso esteja vazio(não preenchido ou new history):
		 * Apenas em personagem, verificar primeiro se esta história está sendo editada via painel rápido(dentro de produto), e portanto já pertence/pertencerá
		 * à algum produto. Assim, pegar a série correspondente ao produto e buscar sua série. Caso esteja cadastrado em alguma, será puxado a term_meta 'personagem' e adicionado(append) à história.
		 * 
		 */
		if( $selected_terms == false ){
			
			// taxonomias de histórias que poderão ter default vindo de série
			$history_defaults_from_serie = array(
				'personagem',
				'escritor',
				'desenhista',
			);
			if( $_GET['serie'] != 0 and in_array( $tax_name, $history_defaults_from_serie ) ){
				/**
				 * Verificar se a revista já possui uma série.
				 * 
				 */
				$selected_terms = get_metadata('term', $_GET['serie'], $tax_name, true);
			}
			
		}
	}
	//pre($selected_terms);
	?>
	<div class="excs_taxonomy_terms_non_hierachical">
		<div class="core_the_tags inside">
			<div class="tagsdiv" id="<?php echo $tax_name; ?>">
				<div class="jaxtag">
				<div class="nojs-tags hide-if-js">
				<p><?php echo $taxonomy->labels->add_or_remove_items; ?></p>
				<textarea name="<?php echo $input_name; ?>" rows="3" cols="20" class="the-tags" id="tax-input-<?php echo $tax_name; ?>" <?php echo $disabled; ?>><?php echo $selected_terms; ?></textarea></div>
				<?php if ( current_user_can($taxonomy->cap->assign_terms) ){ ?>
				<div class="ajaxtag hide-if-no-js">
					<label class="screen-reader-text" for="new-tag-<?php echo $tax_name; ?>"><?php echo $data['label']; ?></label>
					<div class="taghint"><?php echo $taxonomy->labels->add_new_item; ?></div>
					<p><input type="text" id="new-tag-<?php echo $tax_name; ?>" name="newtag[<?php echo $tax_name; ?>]" class="newtag form-input-tip" size="16" autocomplete="off" value="" />
					<input type="button" class="button tagadd" value="<?php esc_attr_e('Add'); ?>" tabindex="3" /></p>
				</div>
				<p class="howto"><?php echo esc_attr( $taxonomy->labels->separate_items_with_commas ); ?></p>
				<?php } ?>
				</div>
				<div class="tagchecklist"></div>
			</div>
			<?php if ( $options['show_popular'] == true and true == false ){ ?>
			<p class="hide-if-no-js">
				<a href="#titlediv" class="tagcloud-link" id="link-<?php echo $tax_name; ?>"><?php echo $taxonomy->labels->choose_from_most_used; ?></a>
			</p>
			<?php } ?>
		</div>
		<div class="excs_the_tags">
			<?php if ( $options['show_favs'] == true ){ ?>
			<p class="hide-if-no-js excs_user_terms excs_the_tags_favs">
				<?php echo $taxonomy->labels->name; ?> favoritos/fixos: <a href="profile.php" target="_blank" class="excs_select_fav_terms_link" title="Escolher termos desta lista">escolher lista</a> <br />
				
				<?php
				global $current_user;
				$meta_name = "fav_terms_{$tax_name}";
				$user_recents = get_user_meta( $current_user->ID, $meta_name, true );
				if( !empty($user_recents) ){
					$user_recents = explode(',', $user_recents);
					$tags = array();
					foreach( $user_recents as $fav ){
						$tags[] = "<a href='#' class='term'>{$fav}</a>";
					}
					echo implode(', &nbsp;', $tags);
				}
				?>
			</p>
			<?php } ?>
			
			<?php if ( $options['show_recent'] == true ){ ?>
			<p class="hide-if-no-js excs_user_terms excs_the_tags_recent">
				<?php echo $taxonomy->labels->name; ?> recentes: <br />
				
				<?php
				global $current_user;
				$user_recents = get_user_meta( $current_user->ID, "recent_terms", true );
				if( !empty($user_recents) ){
					$tags = array();
					if( !empty($user_recents[$tax_name]) ){
						foreach( $user_recents[$tax_name] as $recent ){
							$tags[] = "<a href='#' class='term'>{$recent}</a>";
						}
						echo implode(', &nbsp;', $tags);
					}
				}
				?>
			</p>
			<?php } ?>
		</div>
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
		case 'simple':
			echo $input;
			break;
		
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
?>