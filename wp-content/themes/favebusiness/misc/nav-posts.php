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
global $wp_query;
if ($wp_query->max_num_pages > 1) {
?>
<div class="pagination">
	<?php
	previous_posts_link(__('Prev', 'favebusiness'));
	next_posts_link(__('Next', 'favebusiness'));
	cfct_page_x_of_y(array(
		'before' => '<p>',
		'after' => '</p>'
	));
	?>
</div>
<?php
}
?>