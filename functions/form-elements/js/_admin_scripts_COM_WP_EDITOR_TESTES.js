/**
 * ADMIN SCRIPTS: GLOBAL
 * Scripts válidos para todo o admin.
 * AS funções de tinymce foram criadas para não interferirem no editor nativo, rodando separadamente e inicializando somente após onload
 * 
 * 
 * 
 */


/**
 * CONFIG TINYMCE
 * Object global para guardar as configs de tinymce.
 * As funções php de incluir textareas irão adicionar elementos de configuração nesse array global.
 * A função php de footer admin_footer_scripts() em admin_static.php, iniciará os editores nos textareas via activate_tiny_mces() presente neste arquivo.
 * 
 * Arquivos relacionados:
 * - /functions/admin_static - inicializa os editores com activate_tiny_mces();
 * - /functions/form_elements/form_element_textarea.php - registra novos global_tinymce_config
 * - /functions/widgets_config.php - registra novos global_tinymce_config na página de widgets
 * FORM_ELEMENTS_TEXTAREA_FUNCTION >>> função php que cria textareas, ela definirá inline as configs de tinymce a serem armazenadas em global_tinymce_config
 */

// NÂO EXISTE ARRAY ASSOCIATIVO EM JAVASCRIPT!!! >>> USAR OBJECTS!!!
var global_tinymce_config = new Object();
global_tinymce_config.by_class = {}; // guardará as configs gerais por 'profile' de editor - definido pela class associada
global_tinymce_config.by_id = {}; // guardará as configs individuais de cada textarea - não usado ainda
global_tinymce_config.by_core = {}; // guardará as configs de textareas padrão do core



/**
 * INICIAR TINY_MCEs
 * Utiliza o object global_tinymce_config e inicia todos os textareas sinalizados
 * 
 */
function activate_tiny_mces(){
	//console.log(global_tinymce_config);
	
	if( global_tinymce_config ){
		
		/**
		 * Loop em todos os registros by_class, inicializando(diferente de renderizar) os editores
		 * A renderização de cada editor dependerá do atributo 'mode', que poderá ser 'none'
		 */
		//console.log(global_tinymce_config.by_class);
		for (var i in global_tinymce_config.by_class){
			//console.log( global_tinymce_config.by_class[i] );
			tinyMCE.init( global_tinymce_config.by_class[i] );
		}
		
		/**
		 * WIDGET PAGE
		 * Renderiza os editores que já foram registrados em tinyMCE.init na página de controle de widgets.
		 * Loop em todos os textareas identificados por .form_text_editor dentro da coluna de widgets ativos(ignora o source para evitar problemas)
		 * 
		 */
		jQuery('#widgets-right .form_text_editor').each(function(){
			//console.log( jQuery(this).attr('id') ); //IDs dox textareas slecionaos para aplicar o tinymce
			tinyMCE.execCommand('mceAddControl', false, jQuery(this).attr('id'));
			update_widget_tinymce_contents();
		});
		
		
		/**
		 * CORE TEXTAREAS
		 * Renderiza os textareas padrão do core - normalmente textareas simples.
		 * É preciso registrar as configs de tinymce dentro do global_tinymce_config['by_core'], com mode:'none' e e a id do textarera em elements:id
		 * 
		 */
		if( global_tinymce_config.by_core ){
			for (var i in global_tinymce_config.by_core){
				if( jQuery('textarea#'+i).length ){
					//console.log(i);
					//console.log( global_tinymce_config.by_core[i] );
					tinyMCE.init( global_tinymce_config.by_core[i] );
				}
			}
			update_widget_tinymce_contents();
		}
	}
}



/**
 * WIDGETS FUNCTIONS
 * Reload de tinymce em retornos de ajax
 * Ao salvar os dados de cada widget, o conteúdo DOM do box é recarregado, necessitando nova renderização do tiny_mce, porém, como o textarea antes do envio 
 * ajax já foi registrado no tinyMCE, é preciso remover o anterior <code>tinyMCE.execCommand('mceRemoveControl')</code> ao enviar os dados, e registrar 
 * novamente, usando <code>tinyMCE.execCommand('mceAddControl')</code> ao terminar de recarregar o box do widget.
 * 
 * @link http://www.johngadbois.com/adding-your-own-callbacks-to-wordpress-ajax-requests/
 * @link http://blog.mirthlab.com/2008/11/13/dynamically-adding-and-removing-tinymce-instances-to-a-page/
 * @link http://hamisageek.blogspot.com/2007/11/multiple-tinymce-3-instances-on-one.html
 * 
 * @notes execCommand('mceAddControl|mceRemoveControl') precisam necessariamente de uma id, então não é possível remover diversos editors por class(verificar)
 * 
 * @uses getParameterByName() - plugin javascript neste arquivo
 */
