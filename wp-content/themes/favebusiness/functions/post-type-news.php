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

// only enable if turned on in settings
if (get_option('cfctbiz_news_enable') != 'yes') {
	return;
}

/**
 * Registers the News custom post type
 */
function cfct_register_news() {
	register_post_type('cfct-news', array(
		'labels' => array(
			'name' => __('News', 'favebusiness'),
			'singular_name' => __('News', 'favebusiness')
		),
		'supports' => array(
			'title',
			'editor',
			'thumbnail',
			'revisions'
		),
		'public' => true,
		'rewrite' => array(
			'slug' => 'news'
		),
	));
}
add_action('init', 'cfct_register_news');

/**
 * Add rewrite rules for News post type's archives.
 * You must flush the rewrite rules to activate this action.
 */
function cfct_add_news_rewrites() {
	global $wp_rewrite;
	
	/* Feeds */
	$new_rules['news/feed/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?feed=$matches[1]&post_type=cfct-news';
	$new_rules['news/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?feed=$matches[1]&post_type=cfct-news';
	
	/* Date-based */
	// news/2010/04/
	$new_rules['news/([0-9]{4})/([0-9]{1,2})/?$'] = 'index.php?post_type=cfct-news&year=$matches[1]&monthnum=$matches[2]';
	// Pagination on months
	$new_rules['news/([0-9]{4})/([0-9]{1,2})/page/([0-9]+)/?$'] = 'index.php?post_type=cfct-news&year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]';
	
	// news/2010/
	$new_rules['news/([0-9]{4})/?$'] = 'index.php?post_type=cfct-news&year=$matches[1]';
	// Pagination on years
	$new_rules['news/([0-9]{4})/page/([0-9]+)/?$'] = 'index.php?post_type=cfct-news&year=$matches[1]&paged=$matches[2]';
	
	// all
	$new_rules['news/?$'] = 'index.php?post_type=cfct-news';
	// All, paginated
	$new_rules['news/page/([0-9]+)/?$'] = 'index.php?post_type=cfct-news&paged=$matches[1]';
	
	$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
}
add_action('generate_rewrite_rules', 'cfct_add_news_rewrites');

?>