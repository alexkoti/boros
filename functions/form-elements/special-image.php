<?php
/**
 * FORM ELEMENT: SPECIAL IMAGE
 * 
 * 
 * @link http://wordpress.stackexchange.com/questions/4307/how-can-i-add-an-image-upload-field-directly-to-a-custom-write-panel/4413#4413
 * 
 * 
 * @BUG ATENÇÃO >>>>>> Quando o metabox de custom_fields padrão está presente na página(mesmo escondido) pode ocorrer da imagem ser resetada para o estado anterior. Detalhes:
 * 					 - 3 campos de special_image: _thumbnail_id, primary_image, secondary_image
 * 					 - metabox de custom fields presente na página
 * 					 - ao modificar uma das imagens, o valor no input:hidden é modificado corretamente, o retorno do ajax, special_image_swap() é corretamente executado, modificando o valor no banco.
 * 					 - ao salvar a página, o 'secondary_image' é resetado para o valor anterior, precisamente o que se mantém no custom_field. Os coutros campos são modificados corretamente.
 * 					 - ao remover o metabos de custom_field, o campo problemático é salvo corretamente.
 *
 * @todo adicionar o envio sem js,agora só aceita os retornos de thickbox
 * @todo possibilitar mais options: descrição, formato do thumb(wp size:thumb, medium, large,custom), tamanho máximo da imagem(css style), imagem default, layout(botões embaixo, botões do lado)
 * @todo verificar esse controle em admin pages
 */
