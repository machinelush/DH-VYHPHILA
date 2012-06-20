<?php

	/**
	 * Testing deregistration of legacy carrington build row
	 * To test, revert the Stacked row to r28073 and the activate this action
	 * @return void
	 */
	function cfct_deregister_legacy_row_test() {
		cfct_build_deregister_row('cfct_row_stacked_example');
	}
	add_action('cfct-rows-included', 'cfct_deregister_legacy_row_test');

	/**
	 * Testing the ability to add a shortcode that is a class member
	 *
	 * @package default
	 */
	function cfct_shortcode_class_test() {
		class shortcode_class {
			public function __construct() {
				add_shortcode('my-shortcode', array($this, 'echo_test'));
			}
			public function echo_test($atts) {
				
				return 'foo';
			}
		}
		$my_shortcode_class = new shortcode_class();
	}
	#add_action('init', 'cfct_shortcode_class_test');

	/**
	 * Throw a module not found error over ajax.
	 * This simulates the admin not being able to find a module when editing over ajax.
	 *
	 * Will cause error dialog to display when editing or adding a Pullquote
	 * module in the post/page edit screen.
	 *
	 * @param object $module 
	 * @param string $id 
	 * @return object module/exception
	 */
	function cfct_build_test_ajax_get_module_error($module, $id) {
		global $cfct_build;
		if (method_exists($cfct_build, 'in_ajax') && $cfct_build->in_ajax()) {
			throw new cfct_template_exception('TESTING: Could not find module '.$id);
		}
		return $module;
	}
	// add_filter('cfct-build-template-get-module', 'cfct_build_test_ajax_get_module_error', 10, 2);

	/**
	 * Remove a module at startup to induce a missing-module error.
	 * This test simulates a Module's class file going missing after a module has been used
	 * to add content to a post.
	 *
	 * Will cause "missing-module" module to display in WP-Admin to indicate error to user.
	 * Front end display will get an empty string for the module content.
	 *
	 * @param array $registered_modules 
	 * @return array
	 */
	function cfct_build_test_missing_module_test($registered_modules) {
		cfct_dbg(__METHOD__, ' ** Removing pullquote module for missing-module error test');
		if (isset($registered_modules['cfct-pullquote'])) {
			unset($registered_modules['cfct-pullquote']);
		}
		return $registered_modules;
	}
	// add_filter('cfct-build-template-pre-module-init', 'cfct_build_test_missing_module_test', 10);

	/**
	 * Test module text() filters
	 * This test will change the output of the module's content in the post_content field in the database.
	 * Normally the text is plain. This filter allows the text to be modified before it hits the database.
	 *
	 * @param string $text 
	 * @param array $data 
	 * @return string
	 */
	function cfct_build_test_module_text_filter($text, $data) {
		$text = '!!! -- '.$text.' ---!!!'.PHP_EOL.PHP_EOL.print_r($data, true).PHP_EOL.PHP_EOL;
		error_log($text);
		return $text;
	}
	// add_filter('cfct-module-cfct-html-text', 'cfct_build_test_module_text_filter', 10, 2);

	/**
	 * Test the exclusion of modern widgets from being made in to modules
	 * 
	 * Will cause the general Widgets module to display and contain the pages and calendar widget
	 *
	 * @param array $widgets 
	 * @return array
	 */
	function my_modern_widget_exclude($widgets) {
		unset($widgets['pages']);
		unset($widgets['calendar']);
		return $widgets;
	}
	// add_filter('cfct-modern-widgets', 'my_modern_widget_exclude');
?>