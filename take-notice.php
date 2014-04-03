<?php
/**
 * Plugin Name: Take Notice
 * Plugin URI: http://alisothegeek.com/wordpress-plugins/take-notice/
 * Description: Add a site-wide message to your blog that displays on every post and/or page.
 * Version: 1.1
 * Author: Alison Barrett
 * Author URI: http://alisothegeek.com/
 * License: GPLv2
 * 
 * Copyright 2011  Alison Barrett (email : alison@barrettcreative.net)
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as 
 * published by the Free Software Foundation.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * @package take-notice
 * @since 1.0
 */
class Take_Notice {
	
	/**
	 * server path to plugin folder
	 *
	 * @var string
	 * @since 1.0
	 */
	public $dir;
	
	/**
	 * Construct
	 *
	 * @since 1.0
	 */
	public function __construct() {
		
		$this->dir = plugin_dir_path( __FILE__ );
		$this->wp_hooks();
		
	}
	
	/**
	 * Add all notices to content
	 *
	 * @since 1.0
	 */
	public function add_to_content( $content ) {
		
		global $post;
		
		if ( ( is_single() || is_page() ) && ! is_front_page() ) :
		
			$notices = get_posts( 'post_type=notice&posts_per_page=-1' );
			foreach ( $notices as $notice ) :
				
				$show_notice = false;
				
				$show_at = get_post_meta( $notice->ID, '_takenotice_show_at', true );
				$show_title = get_post_meta( $notice->ID, '_takenotice_show_title', true );
				$notice_color = get_post_meta( $notice->ID, '_takenotice_color', true );
				
				$show_on = get_post_meta( $notice->ID, '_takenotice_show_on' );
				$show_on_page = get_post_meta( $notice->ID, '_takenotice_show_on_page' );
				$show_on_category = get_post_meta( $notice->ID, '_takenotice_show_on_category' );
				$show_on_tag = get_post_meta( $notice->ID, '_takenotice_show_on_tag' );
				
				if ( is_single() ) {
					if ( in_array( 'posts', $show_on ) ) {
						$show_notice = true;
					}
					else {
						
						$categories = wp_get_post_categories( $post->ID );
						if ( ! empty( $categories ) ) {
							foreach ( $categories as $cat_id ) {
								if ( in_array( $cat_id, $show_on_category ) )
									$show_notice = true;
							}
						}
						
						$tags = wp_get_post_tags( $post->ID );
						if ( ! empty( $tags ) && ! $show_notice ) {
							foreach ( $tags as $tag ) {
								if ( in_array( $tag->term_id, $show_on_tag ) )
									$show_notice = true;
							}
						}
						
					}
				}
				else {
					if ( in_array( 'pages', $show_on ) ) {
						$show_notice = true;
					}
					else {
						if ( in_array( $post->ID, $show_on_page ) )
							$show_notice = true;
					}
				}
				
				if ( $show_notice ) {
					$notice_content = wpautop( wptexturize( $notice->post_content ) );
					if ( $show_title )
						$notice_content = '<h1>' . $notice->post_title . '</h1>' . $notice_content;
					
					if ( $show_at == 'top' )
						$content = '<div class="take-notice ' . $notice_color . '">' . $notice_content . '</div>' . $content;
					else
						$content .= '<div class="take-notice ' . $notice_color . '">' . $notice_content . '</div>';
				}
				
			endforeach;
			
			return $content;
		
		else :
		
			return $content;
		
		endif;
		
	}
	