(function($){
	/**
	//MONITORAR ENVIOS AJAX
	$(document).ajaxSend(function(e, xhr, settings) {
		var $widget = $(e.currentTarget.activeElement).closest('.widget');
		var action = getParameterByName( 'action', settings.data );
		//console.log( $(e.currentTarget.activeElement).closest('.widget').attr('id') );
		//console.log(settings.data);
		
		//caso seja ação de salvamento de widget
		if( action == 'save-widget' ){
				console.log('SEND');
			
			//procurar tinymces
			$tinymce_textarea = $widget.find('.wp-editor-area');
			if( $tinymce_textarea.length ){
				console.log('tiny mce textarea identificado!');
				tinyMCE.execCommand('mceRemoveControl', false, $tinymce_textarea);
			}
		}
	});
	
	//MONITORAR SUCESSOS DE AJAX
	$(document).ajaxSuccess(function(e, xhr, settings) {
		var $widget = $(e.currentTarget.activeElement).closest('.widget');
		var action = getParameterByName( 'action', settings.data );
		console.log( $widget );
		
		//caso seja ação de salvamento de widget
		if( action == 'save-widget' ){
				console.log('SUCESS');
			//procurar tinymces
			$tinymce_textarea = $widget.find('.wp-editor-area');
			if( $tinymce_textarea.length ){
				//renderizar editor
				tinyMCE.execCommand('mceAddControl', false, $tinymce_textarea);
			}
		}
		//sincronizar editor e textarea
		update_widget_tinymce_contents();
	});
	/**/
})(jQuery);



/**
 * LIVE TINYMCE WIDGET >>> .free_box_form
 * Monitorar o bloco (.free_box_form form) para ação click, requisitando a sincronização do editor e do textarea. Usado unbind para que sempre que esta
 * função seja ativada, atribue apenas uma ação de monitoramento.
 * 
 * TAXONOMY FORM >>> #addtag #submit
 * Formulário de taxonomias
 * 
 * handler 'mousedown' para atualizar os textareas antes do handler click dos submits
 * 
 */
function update_widget_tinymce_contents(){
	jQuery('.widget .wp-editor-area').closest('form').unbind('click').bind('mousedown', function(){
		//console.info('tinymce sinc!');
		tinyMCE.triggerSave();
	});
}
/** WIDGETS FUNCTIONS **/



