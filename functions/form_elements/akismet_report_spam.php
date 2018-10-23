<?php
/**
 * AKISMET REPORT SPAM
 * 
 * Em options do campo é preciso passar um array de pareamento dos postmetas do posttype a serem reportados
 * 
 * <code>
 * 'options' => array(
 *     'fields' => array(
 *         'name'    => 'nome',
 *         'email'   => 'email',
 *         'content' => 'mensagem',
 *     ),
 * ),
 * </code>
 * 
 * 
 */

class BFE_akismet_report_spam extends BorosFormElement {
    
    var $valid_attrs = array();
    
    var $enqueues = array(
        'js' => 'akismet_report_spam',
    );
    
    function set_input( $value = null ){
        $post_id = $this->context['post_id'];
        $spam_status = get_post_meta( $post_id, 'spam_status', true );
        if( $spam_status == false ){
            $message   = '';
            $btn_text  = 'denunciar spam';
            $btn_class = 'btn-error';
            $report    = 'spam';
        }
        else{
            $message   = 'marcado como spam';
            $btn_text  = 'desmarcar spam';
            $btn_class = 'btn-neutral';
            $report    = 'ham';
        }

        ob_start();
        
        echo "<div><button type='button' class='button btn-akismet-report-spam {$btn_class}' data-post-id='{$post_id}' data-field-name='{$this->data['options']['fields']['name']}' data-field-email='{$this->data['options']['fields']['email']}' data-field-content='{$this->data['options']['fields']['content']}' data-report='{$report}'>{$btn_text}</button><div class='report-message'>{$message}</div></div>";
        
        $input = ob_get_contents();
        ob_end_clean();
        return $input;
    }
}

add_action( 'wp_ajax_boros_akismet_report_spam', 'boros_akismet_report_spam' );
function boros_akismet_report_spam(){

    //print_r($_POST);
    require_once BOROS_LIBS . '/Akismet.class.php';

    $post_id     = $_POST['post_id'];
    $akismet_key = get_option('wordpress_api_key');
    $home_url    = home_url('/');
    $akismet     = new AkismetValidation( $home_url, $akismet_key);
    $marked_spam = get_post_meta($post_id, 'is_spam', true);

    if( $akismet->isKeyValid() ){
        $fields = array(
            'name'    => 'setCommentAuthor',
            'email'   => 'setCommentAuthorEmail',
            'content' => 'setCommentContent',
        );
        foreach( $fields as $field => $callback ){
            $value = get_post_meta( $post_id, $_POST[$field], true );
            if( !empty( $value ) ){
                $akismet->$callback( $value );
            }
        }

        if( $_POST['report'] == 'spam' ){
            $akismet->submitSpam();
            update_post_meta( $post_id, 'spam_status', true );
            wp_send_json(array(
                'message'     => 'marcado como spam',
                'button'      => 'desmarcar spam',
                'removeClass' => 'btn-error',
                'addClass'    => 'btn-neutral',
                'report'      => 'ham',
            ));
        }
        else{
            update_post_meta( $post_id, 'spam_status', false );
            wp_send_json(array(
                'message'     => 'desmarcado como spam',
                'button'      => 'denunciar spam',
                'removeClass' => 'btn-neutral',
                'addClass'    => 'btn-error',
                'report'      => 'spam',
            ));
        }
    }

    die();
}
