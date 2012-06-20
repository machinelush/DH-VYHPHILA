<?php
/**
* @package carrington-build
* @version 1.2.3
*
* This file is part of Carrington Build for WordPress
* http://crowdfavorite.com/wordpress/carrington-build/
*
* Copyright (c) 2009-2011 Crowd Favorite, Ltd. All rights reserved.
* http://crowdfavorite.com
*
* **********************************************************************
* This program is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
* **********************************************************************
*/

// Constants
define('CFCT_BUILD_VERSION', '1.2.3');
define('CFCT_BUILD_POSTMETA', '_cfct_build_data');
define('CFCT_BUILD_TEMPLATES', 'cfct_build_templates');
define('CFCT_POST_DATA', 'cfct_build');
define('CFCT_POST_CONTENT_MARKER', '<!--CFCT-BD-->'); // intentionally obtuse to avoid search matches

define('CFCT_BUILD_TAXONOMY_LANDING', true);
define('CFCT_BUILD_DEBUG', false);
define('CFCT_BUILD_DEBUG_ERROR_LOG', false);
define('CFCT_BUILD_DEBUG_DISPLAY_ERRORS', false);

// Where am I?
function cfct_where_am_i() {
	$_path = dirname(__FILE__);
	$loc = $url = $path = null;
	
	switch (true) {
		case strpos($_path, DIRECTORY_SEPARATOR . 'mu-plugins') !== false:
			$loc = 'mu-plugins';
			$url = WPMU_PLUGIN_URL;
			$path = WPMU_PLUGIN_DIR;
			break;
		case strpos($_path, DIRECTORY_SEPARATOR . 'plugins') !== false:
			$loc = 'plugins';
			$url = WP_PLUGIN_URL;
			$path = WP_PLUGIN_DIR;
			break;
		case strpos($_path, DIRECTORY_SEPARATOR . 'themes') !== false:
		default:
			$loc = 'theme';
			$theme_path = get_template_directory().'/carrington-build';
			$child_theme_path = get_stylesheet_directory().'/carrington-build';
// check for child theme
			if (is_dir($child_theme_path)) {
				$url = get_stylesheet_directory_uri();
				$path = get_stylesheet_directory();
			}
			else if (is_dir($theme_path)) {
				$url = get_template_directory_uri();
				$path = get_template_directory();
			}
			break;
	}
	return apply_filters('cfct-build-loc', compact('loc','url','path'));
}

$cfct_loc = cfct_where_am_i();

define('CFCT_BUILD_DIR', apply_filters('cfct-build-dir', trailingslashit($cfct_loc['path']).'carrington-build/'), $cfct_loc['loc']);
define('CFCT_BUILD_URL', apply_filters('cfct-build-url', trailingslashit($cfct_loc['url']).'carrington-build/'), $cfct_loc['loc']);

// template tag
function cfct_build() {
	global $cfct_build;
	
	do_action('pre-cfct-build', $cfct_build);
	return $cfct_build->display();
}

// Init
load_theme_textdomain('carrington-build', trailingslashit(CFCT_BUILD_DIR).'languages');

function cfct_object_init() {
	#global $cfct_build, $post, $post_ID, $pagenow;
	global $cfct_build;
			
	// Templates are experimental, enable at your own risk!
	define('CFCT_BUILD_ENABLE_TEMPLATES', apply_filters('cfct-build-enable-templates', false));
			
	if (!defined('CFCT_BUILD_DISABLE') || defined('CFCT_BUILD_DISABLE') && CFCT_BUILD_DISABLE != true) {
		// Includes
		include('lib/cfct-json/cfct-json.php');
		include('lib/wp-revision-manager/cf-revision-manager.php');
		if (defined('CFCT_BUILD_TAXONOMY_LANDING') && CFCT_BUILD_TAXONOMY_LANDING) {
			include('lib/wp-taxonomy-landing/taxonomy-landing.php');
		}
		include('classes/message.class.php');
		include('classes/template.class.php');
		include('classes/common.class.php');
		include('classes/row.class.php');
		include('classes/default-rows.class.php');
		include('classes/module-utility.class.php');
		include('classes/module.class.php');
		include('classes/module-options.php');
		include('classes/module-multi-base.php');
		include('classes/default-modules.class.php');
		include('classes/admin.class.php');
		include('classes/build.class.php');
		include('classes/exception.class.php');
		if (CFCT_BUILD_DEBUG) {
			include('classes/debug.class.php');
			include('classes/tests.php');
		}
			
		if (is_admin()) {
			$cfct_build = new cfct_build_admin();
		}
		else {
			$cfct_build = new cfct_build();
			cfct_build_add_filters();
		}
	}
}
add_action('init', 'cfct_object_init', 1);

