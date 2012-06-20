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

function cfctbiz_option_defaults($defaults) {
	$defaults[cfct_option_name('news_enable')] = 'yes';
	return $defaults;
}
add_filter('cfct_option_defaults', 'cfctbiz_option_defaults');

function cfctbiz_options($options) {
	unset($options['cfct']['fields']['about']);
	unset($options['cfct']['fields']['header']);
	
	$yn_options = array(
		'yes' => __('Yes', 'favebusiness'),
		'no' => __('No', 'favebusiness'),
	);

	$cfctbiz_options = array(
		'cfctbiz' => array(
			'label' => '',
			'description' => 'cfct_options_blank',
			'fields' => array(
				'news_enabled' => array(
					'label' => __('Enable News Section', 'favebusiness'),
					'type' => 'radio',
					'options' => $yn_options,
					'name' => 'news_enable',
				),
				'news_title' => array(
					'type' => 'text',
					'label' => __('News Title', 'favebusiness'),
					'name' => 'news_title',
					'help' => ' <span class="cfct-help">'.__('(shown in header of News section)', 'favebusiness').'</span>',
				),
				'blog_title' => array(
					'type' => 'text',
					'label' => __('Blog Title', 'favebusiness'),
					'name' => 'blog_title',
					'help' => ' <span class="cfct-help">'.__('(shown in header of Blog section)', 'favebusiness').'</span>',
				),
			),
		),
	);
	
	$options = cfct_array_merge_recursive($cfctbiz_options, $options);
	return $options;
}
add_filter('cfct_options', 'cfctbiz_options');


function cfctbiz_cfct_settings_form_after() {
?>
<style type="text/css">
.cfct-help {
	color: #777;
	font-size: 11px;
}
.txt-center {
	text-align: center;
}
#cf {
	width: 90%;
}

/* Developed by and Support by callouts */
#cf-callouts {
	background: url(<?php echo get_bloginfo('template_url'); ?>/wp-admin/settings-page/border-fade-sprite.gif) 0 0 repeat-x;
	float: left;
	margin: 18px 0;
}
.cf-callout {
	float: left;
	margin-top: 18px;
}
#cf-callout-credit {
	margin-right: 9px;
}
#cf-callout-credit .cf-box-title {
	background: #193542 url(<?php echo get_bloginfo('template_url'); ?>/wp-admin/settings-page/box-sprite.png) 0 0 repeat-x;
	border-bottom: 1px solid #0C1A21;
}
#cf-callout-support {
	margin-left: 9px;
}
#cf-callout-support .cf-box-title {
	background: #8D2929 url(<?php echo get_bloginfo('template_url'); ?>/wp-admin/settings-page/box-sprite.png) 0 -200px repeat-x;
	border-bottom: 1px solid #461414;
}

/* General cf-box styles */
.cf-box { 
	background: #EFEFEF url(<?php echo get_bloginfo('template_url'); ?>/wp-admin/settings-page/box-sprite.png) 0 -400px repeat-x;
	border: 1px solid #E3E3E3;
	border-radius: 5px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	-khtml-border-radius: 5px;
}
.cf-box .cf-box-title {
	color: #fff;
	font-size: 14px;
	font-weight: normal;
	padding: 6px 15px;
	margin: 0 0 12px 0;
	-moz-border-radius-topleft: 5px;
	-webkit-border-top-left-radius: 5px;
	-khtml-border-top-left-radius: 5px;
	border-top-left-radius: 5px;
	-moz-border-radius-topright: 5px;
	-webkit-border-top-right-radius: 5px;
	-khtml-border-top-right-radius: 5px;
	border-top-right-radius: 5px;
	text-align: center;
	text-shadow: #333 0 1px 1px;
}
.cf-box .cf-box-title a {
	display: block;
	color: #fff;
}
.cf-box .cf-box-title a:hover {
	color: #E1E1E1;
}
.cf-box .cf-box-content {
	margin: 0 15px 15px 15px;
}
.cf-box .cf-box-content p {
	font-size: 11px;
}
</style>
	<div id="cf">
		<div id="cf-callouts">
			<div class="cf-callout">
				<div id="cf-callout-credit" class="cf-box">
					<h3 class="cf-box-title">Theme Developed By</h3>
					<div class="cf-box-content">
						<p class="txt-center"><a href="http://crowdfavorite.com/" title="Crowd Favorite : Elegant WordPress and Web Application Development"><img src="<?php echo get_bloginfo('template_url'); ?>/wp-admin/settings-page/cf-logo.png" alt="Crowd Favorite"></a></p>
						<p>An independent development firm specializing in WordPress development and integrations, sophisticated web applications, Open Source implementations and user experience consulting. If you need it to work, trust Crowd Favorite to build it.</p>
					</div><!-- .cf-box-content -->
				</div><!-- #cf-callout-credit -->						
			</div>
		</div><!-- #cf-callouts -->
	</div><!-- #cf -->
<?php
}
add_action('cfct_settings_form_after', 'cfctbiz_cfct_settings_form_after');

function cfctbiz_cfct_admin_settings_title($str) {
	return __('FaveBusiness Settings', 'favebusiness');
}
add_filter('cfct_admin_settings_form_title', 'cfctbiz_cfct_admin_settings_title');

function cfctbiz_cfct_admin_settings_form_title($str) {
	global $wp_version;
	return __('FaveBusiness Settings', 'favebusiness');
}
add_filter('cfct_admin_settings_form_title', 'cfctbiz_cfct_admin_settings_form_title');

function cfctbiz_option_prefix($prefix) {
	return 'cfctbiz';
}
add_action('cfct_option_prefix', 'cfctbiz_option_prefix');

?>