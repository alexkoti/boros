<?php
/**
 * FORM ELEMENT: 
 * 
 * 
 * 
 */

/**
 * Para este elemento, $data_value poderá ser o valor de term_meta, no caso de edição de série(taxonomy) ou então $post, no caso de produto(posts)
 * 
 */
class BFE_taxonomy_checkbox_hierachical extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
	);
	
	// Checkboxgroups não possuem label inicial, possuindo cada opção seu próprio label.
	function set_label(){
		if( !empty($this->data['label']) )
			$this->label = apply_filters( "BFE_{$this->data['type']}_label", "<span class='non_click_label'>{$this->data['label']}{$this->label_helper}</span>" );
	}
	
	function set_input(){
		global $post;
		/**
		 * Caso seja uma chamada ajax, $post não estará disponível. Todas as variáveis vindo do ajax
		 * 
		 */
		if( isset($_GET['ajax_post_id']) )
			$post = get_post( intval($_GET['ajax_post_id']) );
		
		// termo selecionado - verifica se está buscando post_meta ou taxonomy_meta
		$selected_terms = array();
		if( isset($_GET['taxonomy']) ){
			$object_id = 0;
			if( isset($_GET['tag_ID']) ){
				$selected_terms = get_metadata( 'term', intval($_GET['tag_ID']), $this->data['options']['taxonomy'], true );
			}
		}
		else{
			$object_id = $post->ID;
			$selecteds = wp_get_object_terms( $post->ID, $this->data['options']['taxonomy'] );
			foreach( $selecteds as $tt ){
				$selected_terms[] = absint( $tt->term_id );
			}
		}
		// caso esteja vazio e possua um default, aplicar
		if( empty( $selected_terms ) and !empty( $this->data['std'] ) ){
			$default_term = get_term_by( 'name', $this->data['std'], $this->data['options']['taxonomy'] );
			$selected_terms = array( $default_term->term_id );
		}
		
		// exibir lista de checkboxes
		$args = array(
			'taxonomy' => $this->data['options']['taxonomy'],
			'checked_ontop' => false,
			'selected_cats' => $selected_terms, 
			'walker' => new Walker_Taxonomy_Terms_Checklist, 
		);
		$input = '<ul class="taxonomy_checkbox_list">';
		// começar a guardar o output do script js em buffer
		ob_start();
		wp_terms_checklist( $object_id, $args );
		// guardar o output em variável
		$checks = ob_get_contents();
		ob_end_clean();
		$input .= $checks;
		$input .= '</ul>';
		return $input;
	}
}
function form_element_excs_taxonomy_terms_hierachical( $data, $data_value, $parent ){
	global $post;
	
	// começar a guardar o output do script js em buffer
	ob_start();
	
	
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
	
	$taxonomy = $data['name'];
	$tax = get_taxonomy($taxonomy);
	?>
	<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
		<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
			<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
			<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
		</ul>

		<div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
			<ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
				<?php $popular_ids = wp_popular_terms_checklist($taxonomy); ?>
			</ul>
		</div>

		<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
			<?php
			$name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy . ']';
			echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
			?>
			<ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
				<?php wp_terms_checklist($post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids ) ) ?>
			</ul>
		</div>
	<?php if ( current_user_can($tax->cap->edit_terms) ) : ?>
			<div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
				<h4>
					<a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js" tabindex="3">
						<?php
							/* translators: %s: add new taxonomy label */
							printf( __( '+ %s' ), $tax->labels->add_new_item );
						?>
					</a>
				</h4>
				<p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
					<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $tax->labels->add_new_item; ?></label>
					<input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" tabindex="3" aria-required="true"/>
					<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
						<?php echo $tax->labels->parent_item_colon; ?>
					</label>
					<?php wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;', 'tab_index' => 3 ) ); ?>
					<input type="button" id="<?php echo $taxonomy; ?>-add-submit" class="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add button category-add-sumbit" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" tabindex="3" />
					<?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy, false ); ?>
					<span id="<?php echo $taxonomy; ?>-ajax-response"></span>
				</p>
			</div>
		<?php endif; ?>
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
					<p class="form_ipt_text">
						<label for="<?php echo $data['name']; ?>"><?php echo $data['label']; ?></label><br />
						<?php echo $input; ?>
					</p>
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