jQuery(function($){

    /**
     * Botão de selecionar nova midia
     * 
     */
    $('.boros-media-selector').on('click', '.media-selector-add, .media-item img', function(e){
        e.preventDefault();
        var btn      = $(this);
        var box      = btn.closest('.boros-media-selector');
        var selected = box.find('.media-selector-img');
        var options  = box.data('options');
        var input    = box.find('input[type="hidden"]');

        /**
         * Configurar modal de midia
         * 
         */
        var media_selector = new wp.media.view.MediaFrame.Select({
            title    : options.modal_title,
            multiple : false,
            button   : {text : options.confirm_button},
            library: {
                order: 'DESC',
                // [ 'name', 'author', 'date', 'title', 'modified', 'uploadedTo', 'id', 'post__in', 'menuOrder' ]
                orderby: 'date',
                // mime type. e.g. 'image', 'image/jpeg'
                type: 'image',
                // Searches the attachment title.
                search: null,
                // Attached to a specific post (ID).
                uploadedTo: null
            },
        });

        /**
         * Atualizar midia e input:hidden com o novo conteúdo
         * 
         */
        media_selector.on('select', function(){
            var attachs = media_selector.state().get('selection').first().toJSON();
            media_selector_update( btn, attachs.sizes[ options.image_size ]['url'], attachs.id );
        });
        
        media_selector.open();
    });

    /**
     * Botão de remover midia
     * 
     */
    $('.boros-media-selector').on('click', '.media-selector-remove, .media-item .remove', function(e){
        e.preventDefault();
        media_selector_update( $(this), 'default', 0 );
    });

    /**
     * Atualizar imagem conforme template
     * 
     * var obj     - botão
     * var new_src - novo src da imagem
     * var new_val - novo valor para ser salvo(image post_ID)
     * 
     */
    function media_selector_update( obj, new_src, new_val ){
        var btn      = obj;
        var box      = btn.closest('.boros-media-selector');
        var selected = box.find('.media-selector-img');
        var options  = box.data('options');
        var input    = box.find('input[type="hidden"]');

        // caso seja default, carregar imagem padrão e mudar class para esconder botões de remoção
        if( new_src == 'default' ){
            new_src = options.default_image;
            box.addClass('image-not-set');
        }
        else{
            box.removeClass('image-not-set');
        }
        
        // template definido no form_element
        var get_template = wp.template('boros-media-selector-image');
        var data = {
            src    : new_src,
            alt    : '',
            width  : options.width,
            height : options.height,
            remove : options.remove_button,
        };
        // carregar template
        var image_html = get_template(data);
        selected.html(image_html);
        
        // definir valor a ser salvo
        input.val(new_val);
    }

});