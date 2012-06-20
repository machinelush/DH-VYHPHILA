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
		</div><!-- .str-content -->
	</div><!-- #main-content -->
</section><!-- .str-container -->
<hr class="accessibility" />
<footer id="footer">
	<div class="str-container">
		<div id="footer-content">
			<div class="str-content clearfix">
				<div class="c6-12">
					<?php
					if (!dynamic_sidebar('footer-a')) { ?>
					<aside class="widget style-f">
						<h1 class="widget-title"><?php _e('No Widgets Yet!', 'favebusiness'); ?></h1>
						<p><?php printf(__('It looks like you haven&rsquo;t added any widgets to this sidebar (Footer Left) yet. To customize this sidebar, go <a href="%s">add some</a>!', 'favebusiness'), admin_url('widgets.php')); ?></p>
					</aside>
					<?php
					}
					?>
				</div>
				<div class="c6-34">
					<?php
					if (!dynamic_sidebar('footer-b')) { ?>
					<aside class="widget style-f">
						<h1 class="widget-title"><?php _e('No Widgets Yet!', 'favebusiness'); ?></h1>
						<p><?php printf(__('It looks like you haven&rsquo;t added any widgets to this sidebar (Footer Center) yet. To customize this sidebar, go <a href="%s">add some</a>!', 'favebusiness'), admin_url('widgets.php')); ?></p>
					</aside>
					<?php
					}
					?>
				</div>
				<div class="c6-56">
					<?php
					if (!dynamic_sidebar('footer-c')) { ?>
					<aside class="widget style-f">
						<h1 class="widget-title"><?php _e('No Widgets Yet!', 'favebusiness'); ?></h1>
						<p><?php printf(__('It looks like you haven&rsquo;t added any widgets to this sidebar (Footer Right) yet. To customize this sidebar, go <a href="%s">add some</a>!', 'favebusiness'), admin_url('widgets.php')); ?></p>
					</aside>
					<?php
					}
					?>
				</div>
			</div><!-- .str-content -->
		</div><!-- #footer-content -->
		<div id="footer-sub">
			<nav class="nav nav-footer">
				<h3 class="site-title"><a href="<?php echo home_url('/') ?>" title="<?php _e('Home', 'favebusiness') ?>"><?php bloginfo('name') ?></a></h3>
				<?php
				wp_nav_menu(array( 
					'theme_location' => 'footer',
					'container' => false,
					'depth' => 1,
				));
				?>
			</nav><!--/nav-footer-->
			<?php
			if (cfct_get_option('cfct_credit') == 'yes') { ?>
			<p id="site-generator"><?php
			printf(__('Powered by <a href="%s">WordPress</a>. <a href="%s" title="FaveBusiness Theme for WordPress">FaveBusiness</a> by <a id="cf-logo" title="Custom Web Applications and WordPress Development" href="%s">Crowd Favorite</a>', 'favebusiness'), 'http://wordpress.org/', 'http://crowdfavorite.com/wordpress/themes/favebusiness/', 'http://crowdfavorite.com/');
			?></p>
			<?php 
			}
			
			$colophon = str_replace('%Y', date('Y'), cfct_get_option('copyright'));
			$sep = ($colophon ? ' &bull; ' : '');
			$loginout = cfct_get_loginout('', $sep);
			if ($colophon || $loginout) {
				echo '<p>'.$colophon.$loginout.'</p>';
			}
			?>
		</div><!-- #footer-sub -->
	</div><!-- .str-container -->
</footer><!-- #footer -->

<?php
if (CFCT_DEBUG) {
?>
<div style="border: 1px solid #ccc; background: #ffc; padding: 20px;">The <code>CFCT_DEBUG</code> setting is currently enabled, which shows the filepath of each included template file. To hide the file paths, edit this setting in the <?php echo CFCT_PATH; ?>functions.php file.</div>
<?php
}

wp_footer();
?>
</body>
</html>