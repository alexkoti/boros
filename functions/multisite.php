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
	$sites = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->blogs} ORDER BY blog_id" ) );
	
	// remover sites desativados da lista
	foreach( $sites as $key => $val ){
		if( $val->deleted == 1 )
			unset( $sites[$key]);
	}
	return $sites;
}



/* ========================================================================== */
/* POST_META IN MULTISITE =================================================== */
/* ========================================================================== */
/**
 * Pegar postmetas quando puxar posts de outro blog
 * 
 * @link	http://www.htmlcenter.com/blog/wordpress-multi-site-get-a-featured-image-from-another-blog/
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

