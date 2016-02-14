<?php
/**
 * ==================================================
 * MEDIA UPLOAD CONTROLS AND HELPERS ================
 * ==================================================
 * Functions e controles para o upload de arquivos
 * 
 * 
 * @link http://www.krishnakantsharma.com/2012/01/image-uploads-on-wordpress-admin-screens-using-jquery-and-new-plupload/
 * @link http://designmodo.com/create-upload-form/
 */



/**
 * ADMIN ENQUEUE SCRIPTS
 * Adicionar scripts e css necessários
 * 
 * Adicionar os slugs das páginas de admin no filtro
 * 
 */
add_action( 'admin_enqueue_scripts', 'boros_upload_admin_pages_enqueues' );
function boros_upload_admin_pages_enqueues( $hook ){
	//pal($hook);
	$media_pages = apply_filters('boros_upload_admin_pages_enqueues', array());
	
	if( !in_array( $hook, $media_pages ) ){
		return;
	}
	
	wp_enqueue_script( 'plupload-handlers' );
	wp_enqueue_script( 'upload', BOROS_JS . 'upload.js', array('jquery') );
	wp_enqueue_style( 'upload', BOROS_CSS . 'upload.css' );
}



/**
 * ADMIN HEAD
 * Adicionar um modelo padrão para as configs do plupload. Por questões de performance, preferir fazer a adição contextual desta varoável.
 * 
 * Adicionar no hook da página de edição, modelos:

add_action( 'admin_head-edit.php', 'boros_upload_admin_head' );
add_action( 'admin_head-product_page_mass_add_produto', 'boros_upload_admin_head' );
add_action( 'admin_head-{admin_page_slug}', 'boros_upload_admin_head' );

 * 
 */
function boros_upload_admin_head(){
	global $post;
	
	// place js config array for plupload
	$plupload_init = array(
		'runtimes' => 'html5,silverlight,flash,html4',
		'browse_button' => 'plupload-browse-button', // will be adjusted per uploader
		'container' => 'plupload-upload-ui', // will be adjusted per uploader
		'drop_element' => 'drop_area', // will be adjusted per uploader
		'file_data_name' => 'quick_upload', // will be adjusted per uploader
		'multiple_queues' => true,
		'max_file_size' => wp_max_upload_size() . 'b',
		'url' => admin_url('admin-ajax.php'),
		'flash_swf_url' => includes_url('js/plupload/plupload.flash.swf'),
		'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
		'filters' => array(array('title' => __('Allowed Files'), 'extensions' => '*')),
		'multipart' => true,
		'urlstream_upload' => true,
		'multi_selection' => false, // will be added per uploader
		 // additional post data to send to our ajax hook
		'multipart_params' => array(
			'_ajax_nonce' => '', // will be added per uploader
			'action' => 'boros_drop_upload_add', // the ajax action name
			'imgid' => 0, // will be added per uploader
			'post_parent' => 0 // post para quem adicionar a imagem e também o _thumbnail_id
		)
	);
?>
<script type="text/javascript">
var base_plupload_config = <?php echo json_encode($plupload_init); ?>;
</script>
<?php
}



/**
 * RETORNO DO AJAX :: ADICIONAR IMAGEM
 * 
 * 
 */
add_action( 'wp_ajax_boros_drop_upload_add', 'boros_drop_upload_add_ajax' );
function boros_drop_upload_add_ajax() {
	// check ajax nonce
	$imgid = $_POST["imgid"];
	check_ajax_referer($imgid . 'pluploadan');
	$tmp = new MediaUpload;
	$attachment = $tmp->saveUpload( $field_name = "{$imgid}quick_upload", $post_parent = $_POST['post_parent'] );
	//pre($attachment);
	update_post_meta( $_POST['post_parent'], '_thumbnail_id', $attachment['attachment_id'] );
	$img = wp_get_attachment_image_src( $attachment['attachment_id'], 'thumbnail' );
	echo "<div class='drop_upload_image'><img src='{$img[0]}' alt='' class='the_post_thumbnail' /><div class='hide-if-no-js drop_upload_image_remove'><span class='btn' title='Remover esta imagem'>&nbsp;</span></div></div>";
	
	exit;
}



