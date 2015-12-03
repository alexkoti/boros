<?php
$paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
$custom = new WP_Query(array(
    'post_type' => 'post',
    'posts_per_page' => 10,
    'posts_status' => 'publish',
    'meta_key' => 'lorem',
    'meta_value' => 'ipsum',
    'paged' => $paged,
    'tax_query' => array(
        'relation' => 'AND',
        array(
            'taxonomy' => 'movie_genre',
            'field'    => 'slug',
            'terms'    => array( 'action', 'comedy' ),
        ),
        array(
            'taxonomy' => 'actor',
            'field'    => 'term_id',
            'terms'    => array( 103, 115, 206 ),
            'operator' => 'NOT IN',
        ),
    ),
    'orderby' => 'menu_order',
    'order' => 'ASC',
));
if( $custom->have_posts() ){   
    $page_args = array(
        'always_show' => false,
        'num_pages' => 5,
        'ul_class' => '',
        'li_class' => ' ',
        'link_class' => 'btn',
        'pages_text' => '',
        'first_text' => '«',
        'dotleft_text' => '',
        'last_text' => '»',
        'dotright_text' => '',
        'prev_text' => '‹',
        'next_text' => '›',
        'page_text' => '%PAGE_NUMBER%',
        'current_text' => '%PAGE_NUMBER%',
    );
    boros_pagination( $page_args );
    echo '<ul>';
    while( $custom->have_posts() ){
        $custom->the_post();
        get_template_part( 'loop-item', 'post' );
    }
    echo '</ul>';
    boros_pagination( $page_args );
}
wp_reset_postdata();

