<?php
/**
 * FUNÇÕES DE MÍDIA
 * Funções de mídia apenas para o admin.
 * 
 * 
 * 
 * 
 * 
 */

/**
 * Forçar o retorno da imagem original ao inserir no editor de texto
 * 
 */
function edit_attachment_link( $url ){
	$original = wp_get_attachment_image_src( $id, 'full' );
	if( $original[0] == $url ){
		$large = wp_get_attachment_image_src( $id, 'large' );
		$url = $large[0];
	}
	return $url;
}
//add_filter('attachment_link', 'edit_attachment_link');



/* ========================================================================== */
/* CONFIGURAR BOTÃO DE AÇÂO DO LIGHTBOX DE INSERÇÂO DE IMAGEM =============== */
/* ========================================================================== */

/**
 * Forçar URL a ser retornada 'none', 'file', 'post'
 * $_GET['link_to'] é enviado no link do botão de upload em functions/admin.php e configurado na chamada do meta_box
 */
//add_filter('admin_init', 'force_linkto_media_button');
function force_linkto_media_button(){
	if( isset($_GET['link_to']) ){
		update_option('image_default_link_type', $_GET['link_to']);
	}
}

/**
 * Ativado na listagem de imagens do thickbox
 * Esta listagem pode vir de duas chamadas: 
 * 		1) link direto para a tab através do prâmetro 'tab=library' no botão thickbox ou link da tab da biblioteca após o thickbox aberto
 * 		2) Retorno da ação de upload por ajax
 * 
 */
add_filter('post_mime_types', 'modify_post_mime_types');
function modify_post_mime_types($post_mime_types) {
    $post_mime_types['text'] = array(__('TXT'), __('Manage TXT'), _n_noop('TXT <span class="count">(%s)</span>', 'TXT <span class="count">(%s)</span>'));
    return $post_mime_types;
}

