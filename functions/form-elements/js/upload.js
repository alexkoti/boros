
jQuery(document).ready(function($){
	/**
	 * .plupload-upload-uic = box de upload estáticos, nas páginas de listagens
	 * #mass_add_produto_results = página de adição em lote
	 * 
	 */
	if( $(".plupload-upload-uic").length > 0 ){
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
	$.fn.extend({ 
		initialize_plupload: function(){
			return this.each(function() {
				//console.log( this );
				//console.log( $(this) );
				var pconfig = false;
				var $this = $(this);
				var id1 = $this.attr("id");
				var imgId = id1.replace("plupload-upload-ui", "");
				var post_parent = $this.find('[name = "post_parent"]');

				//plu_show_thumbs(imgId);

				/**
				 * Configurar o uploader conforme os dados do bloco atual
				 * 
				 */
				pconfig=JSON.parse(JSON.stringify(base_plupload_config));
				pconfig["browse_button"] = imgId + pconfig["browse_button"];
				pconfig["container"] = imgId + pconfig["container"];
				pconfig["drop_element"] = $this.find('.drop_area').attr('id'); //imgId + pconfig["drop_element"];
				pconfig["file_data_name"] = imgId + pconfig["file_data_name"];
				pconfig["multipart_params"]["imgid"] = imgId;
				pconfig["multipart_params"]["post_parent"] = post_parent.val();
				pconfig["multipart_params"]["_ajax_nonce"] = $this.find(".ajaxnonceplu").attr("id").replace("ajaxnonceplu", "");
				
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
					var w=parseInt($this.find(".plupload-width").attr("id").replace("plupload-width", ""));
					var h=parseInt($this.find(".plupload-height").attr("id").replace("plupload-height", ""));
					pconfig["resize"] = {
						width : w,
						height : h,
						quality : 90
					};
				}

				// instanciar o uploader
				var uploader = new plupload.Uploader(pconfig);
				
				/**
				 * Uploader iniciado!
				 * 
				 */
				uploader.bind('Init', function(up, params){
					
				});
				
				// iniciar o uploader
				uploader.init();

				/**
				 * Arquivo adicionado à fila. Nesse caso os uploads são iniciados imediatamente em up.start(), após o drop.
				 * 
				 */
				uploader.bind('FilesAdded', function(up, files){
					// esconder imagem antiga
					$this.find('.plupload-thumbs').slideUp('fast', function(){
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
				uploader.bind('FileUploaded', function(up, file, response) {
					//console.log(up);
					//console.log(file);
					//console.log(response);
					$this.find('.filelist').slideUp('fast', function(){
						$("#" + imgId + "plupload-thumbs").html(response["response"]).slideDown();
					});
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
						post_id: post_parent.val()
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