/**
 * RETORNO DO AJAX :: REMOVER IMAGEM
 * 
 * 
 */
add_action( 'wp_ajax_boros_drop_upload_remove', 'boros_drop_upload_remove_ajax' );
function boros_drop_upload_remove_ajax() {
	$post_id = (int)$_POST['post_id'];
	delete_post_meta( $post_id, '_thumbnail_id' );
	echo '<p class="drag-drop-info"><small>ou</small><br /> Solte a imagem aqui</p>';
	exit;
}



/**
 * RENDERIZAÇÃO DO CONTROLE
 * 
 * 
 * @todo o loop para exibir multiplos arquivos com botão de remoção não está feito!!!
 */
add_action( 'manage_posts_custom_column', 'boros_post_media_column_render' );
add_action( 'manage_pages_custom_column', 'boros_post_media_column_render' );
function boros_post_media_column_render( $column_name ){
	global $post;
	
	//pal($post->ID);
	if( $column_name == 'post_thumbnail_drop' ){
		//wp_enqueue_script('plupload-handlers');
		boros_drop_upload_box( $post );
	}
}



/**
 * ==================================================
 * OUTPUT BOX UPLOAD ================================
 * ==================================================
 * Usado pela coluna e demais controles meta_box e admin_page
 * 
 */
