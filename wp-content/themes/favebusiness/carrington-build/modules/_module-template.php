<?php
/**
 * Module Template
 * bork, bork, bork
 */
if (!class_exists('my_module_class')) {
	class my_module_class extends cfct_build_module {
		
		/**
		 * Set up the module
		 */
		public function __construct() {
			$opts = array(
				'description' => __('My Fabulous Module', 'carrington-build'),
				'icon' => 'my-module/icon.png'
			);
			
			// use if this module is to have no user configurable options
			// Will suppress the module edit button in the admin module display
			# $this->editable = false 
			
			parent::__construct('my-module-id', __('Testing', 'carrington-build'), $opts);
		}

		/**
		 * Display the module content in the Post-Content
		 * 
		 * @param array $data - saved module data
		 * @return array string HTML
		 */
		public function display($data) {
			return $this->wp_formatting($data[$this->get_field_id('content')]);
		}

		/**
		 * Build the admin form
		 * 
		 * @param array $data - saved module data
		 * @return string HTML
		 */
		public function admin_form($data) {
			return '
				<div>
					<label for="'.$this->get_field_id('content').'">'.__('Content', 'carrington-build').'</label>
					<input type="text" name="'.$this->get_field_name('content').'" id="'.$this->get_field_id('content').'" value="'.(!empty($data[$this->get_field_id('content')]) ? esc_attr($data[$this->get_field_id('content')]) : '').'" />
				</div>
				';
		}

		/**
		 * Return a textual representation of this module.
		 *
		 * @param array $data - saved module data
		 * @return string text
		 */
		public function text($data) {
			return strip_tags($data[$this->get_field_id('content')]);
		}

		/**
		 * Modify the data before it is saved, or not
		 *
		 * @param array $new_data 
		 * @param array $old_data 
		 * @return array
		 */
		public function update($new_data, $old_data) {
			return $new_data;
		}
		
		/**
		 * Add custom javascript to the front end display
		 * OPTIONAL: omit this method if you're not using it
		 *
		 * @return string JavaScript
		 */
		public function js() {
			return '';
		}
		
		/**
		 * Add custom CSS to the front end display
		 * OPTIONAL: omit this method if you're not using it
		 *
		 * @return string CSS
		 */
		public function css() {
			return '';
		}
		
		/**
		 * Add custom javascript to the post/page admin
		 * OPTIONAL: omit this method if you're not using it
		 *
		 * @return string JavaScript
		 */
		public function admin_js() {
			return '
				// perform an action when the module admin screen loads
				cfct_builder.addModuleLoadCallback("'.$this->id_base.'", function(form) {
					alert("hi");
				});
				
				// return false to stop form submit, true to allow it
				cfct_builder.addModuleLoadCallback("'.$this->id_base.'", function(form) {
					alert("Goodbye!");
					return true;
				});
			';
		}
		
		/**
		 * Add custom css to the post/page admin
		 * OPTIONAL: omit this method if you're not using it
		 *
		 * @return string CSS
		 */
		public function admin_css() {
			return '
				#'.$this->get_field_id('content').' {
					font-family: courier, "Courier New", monospace;
				}
			';
		}
	}
	// register the module with Carrington Build
	cfct_build_register_module('my_module_class');
}
?>