add_filter( 'attachment_fields_to_edit', 'boros_filter_media_upload', 20, 2 );
function boros_filter_media_upload( $form_fields, $attachment ){
	/**
	if( isset($_REQUEST) ){
		pre( $_REQUEST, 'request' );
	}
	if( isset($_POST) ){
		pre( $_POST, 'post' );
	}
	if( isset($_GET) ){
		pre( $_GET, 'get' );
	}
	/**/
	
	$post_id = isset($_REQUEST['post_id']) ? $_REQUEST['post_id'] : 0;
	//pre($post_id, 'post-post, o parent');
	//pre($attachment->ID, 'attachment');
	
	/**
	 * Identificar o mime_type, porém apenas dentro dos types registrados em get_post_mime_types(), arquivo 'wp-admin/includes/post.php'. Essa função adiciona links de filtragem 
	 * no controle geral de midias(MENU > Midia > Biblioteca). As chaves desse array é simplificado, por exemplo 'images', 'pdf', agrupando os diversos mime_types.
	 * 
	 * $mime_type também poderá ser usado para retornar 
	 * @link http://wordpress.org/support/topic/filter-media-library-by-file-type#post-1877596
	 * 
	 */
	$post_mime_types = get_post_mime_types();
	$keys = array_keys( wp_match_mime_types( array_keys( $post_mime_types ), $attachment->post_mime_type ) );
	$mime_type = array_shift( $keys );
	
	$att_url = $thumbnail = $delete = '';
	if( $mime_type == 'image' ){
		$type_text = 'Imagem';
		$send_text = 'Usar essa imagem';
	}
	else{
		$type_text = 'Anexo';
		$send_text = 'Usar esse arquivo';
	}
	$send = get_submit_button( $send_text, 'button', "send[{$attachment->ID}]", false );
	
	/**
	 * Consultar o arquivo 'wp-admin/includes/media.php', function get_media_item(), que renderiza as linhas de media nos thickboxes
	 * 
	 * 
	 */
	
	/**
	 * Recomendável usar apenas para forçar o link para o arquivo direto, em caso arquivos que não sejam imagens, como pdf, txt, doc, zip.
	 * Para imagens, usar $_REQUEST['force_size'], onde estará subentendido que é uma imagem, e será usado wp_get_attachment_image_src() no lugar de wp_get_attachment_url()
	 * 
	 * ATENÇÃO: a função do campo 'url' é o endereço a qual será linkado a anexo, e não o endereço do anexo em si!
	 * 
	 */
	if( isset($_REQUEST['link_to']) ){
		$accept = array( 'none', 'file', 'post' );
		if( in_array( $_REQUEST['link_to'], $accept ) ){
			$form_fields['link_to'] = array( 'input' => 'hidden', 'value' => $_REQUEST['link_to'] );
			
			if ( $_REQUEST['link_to'] == 'file' )
				$att_url = wp_get_attachment_url($attachment->ID);
			elseif ( $_REQUEST['link_to'] == 'post' )
				$att_url = get_attachment_link($attachment->ID);
			else
				$att_url = '';
		}
	}
	
	/**
	 * Caso seja declarado, sempre irá considerar que é desejado filtrar apenas as imagens. Portanto, arquivos comuns não possuirão o botão de send_back
	 * 
	 */
	$form_fields['force_size'] = array( 'input' => 'hidden', 'value' => 'full' );
	if( isset($_REQUEST['force_size']) ){
		if( $mime_type == 'image' ){
			$image_sizes = get_intermediate_image_sizes();
			if( in_array( $_REQUEST['force_size'], $image_sizes ) ){
				$form_fields['force_size'] = array( 'input' => 'hidden', 'value' => $_REQUEST['force_size'] );
			}
		}
		else{
			$send = '';
		}
	}
	//pal($size);
	
	/**
	 * Restringir o retorno apenas para um tipo de arquivo
	 * 
	 */
	if( isset($_REQUEST['file_type']) ){
		if( $mime_type != $_REQUEST['file_type'] and 'any' != $_REQUEST['file_type'] ){
			$send = "<span class='special_image_message'>Tipo de arquivo bloqueado para esta opção.</span>";
		}
		$form_fields['file_type'] = array( 'input' => 'hidden', 'value' => $_REQUEST['file_type'] );
	}
	
	/**
	 * Verificar se a imagem já não está sendo utilizada para a opção atual
	 * Verificar primeiro se o input_name não é duplicate, por exemplo 'name[2][subname]'
	 * 
	 * @todo verificar em taxonomy_meta
	 */
	if( isset($_REQUEST['input_name']) ){
		$input_name = $_REQUEST['input_name'];
		
		// retornar o 'input_name' para o 'media_send_to_editor'
		$form_fields['input_name'] = array(
			'input' => 'hidden',
			'value' => $input_name
		);
		
		// estamos em admin page
		if( $post_id == 0 ){
			$value = get_option($input_name);
		}
		// post_meta
		else{
			$value = get_post_meta( $post_id, $input_name, true );
		}
		
		// A imagem que se quer exibir já é a escolhida, mostrar apenas uma mensgaem de aviso
		if( $value == $attachment->ID )
			$send = "<span class='special_image_message'>{$type_text} atual.</span>";
	}
	
	
	if( isset($_REQUEST['hide_order']) and $_REQUEST['hide_order'] == true ){
		$form_fields['menu_order']['input'] = 'hidden';
	}
	
	// Definir o que será enviado de volta ao parent
	if( isset($_REQUEST['send_back']) ){
		$form_fields_done = array();
		switch( $_REQUEST['send_back'] ){
			case 'id':
				$form_fields['url']['input'] = 'hidden';
				$form_fields['url']['value'] = $att_url;
				$form_fields_done[] = 'url';
				
				$send_back = 'id';
				break;
			
			case 'url':
				$form_fields['url']['input'] = 'hidden';
				$form_fields['url']['value'] = wp_get_attachment_url($attachment->ID);
				$form_fields_done[] = 'url';
				
				$send_back = 'url';
				break;
			
			case 'tag':
				$form_fields['url']['input'] = 'hidden';
				$form_fields['url']['value'] = wp_get_attachment_url($attachment->ID);
				$form_fields_done[] = 'url';
				
				$send_back = 'tag';
				break;
		}
		
		// post_title e post_excerpt são obrigatórios. post_title é requerido pelo form de midia e post_excerpt pela function get_image_send_to_editor()
		$form_fields['post_title']['input'] = 'hidden';
		$form_fields_done[] = 'post_title';
		$form_fields['image_alt']['input'] = 'hidden';
		$form_fields_done[] = 'image_alt';
		$form_fields['post_excerpt']['input'] = 'hidden';
		$form_fields_done[] = 'post_excerpt';
		
		$form_fields['buttons'] = array( 'tr' => "\t\t<tr class='submit'><td></td><td class='savesend'>$send $thumbnail $delete</td></tr>\n" );
		$form_fields_done[] = 'buttons';
		
		// esconder os campos restantes, mesmo os custom, mas apenas os que já possuem a chave 'input'
		foreach( $form_fields as $name => $attr ){
			if( !in_array( $name, $form_fields_done ) and isset($form_fields[$name]['input']) ){
				$form_fields[$name]['input'] = 'hidden';
			}
		}
		
		// será usado por boros_image_send_to_editor()
		if( isset($send_back) )
			$form_fields['send_back'] = array( 'input' => 'hidden', 'value' => $send_back );
	}
	
	//pre($form_fields);
	
	return $form_fields;
}


