jQuery(function($){

    var media_selector = {};

    var current_opt = {};

    /**
     * Botão de selecionar nova midia
     * 
     */
    $('.boros-media-selector').on('click', '.media-selector-add, .media-item img', function(e){
        e.preventDefault();

        var btn      = $(this);
        var box      = btn.closest('.boros-media-selector');
        var index    = box.attr('id');
        var mindex   = box.attr('data-query-id');
        current_opt  = box.data('options');
        current_opt['box'] = box;

        //console.log(current_opt);
        //console.log('mindex: ' + mindex);

        if( media_selector[mindex] ){
            //console.log('reabrir wp.media');
            media_selector[mindex].open();
        }
        else{
            //console.log('criar wp.media');
            media_selector_create(mindex);
        }
        
        media_selector[mindex].open();
    });

    /**
     * Botão de remover midia
     * 
     */
    $('.boros-media-selector').on('click', '.media-selector-remove, .media-item .remove', function(e){
        e.preventDefault();
        var box = $(this).closest('.boros-media-selector');
        media_selector_update( box, 'default', 0 );
    });


    function media_selector_create( mindex ){

        /**
         * Configurar modal de midia
         * 
         */
        media_selector[mindex] = new wp.media.view.MediaFrame.Select({
            title    : current_opt.modal_title,
            multiple : false,
            button   : {text : current_opt.confirm_button},
            library: {
                order: current_opt.file_order,
                // [ 'name', 'author', 'date', 'title', 'modified', 'uploadedTo', 'id', 'post__in', 'menuOrder' ]
                orderby: current_opt.file_orderby,
                // mime type. e.g. 'image', 'image/jpeg'
                type: current_opt.file_type,
                // Searches the attachment title.
                //search: null,
                // Attached to a specific post (ID).
                //uploadedTo: null,
                // É possível escolher a quantidade de itens por requisição
                //posts_per_page: 50
            },
        });

        /**
         * Atualizar midia e input:hidden com o novo conteúdo
         * 
         */
        media_selector[mindex].on('select', function(){
            var attachs = media_selector[mindex].state().get('selection').first().toJSON();
            var library = media_selector[mindex].state().get( 'library' );
            console.log( attachs );
            console.log( current_opt );
            media_selector_update( current_opt.box, attachs, attachs.id );
            
            attachment = wp.media.attachment(attachs.id);
            library.add( attachment );
        });

        /**
         * Deixar selecionado os arquivos previsamente escolhidos
         * 
         */
        media_selector[mindex].on('open', function(){
            var selection = media_selector[mindex].state().get('selection');
            var library   = media_selector[mindex].state().get( 'library' );
            //console.log(selection);
            //console.log(library);
            var selected = current_opt.box.find('input[type="hidden"]').val();
            console.log( selected );
            if( selected > 0 ){
                attachment = wp.media.attachment(selected);
                attachment.fetch();
                selection.add(attachment ? [attachment] : []);
                library.add( attachment ? [ attachment ] : [] );
            }
        });
    }

    /**
     * Atualizar imagem conforme template
     * 
     * var obj     - botão
     * var new_src - novo src da imagem
     * var new_val - novo valor para ser salvo(image post_ID)
     * 
     */
    function media_selector_update( box, attachs, new_val ){
        var selected = box.find('.selected-medias');
        var options  = box.data('options');
        var input    = box.find('input[type="hidden"]');
        
        // default data
        var data = {
            src    : options.default_image,
            alt    : '',
            width  : options.width,
            height : options.height,
            remove : options.remove_text,
            title  : '',
            type   : '',
            dims   : '',
            size   : '',
            hthumb : '',
        };

        // caso seja default, carregar imagem padrão e mudar class para esconder botões de remoção
        if( attachs == 'default' ){
            box.addClass('image-not-set');
        }
        else{
            // para arquivos de imagem, deverá apontar para o tamanho correto de wp-image-size
            if( attachs.sizes ){
                data.src    = attachs.sizes[ current_opt.image_size ]['url'];
                data.hthumb = 'has-thumb';
            }
            // para demias tipos, apontar para o ícone
            else{
                data.src = attachs.icon;
            }
            // alguns tipos de vídeo possuem dimensões
            if( typeof(attachs.width) !== 'undefined' ){
                data.dims = attachs.width + ' × ' + attachs.height;
            }
            data.title = attachs.title;
            data.type  = attachs.mime;
            data.size  = attachs.filesizeHumanReadable;
            box.removeClass('image-not-set');
        }
        
        // template definido no form_element
        var get_template = wp.template('boros-media-selector-image');
        // carregar template
        var image_html = get_template(data);
        selected.html(image_html);
        
        // definir valor a ser salvo
        input.val(new_val);
    }

});