// Content Output
function cfct_build_the_post($post) {
	global $cfct_build;
	$cfct_build->_init($post->ID,true);
}

function cfct_build_the_content($the_content) {
	global $cfct_build, $post;
	
	if ( !post_password_required($post) ) {
		if ($html = cfct_build()) {
			$the_content = $html;
		}
	}

	return $the_content;
}

function cfct_build_clear_build_search_content($the_content) {
	if (strpos($the_content, CFCT_POST_CONTENT_MARKER) !== false) {
		$the_content = '';
	}
	
	return $the_content;
}

function cfct_build_post_class($class) {
	global $cfct_build;
	if ($cfct_build->can_do_build()) {
		$class[] = 'cfct-can-haz-build';
	}
	return $class;
}

/**
 * We need to keep Build from running when WordPress fakes in an excerpt
 *
 * @param string $the_excerpt 
 * @return string
 */
function cfct_build_disable($the_excerpt) {
	remove_filter('the_content', 'cfct_build_the_content');
	// remove_filter('get_the_excerpt', 'cfct_build_disable', 1);
	add_filter('get_the_excerpt', 'cfct_build_enable', 99999);
	return $the_excerpt;
}

/**
 * Enable Carrington Build via a Post Content filter
 *
 * @param string $the_excerpt 
 * @return void
 */
function cfct_build_enable($the_excerpt) {
	cfct_build_add_filters();
	return $the_excerpt;
}

function cfct_build_add_filters() {
	add_filter('get_the_excerpt', 'cfct_build_disable', 1);
	add_filter('post_class', 'cfct_build_post_class', 10);
	add_filter('the_post', 'cfct_build_the_post');
	add_filter('the_content', 'cfct_build_the_content',10);
	add_filter('the_content', 'cfct_build_clear_build_search_content',1);
}	

// Module Registration
function cfct_build_register_module($classname, $args = array()) {
	if (func_num_args() > 1 && !is_array($args)) {
		_deprecated_argument(__FUNCTION__, '1.0.2' , 'Use of the <code>$id</code> parameter when registering a module has been deprecated. Pass only the module\'s classname when registering your module');
		$args = array();
		list(, $classname, $args) = func_get_args();
	}
	
	global $cfct_build;
	if ($cfct_build instanceof cfct_build_common) {
		$cfct_build->template->register_type('module', $classname, $args);
		return true;
	}
	else {
		return false;
	}
}

// best called on `cfct-modules-loaded` action
function cfct_build_deregister_module($classname) {
	global $cfct_build;
	if ($cfct_build instanceof cfct_build_common) {
		$cfct_build->template->deregister_type('module', $classname);
		return true;
	}
	else {
		return false;
	}
}

// Row Type Registration
function cfct_build_register_row($classname) {
	if (func_num_args() > 1) {
		_deprecated_argument(__FUNCTION__, '1.0.2' , 'Use of the <code>$id</code> parameter when registering a row has been deprecated. Pass only the row\'s classname when registering your row');
		$args = array();
		list(, $classname) = func_get_args();
	}
	
	global $cfct_build;
	
	if ($cfct_build instanceof cfct_build_common) {
		$cfct_build->template->register_type('row', $classname);
		return true;
	}
	else {
		return false;
	}
}