	/**
	 * Add meta box for notice post type
	 *
	 * @since 1.0
	 */
	public function notice_meta_box() {
		
		if ( is_admin() ) {
			
			require_once( $this->dir . 'class-rw-meta-box.php' );
			$prefix = '_takenotice_';
			
			// Put all pages into an array for the checkbox list
			$all_pages = array();
			$pages = get_posts( 'post_type=page&posts_per_page=-1&orderby=title&order=ASC' );
			foreach ( $pages as $page )
				$all_pages[$page->ID] = $page->post_title;
			
			// Put all categories into an array for the checkbox list
			$all_categories = array();
			$categories = get_terms( 'category', 'orderby=name&hide_empty=0' );
			foreach ( $categories as $category )
				$all_categories[$category->term_id] = $category->name;
			
			// Put all tags into an array for the checkbox list
			$all_tags = array();
			$tags = get_terms( 'post_tag', 'orderby=name&hide_empty=0' );
			foreach ( $tags as $tag )
				$all_tags[$tag->term_id] = $tag->name;
			
			$meta_box = array(
				'id' => 'takenotice-meta-box',
				'title' => 'Notice Details',
				'pages' => array( 'notice' ), // custom post types, since WordPress 3.0
				'context' => 'normal',
				'priority' => 'high',
				'fields' => array(
					array(
						'name' => 'Position in content',
						'desc' => '',
						'id' => $prefix . 'show_at',
						'type' => 'select',
						'options' => array(
							'top' => 'Above content',
							'bottom' => 'Below content'
						)
					),
					array(
						'name' => 'Show title',
						'desc' => 'Show title of notice',
						'id' => $prefix . 'show_title',
						'type' => 'checkbox'
					),
					array(
						'name' => 'Color',
						'desc' => '',
						'id' => $prefix . 'color',
						'type' => 'select',
						'options' => array(
							'grey' => 'Grey',
							'red' => 'Red',
							'yellow' => 'Yellow',
							'green' => 'Green',
							'blue' => 'Blue'
						)
					),
					array(
						'name' => 'Show on all',
						'desc' => '<em>NOTE: This will override any specific page/post settings below.</em>',
						'id' => $prefix . 'show_on',
						'type' => 'checkbox_list',
						'options' => array(
							'posts' => 'Posts',
							'pages' => 'Pages'
						)
					),
					array(
						'name' => 'Show on specific pages',
						'desc' => '',
						'id' => $prefix . 'show_on_page',
						'type' => 'checkbox_list',
						'options' => $all_pages
					),
					array(
						'name' => 'Show on posts by category',
						'desc' => '',
						'id' => $prefix . 'show_on_category',
						'type' => 'checkbox_list',
						'options' => $all_categories
					),
					array(
						'name' => 'Show on posts by tag',
						'desc' => '',
						'id' => $prefix . 'show_on_tag',
						'type' => 'checkbox_list',
						'options' => $all_tags
					)
				)
			);
			
			$notice_meta_box = new RW_Meta_Box( $meta_box );
			
		}
		
	}
	
	/**
	 * Register post type
	 *
	 * @since 1.0
	 */
	public function register_post_type() {
		
		register_post_type( 'notice', array(
			'labels' => array(
				'name' => __( 'Notices' ),
				'singular_name' => __( 'Notice' ),
				'add_new' => __( 'Add New' ),
				'add_new_item' => __( 'Add New Notice' ),
				'edit_item' => __( 'Edit Notice' ),
				'new_item' => __( 'New Notice' ),
				'view_item' => __( 'View Notice' ),
				'search_items' => __( 'Search Notices' ),
				'not_found' => __( 'No notices found' ),
				'not_found_in_trash' => __( 'No notices found in Trash' ), 
				'parent_item_colon' => __( 'Parent Notice:' ),
				'menu_name' => __( 'Notices' )
			),
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => true, 
			'query_var' => true,
			'rewrite' => array( 'slug' => 'notice' ),
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => null,
			'menu_icon' => plugins_url( '', __FILE__ ) . '/sticky-note-pin.png',
			'supports' => array( 'title', 'editor', 'author' )
		) );
		
	}
	
	/**
	 * Add stylesheet
	 *
	 * @since 1.0
	 */
	public function register_styles() {
		
		if ( ( is_single() || is_page() ) && ! is_front_page() ) {
			wp_register_style( 'take-notice', plugins_url( '', __FILE__ ) . '/style.css' );
			wp_enqueue_style( 'take-notice' );
		}
		
	}
	
	/**
	 * WordPress hooks & filters
	 *
	 * @since 1.0
	 */
	public function wp_hooks() {
		
		add_action( 'init',            array( &$this, 'register_post_type' ) );
		add_action( 'init',            array( &$this, 'notice_meta_box' ) );
		add_action( 'wp_print_styles', array( &$this, 'register_styles' ) );
		add_filter( 'the_content',     array( &$this, 'add_to_content' ), 1, 1 );
		
	}
	
}

if ( class_exists( 'Take_Notice' ) )
	$Take_Notice = new Take_Notice();

?>