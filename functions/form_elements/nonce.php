<?php
/**
 * NONCE
 * Nonce para form frontend
 * 
 * 
 */

class BFE_nonce extends BorosFormElement {
    
    function set_input( $value = null ){

        $input = wp_nonce_field( "{$this->data['name']}_nonce", $this->data['name'], true, false );

        return $input;
    }
}