function boros_drop_upload_box( $post ){
	// adjust values here
	$id = "img{$post->ID}"; // this will be the name of form field. Image url(s) will be submitted in $_POST using this key. So if $id == “img1” then $_POST[“img1”] will have all the image urls
	$svalue = ''; // this will be initial value of the above form field. Image urls.
	$multiple = false; // allow multiple files upload
	$width = null; // If you want to automatically resize all uploaded images then provide width here (in pixels)
	$height = null; // If you want to automatically resize all uploaded images then provide height here (in pixels)
	?>
	<div class="plupload-upload-uic hide-if-no-js <?php if ($multiple): ?>plupload-upload-uic-multiple<?php endif; ?>" id="<?php echo $id; ?>plupload-upload-ui">
		<input type="hidden" name="post_parent" value="<?php echo $post->ID; ?>" disabled="disabled" />
		<?php if ($width && $height): ?>
				<span class="plupload-resize"></span><span class="plupload-width" id="plupload-width<?php echo $width; ?>"></span>
				<span class="plupload-height" id="plupload-height<?php echo $height; ?>"></span>
		<?php endif; ?>
		<div class="drop_area" id="drop_area_<?php echo $id; ?>">
			<input id="<?php echo $id; ?>plupload-browse-button" type="button" value="Selecionar imagem" class="button button_select_files" />
			<span class="ajaxnonceplu" id="ajaxnonceplu<?php echo wp_create_nonce($id . 'pluploadan'); ?>"></span>
			<div class="filelist"></div>
			<div class="plupload-thumbs drop_upload_image_view <?php if ($multiple): ?>plupload-thumbs-multiple<?php endif; ?>" id="<?php echo $id; ?>plupload-thumbs">
				<?php if ( '' != get_the_post_thumbnail($post->ID, 'thumbnail') ){ ?>
				<div class="drop_upload_image">
					<?php the_post_thumbnail('thumbnail', array('class' => 'the_post_thumbnail')); ?>
					<div class="hide-if-no-js drop_upload_image_remove"><span class="btn" title="Remover esta imagem">&nbsp;</span></div>
				</div>
				<?php } else { ?>
				<p class="drag-drop-info"><small>ou</small><br /> Solte a imagem aqui</p>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php
}



/**
 * CLASSE DE MANIPULAÇÃO DO UPLOAD
 * 
 * 
 * A series of related methods for managing file uploads within
 * WordPress.
 * Permissions NOT handled here!
 *
 * @author Zane M. Kolnik zanematthew[at]gmail[dot]com
 * @link https://github.com/zanematthew/zm-upload
 */
Class MediaUpload {

	public $upload_dir;
	private $attachment_id;

	public function __construct(){
		$this->upload_dir = wp_upload_dir();
		if ( is_admin() )
			add_action( 'post_edit_form_tag' , array( &$this, 'addEnctype' ) );
	}

	/**
	 * Handles the saving, i.e. creates a post type of attachment.
	 *
	 * During form submission run the method:
	 * $class->fileUpload( $field_name='form_field_name' );
	 *
	 * @return $final_file An array of array of f*cking cool stuff
	 * I guess if you think arrays are cool i like (*)(*)s
	 * $final_file['attachment_id'] = $this->attachment_id;
	 * $final_file['file'] = $uploaded_file['file'];
	 * $final_file['file_info'] = $file_info[];
	 */
	public function saveUpload( $field_name = null, $post_parent = 0, $user_id = null ) {

		if ( is_null( $field_name ) )
			die('Need field_name');

		// Move the file to the uploads directory, returns an array
		// of information from $_FILES
		//pre($_FILES[ $field_name ]);
		$uploaded_file = $this->handleUpload( $_FILES[ $field_name ] );
		
		//pre($uploaded_file, 'uploaded_file');

		if ( ! isset( $uploaded_file['file'] ) )
			return false;

		// If we were to have a unique user account for uploading
		if ( is_null( $user_id ) ) {
			$current_user = wp_get_current_user();
			$user_id = $current_user->ID;
		}

		// Build the Global Unique Identifier
		$guid = $this->buildGuid( $uploaded_file['file'] );

		// Build our array of data to be inserted as a post
		$attachment = array(
			'post_mime_type' => $_FILES[ $field_name ]['type'],
			'guid' => $guid,
			'post_title' => 'Uploaded : ' . $this->mediaTitle( $uploaded_file['file'] ),
			'post_content' => '',
			'post_author' => $user_id,
			'post_status' => 'inherit',
			'post_date' => date( 'Y-m-d H:i:s' ),
			'post_date_gmt' => date( 'Y-m-d H:i:s' ),
			'post_parent' => $post_parent,
		);

		// Add the file to the media library and generate thumbnail.
		$this->attachment_id = wp_insert_attachment( $attachment, $uploaded_file['file'] );

		// @todo bug, this does NOT work when used in a PLUGIN!, so you'll have to make
		// your OWN thumbnail sizes!
		require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
		$meta = wp_generate_attachment_metadata( $this->attachment_id, $uploaded_file['file'] );

		$image_meta = wp_read_image_metadata( $uploaded_file['file'] );
		$meta['image_meta'] = $image_meta;

		wp_update_attachment_metadata( $this->attachment_id, $meta );

		$file_info = pathinfo( $uploaded_file['file'] );

		// Set the feedback flag to false, since the upload was successful
		$upload_feedback = false;

		$final_file = array();
		$final_file['attachment_id'] = $this->attachment_id;
		$final_file['file'] = $uploaded_file['file'];
		$final_file['file_info'] = $file_info;

		return $final_file;
	}

	/**
	 * Do some set-up before calling the wp_handle_upload function
	 */
	public function handleUpload( $file=array() ){
		require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
		return wp_handle_upload( $file, array( 'test_form' => false ), date('Y/m') );
	}

	/**
	 * Builds the GUID for a given file from the media library
	 * @param full/path/to/file.jpg
	 * @return guid
	 */
	public function buildGuid( $file=null ){
		// $wp_upload_dir = wp_upload_dir();
		return $this->upload_dir['baseurl'] . '/' . _wp_relative_upload_path( $file );
	}

	/**
	 * Parse the title of the media based on the file name
	 * @return title
	 */
	public function mediaTitle( $file ){
		return addslashes( preg_replace('/\.[^.]+$/', '', basename( $file ) ) );
	}

	/**
	 * Adds the enctype for file upload, used with the hook
	 * post_edit_form_tag for adding uploader to post meta
	 */
	public function addEnctype(){
		echo ' enctype="multipart/form-data"';
	}
}







