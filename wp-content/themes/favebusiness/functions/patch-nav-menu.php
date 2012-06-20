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
 * Take care of delta between argument names in wp_nav_menu vs wp_page menu
 */
function cfct_patch_nav_menu_args( $args ) {
	// Show home if wp_page_menu is used as fallback
	$args['show_home'] = true;
	// wp_page_menu uses different argument names for container class. We'll take care of the difference.
	$args['menu_class'] = $args['container_class'];
	return $args;
}
add_filter( 'wp_page_menu_args', 'cfct_patch_nav_menu_args' );

/**
 * Reduce delta between markup output of wp_nav_menu and wp_page_menu
 * Honor container setting for wp_nav_menu in wp_page_menu
 */
function cfct_patch_nav_menu_container($menu, $args) {
	// Container arg is passed along by wp_nav_menu.
	// If no conainer is passed, strip it out from wp_page_menu.
	
	$id = ($args['container_id'] ? ' id="'.$args['container_id'].'"' : '');
	
	// String replacements are brittle, but it's all we have for now.
	// Remove menu divs if there is no container specified AND this function has been called by wp_nav_menu.
	if (!$args['container'] && $args['fallback_cb'] == 'wp_page_menu') {
		$menu = str_replace(array('<div class="'.$args['menu_class'].'">', "</div>\n"), '', $menu);
	}
	// If container is a nav tag, replace div with nav. Include ID, too.
	else if ($args['container'] == 'nav') {
		$menu = str_replace(array('<div class="'.$args['menu_class'].'">', "</div>\n"), array('<nav class="'.$args['menu_class'].'" '.$id.'>', "</nav>\n"), $menu);
	}
	// If we have a container, make sure container ID is included
	else if ($args['container_id']) {
		$menu = str_replace('class="'.$args['menu_class'].'"', 'class="'.$args['menu_class'].'"'.$id, $menu);
	}
	return $menu;
}
add_filter('wp_page_menu', 'cfct_patch_nav_menu_container', 10, 2);
?>