// best called on `cfct-rows-loaded` action
function cfct_build_deregister_row($classname) {
	global $cfct_build;
	if ($cfct_build instanceof cfct_build_common) {
		$cfct_build->template->deregister_type('row', $classname);
		return true;
	}
	else {
		return false;
	}
}

// Custom Module Options Registration
function cfct_module_register_extra($classname) {
	if (func_num_args() > 1) {
		_deprecated_argument(__FUNCTION__, '1.0.2' , 'Use of the <code>$id</code> parameter when registering a module-extra has been deprecated. Pass only the module-extra\'s classname when registering your extra');
		$args = array();
		list(, $classname) = func_get_args();
	}
	$module_extras = cfct_module_options::get_instance();
	return $module_extras->register($classname);
}

function cfct_module_deregister_extra($classname) {
	$module_extras = cfct_module_options::get_instance();
	return $module_extras->deregister($classname);
}

// Common JS files/Libraries

/**
 * We take some common included libraries and register them for
 * enqueuing so others can simply address them by name when required.
 *
 * @return void
 */
function cfct_register_js_libs() {
	// require enqueuing on both front end and admin
	// none, yet
	
	// require enqueuing on front end only, these items are auto-included in to the carrington-build js
	wp_register_script('jquery-placeholder', CFCT_BUILD_URL.'js/jquery.placeholder/jquery.placeholder.js', array('jquery'), CFCT_BUILD_VERSION);
	wp_register_script('jquery-popover', CFCT_BUILD_URL.'js/jquery.popover/jquery.cf.popover.js', array('jquery', 'jquery-ui-position'), CFCT_BUILD_VERSION);
	wp_register_script('jquery-columnizelists', CFCT_BUILD_URL.'js/jquery.columnizelists.js', array('jquery'), CFCT_BUILD_VERSION);
	wp_register_script('o-type-ahead', CFCT_BUILD_URL.'js/o-type-ahead.js', array('jquery'), CFCT_BUILD_VERSION);
	wp_register_script('json2', CFCT_BUILD_URL.'js/json2.js', array('jquery'), CFCT_BUILD_VERSION);
}
add_action('wp_loaded', 'cfct_register_js_libs');

// Common CSS Attribute classes

/**
 * Provide a base set of common class names for various uses
 *
 * @param string $type - group to return
 * @return mixed array/bool
 */
function cfct_class_groups($type, $defaults=false) {
	static $types;
	$default_styles = array(
		'header' => array(
			'cfct-header-small' => 'Small',
			'cfct-header-medium' => 'Medium',
			'cfct-header-large' => 'Large'
		),
		'content' => array(
			'cfct-content-small' => 'Small',
			'cfct-content-medium' => 'Medium',
			'cfct-content-large' => 'Large'				
		), 
		'image' => array(
			'cfct-image-left' => 'Left',
			'cfct-image-center' => 'Center',
			'cfct-image-right' => 'Right'
		)
	);
	
	if ($defaults) {
		return (!empty($default_styles[$type]) ? $default_styles[$type] : false);			
	}
	else {
		if (is_null($types)) {
			$types = apply_filters('cfct-class-groups', $default_styles);
		}
		return (!empty($types[$type]) ? $types[$type] : false);
	}
}

// Helpers

/**
 * Get a list of the Object based Widgets available
 *
 * @return array
 */
function cfct_get_modern_widgets() {
	if ($widgets = wp_cache_get('cfct_build_modern_widgets', 'cfct_build')) {
		return $widgets;
	}
	
	global $wp_registered_widgets;
	$widgets = array();
	foreach($wp_registered_widgets as $id => $widget) {
		if (!empty($widget['callback']) && $widget['callback'][0] instanceof WP_Widget) {
			$widgets[_get_widget_id_base($id)] = $widget;
		}
	}
	
	$widgets = apply_filters('cfct-modern-widgets', $widgets);
	wp_cache_set('cfct_build_modern_widgets', $widgets, 'cfct_build', 3600);
	
	return $widgets;
}

