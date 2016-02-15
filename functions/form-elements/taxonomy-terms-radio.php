<?php
/**
 * TAXONOMY TERMS RADIO
 * Este metabox é um upgrade do taxonomy_radio, com mais opções
 * 
 * 
 * @todo deixar a "nenhum(a)" opcional
 */

class BFE_taxonomy_terms_radio extends BorosFormElement {
	
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'size' => false,
		'disabled' => false,
		'readonly' => false,
		'maxlength' => false,
	);
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
		$attrs = $this->make_attributes($this->data['attr']);
		
		/**
		 * ATENÇÃO: VALUE_FIELD define a coluna a ser usada para o atributo VALUE do input.
		 * Isso é necessário para que se possa escolher se deseja gravar o TERM_ID, ou o SLUG do termo como term_meta
		 * SLUG está sendo usado para a gravação do ALIAS_OF, que requer o slug e não ID
		 */
		$defaults = array(
			'object_type' => 'post',
			'value_field' => 'term_id',
			'show_popular' => true,
			'show_recent' => true,
			'show_favs' => true,
			'show_adder' => false,
			'show_filter' => false,
		);
		$options = wp_parse_args($this->data['options'], $defaults);
		//pre($options);
		
		//$taxonomy = $this->data['name'];
		$taxonomy = isset($options['taxonomy']) ? $options['taxonomy'] : $this->data['name'];
		$tax = get_taxonomy($taxonomy);
		// pegar o std
		$std_term = empty($this->data['std']) ? 0 : $this->data['std'];
		
		// começar a guardar o output do script js em buffer
		ob_start();
		
		if($tax){
			if( $options['object_type'] == 'taxonomy' ){
				//$input_name = 'personagem';
				$input_name = $this->data['name'];
				if( isset($_GET['tag_ID']) ){
					$selected_term = get_metadata( 'term', intval($_GET['tag_ID']), $input_name, true );
					
					if( empty($selected_term) or $selected_term == 'Array' )
						$selected_term = ''; // filtrar erros de gravação
				}
				else{
					$selected_term = $std_term;
				}
				
			}
			elseif( $options['object_type'] == 'user' ){
				die('Trecho não programado!!!');
			}
			elseif( $options['object_type'] == 'admin_page' ){
				$input_name = $this->data['name'];
				$selected_term = get_option($input_name);
				if( empty($selected_term) )
					$selected_term = $std_term;
			}
			elseif( $options['object_type'] == 'post' ){
				global $post;
				$input_name = "tax_input[{$taxonomy}]";
				// verificar qual é o checkado
				$selected_terms = wp_get_object_terms( $post->ID, $taxonomy );
				if( !is_wp_error($selected_terms) and !empty($selected_terms) )
					$selected_term = $selected_terms[0]->$options['value_field'];
				else
					$selected_term = $std_term;
			}
			
			/**
			 * Tratamento de ALIAS
			 * 
			 */
			if( isset($_GET['tag_ID']) and isset($_GET['taxonomy']) ){
				global $tag;
				if( $tag->term_group != 0 ){
					$aliases = get_term_aliases( $tag->term_group );
					//pre($tag, 'TAG');
					//pre($aliases, 'ALIASES');
					$first_alias = false;
					$links = array();
					foreach( $aliases as $alias ){
						if( $tag->term_id != $alias ){
							$term = get_term($alias, $tag->taxonomy);
							if($term){
								$links[] = edit_term_link($term->name, '', '', $term, false);
								if( $first_alias === false ){
									$first_alias = $term;
									$selected_term = $first_alias->$options['value_field'];
								}
							}
						}
					}
					$links = join(', ', $links);
					echo "<div class='excs_alert'>Este termo é um dos aliases para os seguintes {$tax->labels->name}: {$links}</div>";
				}
			}
			
			/**
			$input_name = "tax_input[{$taxonomy}]";
			// verificar qual é o checkado
			$selected_terms = wp_get_object_terms( $post->ID, $taxonomy );
			if( !is_wp_error($selected_terms) and !empty($selected_terms) )
				$selected_term = $selected_terms[0]->term_id;
			else
				$selected_term = 0;
			/**/
			//pal($selected_term);
			
			?>
			<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
				<div id="<?php echo $taxonomy; ?>-favs" class="excs_taxonomy_radio_favs">
				<?php
				global $current_user;
				$user_favs = get_user_meta( $current_user->ID, $taxonomy, true );
				$removes = array();
				if( $options['show_favs'] == true ){
					if( !empty($user_favs) ){
				?>
					Favoritas / Fixas: 
					<a href="profile.php#user_favs_list" target="_blank" class="excs_select_fav_terms_link" title="Escolher termos desta lista">escolher lista</a>
					<ul class="fav_list_itens">
						<?php
						/**
						 * Pode acontecer do nome da série ser modificado, e o $user_favs ficar comprometido.
						 * @todo Revisar a forma como é gravado o $user_favs, para que salva o term_id no lugar -- talvez seja problema das tags que não retornas as ids para o frontend
						 */
						$user_favs = explode(',', $user_favs);
						foreach( $user_favs as $fav ){
							$term = get_term_by( 'name', $fav, $taxonomy );
							if( $term !== false ){
								$item_input_value = $term->$options['value_field'];
								$removes[] = $term->term_id;
								$checked = checked( $term->$options['value_field'], $selected_term, false );
								echo "<li><label for='{$taxonomy}_radio_{$term->term_id}'><strong><input type='radio' name='{$input_name}' value='{$item_input_value}' id='{$taxonomy}_radio_{$term->term_id}'{$checked} /> <span>{$term->name}</span></strong></label></li>";
							}
						}
						?>
					</ul>
				<?php } else { ?>
				Sem favoritos definidos - <a href="profile.php#user_favs_list" target="_blank">editar lista</a> <a href="profile.php#user_favs_list" target="_blank" class="excs_select_fav_terms_link" title="Escolher termos desta lista">escolher lista</a>
				<?php }} ?>
				</div>
				<hr />
				<div id="<?php echo $taxonomy; ?>-all" class="excs_taxonomy_radio_all">
					<div id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
						<?php if( $options['show_filter'] == true ){ ?>
						<p>
							<label for="<?php echo $taxonomy; ?>_filter_list_input">Filtrar</label> 
							<input type="text" id="<?php echo $taxonomy; ?>_filter_list_input" class="filter_list_input" />
							<span class="excs_taxonomy_radio_show_all button">mostrar tudo</span>
						</p>
						<?php } ?>
						
						<ul class="filter_list_itens">
							<?php
							// vazio
							$checked = checked( 0, $selected_term, false );
							$empty_radio = "<li><label for='{$taxonomy}_radio_0'><input type='radio' name='{$input_name}' value='0' id='{$taxonomy}_radio_0' {$checked} /> <span>Nenhum(a)</span></label></li>";
							
							// todos os termos
							$terms_list = array();
							$terms = get_terms( $taxonomy, array('hide_empty' => false) );
							foreach( $terms as $term ){
								if( !in_array( $term->term_id, $removes ) ){
									$item_input_value = $term->$options['value_field'];
									$checked = checked( $item_input_value, $selected_term, false );
									$item = "<li><label for='{$taxonomy}_radio_{$term->term_id}'><input type='radio' name='{$input_name}' value='{$item_input_value}' id='{$taxonomy}_radio_{$term->term_id}'{$checked} /> <span>{$term->name}</span></label></li>";
									
									// colocar o selecionado no começo da lista
									if( $term->$options['value_field'] == $selected_term )
										array_unshift( $terms_list, $item );
									else
										$terms_list[] = $item;
								}
							}
							// adicionar o vazio no começo
							array_unshift( $terms_list, $empty_radio );
							// exibir itens
							echo implode( "\n", $terms_list );
							?>
						</ul>
					</div>
				</div>
			</div>
			<?php
		}
		else{
			?>
			<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
				<div class="inside txt_center message_type_alert">
					A taxonomia <strong><?php echo $taxonomy; ?></strong> não existe.
				</div>
			</div>
			<?php
		}
		
		// actions after
		do_action( 'excs_taxonomy_radio_after', 'term_meta', $this->data, $taxonomy, isset($_GET['tag_ID']) ? $_GET['tag_ID'] : 0, $value );
		
		// guardar o output em variável
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}




