<?php
/**
 * ==================================================
 * SECURITY =========================================
 * ==================================================
 * Funções para melhorar a segurança do WordPress. A maioria dos itens usados são de artigos e tutoriais encontrados na internet.
 * 
 * Neste arquivo estão os filtros e functions gerais que valem para qualquer projeto.
 * 
 */


/**
 * Desabilitar o pingback, baseado no plugin "Disable XML-RPC Pingback", de 'Samuel Aguilera'
 * 
 * @link https://wordpress.org/plugins/disable-xml-rpc-pingback/
 */
add_filter( 'xmlrpc_methods', 'Remove_Pingback_Method' );
function Remove_Pingback_Method( $methods ) {
	unset( $methods['pingback.ping'] );
	unset( $methods['pingback.extensions.getPingbacks'] );
	return $methods;
}



