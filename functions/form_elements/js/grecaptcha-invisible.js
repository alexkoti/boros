
var current_recap;
var current_form;
var recaptchaId;

function boros_grecaptcha_invisible_init(){
    
    jQuery('.boros_element_grecaptcha_invisible').each(function(e){
        var irecap = jQuery(this);
        var form   = irecap.closest('form');
        var id     = form.find('.g-recaptcha').attr('id');
        var htmlEl = document.getElementById(id);

        var captchaOptions = {
            sitekey: grecaptcha_keys.sitekey,
            callback: window.boros_invisible_recaptcha
        };

        recaptchaId = window.grecaptcha.render( htmlEl, captchaOptions, true);
        grecaptcha.execute( recaptchaId );
        //console.log( htmlEl );
        //console.log( id );
        //console.log( captchaOptions );
        //console.log( recaptchaId );
    });
}

function boros_invisible_recaptcha( response ){
    //console.log(response);
    //console.log(current_form);
    //console.log(recaptchaId);
    current_form.find('[name="g-recaptcha-response"]').val(response);
}

