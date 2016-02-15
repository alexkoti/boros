<?php
/**
 * ATTACH SELECT
 * Seleionar um dos anexos, independente do MIME_TYPE
 * 
 * @TODO URGENTE: botão para remover anexo!!!
 * 
 */
class BFE_attach_select extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
		'rel' => false,
	);
	
	var $enqueues = array(
		'js'  => 'attach-select',
		'css' => 'attach-select',
	);
	
	function set_input( $value = null ){
		$input_name = $this->data['name'];
		$actions_class = 'has_no_attachs';
		$send_string = 'Enviar um anexo';
		$link_tab = 'library';
		
		// começar a guardar o output do script js em buffer
		ob_start();
		
		// Verificar se o post já possui alguma imagem anexada. Caso positivo, será exibido o botão de selecionar entre as existentes e mudar o texto link de adcionar imagens
		if( isset($this->context['post_id']) and $this->context['post_id'] != 0 ){
			$args = array(
				'post_parent' => $this->context['post_id'],
				'post_type' => 'attachment',
			);
			$images = get_children( $args );
			if( $images ){
				$actions_class = 'has_attachs';
				$send_string = 'Enviar outro anexo';
				$link_tab = 'gallery';
			}
		}
		?>
		<div class="attach_select" id="<?php echo "attach_select_{$input_name}"; ?>">
			<?php
			$attrs = make_attributes($this->data['attr']);
			echo "<input type='hidden' value='{$this->data_value}' {$attrs} />";
			?>
			
			<div class="attach_select_view">
				<?php
				if( !empty( $this->data_value ) ){
					attach_select_load( $this->data_value );
				}
				?>
			</div>
			
			<?php
			$post_id = ( isset($this->context['post_id']) ) ? $this->context['post_id'] : 0;
			
			$link_args = array();
			$link_args['post_id'] = $post_id;
			$link_args['send_back'] = 'id';
			$link_args['file_type'] = 'any';
			$link_args['input_name'] = $input_name;
			$link_args['hide_order'] = 1;
			$link_args['TB_iframe'] = 1;
			$new_link = add_query_arg( $link_args, 'media-upload.php' );
			
			$link_args = array();
			$link_args['post_id'] = $post_id;
			$link_args['tab'] = $link_tab;
			$link_args['send_back'] = 'id';
			$link_args['file_type'] = 'any';
			$link_args['input_name'] = $input_name;
			$link_args['hide_order'] = 1;
			$link_args['TB_iframe'] = 1;
			$select_link = add_query_arg( $link_args, 'media-upload.php' );
			
			$dataset_args = array(
				'input' => $input_name,
				'callback' => 'attach_select',
			);
			$dataset = make_attributes($dataset_args, 'data-');
			?>
			<div class="attach_select_view_actions hide-if-no-js <?php echo $actions_class; ?>">
				<a class="thickbox btn_item_new" href="<?php echo $new_link; ?>" <?php echo $dataset;?>><?php echo $send_string; ?></a> &nbsp; &nbsp; 
				<a class="thickbox btn_item_select" href="<?php echo $select_link; ?>" <?php echo $dataset;?>>Escolher entre os anexos existentes</a>
			</div>
		</div>
		<?php
		// guardar o output em variável
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}

add_action('wp_ajax_attach_select', 'attach_select');
function attach_select(){
	$action 	= $_POST['action'];
	$context 	= $_POST['context'];
	$value 		= $_POST['value'];
	
	// carregar config do elemento
	$elem = load_element_config( $context );
	
	// salvar caso não pertença a um duplicate
	if( !isset($elem->context['in_duplicate_group']) or $elem->context['in_duplicate_group'] == false ){
		if( $context['type'] == 'option' ){
			update_option( $context['name'], $value );
		}
		elseif( $context['type'] == 'post_meta' ){
			update_post_meta( $context['post_id'], $context['name'], $value );
		}
		elseif( $context['type'] == 'user_meta' ){
			update_user_meta( $context['user_id'], $context['name'], $value );
		}
	}
	attach_select_load( $value );
	die();
	/**
	$post_ID = $_POST['post_ID'];
	$meta_key = $_POST['meta_key'];
	$att_id = $_POST['att_id'];
	$input_name = $_POST['input_name'];
	
	// apenas gravar esse valor caso não seja um duplicate
	preg_match( '/(\[.*\])/', $input_name, $matches );
	if( empty($matches) ){
		update_post_meta( $post_ID, $meta_key, $att_id );
	}
	attach_select_load( $att_id );
	die();
	/**/
}

function attach_select_load( $id ){
	if( !empty($id) ){
		$attch = get_post( $id );
		$media_dims = '';
		$meta = wp_get_attachment_metadata( $id );
		if ( is_array( $meta ) && array_key_exists( 'width', $meta ) && array_key_exists( 'height', $meta ) ){
			$media_dims = "<strong>Dimensões:</strong> <span>{$meta['width']}&nbsp;&times;&nbsp;{$meta['height']}</span><br />";
		}
		$file  = get_attached_file( $id );
		$file_size = false;
		if( isset( $meta['filesize'] ) ){
			$file_size = $meta['filesize'];
		}
		elseif( file_exists( $file ) ){
			$file_size = filesize( $file );
		}
		$thumb = wp_get_attachment_image_src( $id, 'thumbnail', true );
		$title = apply_filters( 'the_title', $attch->post_title, $attch->ID );
		echo '<div class="inner">';
		echo "<div class='attach_select_icon'><img class='thumbnail' src='{$thumb[0]}' alt='' /><div class='hide-if-no-js attach_select_remove'><span class='btn' title='Remover este arquivo'>&nbsp;</span></div></div>";
		echo "<strong>Nome:</strong> {$title} <br /><strong>Tipo do arquivo:</strong> {$attch->post_mime_type} <br />";
		if( !empty( $file_size ) ){
			echo '<strong>Tamanho:</strong> ' . size_format( $file_size );
		}
		echo $media_dims;
		echo '</div>';
	}
}

add_action('wp_ajax_attach_select_remove', 'attach_select_remove');
function attach_select_remove(){
	$context = $_POST['context'];
	
	// não salvar dados e interromper caso esteja dentro de um duplicate
	if( $context['in_duplicate_group'] == true )
		die();
	
	if( $context['type'] == 'option' ){
		delete_option( $context['name'] );
	}
	elseif( $context['type'] == 'post_meta' ){
		delete_post_meta( $context['post_id'], $context['name'] );
	}
	die();
}