/**
 * SEARCH MEDIA
 * Este bloco é um POG necessário!!! Como o form de busca não possui nenhum filtro ou action apropriado, foi usado o único filtro disponível, o 'media_upload_mime_type_links', para poder 
 * adicionar os campos hidden referentes aos argumentos $_GET necessários para o special_image
 * 
 */
add_filter( 'media_upload_mime_type_links', 'search_media_query_args' );
function search_media_query_args( $type_links ){
	//pre($type_links);
	//pre($_REQUEST);
	$append = '';
	$custom_args = array(
		'send_back',
		'input_name',
		'file_type',
		'force_size',
		'hide_order',
		'remove_fields',
		'link_to',
	);
	foreach( $custom_args as $arg ){
		if( isset($_REQUEST[$arg]) )
			$append .= "<input type='hidden' name='{$arg}' value='{$_REQUEST[$arg]}' />";
	}
	$type_links[0] .= $append;
	
	return $type_links;
}



/**
 * O 'send_to_editor' possui um hook genérico chamado 'media_send_to_editor'. O filtro 'image_media_send_to_editor()', que verifica o mime_type, e caso identifique como imagem, aciona 
 * a function get_image_send_to_editor(), que por sua vez possui o hook 'image_send_to_editor'. Revisando o processo:
 * 1) Ao clicar no botão 'send_to_editor', é feito o submit no form completo, para que qualquer atributo de qualquer imagem seja salvo.
 * 2) Caso tenha sido declarado o $send_id(ou equivalente), que é o botão clicado, é feito uma sequência de tentativas de definir o HTML de retorno ao editor:
 * 3) Primeiro sempre será o post_title, pois como é obrigatório, sempre estará disponível
 * 4) Depois verifica-se se o campo URL foi preenchido, criando assim um link com o post_title como texto
 * 5) Aplica-se o hook 'media_send_to_editor', enviando como parâmetros $html(titulo + link), $send_id(id selecionado), $attachment(objeto do anexo)
 * 6) Em um dos filtros aplicados(image_media_send_to_editor) é verificado se o anexo é uma imagem, acionando get_image_send_to_editor()
 * 7) get_image_send_to_editor(), possui o hook 'image_send_to_editor', que cria o HTML final. Nesse hook é aplicado pelo core o filtro 'image_add_caption', quer verifica se a imagem 
 *    necessita de legenda(caption)
 * 
 */
