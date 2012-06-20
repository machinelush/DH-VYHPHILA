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

get_header();

$s = esc_html(get_search_query());
?>
<header id="masthead">
	<h1 class="page-title-b"><?php printf(__('<i class="label">Results for:</i> %s', 'favebusiness'), $s); ?></h1>
</header><!-- #masthead -->
<div class="col-full">
	<?php
	cfct_loop();
	cfct_misc('nav-posts');
	?>
</div>
<?php get_footer(); ?>