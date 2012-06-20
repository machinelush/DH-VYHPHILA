<?php

/**
 * @package favebusiness
 *
 * This file is part of the FaveBusiness Theme for WordPress
 * http://crowdfavorite.com/wordpress/themes/favebusiness/
 *
 * Copyright (c) 2008-2012 Crowd Favorite, Ltd. All rights reserved.
 * http://crowdfavorite.com
 *
 * **********************************************************************
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * **********************************************************************
 */

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

/**
 * Register widgetized areas
 * @uses register_sidebar
 */
function cfct_widgets_init() {
	$sidebar_defaults = array(
		'before_widget' => '<aside id="%1$s" class="widget clearfix %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h1 class="widget-title">',
		'after_title' => '</h1>'
	);

	register_sidebar(array_merge($sidebar_defaults, array(
		'id' => 'sidebar-default',
		'name' => __('Blog Sidebar', 'favebusiness'),
		'description' => __('Shown on blog posts and archives.', 'favebusiness')
	)));
	register_sidebar(array_merge($sidebar_defaults, array(
		'id' => 'sidebar-news',
		'name' => __('News Sidebar', 'favebusiness'),
		'description' => __('Shown on news pages and archives.', 'favebusiness')
	)));
	
	// Modify args for footer
	$footer_defaults = array_merge($sidebar_defaults, array(
		'before_widget' => '<aside id="%1$s" class="widget style-f clearfix %2$s">',
		'after_widget' => '</aside>'
	));
	register_sidebar(array_merge($footer_defaults, array(
		'id' => 'footer-a',
		'name' => __('Footer (left)', 'favebusiness'),
		'description' => __('Customizable footer area on the left.', 'favebusiness')
	)));
	register_sidebar(array_merge($footer_defaults, array(
		'id' => 'footer-b',
		'name' => __('Footer (center)', 'favebusiness'),
		'description' => __('Customizable footer area in the middle.', 'favebusiness')
	)));
	register_sidebar(array_merge($footer_defaults, array(
		'id' => 'footer-c',
		'name' => __('Footer (right)', 'favebusiness'),
		'description' => __('Customizable footer area on the right.', 'favebusiness')
	)));
}
add_action( 'widgets_init', 'cfct_widgets_init' );
?>