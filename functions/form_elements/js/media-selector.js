jQuery(function($){

    /**
     * Todos os modais de mídia iniciados na página
     * 
     */
    var media_selector = {};

    /**
     * Opções de modal(title, botões, query, etc)
     * Sempre é preenchido com as opções do controle acionado.
     * 
     */
    var current_opt = {};

    /**
     * Botão de selecionar nova midia
     * 
     * Ao acionar o botão:
     * - as opções em 'current_opt' serão preenchidas com os valores armazenados em data-options
     * - é verificado se o modal da query 'data-query-id' já foi criado, retornando o modal pré-existente ou criando um novo, 
     * armazenando no índice 'mindex'
     * 
     */
    $('.boros_form_block').on('click', '.boros-media-selector .media-selector-add, .boros-media-selector .media-item img', function(e){
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
            media_selector[mindex].open();
        }
        else{
            media_selector_create(mindex);
        }
        
        media_selector[mindex].open();
    });

    /**
     * Botão de remover midia
     * 
     */
    $('.boros_form_block').on('click', '.boros-media-selector .media-selector-remove, .boros-media-selector .media-item .remove', function(e){
        e.preventDefault();
        var box = $(this).closest('.boros-media-selector');
        media_selector_update( box, 'default', 0 );
    });

    /**
     * Registrar modal de mídia
     * 
     * Cada modal de mídia, gera uma query das mídias, definida em 'library'. Caso dois controles precisem da mesma query, o modal 
     * poderá ser reutilizado para os mesmos controles. Por ex todos os controles que buscam as imagens mais recentes podem usar o 
     * mesmo modal. Já outro modal que busque apenas os pdfs, deverá ser único.
     * 
     * Todos os modais presentes na página são armazenados em 'media_selector' e cada tipo de requisição no índice 'mindex', por ex
     * 'image-date-DESC' ou 'application-pdf-date-DESC'
     * 
     */
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
                //// searches the attachment title.
                //search: null,
                //// attached to a specific post (ID).
                //uploadedTo: null,
                //// é possível escolher a quantidade de itens por requisição
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
            //console.log( attachs );
            //console.log( current_opt );
            media_selector_update( current_opt.box, attachs, attachs.id );
            
            attachment = wp.media.attachment(attachs.id);
            library.add( attachment );
        });

        /**
         * Deixar selecionado os arquivos previamente escolhidos
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
     * Após selecionar a imagem no modal de midia do WordPress, enviar os dados para o 
     * template js 'tmpl-boros-media-selector-image', presente em media_Selecotr.php:footer()
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
            style  : {
                width  : `width:${options.width}px;`,
                height : `height:${options.height}px;`,
            },
            remove : options.remove_text,
            title  : '',
            type   : '',
            dims   : '',
            size   : '',
            hthumb : '',
            hclass : 'height-fixed',
        };

        // remover o px caso seja 'auto'
        if( data.height == 'auto' ){
            data.style.height = `height:${options.height};`;
            data.hclass = 'height-auto';
        }

        // caso seja a string 'default' em vez do objeto attachs, carregar imagem padrão e mudar class para esconder botões de remoção
        if( attachs == 'default' ){
            box.addClass('value-not-set');
        }
        else{
            // para arquivos de imagem, deverá apontar para o tamanho correto de wp-image-size
            if( attachs.sizes ){
                // pode acontecer do tamanho da imagem requisitada não ter sido registrada, usar 'full', que é sempre definido em _get_all_image_sizes()
                var image_size = ( attachs.sizes[ current_opt.image_size ] === undefined ) ? 'full' : current_opt.image_size;
                data.src       = attachs.sizes[ image_size ]['url'];
                data.hthumb    = 'has-thumb';
            }
            // para demias tipos, apontar para o ícone
            else{
                data.src = attachs.icon;
            }
            // além de imagem, alguns tipos de vídeo possuem dimensões
            if( typeof(attachs.width) !== 'undefined' ){
                data.dims = attachs.width + ' × ' + attachs.height;
            }
            data.title = attachs.title;
            data.type  = attachs.mime;
            data.size  = attachs.filesizeHumanReadable;
            box.removeClass('value-not-set');
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