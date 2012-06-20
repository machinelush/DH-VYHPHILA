## About Module

This module option provides a chance for modules to register some "About" text that will be shown as an option in the module options list.

### Usage

A module can hook in on the filter `cfct-module-about-text` and check the supplied `$module_type` variable to add text to a module. For example:

	class my_module extends cfct_build_module {
		
		function __construct() {
			// normal construct here
			
			add_filter('cfct-module-about-text', array($this, 'about_text'));
		}
		
		function about_text($text, $module_type) {
			if ($this->get_type() == $module_type) {
				$text = '<p>'.__('About this module', 'my-text-domain').'</p>';
			}
			return $text;
		}
		
	}