/**
 * ADMIN SCRIPTS: UPLOAD
 * Funções auxiliares de upload, aproveitando a interface core.
 * O thickbox está sendo requisitado em todas as páginas do admin. É ele quem chama os iframes de upload. Por padrão, os retornos dos thickboxes 
 * são ativados pela chamada da função send_to_editor(), que sempre terá como target o editor de texto principal. Para contornar isso, a função original
 * é atribuída em uma nova função, original_send_to_editor(h) e uma nova função para trabalhar com os inputs personalizados será new_send_to_editor(h), 
 * e ambas serão encapsuladas em uma função que sobrescreverá a original ( com window.send_to_editor = function(h)... ), filtrando qual delas será ativada 
 * com base na variável 'editor_upload'.
 * O campo personalizado é identificado por 'input_index', que receberá os dados enviados pelo thickbox de media.
 * 
 * 
 * 
 * Arquivos relacionados:
 * 
 * 
 * 
 * 
 */

jQuery(document).ready(function($){
	
	/* 
	 * FUNÇÕES DE UPLOAD
	 *
	 * variável que armazena qual input deve ser populado pelo input text image url
	 * essa variável precisa ser global
	 */
	input_index = "";
	
	/*
	 * Recebe o valor do iframe de mídia, filtra a tag recebida(<img>) e insere apenas o src no campo definido em 'input_index',
	 * definido no clique do mesmo botão que abre o thickbox. Por alguma razão essa chamada não pode ser feita dentro do jquery, sendo preciso usar jQuery('#')
	 * 
	 * '$(".thickbox").click' no script do <head>, abre o modal para upload/seleção de imagem E define 'editor_upload' como false.
	 * 
	 */
	
	// declarar a função de override do send_to_editor
	function new_send_to_editor(h){
		
		
		/**
		 * TEMP
		 * TODO: corrigir bugs para os filtros
		 */
		/* Filtrar valor de 'h' e pegar apenas o src do elemento.
		Foi preciso criar um elemento para inserir o conteúdo de 'h' e fazer a busca, já que este pode ser uma imagem ou um link + img. */
		var OuterDiv = $('<div></div>');
		$(OuterDiv).append(h);
		//tentar identificar um src
		img_src = $(OuterDiv).find('img:first').attr('src');
		if( img_src ){
			h = img_src;
		}
		//tentar identificar um href
		a_href = $(OuterDiv).find('a:first').attr('href');
		if( a_href ){
			h = a_href;
		}
		
		
		
		
		
		
		
		
		
		/* 
		 * Os antigos filtros de jquery foram removidos, supondo que os filtros do wordpress já irão enviar para o send_to_editor
		 * o conteúdo correto.
		 */
		
		// inserir o valor filtrado no campo definido em 'input_index'
		$('#' + input_index).val(h);
		update_image_upload_preview( $('#' + input_index) );
		
		// atualizar imagem de preview, se houver
		//var img_preview = $('#' + input_index).closest('.boros_element_image_url').find('img.uploaded_image');
		//if( img_preview.length > 0 ){
		//	img_preview.attr('src', h).slideDown();
		//}
		
		tb_remove(); // remover o thickbox - função original sem clone
		editor_upload = true; // retornar o estado padrão para enviar os anexos ao editor de texto
	}
	function update_image_upload_preview( obj ){
		var href = $(obj).val();
		var img_preview = $(obj).closest('.boros_element_image_url').find('img.uploaded_image');
		if( $(obj).val() ){
			img_preview.attr('src', href).slideDown();
		}
		else{
			img_preview.slideUp();
		}
	}
	
	// definir o status padrão de send_do_editor
	editor_upload = true;
	
	if( $('.upload_button').length > 0 ){ // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> MUDAR PARA .upload_button apenas
		// armazenar a função send_to_editor() original
		if(typeof send_to_editor == 'function'){
			//console.log('new send_to_editor');
			var original_send_to_editor = send_to_editor;
		}
		else{
			var original_send_to_editor = function(){};
			//console.log('send_to_editor');
		}
		
		// declarar função nova
		window.send_to_editor = function(h){
			if( editor_upload == false ){
				//console.log('ativando new_send_to_editor');
				new_send_to_editor(h); // chamando o original
			}
			else{
				//console.log('ativando original_send_to_editor');
				original_send_to_editor(h);
			}
		}
		
		// atualizar a imagem caso seja alterado o input diretamente por digitação/colagem.
		$('.image_url_text').blur(function(){
			update_image_upload_preview( $(this) );
		});
	}
	
	
	
	// write_page_functions javascript
	/* 
	 * ==========================================================================
	 * INPUT TEXT IMAGE URL
	 * ==========================================================================
	 * 
	 * Associar corretamente a variável global 'input_index'(declarada no corpo do HTML) ao input text desejado.
	 * Este mesmo clique abre o thickbox, que já foi instanciado pelo plugin thickbox aos '.thickbox'.
	 */
	// instanciar thickbox
	$(".upload_button").click(function(event){
		input_index = $(this).attr('rel');
		
		/*
		 * Desabilitar o envio de imagens ao editor(s) de texto
		 * O fechamento do lightboxs será verificado pelo novo tb_remove instanciado logo abaixo, após a chamada original_tb_remove();
		 */
		editor_upload = false;
	});
	
});