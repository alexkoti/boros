/**
 * FORM ELEMENTS
 * Arquivo de scripts gerais para form elements.
 * Usar caso o código necessário opara o element seja muito pequeno, não necessitando de um arquivo separado, OU quando é preciso compartilhar o mesmo script 
 * para diversos elements, pois os enqueues de elements são contextuais, e só carregam quando o element é utilizado na página.
 * 
 */

jQuery(document).ready(function($){
    
    /**
     * TAXONOMY CHECKBOX
     * 
     */
    $('.force_hierachical .input_checkbox').change(function(){
        var checkbox = $(this);
        // caso seja um child, marcar o parent
        if( checkbox.is('.force_hierachical .children li .input_checkbox') ){
            var parent_check = checkbox.closest('.children').parent().find('.input_checkbox:first');
            if( checkbox.is(':checked') ){
                parent_check.attr('checked', true);
            }
        }
        
        // caso seja um parent, marcar os childs, se houver
        if( checkbox.not(':checked') ){
            checkbox.closest('li').find('.children .input_checkbox').attr('checked', false);
        }
    });
    


    /**
     * Bloco duplicáveis com linhas condicionais, baseado no 'type' definido no primeiro select do bloco
     * 
     */
    function boros_element_opt_options( select_type, val ){
        var obj = select_type.closest('.form-table');
        obj.find('.conditional-row').hide();
        obj.find('.row-' + val).show();
    }

    // update onload/ on duplicate
    $('.duplicate_group').bind('duplicate_group_complete sortcreate', function(event, ui) {
        $(this).find('.conditional-type').each(function(){
            boros_element_opt_options( $(this), $(this).val() );
        });
    });

    // update onchange do select
    $('.boros_form_block').delegate('.conditional-type', 'change', function(){
        boros_element_opt_options( $(this), $(this).val() );
    });

    // onload
    if( $('.conditional-type').length ){
        $('.conditional-type').trigger('change');
    }
    
});
