/**
 * Iniciar a renderização dos boxes de grecaptcha onload
 * 
 */
function boros_grecaptcha_onload(){
	// querySelectorAll usado para compatibilidade com IE8, normalmente seria usado getElementsByClassName
	var elems = document.querySelectorAll('.grecaptcha_render');
	for(var i = 0; i < elems.length; i++){

        // recaptcha element
        var recap = jQuery(elems[i]);

        // verificar se o elemento possui a class defer-render, que significa que o captcha só deverá ser 
        // inicializado ao focar em algum dos campos do formulário parent
        if( recap.is('.defer-render') ){
            recap.closest('form').on('focusin', function(){
                // só renderizar uma vez
                if( !recap.is('.rendered') ){
                    grecaptcha.render(recap.attr('id'), {
                        sitekey : grecaptcha_keys.sitekey
                    });
                    recap.addClass('rendered');
                }
            });
        }
        // inicializar diretamente
        else{
            grecaptcha.render(recap.attr('id'), {
                sitekey : grecaptcha_keys.sitekey
            });
            recap.addClass('rendered');
        }
	}
}
