/**
 * Iniciar a renderização dos boxes de grecaptcha onload
 * 
 */
function boros_grecaptcha_onload(){
	// querySelectorAll usado para compatibilidade com IE8, normalmente seria usado getElementsByClassName
	var elems = document.querySelectorAll('.grecaptcha_render');
	for(var i = 0; i < elems.length; i++){
		grecaptcha.render(elems[i].id, {
			'sitekey' : grecaptcha_keys.sitekey
		});
	}
	
}
