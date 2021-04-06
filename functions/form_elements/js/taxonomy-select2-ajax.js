
jQuery(document).ready(function($){
    
    $('.input_taxonomy_select2_ajax').each(function(){
        var select = $(this);
        select.select2({
            ajax: {
                delay: 200,
                url: ajaxurl,
                data: function(params){
                    return {
                        action: 'boros_taxonomy_select2',
                        taxonomy: select.attr('data-taxonomy'),
                        post_id: select.attr('data-post_id'),
                        number: select.attr('data-number'),
                        term: params.term,
                    }
                },
                dataType: 'json',
            },
            minimumInputLength: select.attr('data-input_min_len'),
            language: {
                errorLoading: function () {
                    return 'Os resultados não puderam ser carregados.';
                },
                inputTooLong: function (args) {
                    var overChars = args.input.length - args.maximum;
                    var message = 'Apague ' + overChars + ' caracter';
                    if (overChars != 1) {
                        message += 'es';
                    }
                    return message;
                },
                inputTooShort: function (args) {
                    var remainingChars = args.minimum - args.input.length;
                    var message = 'Digite ' + remainingChars + ' ou mais caracteres';
                    return message;
                },
                loadingMore: function () {
                    return 'Carregando mais resultados…';
                },
                maximumSelected: function (args) {
                    var message = 'Você só pode selecionar ' + args.maximum + ' ite';
                    if (args.maximum == 1) {
                        message += 'm';
                    } else {
                        message += 'ns';
                    }
                    return message;
                },
                noResults: function () {
                    return 'Nenhum resultado encontrado';
                },
                searching: function () {
                    return 'Buscando…';
                },
                removeAllItems: function () {
                    return 'Remover todos os itens';
                }
            }
        });
    });
    
});