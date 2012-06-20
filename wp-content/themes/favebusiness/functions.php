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

load_theme_textdomain('favebusiness');
/**
 * Set this to "true" to turn on debugging mode.
 * Debug helps with development by showing you the paths of the files loaded by Carrington.
 */
define('CFCT_DEBUG', false);

/**
 * Set this to "false" to disable CSS bundling.
 **/
define('CFCT_PRODUCTION', true);

define('CFCT_PATH', trailingslashit(TEMPLATEPATH));

/**
 * Theme version.
 */
define('CFCT_THEME_VERSION', '1.2.3');

/**
 * Theme URL version.
 * Added to query var at the end of assets to force browser cache to reload after upgrade.
 */
define('CFCT_URL_VERSION', '1.2.3');

include_once(CFCT_PATH.'carrington-core/carrington.php');
include_once(CFCT_PATH.'carrington-build/carrington-build.php');
include_once(CFCT_PATH.'functions/patch-nav-menu.php');
include_once(CFCT_PATH.'functions/css3pie.php');
include_once(CFCT_PATH.'functions/post-type-news.php');
include_once(CFCT_PATH.'functions/sidebars.php');
include_once(CFCT_PATH.'functions/admin.php');

if ( ! function_exists( 'cfct_setup' ) ) {
	function cfct_setup() {
		// Add default posts and comments RSS feed links to head
		add_theme_support( 'automatic-feed-links' );
		
		// This theme uses post thumbnails
		add_theme_support( 'post-thumbnails' );
		// Width, Height, Crop
		set_post_thumbnail_size( 90, 90, true );
		// Image sizes to support Carousel
		add_image_size('post-image-large', 584, 370, true);
		add_image_size('post-image-medium', 426, 270, true);
		add_image_size('post-image-small', 268, 170, true);

		register_nav_menus(array(
			'main' => __( 'Main Navigation', 'favebusiness' ),
			'featured' => __( 'Featured Navigation', 'favebusiness' ),
			'footer' => __( 'Footer Navigation', 'favebusiness' )
		));
		
		
	
		// Attach CSS3PIE behavior to the following elements
		css3pie_enqueue('#main-content, #main-content .str-content, #masthead, #footer-content, #footer-content .str-content, nav.nav li ul, .cfct-module.style-b, .cfct-module.style-b .cfct-mod-title, .cfct-module.style-c, .cfct-module.style-d, .cft-module.style-d .cfct-mod-title, .cfct-notice, .notice, .cfct-pullquote, .cfct-module-image img.cfct-mod-image, .cfct-module-hero, .cfct-module-hero-image, .wp-caption, .loading span, .c4-1234 .cfct-module-carousel, .c4-1234 .cfct-module-carousel, .carousel, .c4-1234 .cfct-module-carousel .car-content');
	}
}
add_action( 'after_setup_theme', 'cfct_setup' );

function cfct_js_global() {
	echo '<script type="text/javascript">
	CFCT = {
		url: "'.trailingslashit(get_bloginfo('url')).'"
	};
</script>';
}
// wp_enqueue_script adds at priority 9, wp_enqueue_style at priority 7. We want this in-between.
add_action('wp_head', 'cfct_js_global', 8);

/**
 * Run the following tasks on init
 */
function cfct_theme_init() {
	// Keep Carrington Build styles out of the front-end. We'll ad our own.
	wp_deregister_style('cfct-build-css');

	// Set up AJAX post request handler
	cfct_ajax_load();
}
add_action('init', 'cfct_theme_init');

/**
 * Next Posts/Comments link attributes
 */
function cfct_next_link_attributes() {
	return 'class="next" rel="next"';
}
add_filter('next_comments_link_attributes', 'cfct_next_link_attributes');
add_filter('next_posts_link_attributes', 'cfct_next_link_attributes');

/**
 * Previous Posts/Comments link attributes
 */
function cfct_previous_link_attributes() {
	return 'class="prev" rel="previous"';
}
add_filter('previous_comments_link_attributes', 'cfct_previous_link_attributes');
add_filter('previous_posts_link_attributes', 'cfct_previous_link_attributes');