/**
 * Generic guid creator
 * @TODO - does 'cfct-' need to come off below?
 */
function cfct_build_guid($key, $type='') {
	return 'cfct-'.(!empty($type) ? $type.'-' : '').md5(strval(time()).$key);
}

/**
 * Sort an array by a key within the array_items
 * Items can be arrays or objects, but must all be the same type
 *
 * @example
 * $array = array(
 * 'mary' => array('age' => 21),
 * 'bob' => array('age' => 5),
 * 'justin' => array('age' => 15)
 * );
 * $array = cf_sort_by_key($array, 'age');
 * # array is now: bob,justin,mary
 *
 * @param $data - the array of items to work on
 * @param $sort_key - an array key or object member to use as the sort key
 * @param $ascending - wether to sort in reverse/descending order
 * @return array - sorted array
 */
function cfct_array_sort_by_key($data, $sort_key, $ascending=true) {
	$order = $ascending ? '$a, $b' : '$b, $a';
	if (is_object(current($data))) { $callback = create_function($order, 'return strnatcasecmp($a->'.$sort_key.', $b->'.$sort_key.');'); }
	else { $callback = create_function($order, 'return strnatcasecmp($a["'.$sort_key.'"], $b["'.$sort_key.'"]);'); }
	uasort($data, $callback);
	return $data;
}

// Carrington Framework Integration

/**
 * Choose a module-specific Carrington Framework template during a build loop
 * Filename required to be: module-{module-id}.php where {module-id} is the id passed to the parent
 * constructor in the target module
 *   - ie: parent::__construct('module-id', $module_opts);
 *
 * @see carrington-framework
 * @param string $dir 
 * @param array $files 
 * @param string $filter 
 * @return string filename to choose
 */
function cfct_choose_single_template_module($dir, $files, $filter) {
	$filename = false;
	
	$context = cfct_context();
	if ($context == 'module') {
		global $cfct_build;			
		$current_module = $cfct_build->get_current_module_type();
		if (!empty($current_module)) {
			$file = apply_filters('cfct-module-single-template-name', $context.'-'.$current_module.'.php', $current_module);
			if (in_array($file, $files)) {
				$filename = $file;
			}
		}
	}

	return $filename;
}

// Helpers

function cfct_describe_postmeta($postmeta) {
	global $cfct_build;
	return $cfct_build->describe($postmeta);
}

function cfct_build_humanize($str, $titlecase = true, $replace_extras = array()) {
	$find = array('_');
	if (is_array($replace_extras) && !empty($replace_extras)) {
		$find = array_merge($find, $replace_extras);
	}
	$str = str_replace($find, ' ', $str);
	if ($titlecase) {
		$str = ucwords($str);
	}
	return $str;
}

// Upgrade

