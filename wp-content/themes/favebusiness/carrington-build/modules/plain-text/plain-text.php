<?php

if (!class_exists('cfct_module_plain_text')) {
	/**
	 * Plain Text Carrington Build Module
	 * Simple plain text box that stores and displays exactly what it is given.
	 * Good for displaying raw HTML and/or JavaScript
	 */
	class cfct_module_plain_text extends cfct_build_module {
		protected $_deprecated_id = 'cfct-plain-text'; // deprecated property, not needed for new module development
		
		// remove padding from the popup-content form
		protected $admin_form_fullscreen = true;

		public function __construct() {
			$opts = array(
				'description' => __('Standard WordPress formatting will be applied.', 'carrington-build'),
				'icon' => 'plain-text/icon.png'
			);
			parent::__construct('cfct-plain-text', __('Plain Text', 'carrington-build'), $opts);
		}

		public function display($data) {
			$text = do_shortcode($data[$this->get_field_id('content')]);
			if (function_exists('cfct_basic_content_formatting')) {
				$text = cfct_basic_content_formatting($text);
			}
			return $this->load_view($data, compact('text'));
		}

		public function admin_form($data) {
			$ret = '
				<div class="cfct-textarea-wrapper-plain">
					<textarea class="cfct-textarea-full" name="'.$this->get_field_name('content').'" id="'.$this->get_field_id('content').'">'.
						(isset($data[$this->get_field_name('content')]) ? htmlspecialchars($data[$this->get_field_name('content')]) : null).
					'</textarea>
				</div>
				';
			return $ret;
		}

		/**
		 * Return a textual representation of this module.
		 *
		 * @param array $data 
		 * @return string
		 */
		public function text($data) {
			return strip_tags($data[$this->get_field_name('content')]);
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
		 * Add some admin CSS for formatting
		 *
		 * @return void
		 */
		public function admin_css() {
			return '
				#'.$this->get_field_id('content').' {
					height: 300px;
				}
			';
		}
		
		public function admin_js() {
			return '
				// automatically set focus on the rich text editor
				cfct_builder.addModuleLoadCallback("'.$this->id_base.'",function(form) {
					$("#'.$this->get_field_id('content').'").focus();				
				});
				';
		}

	}
	cfct_build_register_module('cfct_module_plain_text');
}
?>