add_filter( 'media_send_to_editor', 'boros_media_send_to_editor', 9, 3 );
function boros_media_send_to_editor( $html, $id, $attachment ){
	// Caso seja uma imagem, cancelar este filtro de media comum, pois o hook 'image_send_to_editor' sempre roda depois deste.
	if( isset($_POST['attachments'][$id]['file_type']) and $_POST['attachments'][$id]['file_type'] == 'image' )
		return $html;
	
	//remove_filter( 'media_send_to_editor', 'image_media_send_to_editor' );
	
	if( isset($_POST['attachments'][$id]['send_back']) ){
		switch( $_POST['attachments'][$id]['send_back'] ){
			case 'id';
				return $id;
				break;
			
			case 'url':
				return wp_get_attachment_url($id);
				break;
			
			/**
			 * O retorno de tag tem mais sentido com imagens, mas caso não seja, será retornado um link, repetindo a url como texto
			 * 
			 */
			case 'tag':
				$url = wp_get_attachment_url($id);
				// verificar se é imagem
				$ext = preg_match('/\.([^.]+)$/', $url, $matches) ? strtolower($matches[1]) : false;
				$image_exts = array('jpg', 'jpeg', 'gif', 'png');
				if( in_array($ext, $image_exts) ){
					$size = isset($_POST['attachments'][$id]['force_size']) ? $_POST['attachments'][$id]['force_size'] : 'full';
					return get_image_tag($id, $_POST['attachments'][$id]['post_excerpt'], $_POST['attachments'][$id]['post_title'], 'none', $size);
				}
				// link comum
				else{
					return "<a href='{$url}>{$url}</a>";
				}
				break;
		}
	}
	
	return $html;
}

add_filter( 'image_send_to_editor', 'boros_image_send_to_editor', 20, 8 );
function boros_image_send_to_editor( $html, $id, $caption, $title, $align, $url, $size, $alt ){
	if( !isset($_POST['attachments'][$id]['send_back']) )
		return $html;
	
	$size = isset($_POST['attachments'][$id]['force_size']) ? $_POST['attachments'][$id]['force_size'] : $size;
	
	if( isset($_POST['attachments'][$id]['send_back']) ){
		switch( $_POST['attachments'][$id]['send_back'] ){
			case 'id';
				return $id;
				break;
			
			case 'url':
				return wp_get_attachment_url($id);
				break;
			
			case 'tag':
				return get_image_tag($id, $alt, $title, 'none', $size);
				break;
		}
	}
	
	return $html;
}

/**
 * Adicionar campos ao form de upload
 * 
 */
//add_action( 'post-upload-ui', 'mah_upload_form' );
function mah_upload_form(){
	echo '<input type="hidden" name="lorem" value="ipsum" />';
}

/**
 * Forçar o tamanho da imagem de retorno. A variável $_POST['force_size'] é definida na função force_insert_media_button(), como campo hidden, na 
 * verdade originário do link do thickbox, criado via create_form_element()
 * O returno ficará 'escondido' e acessível apenas botões de send_back
 * Foi clonado a maior parte da função image_downsize(), em wp-includes/media.php
 * 
 * Versão 2, melhorada:
 * O retorno dessa função deve ser um array com os seguintes dados:
<code>
	array(
		$img_url => str,
		$width => int,
		$height => int,
		$is_intermediate => bool;
	);
</code>
 * 
 * @param str $ignore - definido apenas chamada de filtro, não usado
 * @param str $id - ID do attachment
 * @param str $size - size original
 */
