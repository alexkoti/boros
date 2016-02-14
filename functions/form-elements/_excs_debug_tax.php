<?php
/**
 * FORM ELEMENT: TEXT
 * Elemento input:text comum
 * 
 * 
 * 
 */

function form_element_excs_debug_tax( $data, $data_value, $parent ){
	global $post;
	
	// começar a guardar o output do script js em buffer
	ob_start();
	
	$taxonomies = array(
		'serie' => array(
			'editora',
			'editora-original',
			'formato',
			'capa',
			'encadernacao',
			'cor',
			'origem',
			'idioma',
			'pais-origem',
			'peridiocidade',
		),
		'historias' => array(
			'personagem',
			'escritor',
			'desenhista',
			'arte-finalista',
			'colorista',
			'genero',
			'arco-saga',
		),
	);
	foreach( $taxonomies as $type => $taxes ){
		echo "<hr /><p>Herdado de <em>{$type}</em></p>";
		foreach( $taxes as $tax ){
			$terms = wp_get_object_terms( $post->ID, $tax );
			if( $terms ){
				echo '<dl>';
				echo "<dt><strong>{$tax}</strong></dt>";
				foreach( $terms as $term ){
					echo "<dd> &nbsp; {$term->name}</dd>";
				}
				echo '</dl>';
			}
		}
	}
	
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