<?php
/**
 * TRIP GALLERY
 * Galeria com inserção de imagens e texto puro
 * 
 */
class BFE_trip_comercial_contatos extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
	);
	
	var $enqueues = array(
		'js' => 'z_trip_comercial_contatos',
		'css' => 'z_trip_comercial_contatos',
	);
	
	function set_input( $value = null ){
		//pre( $_POST );
		//pre($this->data_value);
		$values = !empty($this->data_value) ? $this->data_value : array( array('titulo' => '','id' => 0,) ) ;
		$input_name = $this->data['name'];
		$query = array(
			'post_type' => 'contato',
			'orderby' => 'title',
			'order' => 'ASC',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'contato_type',
					'field' => 'slug',
					'terms' => 'interno'
				),
			),
		);
		$contatos = new WP_Query();
		$contatos->query($query);
		$contatos_data = array();
		if( $contatos->posts ){
			foreach($contatos->posts as $post){
				setup_postdata($post);
				$contatos_data[] = array(
					'id' => $post->ID,
					'name' => $post->post_title
				);
			}
		}
		wp_reset_query();
		
		// começar a guardar o output do script js em buffer
		ob_start();
		
		$values_size = count($values);
		echo '<div id="trip_comercial_contatos"><ul id="trip_contact_list">';
		for( $i = 0; $i < $values_size; $i++ ){
			//pre($values, 'values');
			//pre($contatos_data);
			$select = "<select name='trip_comercial_contatos[{$i}][id]'>";
			foreach( $contatos_data as $contato ){
				//pre($contato['id']);
				//pre($values[$i]['id']);
				$selected = selected( $contato['id'], $values[$i]['id'], false );
				$select .= "<option value='{$contato['id']}'{$selected}>{$contato['name']}</option>";
			}
			$select .= '</select>';
			
			$input_name = "trip_comercial_contatos[{$i}][titulo]";
			?>
			<li>
				Contato: <?php echo $select; ?> &nbsp;&nbsp;
				Título: <input type="text" value="<?php echo $values[$i]['titulo']; ?>" name="<?php echo $input_name; ?>" class="boros_form_input input_text iptw_medium" />
				<span class="remove_contact">remover</span>
			</li>
			<?php
		}
		echo '</ul>
				<p id="duplicate_contact">
					<span>Adicionar contato</span>
				</p></div>';
		
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
	
	function set_label(){
		if( !empty($this->data['label']) )
			$this->label = "{$this->data['label']}{$this->label_helper}";
	}
}