//add_filter('image_downsize', 'force_image_size', 99, 3);
function force_image_size( $ignore, $id, $size ){
	pre('force_image_size() - filtro de image_downsize');
	//pre($_GET, 'force_image_size $_GET');
	//pre($id, 'force_image_size $id');
	//pre($size, 'force_image_size $size');
	//pre('<hr />');
	
	// $_POST é a açõo ativada parao send_to_editor
	if( isset($_POST['force_size']) and $_POST['force_size'] ){
		
		//forçar o retorno nesse formato
		$new_size = $_POST['force_size'];
		//url do attachment original, sme redmensionamento
		$img_url = wp_get_attachment_url($id);
		
		$intermediate = image_get_intermediate_size($id, $size);
		
		/**
		$new_size = $_POST['force_size'];
		$img_url = wp_get_attachment_url($id);
		
		pre($_POST['force_size'], 'force_image_size() >>> $_POST[force_size]');
		
		//tentar pegar a imagem intermediária
		$intermediate = image_get_intermediate_size($id, $new_size);
		pre($new_size, 'force_image_size() >>> $new_size');
		pre($img_url, 'force_image_size() >>> $img_url');
		pre($intermediate, 'force_image_size() >>> $intermediate');
		
		//return array( $intermediate['url'], $intermediate['width'], $intermediate['height'], true );
		/**/
		
		/**
		if ( $intermediate = image_get_intermediate_size($id, $size) ) {
			$img_url = str_replace(basename($img_url), $intermediate['file'], $img_url);
			$width = $intermediate['width'];
			$height = $intermediate['height'];
			$is_intermediate = true;
		}
		elseif ( $size == 'thumbnail' ) {
			// fall back to the old thumbnail
			if ( ($thumb_file = wp_get_attachment_thumb_file($id)) && $info = getimagesize($thumb_file) ) {
				$img_url = str_replace(basename($img_url), basename($thumb_file), $img_url);
				$width = $info[0];
				$height = $info[1];
				$is_intermediate = true;
			}
		}
		if ( !$width && !$height && isset($meta['width'], $meta['height']) ) {
			// any other type: use the real image
			$width = $meta['width'];
			$height = $meta['height'];
		}

		if ( $img_url ) {
			// we have the actual image size, but might need to further constrain it if content_width is narrower
			list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );
			return array( $img_url, $width, $height, $is_intermediate );
		}
		/**/
	}
}


// URL do iframe de post thumbnail. Adicionar query_arg para filtrar inputs
//add_filter('admin_post_thumbnail_html', 'custom_post_thumbnail_html');
function custom_post_thumbnail_html( $content ){
	global $post;
	
	if( $post->post_type == 'video'){
		$pre_thumb = '';
		
		$post_thumbnail = get_post_meta($post->ID, '_thumbnail_id', true);
		$video_att = get_post_meta($post->ID, 'video_att', true);
		$default_message = '';
		
		if($post_thumbnail and !is_wp_error($video_att)){
			if( $post_thumbnail == $video_att ){
				$vimeo_thumb_display = 'block';
				$wp_thumb_display = $ajax_thumb_display = 'none';
			}
			else{
				$vimeo_thumb_display = $ajax_thumb_display = 'none';
				$wp_thumb_display = 'block';
			}
		}
		else{
			$video_url = get_post_meta($post->ID, 'video_url', true);
			if( $video_url ){
				$vimeo_json = curl_get('http://vimeo.com/api/oembed.json?url=' . rawurlencode($video_url));
				if( $vimeo_json == '403 Forbidden' ){
					$default_message = "<span style='color:red;'>Não foi possível gravar automaticamente a imagem cadastrada no Vimeo. Os dados desse vídeo <strong>não estão disponíveis para acesso público</strong>.</span> Você pode configurar uma imagem personalizada no link abaixo:";
				}
				else{
					$default_message = "Não foi possível gravar a imagem diretamente do Vimeo, embora as informações do vídeo estejam públicas. Por favor tente salvar o video novamente.";
				}
			}
			else{
				$default_message = "Este vídeo usará a imagem gravada diretamente no Vimeo caso o campo <strong>Endereço do vídeo</strong> seja preenchido. Você pode configurar uma diferente clicando no link abaixo:";
			}
			$ajax_thumb_display = 'block';
			$vimeo_thumb_display = $wp_thumb_display = 'none';
		}
		
		$pre_thumb = "
					<div id='custom_thumb_msg'>
						<div id='vimeo_thumb' style='display:{$vimeo_thumb_display};' class='custom_thumb_msg'>
							<p>
								Este vídeo está usando a imagem gravada diretamente no Vimeo.
								Você pode configurar uma diferente clicando na imagem abaixo:
							</p>
						</div>
						<div id='vimeo_thumb_ajax' style='display:{$ajax_thumb_display};' class='custom_thumb_msg'>
							<p>{$default_message}</p>
						</div>
						<div id='wp_thumb' style='display:{$wp_thumb_display};' class='custom_thumb_msg'>
							<p>
								Este vídeo está usando uma imagem personalizada de exibição.
								Para exibir a imagem padrão usada no Vimeo, clique em <em>Remover imagem destacada</em>, ou
								pode escolher uma outra, diferente clicando na imagem abaixo:
							</p>
						</div>
					</div>
					";
		
		$content = str_replace('TB_iframe', 'remove_fields=true&TB_iframe', $content);
		$content = $pre_thumb . $content;
	}
	elseif( $post->post_type == 'mosaico'){
		$content = str_replace('TB_iframe', 'remove_fields=true&TB_iframe', $content);
		$pre_thumb = "Escolher uma imagem caso o conteúdo seja externo, ou não possua uma imagem pré-definida.";
		$content = $pre_thumb . $content;
	}
	else{
		$content = str_replace('TB_iframe', 'remove_fields=true&TB_iframe', $content);
	}
	return $content;
}