/**
 * Add has-img post class to posts with featured image.
 */
function cfct_post_class_thumbnail($classes, $class, $post_id) {
	if (has_post_thumbnail()) {
		$classes[] = 'has-img';
	}
	
	return $classes;
}
add_filter('post_class', 'cfct_post_class_thumbnail', 10, 3);

/**
 * Override the default caption shortcode output
 */
function cfct_img_caption_shortcode($unused, $attr, $content = null) {
	extract(shortcode_atts(array(
		'id' => '',
		'align' => 'alignnone',
		'width' => '',
		'caption' => ''
	), $attr));

	if ( 1 > (int) $width || empty($caption) )
		return $content;

	if ( $id ) $id = 'id="' . esc_attr($id) . '" ';

	return '<div ' . $id . 'class="wp-caption ' . esc_attr($align) . '" style="width: ' . $width . 'px">'
	. do_shortcode( $content ) . '<p class="wp-caption-text">' . $caption . '</p></div>';
}
add_filter('img_caption_shortcode', 'cfct_img_caption_shortcode', 10, 3);

/**
 * Show number of pages available on archive page and where you are
 * @param array||str $args
 */
function cfct_page_x_of_y($args = '') {
	global $wp_query;
	
	$default_args = array(
		'before' => '',
		'after' => '',
		'showalways' => false
	);
	$args = wp_parse_args($args, $default_args);
	extract($args);
	
	$max_num_pages = $wp_query->max_num_pages;
	
	$paged = get_query_var('paged');
	
	if (!$paged && !empty($wp_query->query['offset']) && !empty($wp_query->query['posts_per_page'])) {
		$paged = ($wp_query->query['offset']/$wp_query->query['posts_per_page'])+1;
	}
	
	// If we aren't paged, we're on page 1.
	(!$paged) ? $paged = 1 : $paged;
	
	if ($showalways || $max_num_pages > 1) {
		echo $before . sprintf(__('%s of %s', 'favebusiness'), $paged, $max_num_pages) . $after;
	}
}

/**
 * Output the blog title, or the site title + "Blog" if the blog title is empty.
 */
function cfct_blog_title() {
	$title = cfct_get_option('cfctbiz_blog_title');
	if (!$title) {
		$title = sprintf(__('%s Blog', 'favebusiness'), get_bloginfo('name'));
	}
	echo $title;
}

function cfct_news_title() {
	$title = cfct_get_option('cfctbiz_news_title');
	if (!$title) {
		$title = sprintf(__('%s News', 'favebusiness'), get_bloginfo('name'));
	}
	echo $title;
}

/**
 * Filter default wp_link_pages output
 */
function cfct_link_pages_args($args) {
	$my_args = array(
		'before' => '<div class="pagination-content"><p>'.__('Pages:', 'favebusiness'),
		'after' => '</p></div>',
	);
	return array_merge($args, $my_args);
}
add_filter('wp_link_pages_args', 'cfct_link_pages_args');

/**
 * Neuter Carrington Core's image gallery. Use standard WP gallery instead.
 */
remove_filter('post_gallery', 'cfct_post_gallery', 10, 2);

/**
 * Since we're using HTML5, we don't want to use the rev attribute on permalinks either.
 * This is used for AJAX in Carrington Blog and the filter ships with Carrington by
 * default but we don't need it here and it causes validation errors.
 */
remove_filter('comments_popup_link_attributes', 'cfct_ajax_comment_link');

/**
 * Filter out extra Build wrapping div
 */
function cfct_cfct_row_html($html, $class) {
	return '<div id="{id}" class="{class}">
	{blocks}
</div>';
}
add_filter('cfct-row-html', 'cfct_cfct_row_html', 10, 3);

/**
 * A collection of filters for comment_form().
 * Usage: CFCT_Comment_Form::setup();
 */
class CFCT_Comment_Form {
	public static $i18n = 'favebusiness';
	protected static $instance;
	protected static $hooks_attached = false;
	
	protected function __construct() {}
	
	/**
	 * Singleton factory method
	 */
	public static function get_instance() {
		if (!self::$instance) {
			self::$instance = new CFCT_Comment_Form();
		}
		return self::$instance;
	}
	