class BFE_special_image extends BorosFormElement {
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'disabled' => false,
		'readonly' => false,
	);
	
	var $enqueues = array(
		'js'  => 'special-image',
		'css' => 'special-image',
	);
	
	/**
	 * Opções deste controle:
	 * 
	 * $image_size 	- sizes cadastrados: thumbnail, medium, large, full, custom
	 * $width 	- largura máxima da imagem - será aplicado via style="width:100px;"
	 * $default_image 	- caminho da imagem padrão caso deixe vazio - ATENÇÃO: essa imagem só serve com o propósito de exibição no admin, não será gravado o valor dela para o campo, que será considerdo vazio
	 * $layout 		- modelo de exibição: large(imagem em cima, botões em baixo), row(imagem à esquerda, botões ao lado), grid(imagem em cima, botões icones em baixo, caixa pequena com float)
	 */
	function add_defaults(){
		$options = array(
			'image_size'    => 'thumbnail',
			'width'         => 100,
			'layout'        => 'row',
			'default_image' => false,
		);
		$this->defaults['options'] = $options;
		//$this->defaults['attr']['dataset']['image_size'] = 'lala';
	}
	
	/**
	 * Setar os datasets necessários
	 * 
	 */
	function filter_data(){
		$this->data['attr']['dataset']['image_size'] = $this->defaults['options']['image_size'];
	}
	
	function set_label(){
		if( !empty($this->data['label']) )
			$this->label = "<span class='non_click_label'>{$this->data['label']}{$this->label_helper}</span>";
	}
	
	/**
	 * 
	 * @todo IMPORTANTE: rever código para usar $this->attrs
	 */
	function set_input( $value = null ){
		$input_name = $this->data['name'];
		$actions_class = 'has_no_images';
		$send_string = 'Enviar uma imagem';
		$link_tab = 'library';
		
		// começar a guardar o output do script js em buffer
		ob_start();
		
		// Verificar se o post já possui alguma imagem anexada. Caso positivo, será exibido o botão de selecionar entre as existentes e mudar o texto link de adcionar imagens
		if( isset($this->context['post_id']) and $this->context['post_id'] != 0 ){
			$args = array(
				'post_parent' => $this->context['post_id'],
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
			);
			$images = get_children( $args );
			if( $images ){
				$actions_class = 'has_images';
				$send_string = 'Enviar outra imagem';
				$link_tab = 'gallery';
			}
		}
		//pre($this->data['options']);
		//pre($this->data['attr']);
		//pre($this->context);
		?>
		<div class="special_image <?php echo "special_image_layout_{$this->data['options']['layout']}"; ?>" id="<?php echo "special_image_{$input_name}"; ?>">
			<?php
				// pegar a largura do image_size escolhido
				global $_wp_additional_image_sizes;
				$core_sizes = array('thumbnail', 'medium', 'large', 'full');
				if( in_array( $this->data['options']['image_size'], $core_sizes ) )
					$image_size_w = get_option( "{$this->data['options']['image_size']}_size_w" );
				else{
					$image_size_w = $_wp_additional_image_sizes[ $this->data['options']['image_size'] ]['width'];
				}
				
				// usar medidas do 'image_size' caso o width não tenha sido definido
				if( $this->data['options']['width'] == false ){
					$view_width = $image_size_w + 2;
					$view_style = "style='width:{$view_width}px;'";
					$margin = $image_size_w + 10;
					$actions_width = "style='margin-left:{$margin}px;'";
				}
				else{
					$view_width = $this->data['options']['width'] + 2;
					$view_style = "style='width:{$view_width}px;'";
					$margin = $this->data['options']['width'] + 10;
					$actions_width = "style='margin-left:{$margin}px;'";
				}
				$actions_width = '';
				
				$attrs = $this->make_attributes($this->data['attr']);
				echo "<input type='hidden' value='{$this->data_value}' {$attrs} />";
				
				// Existe imagem padrão?
				//$default_image = ( $this->data['options']['default_image'] != false ) ? " data-default_image='{$this->data['options']['default_image']}'" : '';
				$dataset = $this->make_attributes($this->data['options'], 'data-');
			?>
			<div class="special_image_view" <?php echo $dataset; ?>>
				<?php
				//pre($this->data_value);
				$this->special_image_load( $this->data['name'], $this->data_value, $this->data['options'] );
				?>
			</div>
			
			<?php
			$post_id = ( isset($this->context['post_id']) ) ? $this->context['post_id'] : 0;
			
			$link_args = array();
			$link_args['post_id'] = $post_id;
			$link_args['send_back'] = 'id';
			$link_args['file_type'] = 'image';
			$link_args['input_name'] = $input_name;
			$link_args['hide_order'] = 1;
			$link_args['TB_iframe'] = 1;
			$new_link = add_query_arg( $link_args, 'media-upload.php' );
			
			$link_args = array();
			$link_args['post_id'] = $post_id;
			$link_args['tab'] = $link_tab;
			$link_args['send_back'] = 'id';
			$link_args['file_type'] = 'image';
			$link_args['input_name'] = $input_name;
			$link_args['hide_order'] = 1;
			$link_args['TB_iframe'] = 1;
			$select_link = add_query_arg( $link_args, 'media-upload.php' );
			
			/**
			 * Este dataset será aplicado aos links .thickbox
			 * 
			 */
			$dataset_args = array(
				'input' => $input_name,
				'callback' => 'boros_special_image',
			);
			$dataset = $this->make_attributes($dataset_args, 'data-');
			
			/**
			 * Actions buttons
			 * 
			 */
			?>
			<div class="special_image_actions hide-if-no-js <?php echo $actions_class; ?>" <?php echo $actions_width; ?>>
				<a class="thickbox btn-action btn-action-new" href="<?php echo $new_link; ?>" <?php echo $dataset;?>><?php echo $send_string; ?></a> <span class="separator">&nbsp; &nbsp;</span>
				<a class="thickbox btn-action btn-action-select" href="<?php echo $select_link; ?>" <?php echo $dataset;?>>Escolher entre as existentes</a>
			</div>
			<?php echo $this->input_helper; ?>
		</div>
		<?php
		// guardar o output em variável
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
    
    /**
     * Bloco com a imagem escolhida + botão de remover
     * 
     */
    function special_image_load( $name, $attch_id, $opts ){
        $defaults = array(
            'image_size' 	=> 'thumbnail',
            'width' 		=> 100,
            'default_image' => false,
            'layout' 		=> 'row',
        );
        $options = boros_parse_args( $defaults, $opts );
        //pre($opts, 'opts');
        //pre($options, 'options');
        
        // existe uma imagem
        if( !empty($attch_id) ){
            $image = wp_get_attachment_image_src($attch_id, $options['image_size']);
            
            if( !empty($image) ){
                // caso seja false, usar a largura real da imagem
                if( $options['width'] == false ){
                    $image_dimensions = "width='{$image[1]}' height='{$image[2]}'";
                    $holder_style = "style='width:{$image[1]}px;height:{$image[2]}px'";
                }
                // calcular a altura proporcinalmente à largura
                else{
                    $ratio = $image[2]/ $image[1];
                    $height = $options['width'] * $ratio;
                    $image_dimensions = "width='{$options['width']}' height='{$height}'";
                    $holder_style = "style='width:{$options['width']}px;height:{$height}px'";
                }
                $image_url = $image[0];
                $remove_buttom = "<div class='hide-if-no-js special_img_remove'><span class='btn' title='Remover esta imagem'>&nbsp;</span></div>";
            }
        }
        // imagem padrão
        else{
            if( $options['width'] == false ){
                $image_dimensions = "width='100'";
                $holder_style = "style='width:100px;'";
            }
            else{
                $image_dimensions = "width='{$options['width']}'";
                $holder_style = "style='width:{$options['width']}px;'";
            }
            $image_url = $options['default_image'];
            $remove_buttom = '';
        }
        
        // apenas exibir a view caso exista um attch ou default_image
        if( !empty($attch_id) or $options['default_image'] != false ){
            echo "
                <div class='special_image_img' {$holder_style}>
                    <img src='{$image_url}' id='special_image_{$attch_id}' alt='' {$image_dimensions} />
                    {$remove_buttom}
                </div>
            ";
        }
    }
    
    function ajax(){
        if( !isset($_POST['task']) ){
            die('Task não definida');
        }
        
        if( $_POST['task'] == 'swap' ){
            self::image_swap();
        }
        elseif( $_POST['task'] == 'remove' ){
            self::image_remove();
        }
    }
    
    /**
     * Recarregar o thumbnal após trocar de imagem
     * 
     * @todo adicionar contextos para termmeta e usermeta
     * @todo criar função verificadora de 'valid_post_metas'
     */
    function image_swap(){
        //pal('special_image_swap');
        $action  = $_POST['action'];
        $context = $_POST['context'];
        $value   = $_POST['value'];
        
        // carregar config do elemento
        $elem = load_element_config( $context );
        
        // salvar caso não pertença a um duplicate
        if( isset($context['in_duplicate_group']) and $context['in_duplicate_group'] == false ){
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
        $options = isset($elem['options']) ? $elem['options'] : false;
        self::special_image_load( $context['name'], $value, $options );
        die();
    }

    /**
     * Remover a imagem e o post_meta correspondente
     * 
     * 
     */
    function image_remove(){
        $context = $_POST['context'];
        $options = isset($_POST['options']) ? $_POST['options'] : array();
        
        self::special_image_load( $context['name'], 0, $options );
        
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
}

/**
 * Adicionar tab apenas de imagens
 * 
 * 
 */
//add_filter( 'media_upload_tabs', 'special_images_tab' );
function special_images_tab( $tabs ){
	$new_tab['special_images'] = 'Imagens';
	return array_merge( $tabs, $new_tab );
}
/**
 * Conteúdo da aba
 * 
 * @action "media_upload_{$tab_slug}" - wp-admin/media-upload.php
 */
//add_action( 'media_upload_special_images', 'boros_special_images_iframe' );
function add_boros_special_images_iframe(){ wp_iframe( 'boros_special_images_iframe'); }
function boros_special_images_iframe(){
	media_upload_header();
	echo 'Custom tab';
}



/**
 * Adcionar um box de diálogo para o form de atualização ajax
 * 
 */
//add_action( 'admin_footer', 'ajax_upload_div' );
function ajax_upload_div(){
	?>
	<div id="ajax_upload_div" style="display:none;">
		<form action="<?php echo admin_url('admin-ajax.php'); ?>">
			<input type="hidden" name="action" value="special_image_ajax" />
			<input type="hidden" name="post_ID" value="0" />
			<input type="hidden" name="existing_image_id" value="0" />
			<input type="hidden" name="input_name" value="0" />
			<input type="hidden" name="special_image_flag" value="1" />
			
			<p><input type="file" name="0" class='special_image_file_input' size="20" /></p>
			<p class="txt_center"><input type="submit" value="Enviar imagem" class="button" /></p>
		</form>
		<div id="ajax_upload_div_loading"></div>
	</div>
	<div id="ajax_select_image"></div>
	<?php
}

/**
 * Carregar imagens já enviadas ao post
 * 
 * 
 */
add_action('wp_ajax_special_image_attchs', 'special_image_attchs');
function special_image_attchs(){
    pal('special_image_attchs');
	$parent = (int)$_GET['parent'];
	
	$args = array(
		'post_parent' => $parent,
		'post_type' => 'attachment',
		'post_mime_type' => 'image',
	);
	$images = get_children( $args );
	
	if( $images ){
		echo '<ul class="image_attchs_itens">';
		foreach( $images as $image ){
			$img = wp_get_attachment_image_src( $image->ID, 'thumbnail' );
			echo "<li><img src='{$img[0]}' alt='' rel='{$image->ID}' />{$image->post_title}</li>";
		}
		echo '</ul>';
	}
	
	die();
}