// URL do iframe de post thumbnail. Adicionar query_arg para filtrar inputs
add_filter('admin_post_thumbnail_html', 'custom_post_thumbail_url');
function custom_post_thumbail_url( $content ){
	$content = str_replace('TB_iframe', 'remove_fields=true&TB_iframe', $content);
	return $content;
}

/**
 * FORMATAÇÂO EXTRA DO THICKBOX
 * 
 * 1) Esconder o controle de galeria
 * 2) Formatar mensagem de special_image
 * 3) Esconder botão de editar imagem
 * 
 */
//add_action('admin_head-media-upload-popup', 'pop_media_scripts');
function pop_media_scripts(){
	echo '
<script>
jQuery(document).ready(function($){
$("#gallery-settings").hide();
});
</script>
<style type="text/css">
.special_image_message {
	background-color: #FFFFE0;
	border: 1px solid #E6DB55;
	border-radius: 3px 3px 3px 3px;
	display: inline-block;
	padding: 5px 8px;
}
.media-item-info .A1B1 p + p {
	display:none;
}
</style>
	';
}

/**
 * Adicionar variáveis ao envio de imagens em ajax
 * É preciso fazer a substituição(overwrite) do javascript original, prepareMediaItem(), adicionando na chamada as variáveis desejadas
 * 
 * @link http://wordpress.stackexchange.com/questions/33173/plupload-intergration-in-a-meta-box/34771#34771
 */
add_action('admin_print_footer_scripts', 'boros_admin_footer_scripts');
function boros_admin_footer_scripts(){
	$get_keys = array_keys( $_GET );
	$custom_args = array(
		'send_back',
		'input_name',
		'file_type',
		'force_size',
		'hide_order',
		'remove_fields',
		'link_to',
	);
	$intersect = array_intersect($custom_args, $get_keys);
	if( !empty($intersect) ){
		$args = array(
			'attachment_id' => 'serverData',
			'fetch' => 'f',
		);
		foreach( $intersect as $narg ){
			$args[$narg] = "'{$_GET[$narg]}'";
		}
		?>
<script type='text/javascript'>
// declarar função nova para updateMediaForm
if(typeof prepareMediaItem == 'function'){
	var original_prepareMediaItem = prepareMediaItem;
}
window.prepareMediaItem = function( fileObj, serverData ){
	var f = ( typeof shortform == 'undefined' ) ? 1 : 2, item = jQuery('#media-item-' + fileObj.id);

	try {
		if ( typeof topWin.tb_remove != 'undefined' )
			topWin.jQuery('#TB_overlay').click(topWin.tb_remove);
	} catch(e){}

	if ( isNaN(serverData) || !serverData ) { // Old style: Append the HTML returned by the server -- thumbnail and form inputs
		item.append(serverData);
		prepareMediaItemInit(fileObj);
	} else { // New style: server data is just the attachment ID, fetch the thumbnail and form html from the server
		item.load('async-upload.php', {<?php echo implode_with_key($args, ':', ','); ?>}, function(){prepareMediaItemInit(fileObj);updateMediaForm()});
	}
}

// declarar função nova para updateMediaForm - isso irá esconder o botão de 'salvar alterações', e desativar o sortable.
if(typeof updateMediaForm == 'function'){
	var original_updateMediaForm = updateMediaForm;
}
window.updateMediaForm = function( fileObj, serverData ){
	var items = jQuery('#media-items').children();
	// Just one file, no need for collapsible part
	if ( items.length == 1 ) {
		items.addClass('open').find('.slidetoggle').show();
	} else if ( items.length > 1 ) {
		items.removeClass('open');
	}
	jQuery('.savebutton').hide();
}

jQuery(document).ready(function($){
$( "#media-items" ).sortable( "destroy" );
$( "#media-items .menu_order" ).hide();
});
</script>
	<?php
	}
}