function cfct_upgrade_postmeta() {
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have adequate privileges to do that.', 'carrington-build'));
	}
	
	global $wpdb;
	$query = "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->postmeta} WHERE meta_key='".CFCT_BUILD_POSTMETA."'";
	$result = mysql_query($query, $wpdb->dbh);
	
	if ($result != false) {
		$updated = 0;
		while ($row = mysql_fetch_assoc($result)) {
			$cfct_data = unserialize($row['meta_value']);
			$update_row = false;
			
			// blocks upgrade 
			if (!isset($cfct_data['data']['blocks'])) {
				// convert
				$modules = array();
				$blocks = array();
				foreach ($cfct_data['data'] as $b_key => $block) {
					$blocks[$b_key] = array();
					foreach ($block as $m_key => $module) {
						unset($module['row_id']);
						$blocks[$b_key][] = $m_key;
						$modules[$m_key] = $module;
					}
				}
				$cfct_data['data'] = array(
					'blocks' => $blocks,
					'modules' => $modules
				);
				
				$update_row = true;
			}
			
			// rows upgrade, data obsoleted with 1.1 upgrade
			$obsolete_row_ids = apply_filters('cfct-obsolete-row-ids', array(
				'row-a' => 'cfct_row_a',
				'row-ab' => 'cfct_row_ab',
				'row-abc' => 'cfct_row_abc',
				'row-ab-c' => 'cfct_row_ab_c',
				'row-a-bc' => 'cfct_row_a_bc',
				'two-col-float-left' => 'cfct_row_two_col_float_left',
				'two-col-float-right' => 'cfct_row_two_col_float_right',
				'cfct-row-stacked-example' => 'cfct_row_stacked_example'
			));
			foreach($cfct_data['template']['rows'] as &$_row) {
				if (!empty($obsolete_row_ids[$_row['type']])) {
					$_row['type'] = $obsolete_row_ids[$_row['type']];
					$update_row = true;
				}
			}
			
			// modules upgrade, data obsoleted with 1.1 upgrade
			// widget modules require no upgrade
			$obsolete_module_ids = apply_filters('cfct-obsolete-module-ids', array(
				'cfct-rich-text-module' => 'cfct_module_rich_text',
				'cfct-module-loop' =>'cfct_module_loop',
				'cfct-sidebar-module' => 'cfct_module_sidebar',
				'cfct-pullquote' => 'cfct_module_pullquote',
				'cfct-shortcode' => 'cfct_module_shortcode',
				'cfct-module-hero' => 'cfct_module_hero',
				'cfct-module-gallery' => 'cfct_module_gallery',
				'cfct-heading' => 'cfct_module_heading',
				'cfct-module-loop-subpages' => 'cfct_module_loop_subpages',
				'cfct-notice' => 'cfct_module_notice',
				'cfct-module-image' => 'cfct_module_image',
				'cfct-callout' => 'cfct_module_callout',
				'cfct-html' => 'cfct_module_html',
				'cfct-plain-text' => 'cfct_module_plain_text',
				'cfct-divider' => 'cfct_module_divider',
				'cf-post-callout-module' => 'post_callout_module'
			));
			foreach($cfct_data['data']['modules'] as &$_module) {
				if (!empty($obsolete_module_ids[$_module['module_type']])) {
					$_module['module_type'] = $obsolete_module_ids[$_module['module_type']];
					$update_row = true;
				}
			}
			
			// save changes
			if ($update_row === true) {
				$query = 'UPDATE '.$wpdb->postmeta.' 
						SET meta_value="'.$wpdb->escape(serialize($cfct_data)).'" 
						WHERE post_id="'.$row['post_id'].'" 
						AND meta_key="'.CFCT_BUILD_POSTMETA.'"';
				if (mysql_query($query, $wpdb->dbh) == false) {
					echo mysql_error($wpdb->dbh);
					exit;
				}
				else {
					$updated++;
				}
			}
		}
	}
	
	$f = mysql_query("SELECT FOUND_ROWS() as rows", $wpdb->dbh);
	$found = mysql_fetch_assoc($f);
	echo 'updated '.$updated.' rows out of '.$found['rows'].' rows found';
	exit;
}

if (is_admin() && !empty($_GET['cfct-upgrade-postmeta'])) {
	add_action('init', 'cfct_upgrade_postmeta');
}

// Deploy

function cfct_build_register_deploy_extras() {
	include('lib/cf-deploy/cfct-deploy.php');
}
add_action('cfd_admin_init', 'cfct_build_register_deploy_extras');

// Readme

function cfct_readme_menu() {
	if (!defined('CFCT_BUILD_DISABLE') || (defined('CFCT_BUILD_DISABLE') && CFCT_BUILD_DISABLE != true)) {
		global $user_level;
		add_submenu_page('cf-faq', __('Carrington Build FAQ', 'carrington-build') , __('Carrington Build', 'carrington-build'), 'edit_posts', 'cfct-faq', 'cfreadme_show');
		add_action('cfreadme_content', 'cfct_readme_content');
	}
}
add_action('admin_menu', 'cfct_readme_menu', 99);