jQuery(document).ready(function($){
	/**
	 * APPEND FUNCTION DO SORTABLE :: WIDGETS
	 * Adicionar função de callback para o fim do evento de sort de um widget
	 */
	/**/
	$('div.widgets-sortables').bind( "sortstart", function(event, ui){
		var ui_id = ui.item.attr('id');
		
		if( $('#'+ui_id+' .wp-editor-area').length ){
			var form_text_editor = $('#'+ui_id+' .wp-editor-area').attr('id');
			//remover editor
			tinyMCE.execCommand('mceRemoveControl', false, form_text_editor);
		}
	});
	$('div.widgets-sortables').bind( "sortstop", function(event, ui){
		var ui_id = ui.item.attr('id');
		
		if( $('#'+ui_id+' .wp-editor-area').length ){
			var form_text_editor = $('#'+ui_id+' .wp-editor-area').attr('id');
			//renderizar editor
			tinyMCE.execCommand('mceAddControl', false, form_text_editor);
			//sincronizar editor e textarea
			update_widget_tinymce_contents();
		}
	});
	/**/
	
	
	/**
	 * AUTOSELECT DE TEXTO
	 * Seleciona texto dentro do elemento, porém não copia o texto
	 */
	$('.autoselect').click(function(){
		$(this).focus().select();
	});
	
	
	
	/**
	 * CHECKBOX TOGGLE VISIBILITY
	 * Muda o display do elemento referenciado no 'rel' ou o .visibility_target dentro do .visibility_parent mais próximo acima no DOM.
	 * 
	 */
	$('.visibility_toggle').click(function(){
		var rel = $(this).attr('rel');
		if( undefined == rel )
			target = $(this).closest('.visibility_parent').find('.visibility_target');
		else
			target = $('#'+rel);
		
		target.toggle();
	});
	
	
	
	/*********************************************************
	 ******************* PLUGINS JQUERY **********************
	 *********************************************************/
	/* 
	 * #POSITION_CENTER
	 * Posicionar elemento no centro da janela, em relação ao viewport e não o documento todo.
	 * 
	 * http://test.learningjquery.com/center.html
	 * http://www.mail-archive.com/jquery-en@googlegroups.com/msg23295.html
	 */
	(function($){$.fn.positionCenter=function(options){var pos={sTop:function(){return window.pageYOffset||document.documentElement&&document.documentElement.scrollTop||document.body.scrollTop;},wHeight:function(){return window.innerHeight||document.documentElement&&document.documentElement.clientHeight||document.body.clientHeight;}};return this.each(function(index){if(index==0){var $this=$(this);var elHeight=$this.outerHeight();var elTop=pos.sTop()+(pos.wHeight()/2)-(elHeight/2);$this.css({position:'absolute',margin:'0',top:elTop,left:(($(window).width()-$this.outerWidth())/2)+'px'});}});};})(jQuery);
	
	/* 
	 * #URL_PARSER
	 * Para verificar os tipos de objetos a serem carregados no lightbox
	 * 
	 * http://projects.allmarkedup.com/jquery_url_parser/
	 */
	jQuery.url=function(){var segments={};var parsed={};var options={url:window.location,strictMode:false,key:["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],q:{name:"queryKey",parser:/(?:^|&)([^&=]*)=?([^&]*)/g},parser:{strict:/^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,loose:/^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/}};var parseUri=function(){str=decodeURI(options.url);var m=options.parser[options.strictMode?"strict":"loose"].exec(str);var uri={};var i=14;while(i--){uri[options.key[i]]=m[i]||""}uri[options.q.name]={};uri[options.key[12]].replace(options.q.parser,function($0,$1,$2){if($1){uri[options.q.name][$1]=$2}});return uri};var key=function(key){if(!parsed.length){setUp()}if(key=="base"){if(parsed.port!==null&&parsed.port!==""){return parsed.protocol+"://"+parsed.host+":"+parsed.port+"/"}else{return parsed.protocol+"://"+parsed.host+"/"}}return(parsed[key]==="")?null:parsed[key]};var param=function(item){if(!parsed.length){setUp()}return(parsed.queryKey[item]===null)?null:parsed.queryKey[item]};var setUp=function(){parsed=parseUri();getSegments()};var getSegments=function(){var p=parsed.path;segments=[];segments=parsed.path.length==1?{}:(p.charAt(p.length-1)=="/"?p.substring(1,p.length-1):path=p.substring(1)).split("/")};return{setMode:function(mode){strictMode=mode=="strict"?true:false;return this},setUrl:function(newUri){options.url=newUri===undefined?window.location:newUri;setUp();return this},segment:function(pos){if(!parsed.length){setUp()}if(pos===undefined){return segments.length}return(segments[pos]===""||segments[pos]===undefined)?null:segments[pos]},attr:key,param:param}}();
	
	/* 
	 * #JQUERY_CREATE
	 * Criar elementos com atributos
	 * $.create('ELEMENTO',{'ATTR':'VALUE'},['TEXTO']);
	 * IE(s) exigem o 3º argumento, que seria opcional
	 * 
	 * http://blogs.microsoft.co.il/blogs/basil/archive/2008/08/21/jquery-create-jquery-plug-in-to-create-elements.aspx
	 */
	jQuery.create=function(){if(arguments.length==0)return[];var args=arguments[0]||{},elem=null,elements=null;var siblings=null;if(args==null)args="";if(args.constructor==String){if(arguments.length>1){var attributes=arguments[1];if(attributes.constructor==String){elem=document.createTextNode(args);elements=[];elements.push(elem);siblings=jQuery.create.apply(null,Array.prototype.slice.call(arguments,1));elements=elements.concat(siblings);return elements;}else{elem=document.createElement(args);var attributes=arguments[1];for(var attr in attributes)jQuery(elem).attr(attr,attributes[attr]);var children=arguments[2];children=jQuery.create.apply(null,children);jQuery(elem).append(children);if(arguments.length>3){siblings=jQuery.create.apply(null,Array.prototype.slice.call(arguments,3));return[elem].concat(siblings);}return elem;}}else return document.createTextNode(args);}else{elements=[];elements.push(args);siblings=jQuery.create.apply(null,(Array.prototype.slice.call(arguments,1)));elements=elements.concat(siblings);return elements;}};
	
	
	
	(function($) {
	$.fn.yellowFade = function( config ) {
		var defaults = {
			start: '#ffffcc',
			end: '#ffffff',
			time: 2000
		}
		config = $.extend( defaults, config );
		this.animate( { backgroundColor: config.start }, 1 ).animate( { backgroundColor: config.end }, 2000 );
	}
	})(jQuery);

});



/*********************************************************
 ************************ PLUGINS ************************
 *********************************************************/
/**
 * PARSE QUERY STRING
 * Recupera o valor de 'name' dentro da string em formato query.
 * Exemplo 
	<code>
	str = 'foo=bar&foz=bla';
	getParameterByName( 'foz', str ); //result 'bla'
	</code>
 * 
 */
function getParameterByName( name, str ){name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");var regexS = "[\\?&]"+name+"=([^&#]*)";var regex = new RegExp( regexS );var results = regex.exec( str );if( results == null ){return "";}else{return decodeURIComponent(results[1].replace(/\+/g, " "));}}

/**
 * #URL_PARSER 2
 * Retorna um objeto json, bem fácil de manipular, com possibilidade de usar jQuery.param() para devolver a url formatada novamente. Exemplo
<code>
	var urlvars = url_params( $(foo).attr('href') );
	urlvars.serie = 123; // editar paramêtro 'serie'
	
	// voltar para url
	var new_url = $(foo).attr('href').split('?')[0] + '?' + $.param(urlvars);
	$(foo).attr('href', new_url);
</code>
 * 
 * @link http://stackoverflow.com/questions/901115/get-query-string-values-in-javascript/2880929#2880929
 */
function url_params( url ){
	var urlParams = {};
	var e,
	a = /\+/g,  // Regex for replacing addition symbol with a space
	r = /([^&=]+)=?([^&]*)/g,
	d = function (s) { return decodeURIComponent(s.replace(a, " ")); },
	q = url.split('?')[1];
	
	while (e = r.exec(q))
		urlParams[d(e[1])] = d(e[2]);
	
	return urlParams;
}