<?php
/**
 * FORM ELEMENT: 
 * 
 * 
 * 
 */

function form_element_excs_static_title( $data, $data_value, $parent ){
	global $post;
	?>
	<tr>
		<td class="boros_form_element boros_element_static_title" colspan="2">
			<h4>Título automático: <strong><?php echo get_the_title($post->ID); ?></strong></h4>
			<span class="ipt_helper">O título automático é gerado baseado no nome da série e número cadastrados.</span><br />
			<span class="ipt_helper">Caso o produto não faça parte de uma série, e portanto não sujeito à criação automática de título, usar o campo abaixo 
			para o cadastro do título. Usar também nos casos da revista possuir um subtítulo, por ex. Asterix(série), O Escudo Arverno(Título da Edição).</span>
		</td>
	</tr>
	<?php
}