/**
 * Salvar imagens
 * 
 * @todo substituir por uma class
 */
function boros_save_uploaded_image( $file_info ){
	$uploads = wp_upload_dir();
	$filtered_filename = apply_filters( 'boros_save_uploaded_image_filename', $file_info['name'] );
	$filename = wp_unique_filename( $uploads['path'], $filtered_filename, $unique_filename_callback = null );
	$wp_filetype = wp_check_filetype($filename, null );
	$fullpathfilename = $uploads['path'] . "/" . $filename;
	
	$fileSaved = file_put_contents($uploads['path'] . "/" . $filename, $image_string);
	if ( !$fileSaved ) {
		throw new Exception("The file cannot be saved.");
	}
}



/**
 * Salvar imagens na biblioteca através de URL
 * Baseado no plugin 'Grab & Save' @link http://wordpress.org/extend/plugins/save-grab/
 * 
 * 
 */
class BorosSaveImageFromUrl {
	
	var $imageName;
	
	var $error;
	
	function __construct(){
		
	}
	
	function get_and_save_image( $imageurl, $post_id = 0 ) {
		if ( $imageurl ) {
			$imageurl = stripslashes($imageurl);
			$uploads = wp_upload_dir();
			$filtered_filename = apply_filters( 'boros_save_image_from_url_filename', basename($imageurl) );
			$filename = wp_unique_filename( $uploads['path'], $filtered_filename, $unique_filename_callback = null );
			
			$wp_filetype = wp_check_filetype($filename, null );
			$fullpathfilename = $uploads['path'] . "/" . $filename;
			
			try {
				if ( !substr_count($wp_filetype['type'], "image") ) {
					throw new Exception( basename($imageurl) . ' is not a valid image. ' . $wp_filetype['type']  . '' );
				}
				
				$image_string = $this->fetch_image($imageurl);
				$fileSaved = file_put_contents($uploads['path'] . "/" . $filename, $image_string);
				if ( !$fileSaved ) {
					throw new Exception("The file cannot be saved.");
				}
				
				$attachment = array(
					 'post_mime_type' => $wp_filetype['type'],
					 'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
					 'post_content' => '',
					 'post_status' => 'inherit',
					 'guid' => $uploads['url'] . "/" . $filename
				);
				$attach_id = wp_insert_attachment( $attachment, $fullpathfilename, $post_id );
				if ( !$attach_id ) {
					throw new Exception("Failed to save record into database.");
				}
				require_once(ABSPATH . "wp-admin" . '/includes/image.php');
				$attach_data = wp_generate_attachment_metadata( $attach_id, $fullpathfilename );
				wp_update_attachment_metadata( $attach_id,  $attach_data );
				
				return $attach_id;
			
			} catch (Exception $e) {
				$this->error = '<div id="message" class="error"><p>' . $e->getMessage() . '</p></div>';
				$error = new WP_Error('image_request_error', 'Ocorreu um erro na requisição da imagem.');
				$error->add_data( 'error_object', $e );
				return $error;
			}
		}
		else{
			return new WP_Error('no_imageurl', 'URL não fornecida.');
		}
	}
	
	function fetch_image($url) {
		if ( function_exists("curl_init") ) {
			return $this->curl_fetch_image($url);
		} elseif ( ini_get("allow_url_fopen") ) {
			return $this->fopen_fetch_image($url);
		}
	}
	
	function curl_fetch_image($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$image = curl_exec($ch);
		curl_close($ch);
		return $image;
	}
	
	function fopen_fetch_image($url) {
		$image = file_get_contents($url, false, $context);
		return $image;
	}
	
	function image_exists( $uri ){
		$ch = curl_init($uri);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $code == 200;
	}
	
}




