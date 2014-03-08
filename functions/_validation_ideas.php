<?php
/**
 * SANITIZE EMAIL
 * Previne headers injection
 * 
 * @link http://www.phpro.org/examples/Sanitize-Email.html
 */
function safeEmail($string) {
     return  preg_replace( '((?:\n|\r|\t|%0A|%0D|%08|%09)+)i' , '', $string );
}

/*** example usage ***/
$from = "sender@example.com
Cc:victim@example.com";

if(strlen($from) < 100)
{
    $from = safeEmail($from);
}



