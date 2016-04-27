<?php
/**
 * Functions para auxiliar o desenvolvimento localhost. NÃO USAR EM PRODUÇÃO!
 * Para manter este arquivo seguro, todas as configurações usadas neste arquivo são definidas no wp-config.php
 * 
 */

/**
 * ==================================================
 * CONFIGURAR EMAIL LOCALHOST PARA GMAIL ============
 * ==================================================
 * O WordPress usao o phpmailer, que pode ser filtrado pelo hook 'phpmailer_init', portanto é possível utilizar o gmail como servidor de emails enquanto se desenvolve em localhost.
 * 
 */
add_action( 'phpmailer_init', 'boros_gmail_smtp' );
function boros_gmail_smtp( $phpmailer ){
    if( defined('BOROS_SMTP_FROM') ){
        $phpmailer->Mailer     = 'smtp';
        $phpmailer->From       = BOROS_SMTP_FROM;
        $phpmailer->FromName   = BOROS_SMTP_NAME;
        $phpmailer->Sender     = $phpmailer->From;
        $phpmailer->Host       = 'smtp.gmail.com';
        $phpmailer->SMTPSecure = 'tls';
        $phpmailer->Port       = 587;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = BOROS_SMTP_LOGIN;
        $phpmailer->Password   = BOROS_SMTP_PASS;
        $phpmailer->AddReplyTo(
            $phpmailer->From,
            $phpmailer->FromName
        );
    }
}


