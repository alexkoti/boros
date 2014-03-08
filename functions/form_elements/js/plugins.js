



jQuery(document).ready(function($){
	/*********************************************************
	 ******************* PLUGINS JQUERY **********************
	 *********************************************************/
	/**
	 * #POSITION_CENTER
	 * Posicionar elemento no centro da janela, em relação ao viewport e não o documento todo.
	 * 
	 * http://test.learningjquery.com/center.html
	 * http://www.mail-archive.com/jquery-en@googlegroups.com/msg23295.html
	 */
	(function($){$.fn.positionCenter=function(options){var pos={sTop:function(){return window.pageYOffset||document.documentElement&&document.documentElement.scrollTop||document.body.scrollTop;},wHeight:function(){return window.innerHeight||document.documentElement&&document.documentElement.clientHeight||document.body.clientHeight;}};return this.each(function(index){if(index==0){var $this=$(this);var elHeight=$this.outerHeight();var elTop=pos.sTop()+(pos.wHeight()/2)-(elHeight/2);$this.css({position:'absolute',margin:'0',top:elTop,left:(($(window).width()-$this.outerWidth())/2)+'px'});}});};})(jQuery);
	
	/**
	 * #URL_PARSER _ TROCADO POR ARQUIVO SEPARADO!!!
	 * Para verificar os tipos de objetos a serem carregados no lightbox
	 * 
	 * https://github.com/allmarkedup/jQuery-URL-Parser
	 */
	;(function($, undefined){var tag2attr={a:'href',img:'src',form:'action',base:'href',script:'src',iframe:'src',link:'href'},key=["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","fragment"],aliases={ "anchor":"fragment" },parser={strict:/^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,loose:/^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/},querystring_parser=/(?:^|&|;)([^&=;]*)=?([^&;]*)/g,fragment_parser=/(?:^|&|;)([^&=;]*)=?([^&;]*)/g;function parseUri( url, strictMode ){var str=decodeURI( url ),res=parser[ strictMode || false ? "strict":"loose" ].exec( str ),uri={ attr:{}, param:{}, seg:{} },i=14;while ( i-- ){uri.attr[ key[i] ]=res[i] || "";}uri.param['query']={};uri.param['fragment']={};uri.attr['query'].replace( querystring_parser, function ( $0, $1, $2 ){if ($1){uri.param['query'][$1]=$2;}});uri.attr['fragment'].replace( fragment_parser, function ( $0, $1, $2 ){if ($1){uri.param['fragment'][$1]=$2;}});uri.seg['path']=uri.attr.path.replace(/^\/+|\/+$/g,'').split('/');uri.seg['fragment']=uri.attr.fragment.replace(/^\/+|\/+$/g,'').split('/');uri.attr['base']=uri.attr.host ? uri.attr.protocol+"://"+uri.attr.host + (uri.attr.port ? ":"+uri.attr.port:''):'';return uri;};function getAttrName( elm ){var tn=elm.tagName;if ( tn !== undefined ) return tag2attr[tn.toLowerCase()];return tn;}$.fn.url=function( strictMode ){var url='';if ( this.length ){url=$(this).attr( getAttrName(this[0]) ) || '';}return $.url( url, strictMode );};$.url=function( url, strictMode ){if ( arguments.length === 1 && url === true ){strictMode=true;url=undefined;}strictMode=strictMode || false;url=url || window.location.toString();return {data:parseUri(url, strictMode),attr:function( attr ){attr=aliases[attr] || attr;return attr !== undefined ? this.data.attr[attr]:this.data.attr;},param:function( param ){return param !== undefined ? this.data.param.query[param]:this.data.param.query;},fparam:function( param ){return param !== undefined ? this.data.param.fragment[param]:this.data.param.fragment;},segment:function( seg ){if ( seg === undefined ){return this.data.seg.path;}else{seg=seg < 0 ? this.data.seg.path.length + seg:seg - 1;return this.data.seg.path[seg];}},fsegment:function( seg ){if ( seg === undefined ){return this.data.seg.fragment;}else{seg=seg < 0 ? this.data.seg.fragment.length + seg:seg - 1;return this.data.seg.fragment[seg];}}};};})(jQuery);
	
	/**
	 * #JQUERY_CREATE
	 * Criar elementos com atributos
	 * $.create('ELEMENTO',{'ATTR':'VALUE'},['TEXTO']);
	 * IE(s) exigem o 3º argumento, que seria opcional
	 * 
	 * http://blogs.microsoft.co.il/blogs/basil/archive/2008/08/21/jquery-create-jquery-plug-in-to-create-elements.aspx
	 */
	jQuery.create=function(){if(arguments.length==0)return[];var args=arguments[0]||{},elem=null,elements=null;var siblings=null;if(args==null)args="";if(args.constructor==String){if(arguments.length>1){var attributes=arguments[1];if(attributes.constructor==String){elem=document.createTextNode(args);elements=[];elements.push(elem);siblings=jQuery.create.apply(null,Array.prototype.slice.call(arguments,1));elements=elements.concat(siblings);return elements;}else{elem=document.createElement(args);var attributes=arguments[1];for(var attr in attributes)jQuery(elem).attr(attr,attributes[attr]);var children=arguments[2];children=jQuery.create.apply(null,children);jQuery(elem).append(children);if(arguments.length>3){siblings=jQuery.create.apply(null,Array.prototype.slice.call(arguments,3));return[elem].concat(siblings);}return elem;}}else return document.createTextNode(args);}else{elements=[];elements.push(args);siblings=jQuery.create.apply(null,(Array.prototype.slice.call(arguments,1)));elements=elements.concat(siblings);return elements;}};
	
	/**
	 * SWITCH CLASS
	 * Trocar classes, entre dois termos. Consultar o original para ver aplicação com object, array object e selector(padrão)
	 * 
	 * @author pedrocorreia.net
	 * @link http://www.pedrocorreia.net/mySnippets/javascript/jQuery-Switch-Replace-Change-Class-Name
	 */
	(function($){$.SwitchClass = function (selectors){var _selectors = [],_Switch = function(o, oldClass, newClass){if(o && oldClass && newClass && o.hasClass(oldClass)){o.removeClass(oldClass).addClass(newClass);}};if($.isArray(selectors)){ _selectors = selectors; }else{ _selectors.push(selectors); }if (_selectors.length === 0) { return; }$.each(_selectors, function(idx, selector){_Switch($(selector.elem), selector.old_class, selector.new_class);});};$.fn.SwitchClass = function(old_class, new_class){return this.each(function(){$.SwitchClass({elem: this, old_class: old_class, new_class: new_class});});};})(jQuery);
	
	
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
	
	/**
	 * SERIALIZE FORM DATA
	 * Utilizando array associativo, ex name="foo[bar]"
	 * @link http://stackoverflow.com/a/19643311
	 * 
	 */
	$.fn.serializeObject = function() {
	var data = { };
	$.each( this.serializeArray(), function( key, obj ) {
		var a = obj.name.match(/(.*?)\[(.*?)\]/);
		if(a !== null)
		{
			var subName = new String(a[1]);
			var subKey = new String(a[2]);
			if( !data[subName] ) data[subName] = { };
			if( data[subName][subKey] ) {
				if( $.isArray( data[subName][subKey] ) ) {
					data[subName][subKey].push( obj.value );
				} else {
					data[subName][subKey] = { };
					data[subName][subKey].push( obj.value );
				};
			} else {
				data[subName][subKey] = obj.value;
			};  
		} else {
			var keyName = new String(obj.name);
			if( data[keyName] ) {
				if( $.isArray( data[keyName] ) ) {
					data[keyName].push( obj.value );
				} else {
					data[keyName] = { };
					data[keyName].push( obj.value );
				};
			} else {
				data[keyName] = obj.value;
			};
		};
	});
	return data;
};

});

