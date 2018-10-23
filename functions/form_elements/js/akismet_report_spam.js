
jQuery(document).ready(function($){

    // 
    $('.btn-akismet-report-spam').on('click', function(){
        var btn = $(this);
        btn.prop('disabled', true);

        var data = {
            action:  'boros_akismet_report_spam',
            post_id: $(this).attr('data-post-id'),
            name:    $(this).attr('data-field-name'),
            email:   $(this).attr('data-field-email'),
            content: $(this).attr('data-field-content'),
            report:  $(this).attr('data-report')
        };
        
        jQuery.post(ajaxurl, data, function(response){
            console.log( response.message );
            btn.prop('disabled', false).text(response.button).removeClass(response.removeClass).addClass(response.addClass).attr('data-report', response.report);
            btn.next('.report-message').text(response.message);
        });
    });

});