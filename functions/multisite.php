<?php
/**
 * ==================================================
 * MULTISITE ========================================
 * ==================================================
 * Functions extras para instalações multisite.
 * 
 */

function _get_subsites_list(){
    global $wpdb;
    $sites = $wpdb->get_results( "SELECT * FROM {$wpdb->blogs} ORDER BY blog_id" );
    
    // remover sites desativados da lista
    foreach( $sites as $key => $val ){
        if( $val->deleted == 1 )
            unset( $sites[$key]);
    }
    return $sites;
}



/**
 * ==================================================
 * POST_META IN MULTISITE ===========================
 * ==================================================
 * 
 * Buscar postmetas quando puxar posts de outro blog
 * 
 * @link   http://www.htmlcenter.com/blog/wordpress-multi-site-get-a-featured-image-from-another-blog/
 */
if( !function_exists( 'get_the_post_thumbnail_by_blog' ) ) {
    function get_the_post_thumbnail_by_blog($blog_id = NULL, $post_id = NULL, $size='post-thumbnail', $attrs=NULL) {
        global $current_blog;
        $sameblog = false;

        if( empty( $blog_id ) || $blog_id == $current_blog->ID ) {
            $blog_id = $current_blog->ID;
            $sameblog = true;
        }
        if( empty( $post_id ) ) {
            global $post;
            $post_id = $post->ID;
        }
        if( $sameblog )
            return get_the_post_thumbnail( $post_id, $size, $attrs );

        if( !has_post_thumbnail_by_blog($blog_id, $post_id) )
            return false;

        
        
        switch_to_blog( $blog_id );
        $thumb_id = get_post_meta($post_id, '_thumbnail_id', true);
        if($thumb_id){
            $tt = wp_get_attachment_image_src( $thumb_id, 'post-thumbnail' );
            return $tt;
        }
        else{
            return false;
        }
        
        
        
        global $wpdb;
        $oldblog = $wpdb->set_blog_id( $blog_id );

        $blogdetails = get_blog_details( $blog_id );
        $thumbcode = str_replace( $current_blog->domain . $current_blog->path, $blogdetails->domain . $blogdetails->path, get_the_post_thumbnail( $post_id, $size, $attrs ) );

        $wpdb->set_blog_id( $oldblog );
        return $thumbcode;
    }

    function has_post_thumbnail_by_blog( $blog_id = NULL, $post_id = NULL) {
        if( empty( $blog_id ) ) {
            global $current_blog;
            $blog_id = $current_blog;
        }
        if( empty( $post_id ) ) {
            global $post;
            $post_id = $post->ID;
        }

        global $wpdb;
        $oldblog = $wpdb->set_blog_id( $blog_id );

        $thumbid = has_post_thumbnail( $post_id );
        $wpdb->set_blog_id( $oldblog );
        return ($thumbid !== false) ? true : false;
    }

    function the_post_thumbnail_by_blog( $blog_id = NULL, $post_id = NULL, $size = 'post-thumbnail', $attrs = NULL) {
        echo get_the_post_thumbnail_by_blog( $blog_id, $post_id, $size,$attrs);
    }
}



/**
 * ==================================================
 * WORDPRESS QUERY MULTISITE ========================
 * ==================================================
 * Busca global em todos os sites da rede
 * 
 * Necessário iniciar a classe com :
 
   new WP_Query_Multisite();

 * E filtrar o pre_get_posts, setando multisite true e os post types que deseja buscar:
 
   add_action('pre_get_posts', 'fjsp_multisite_search');
   function fjsp_multisite_search( $query ){
       if( !is_admin() && $query->is_main_query() && $query->is_search ){
           $query->set('multisite', 1);
           $query->set('post_type', array('page', 'post'));
       }
   }
   
 * 
 * 
 * @link https://github.com/miguelpeixe/WP_Query_Multisite
 * bab280e  on 18 Jan 2016
 */
if( !class_exists('WP_Query_Multisite') ){
class WP_Query_Multisite {

    function __construct() {
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('pre_get_posts', array($this, 'pre_get_posts'), 100);
        add_filter('posts_clauses', array($this, 'posts_clauses'), 10, 2);
        add_filter('posts_request', array($this, 'posts_request'), 10, 2);
        add_action('the_post', array($this, 'the_post'));
        add_action('loop_end', array($this, 'loop_end'));
    }

    function query_vars($vars) {
        $vars[] = 'multisite';
        $vars[] = 'sites__not_in';
        $vars[] = 'sites__in';
        return $vars;
    }

    function pre_get_posts($query) {
        if($query->get('multisite')) {

            global $wpdb, $blog_id;

            $this->loop_end = false;
            $this->blog_id = $blog_id;

            $site_IDs = $wpdb->get_col( "select blog_id from $wpdb->blogs" );

            if ( $query->get('sites__not_in') )
                foreach($site_IDs as $key => $site_ID )
                    if (in_array($site_ID, $query->get('sites__not_in')) ) unset($site_IDs[$key]);

            if ( $query->get('sites__in') )
                foreach($site_IDs as $key => $site_ID )
                    if ( ! in_array($site_ID, $query->get('sites__in')) )
                        unset($site_IDs[$key]);

            $site_IDs = array_values($site_IDs);

            $this->sites_to_query = $site_IDs;
        }
    }

    function posts_clauses($clauses, $query) {
        if($query->get('multisite')) {
            global $wpdb;

            // Start new mysql selection to replace wp_posts on posts_request hook
            $this->ms_select = array();

            $root_site_db_prefix = $wpdb->prefix;
            foreach($this->sites_to_query as $site_ID) {

                switch_to_blog($site_ID);

                $ms_select = $clauses['join'] . ' WHERE 1=1 '. $clauses['where'];

                if($clauses['groupby'])
                    $ms_select .= ' GROUP BY ' . $clauses['groupby'];

                $ms_select = str_replace($root_site_db_prefix, $wpdb->prefix, $ms_select);
                $ms_select = " SELECT $wpdb->posts.*, '$site_ID' as site_ID FROM $wpdb->posts $ms_select ";

                $this->ms_select[] = $ms_select;

                restore_current_blog();

            }

            // Clear join, where and groupby to populate with parsed ms select on posts_request hook;
            $clauses['join'] = '';
            $clauses['where'] = '';
            $clauses['groupby'] = '';

            // Orderby for tables (not wp_posts)
            $clauses['orderby'] = str_replace($wpdb->posts, 'tables', $clauses['orderby']);

        }
        return $clauses;
    }

    function posts_request($sql, $query) {

        if($query->get('multisite')) {

            global $wpdb;

            // Clean up remanescent WHERE request
            $sql = str_replace('WHERE 1=1', '', $sql);

            // Multisite request
            $sql = str_replace("$wpdb->posts.* FROM $wpdb->posts", 'tables.* FROM ( ' . implode(" UNION ", $this->ms_select) . ' ) tables', $sql);

        }

        return $sql;
    }

    function the_post($post) {
        global $blog_id;

        if( isset( $this->loop_end ) && !$this->loop_end && $post->site_ID && $blog_id !== $post->site_ID) {
            switch_to_blog($post->site_ID);
        }

    }

    function loop_end($query) {
        global $switched;
        if($query->get('multisite')) {
            $this->loop_end = true;
            if($switched) {
                switch_to_blog($this->blog_id);
            }
        }
    }
}
}