function cfct_readme_content($content) {
	if ($_GET['page'] == 'cfct-faq') {
		$content = file_get_contents(CFCT_BUILD_DIR.'README.txt');
		$content .= cfct_load_module_readmes();
	}
	return PHP_EOL.$content.PHP_EOL;
}

function cfct_load_module_readmes() {
	global $cfct_build;
	$readme = PHP_EOL.'
## Included Modules
Carrington Build Ships with the base modules needed to create complex layouts.


### Module Documentation

If a module contains extra documentation it will appear below.

---

		'.PHP_EOL;
	$module_dir_paths = $cfct_build->get_include_module_dirs();
	if (is_array($module_dir_paths)) {
		foreach ($module_dir_paths as $path) {
			if (is_dir($path) && $handle = opendir($path)) {
				while (false !== ($file = readdir($handle))) {
					$path = trailingslashit($path);
					if ($file == '.' || $file == '..') { continue; }
					if (is_dir($path.$file) && is_file($path.$file.'/README.txt')) {
						$readme .= PHP_EOL.file_get_contents($path.$file.'/README.txt').PHP_EOL.PHP_EOL.'<hr class="light"/>'.PHP_EOL;
					}
				}
			}
		}
	}
	return $readme;
}

// CF Revisions Manager Registration

function cfct_register_postmeta_revisions() {
	if (function_exists('cfr_register_metadata')) {
		cfr_register_metadata(CFCT_BUILD_POSTMETA, 'cfct_describe_postmeta');
	}
}
add_action('init', 'cfct_register_postmeta_revisions', 999);

// Debug

/**
 * log message to the debugger
 *
 * @param string $method - method logging the message
 * @param string $message - log message
 * @return bool
 */
function cfct_dbg($method, $message) {
	if (!CFCT_BUILD_DEBUG) { return false; }
	if (class_exists('cfct_build_debug')) {
		return cfct_build_debug::log($method, $message);
	}
}

/**
 * Mostly static helper functions for working with HTML and templates.
 */
class cfct_tpl {
	/**
	 * Clean up and escape classes. Remove empties, run through esc_attr,
	 * get rid of junk whitespace.
	 * @param array $classes
	 * @return array
	 */
	public static function clean_classes($classes = array()) {
		$classes = array_map('trim', $classes);
		$classes = array_map('esc_attr', $classes);
		// Remove empties
		$classes = array_diff($classes, array(''));
		// Remove dupes
		return array_unique($classes);
	}
	
	/**
	 * Take up to 2 arrays, merge them and combine them into an
	 * HTML classname string
	 * @param array $classes1 (optional) classses
	 * @param array $classes2 (optional) more classes
	 * @return string
	 */
	public static function to_classname(
		$classes1 = array(),
		$classes2 = array()
	) {
		$classes = array_merge($classes1, $classes2);
		$classes = self::clean_classes($classes);
		return implode(' ', $classes);
	}
	
	/**
	 * Take a string of HTML classes and turn them into an array of
	 * strings (1 for each class).
	 * @param string $classname (optional) string of classes
	 * @return array
	 */
	public static function extract_classes($classname = '') {
		$classes = explode(' ', trim($classname));
		$classes = self::clean_classes($classes);
		return $classes;
	}
	
	/**
	 * Take 2 strings of classes and merge them, preventing dupes.
	 * Convenient!
	 * @param string $classname1 (optional) classes
	 * @param string $classname2 (optional) more classes
	 * @return string
	 */
	public static function merge_classnames(
		$classname1 = '',
		$classname2 = ''
	) {
		return self::to_classname(
			self::extract_classes($classname1),
			self::extract_classes($classname2)
		);
	}
	
	/**
	 * Turn an array or two into HTML attribute string
	 */
	public function to_attr($arr1 = array(), $arr2 = array()) {
		$attrs = array();
		$arr = array_merge($arr1, $arr2);
		foreach ($arr as $key => $value) {
			if (!$value) {
				continue;
			}

			$attrs[] = esc_attr($key).'="'.esc_attr($value).'"';
		}
		return implode(' ', $attrs);
	}
}