/**
 * DATASET
 * 
 * @link http://www.orangesoda.net/jquery.dataset.html
 */
//(function($){$.dataset={dashTransform:true};function encodeName(name){return'data-'+name.replace(/([a-z0-9])([A-Z])/g,'$1-$2').toLowerCase();}function decodeName(name){name=name.replace(/^data-/ig,'').toLowerCase();if($.dataset.dashTransform!==true){return name;}return $.map(name.split('-'),function(n,i){return(i>0?n.substr(0,1).toUpperCase()+n.substr(1):n);}).join('');}$.fn.datasets=function(){var sets=[];this.each(function(){sets.push($(this).dataset());});return sets;};$.fn.dataset=function(attr,value){if(arguments.length==0){var dataset={};this.eq(0).each(function(){var a=this.attributes;for(var i=0,il=a.length;i<il;i++){if(a[i].name.substr(0,5)=='data-'){dataset[decodeName(encodeName(a[i].name.substr(5)))]=a[i].value;}}}).end();return dataset;}else if(arguments.length==1&&typeof attr!='object'){return this.attr(encodeName(attr));}else{var dataset=attr;if(typeof attr!='object'){dataset={};dataset[attr]=value;}var tmp={};var eventData={};$.each(dataset,function(k,v){var name=encodeName(k);tmp[name]=eventData[decodeName(name)]=v;});return this.attr(tmp).trigger('dataset',[eventData]);}};$.fn.removeDataset=function(attr){if(typeof attr=='string'){if(attr=='*'){attr=[];$.each($(this).dataset(),function(k){attr.push(k);});}else{attr=[attr];}}return this.each(function(){var _this=this;$.each(attr,function(i,n){$(_this).removeAttr(encodeName(n))});});};function generateSelector(attr,value,comparison){if(arguments.length==0){attr=value='';comparison='*=';}else if(arguments.length==1){value='';comparison='*=';}else if(arguments.length==2){comparison='=';}name=encodeName(attr);var selector=name+comparison+value;if(selector==''){return'';}return'['+selector+']';}function executeFindOfFilter(type,args){if(typeof args[0]=='object'){var selector='';for(var i=0;i<args.length;i++){selector+=generateSelector.apply({},args[i]);}if(selector==''){return this.pushStack([]);}return this[type](selector);}var selector=generateSelector.apply({},args);log('Selector: '+selector);if(selector==''){return this.pushStack([]);}return this[type](selector);}$.fn.datasetFilter=function(){return executeFindOfFilter.call(this,'filter',arguments);};$.fn.datasetFind=function(attr,value,comparison){return executeFindOfFilter.call(this,'find',arguments);};})(jQuery);
(function($){var PREFIX="data-",PATTERN=/^data\-(.*)$/;function dataset(name,value){if(value!==undefined)return this.attr(PREFIX+name,value);switch(typeof name){case "string":return this.attr(PREFIX+name);case "object":return set_items.call(this,name);case "undefined":return get_items.call(this);default:throw"dataset: invalid argument "+name;}}function get_items(){return this.foldAttr(function(index,attr,result){var match=PATTERN.exec(this.name);if(match)result[match[1]]=this.value})}function set_items(items){for(var key in items)this.attr(PREFIX+key,items[key]);return this}function remove(name){if(typeof name=="string")return this.removeAttr(PREFIX+name);return remove_names(name)}function remove_names(obj){var idx,length=obj&&obj.length;if(length===undefined)for(idx in obj)this.removeAttr(PREFIX+idx);else for(idx=0;idx<length;idx++)this.removeAttr(PREFIX+obj[idx]);return this}$.fn.dataset=dataset;$.fn.removeDataset=remove_names})(jQuery);(function($){function each_attr(proc){if(this.length>0)$.each(this[0].attributes,proc);return this}function fold_attr(proc,acc){return fold(this.length>0&&this[0].attributes,proc,acc)}function fold(object,proc,acc){var length=object&&object.length;if(acc===undefined)acc={};if(!object)return acc;if(length!==undefined)for(var i=0,value=object[i];i<length&&proc.call(value,i,value,acc)!==false;value=object[++i]);else for(var name in object)if(proc.call(object[name],name,object[name],acc)===false)break;return acc}function fold_jquery(proc,acc){if(acc===undefined)acc=[];return fold(this,proc,acc)}$.fn.eachAttr=each_attr;$.fn.foldAttr=fold_attr;$.fn.fold=fold_jquery;$.fold=fold})(jQuery);


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

/** 
 * #IN_ARRAY
 * 
 * @link http://stackoverflow.com/questions/784012/javascript-equivalent-of-phps-in-array
 */
function inArray(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i] == needle) return true;
    }
    return false;
}