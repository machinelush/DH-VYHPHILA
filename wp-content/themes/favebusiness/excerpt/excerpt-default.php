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

?>
<div id="post-excerpt-<?php the_ID(); ?>" <?php post_class('entry entry-excerpt clearfix') ?>>
	<div class="entry-header">
		<h2 class="entry-title"><a rel="bookmark" href="<?php the_permalink() ?>"><?php the_title() ?></a></h2>
		<div class="entry-info">
			<abbr class="published" title="<?php the_time('Y-m-d\TH:i'); ?>"><?php the_time('F j, Y'); ?></abbr>
			<span class="spacer">&bull;</span>
			<span class="author vcard"><span class="fn"><?php the_author(); ?></span></span>
			<span class="spacer">&bull;</span>
			<?php comments_popup_link(__('No comments', 'favebusiness'), __('1 comment', 'favebusiness'), __('% comments', 'favebusiness')); ?>
		</div>
	</div>
	<div class="entry-summary">
		<?php
		if (has_post_thumbnail()) {
			echo '<a href="', the_permalink(),'">', the_post_thumbnail('post-thumbnail', array('class' => 'entry-img')), '</a>';
		}
		the_excerpt();
		?>
	</div>
	<div class="entry-footer">
		<?php _e('In', 'favebusiness'); ?>
		<?php
		the_category(', ');
		the_tags(__(' <span class="spacer">&bull;</span> Tagged ', 'favebusiness'), ', ', '');
		?>
	</div>
	<?php edit_post_link(__('Edit', 'favebusiness')); ?>
</div>