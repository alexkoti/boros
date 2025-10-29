jQuery(document).ready(function($){

    $('.input_color_picker').each(function(){
        var options = [];
        var palette = $(this).attr('data-palette');
        
        if( palette ){
            options.palettes = palette.split(',');
        }
        $(this).wpColorPicker( options );
    });

});