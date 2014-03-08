<?php
/**
 * FORM ELEMENT: WP IMAGE
 * Utilizar o WordPress 3.5 Media Uploader parao controle de imagem simples(uma imagem)
 * 
 * 
 */
class BFE_wp_image extends BorosFormElement {
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
		'js' => 'wp_image',
	);
	
	/**
	 * Opções deste controle:
	 * 
	 * $image_size    - sizes cadastrados: thumbnail, medium, large, full, custom
	 * $width         - largura máxima da imagem - será aplicado via style="width:100px;"
	 * $default_image - caminho da imagem padrão caso deixe vazio - ATENÇÃO: essa imagem só serve com o propósito de exibição no admin, não será gravado o valor dela para o campo, que será considerdo vazio
	 * $layout        - modelo de exibição: large(imagem em cima, botões em baixo), row(imagem à esquerda, botões ao lado), grid(imagem em cima, botões icones em baixo, caixa pequena com float)
	 */
	function add_defaults(){
		$options = array(
			'image_size'    => 'thumbnail',
			'width'         => 100,
			'default_image' => false,
			'layout'        => 'row',
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
	 * 
	 */
	function set_input( $value = null ){
		$input_name = $this->data['name'];
		$actions_class = 'has_no_images';
		$send_string = 'Enviar uma imagem';
		$link_tab = 'library';
		
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
		
		// começar a guardar o output do script js em buffer
		ob_start();
		
		$input = ob_get_contents();
		pre($this->data);
		?>
		<div class="wp_image">
			<div class="wp_image_view"></div>
			<div class="wp_image_controls">
				<a href="#" class="btn_item_new"><?php echo $send_string; ?></a> <span class="separator">&nbsp; &nbsp;</span>
				<a href="#" class="btn_item_select">Escolher entre as existentes</a>
			</div>
		</div>
		<?php
		
		// guardar o output em variável
		$input = ob_get_contents();
		ob_end_clean();
		return $input;
	}
}




