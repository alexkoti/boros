
var current_recap;
var current_form;
var recaptchaId;

function boros_grecaptcha_invisible_init(){


    $('.boros_element_grecaptcha_invisible').each(function(e){
        
        var irecap = $(this);
        var form   = irecap.closest('form');
        var id     = form.find('.g-recaptcha').attr('id');
        var htmlEl = document.getElementById(id);

        // option to captcha
        var captchaOptions = {
            sitekey: grecaptcha_keys.sitekey,
            callback: window.boros_invisible_recaptcha_submit
        };

        // Only for "invisible" type. if true, will read value from html-element's data-* attribute if its not passed via captchaOptions
        var inheritFromDataAttr = true;

        recaptchaId = window.grecaptcha.render( htmlEl, captchaOptions, true);
        
        //e.preventDefault();

        //console.log('invisible');
        //var irecap = $(this);
        //var form   = irecap.closest('form');
        //var id     = form.find('.g-recaptcha').attr('id');
        //console.log(id);
        //
        //current_recap = id;
        //
        //widgetId = grecaptcha.render(id, {
        //    'sitekey'  : grecaptcha_keys.sitekey,
        //    'callback' : boros_invisible_recaptcha_submit, 
        //});
        //

        form.on('submit', function(e){
            e.preventDefault();
            grecaptcha.execute( recaptchaId );
            current_form = form;
        });

        //grecaptcha.render(id, {
        //    sitekey : grecaptcha_keys.sitekey
        //});

        //form.on('submit', function(e){
        //    current_form = form;
        //    console.log('invisible recap trigger');
        //    grecaptcha.execute();
        //});
    });
}

function boros_invisible_recaptcha_submit( a ){
    console.log(a);
    console.log("\n=======\n");
    console.log(current_form);
    console.log(recaptchaId);
    //var form = $('#'+current_recap).closest('form');
    //console.log(form);
    //current_form.submit();
}

function boros_invisible_recaptcha( a ){
    alert(a);
    console.log(22222);
}
