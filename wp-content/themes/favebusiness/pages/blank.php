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

the_post();
?>

<div <?php post_class('entry entry-full clearfix'); ?>>
	<div class="entry-content">
		<?php
		the_content(__('Continued&hellip;', 'favebusiness'));
		wp_link_pages();
		?>
	</div>
	<?php edit_post_link(__('Edit', 'favebusiness')); ?>
</div><!-- .entry -->

	<?php comments_template(); ?>

<?php get_footer(); ?>