
jQuery(document).ready(function($){

	/**
	 * .plupload-upload-uic = box de upload estáticos, nas páginas de listagens
	 * #mass_add_produto_results = página de adição em lote
	 * 
	 */
    // array global para todos os pluploaders para permitir acesso aos plugins, manter sempre declarado caso sejam adicionados uploaders dinamicamente
    window.boros_uploaders = [];

	if( $(".plupload-upload-uic").length > 0 ){
        // iniciar uploaders
        $(".plupload-upload-uic").initialize_plupload();
	}
	
});

/**
 * ==================================================
 * PLUGIN INITIALIZE PL_UPLOAD ======================
 * ==================================================
 * 
 * 
 */
(function($){

    /**
     * Iniciar tradução
     * 
     * @link https://www.plupload.com/i18n/ 23 Sep, 2017
     * 
     */
    plupload.addI18n({"N\/A":"N\/D","tb":"TB","gb":"GB","mb":"MB","kb":"KB","b":"Bytes","File extension error.":"Tipo de arquivo n\u00e3o permitido.","File size error.":"Tamanho de arquivo n\u00e3o permitido.","Duplicate file error.":"Erro: Arquivo duplicado.","Init error.":"Erro ao iniciar.","HTTP Error.":"Erro HTTP.","%s specified, but cannot be found.":"M\u00e9todo de envio <b>%s<\/b> especificado, mas n\u00e3o p\u00f4de ser encontrado.","You must specify either browse_button or drop_element.":"Voc\u00ea deve especificar o bot\u00e3o para escolher(browse_button) os arquivos ou o elemento para arrastar(drop_element).","Select files":"Selecione os arquivos","Add files to the upload queue and click the start button.":"Adicione os arquivos \u00e0 fila e clique no bot\u00e3o \"Iniciar o envio\".","List":"Listagem","Thumbnails":"Miniaturas","Filename":"Nome do arquivo","Status":"Status","Size":"Tamanho","Drag files here.":"Arraste os arquivos pra c\u00e1","Add Files":"Adicionar arquivo(s)","Start Upload":"Iniciar o envio","Stop Upload":"Parar o envio","File count error.":"Erro na contagem dos arquivos","File: %s":"Arquivo: %s","File: %s, size: %d, max file size: %d":"Arquivo: %s, Tamanho: %d , Tamanho M\u00e1ximo do Arquivo: %d","%s already present in the queue.":"%s j\u00e1 presentes na fila.","Upload element accepts only %d file(s) at a time. Extra files were stripped.":"S\u00f3 s\u00e3o aceitos %d arquivos por vez. O que passou disso foi descartado.","Image format either wrong or not supported.":"Imagem em formato desconhecido ou n\u00e3o permitido.","Runtime ran out of available memory.":"M\u00e9todo de envio ficou sem mem\\u00f3ria.","Resoultion out of boundaries! <b>%s<\/b> runtime supports images only up to %wx%hpx.":"Resolu\u00e7\u00e3o fora de tamanho. O m\u00e9todo de envio <b>%s<\/b> suporta imagens com no m\u00e1ximo %wx%hpx.","Upload URL might be wrong or doesn't exist.":"URL de envio incorreta ou inexistente","Close":"Fechar","Uploaded %d\/%d files":"%d\\\/%d arquivo(s) enviados(s)","%d files queued":"%d arquivo(s)","Error: File too large:":"Erro: Arquivo muito grande:","Error: Invalid file extension:":"Erro: Extens\u00e3o de arquivo inv\u00e1lida:"});

	$.fn.extend({ 
		initialize_plupload: function(){
			return this.each(function() {
				//console.log( this );
				//console.log( $(this) );
				var pconfig        = false;
				var $this          = $(this);
				var id1            = $this.attr("id");
				var imgId          = id1.replace("plupload-upload-ui", "");
				var post_parent    = $this.find('[name="post_parent"]');
				var thumbnail_size = $this.find('[name="thumbnail_size"]');
                var submits        = $this.closest('form').find('[type=submit]');

				//plu_show_thumbs(imgId);

				/**
				 * Configurar o uploader conforme os dados do bloco atual
				 * 
				 */
				pconfig=JSON.parse(JSON.stringify(base_plupload_config));
				pconfig["browse_button"]                   = imgId + pconfig["browse_button"];
				pconfig["container"]                       = imgId + pconfig["container"];
				pconfig["drop_element"]                    = $this.find('.drop_area').attr('id'); //imgId + pconfig["drop_element"];
				pconfig["file_data_name"]                  = imgId + pconfig["file_data_name"];
				pconfig["multipart_params"]["imgid"]       = imgId;
				pconfig["multipart_params"]["post_parent"] = post_parent.val();
				pconfig["multipart_params"]["size"]        = thumbnail_size.val();
                pconfig["multipart_params"]["_ajax_nonce"] = $this.find(".ajaxnonceplu").attr("id").replace("ajaxnonceplu", "");
                pconfig['filters']['mime_types']           = [{ title : "Image files", extensions : "jpg,JPG,jpeg,JPEG,gif,png,PNG" }];
				
				/**
				 * Apenas para múltiplos
				 * 
				 */
				if($this.hasClass("plupload-upload-uic-multiple")) {
					pconfig["multi_selection"]=true;
				}
				
				/**
				 * Apenas para resize-images
				 * 
				 */
				if( $this.find(".plupload-resize").length > 0 ){
					var w = parseInt($this.find(".plupload-width").attr("id").replace("plupload-width", ""));
					var h = parseInt($this.find(".plupload-height").attr("id").replace("plupload-height", ""));
					pconfig["resize"] = {
						width   : w,
						height  : h,
						quality : 90
					};
				}

                // instanciar o uploader
				var uploader = new plupload.Uploader(pconfig);
                window.boros_uploaders.push( uploader );
				
				/**
				 * Uploader iniciado!
				 * 
				 */
				uploader.bind('Init', function(up, params){
					
				});
				
				// iniciar o uploader
                uploader.init();
                
                /**
                 * Tratar erros
                 * 
                 */
                uploader.bind('Error', function(up, err) {
                    var limit = base_plupload_config.max_file_size;
                    if( err.message == 'Tamanho de arquivo não permitido.' ){
                        err.message = 'Tamanho de arquivo acima do limite, por favor reduza o arquivo ou envie outro menor.';
                    }
                    alert(err.message);
                });

				/**
				 * Arquivo adicionado à fila. Nesse caso os uploads são iniciados imediatamente em up.start(), após o drop.
				 * 
				 */
				uploader.bind('FilesAdded', function(up, files){
					// esconder imagem antiga
					$this.find('.plupload-thumbs').slideUp('fast', function(){
                        // impedir submit do form parent
                        submits.prop('disabled', true).css('opacity', 0.5);
						// adicionar arquivos na fila e iniciar upload
						$this.find('.filelist').empty().show();
						$.each(files, function(i, file) {
							$this.find('.filelist').append(
								'<div class="file" id="' + file.id + '"><b>' +
								file.name + '</b> (<span>' + plupload.formatSize(0) + '</span>/' + plupload.formatSize(file.size) + ') ' +
								'<div class="fileprogress"></div></div>');
                        });
						up.refresh();
						up.start();
					});
				});
				
				/**
				 * Barra de progresso
				 * 
				 */
				uploader.bind('UploadProgress', function(up, file) {
					$('#' + file.id + " .fileprogress").width(file.percent + "%");
					$('#' + file.id + " span").html(plupload.formatSize(parseInt(file.size * file.percent / 100)));
				});

				/**
				 * Upload completo: resposta da url que recebeu o arquivo e processa os dados. Embora a requisição tenha sido completada, o upload e salvamento 
				 * do arquivo poderá ter falhado em algum momento, e a url enviará uma mensagem de erro.
				 * 
				 */
				uploader.bind('FileUploaded', function(up, file, server_resp) {
					//console.log(up);
					//console.log(file);
                    //console.log( server_resp );

                    var json = borosTryParseJSON(server_resp.response);
                    console.log(json);

                    if( json != false ){
                        if( json.success == true ){
                            $this.find('.filelist').slideUp('fast', function(){
                                console.log($("#" + imgId + "plupload-thumbs"));
                                $("#" + imgId + "plupload-thumbs").html( json.data.html ).slideDown();
                            });
                        }
                        else{
                            alert( json.data.message );
                        }
                    }
                    else{
                        alert( 'O servidor não conseguiu processar a imagem, por favor tente novamente' );
                    }

                    // re-habilitar submit do form parent
                    submits.prop('disabled', false).css('opacity', 1);
				});
				
				uploader.bind('Refresh', function(up){
					//console.log('Refresh');
				});

				/**
				 * Botão de remoção de imagem
				 * 
				 */
				$this.find('.drop_upload_image_view').delegate('.drop_upload_image_remove .btn', 'click', function( event ){
					var $button = $(this);
					var $view = $button.closest('.drop_upload_image_view');
					var data = {
						action: 'boros_drop_upload_remove',
						post_id: post_parent.val(),
					};
					
					//console.log(data);
					$button.addClass('loading');
					
					// remover o post_meta _thumbnail_id do post_parent
					$.post(ajaxurl, data, function(response){
						// remover imagem da página
						$button.closest('.plupload-upload-uic').find('.drop_upload_image_view').slideUp(500, function(){
							// o css() e o delay() é necessário para que o html seja atualizado dentra da view e possa ocorrer a animação com a altura correta
							$(this).html(response).delay(10).slideDown(500);
						});
						
						// esconder loading
						$button.removeClass('.waiting');
						$button.parent().hide();
					});
				});
			});
		}
	});
})(jQuery);

/**
 * Verificar se a string é um JSON válido, em caso de banco de dados offline, onde será retornado uma página html comum
 * 
 * @link https://stackoverflow.com/a/20392392
 * 
 */
function borosTryParseJSON(jsonString){
    try {
        var o = JSON.parse(jsonString);
        // Handle non-exception-throwing cases:
        // Neither JSON.parse(false) or JSON.parse(1234) throw errors, hence the type-checking,
        // but... JSON.parse(null) returns null, and typeof null === "object", 
        // so we must check for that, too. Thankfully, null is falsey, so this suffices:
        if (o && typeof o === "object") {
            return o;
        }
    }
    catch (e) { }

    return false;
};

