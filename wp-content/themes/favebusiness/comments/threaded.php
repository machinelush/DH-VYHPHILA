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

<li class="li-comment" id="li-comment-<?php comment_ID() ?>">
	<div class="div-comment" id="div-comment-<?php comment_ID(); ?>">
<?php
cfct_comment($data);
?>
	</div>
<?php
// Dropped </li> is intentional: WordPress figures out where to place the </li> so it can nest comment lists.
?>