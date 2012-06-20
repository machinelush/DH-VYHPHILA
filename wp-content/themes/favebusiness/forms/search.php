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
if (CFCT_DEBUG) { cfct_banner(__FILE__); }

$s = get_search_query();

// Generate unique ID for searchform, so this file can be loaded multiple times
$id = uniqid('s-');

if (get_option('permalink_structure') != '') {
	$onsubmit = "location.href=this.action+'search/'+encodeURIComponent(this.s.value).replace(/%20/g, '+'); return false;";
}
else {
	$onsubmit = '';
}

?>
<form class="searchform" method="get" action="<?php echo home_url('/'); ?>" onsubmit="<?php echo $onsubmit; ?>">
	<label for="<?php echo $id; ?>"><?php _e('Search', 'favebusiness'); ?></label>
	<div>
		<input type="text" id="<?php echo $id; ?>" class="s" name="s" value="<?php esc_attr_e($s); ?>" />
		<input type="submit" class="searchsubmit" value="<?php _e('Search', 'favebusiness'); ?>" />
	</div>
</form>