	/**
	 * Convenient factory method that instantiates single instance and
	 * attaches hooks automatically.
	 * @return object instance of CFCT_Comment_Form_Hooks
	 */
	public static function setup() {
		$ins = self::get_instance();
		$ins->attach_hooks();
		return $ins;
	}
	
	/**
	 * Call this once, after
	 */
	public function attach_hooks() {
		if (self::$hooks_attached === true) {
			return false;
		}
		add_action('comment_form_defaults', array($this, 'configure_args'));
		self::$hooks_attached = true;
	}
	
	/**
	 * Attach to 'configure_args' hook
	 */
	public function configure_args($default_args) {
		$commenter = wp_get_current_commenter();
		$req = get_option( 'require_name_email' );
		
		$author_help = ($req ? __('(required)', self::$i18n) : '');
		$email_help = ($req ? __('(required, but never shared)', self::$i18n) : __('(never shared)', self::$i18n));
		
		$fields = array(
			'author' => self::to_input_block(__( 'Name', self::$i18n ), 'author', $commenter['comment_author'], $req, $author_help),
			'email' => self::to_input_block(__( 'Email', self::$i18n ), 'email', $commenter['comment_author_email'], $req, $email_help),
			'url' => self::to_input_block(__( 'Web', self::$i18n ), 'url', $commenter['comment_author_url'])
		);
		
		$textarea = self::to_tag('textarea', '', array(
			'name' => 'comment',
			'id' => 'comment',
			'class' => 'comment',
			'rows' => 6,
			'cols' => 60,
			'required' => 'required'
		));
		
		$comment_field = self::to_tag('p', $textarea, array('class' => 'comment-form-block comment-form-comment'));
		
		$args = array(
			'fields' => $fields,
			'comment_field' => $comment_field,
			'label_submit' => __('Post Comment', self::$i18n),
			'title_reply' => __('Post a Comment', self::$i18n),
			'title_reply_to' => __('Reply to %s', self::$i18n),
			'cancel_reply_link' => __('cancel reply', self::$i18n),
			'comment_notes_after' => '',
			'comment_notes_before' => ''
		);
		return array_merge($default_args, $args);
	}
	
	/**
	 * Helper: Turn an array or two into HTML attribute string
	 */
	public static function to_attr($arr1 = array(), $arr2 = array()) {
		$attrs = array();
		$arr = array_merge($arr1, $arr2);
		foreach ($arr as $key => $value) {
			if (function_exists('esc_attr')) {
				$key = esc_attr($key);
				$value = esc_attr($value);
			}
			$attrs[] = $key.'="'.$value.'"';
		}
		return implode(' ', $attrs);
	}
	
	/**
	 * Helper for creating HTML tag from strings and arrays of attributes.
	 */
	public static function to_tag($tag, $text = '', $attr1 = array(), $attr2 = array()) {
		if (function_exists('esc_attr')) {
			$tag = esc_attr($tag);
		}
		$attrs = self::to_attr($attr1, $attr2);
		if ($text !== false) {
			$tag = '<'.$tag.' '.$attrs.'>'.$text.'</'.$tag.'>';
		}
		// No text == self closing tag
		else {
			$tag = '<'.$tag.' '.$attrs.' />';
		}
		
		return $tag;
	}
	
	public static function to_input_block($label, $id, $value, $req = null, $help_text = '') {
		$label = self::to_tag('label', $label, array('for' => $id));
		
		$maybe_req = ($req ? array('required' => 'required') : array() );
		$input_defaults = array(
			'id' => $id,
			'name' => $id,
			'class' => 'type-text '.$id,
			'value' => $value
		);
		$input = self::to_tag('input', false, $input_defaults, $maybe_req);
		
		$help = '';
		if ($help_text) {
			$help = self::to_tag('small', $help_text, array('class' => 'help'));
		}
		
		return self::to_tag('p', $input . $label . $help, array(
			'class' => 'comment-form-block comment-form-'.$id
		));
	}
}
CFCT_Comment_Form::setup();
?>
