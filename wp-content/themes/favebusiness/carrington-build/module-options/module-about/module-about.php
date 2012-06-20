<?php

define('BUILD_ABOUT_MENU_USER_OPTION', 'build-always-show-module-about');

// Module Extra

	/**
	 * Simple About text in the module-options
	 *
	 * @package default
	 */
	class cfct_module_option_module_about extends cfct_module_option {
		protected $default_persistent_about;
		
		public function __construct() {
			parent::__construct('About&hellip;', 'cfct-module-about', true);
			$this->default_persistent_about = apply_filters('cfct-build-persistent-module-about', 0);
			add_action('admin_head', array($this, 'pref_header_output'));
			
			if (!is_numeric($this->get_user_meta())) {
				$this->set_default_user_meta();
			}
		}
		
		public function button() {
			return '
				<h2 class="cfct-build-help-header"><a class="module-help-button" href="#cfct-popup-cfct-module-about">Module Help</a></h2>';
		}
		
		public function form($data, $module_type) {
			$html = apply_filters('cfct-module-about-text', '', $module_type);
			return $html;
		}
		
		public function admin_js() {
			// disabling the auto-open feature
			/* 
			cfct_builder.addModuleLoadCallback("*", function(form) {
				if (cfct_builder_opts_persistent_about == 1) {
					var _closest = ".cfct-popup-inner-wrap";
					if ($(form).closest(".cfct-module-sideload").size() > 0) {
						_closest = ".cfct-module-sideload";
					}
					// look for the button, but directly open to avoid the animation
					if ($(form).closest(_closest).find("a[href=\"#cfct-popup-cfct-module-about\"]").size() > 0) {
						$(form).find("#cfct-popup-cfct-module-about").show().siblings().hide().closest("#cfct-popup-advanced-actions").show();
					}
				}
			});
			*/
			$js = preg_replace('/^(\t){4}/m', '', '
				// Module Extra: About Module
				$(".cfct-build-module-options .cfct-build-help-header a").live("click", function() {
					if ($("#cfct-popup-cfct-module-about").is(":visible")) {
						cfct_builder.moduleOptionsSlideClose();
					}
					else {
						cfct_builder.moduleOptionsSliderShowHide(this);
					}
					return false;
				});
				');
			return $js;
		}
		
		public function pref_header_output() {
			$user_option = $this->get_user_meta();
			$persistent_about = (is_numeric($user_option) ? $user_option : $this->default_persistent_about);
			echo preg_replace('/^(\t){4}/m', '', '
				<script type="text/javascript">
					var cfct_builder_opts_persistent_about = '.$persistent_about.';
				</script>');
		}
		
		public function get_user_meta() {
			$user_id = get_current_user_id();
			return get_user_meta($user_id, BUILD_ABOUT_MENU_USER_OPTION, true);
		}
		
		public function set_default_user_meta() {
			$user_id = get_current_user_id();
			return update_user_meta($user_id, BUILD_ABOUT_MENU_USER_OPTION, $this->default_persistent_about);
		}
	}
	
	cfct_module_register_extra('cfct_module_option_module_about');

// User option config
	/*
	function cfct_build_about_module_preference($profileuser) {
		$value = get_user_meta($profileuser->ID, BUILD_ABOUT_MENU_USER_OPTION, true);
		echo '
			<tr class="build-always-show-module-about">
				<th scope="row">
					'.__('Always Show Module About', 'carrington-build').'
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><span>'.__('Always show Module About', 'carrington-build').'</span></legend>
						<label for="cfct_always_show_module_about">
							<input name="cfct_always_show_module_about" type="checkbox" id="cfct_always_show_module_about" value="1" '.checked($value, '1', false) .' />
							Always show the Inline Module About text if available for a module
						</label>
					</fieldset>
				</td>
			</tr>';
	}
	add_action('personal_options', 'cfct_build_about_module_preference', 10, 1);
	
	function cfct_build_save_about_module_preference() {
		if (!empty($_POST['action']) && $_POST['action'] == 'update') {
			$user_id = $_POST['user_id'];
			if (!empty($_POST['cfct_always_show_module_about'])) {
				update_user_meta($user_id, BUILD_ABOUT_MENU_USER_OPTION, 1);
			}
			else {
				update_user_meta($user_id, BUILD_ABOUT_MENU_USER_OPTION, 0);
			}
		}
	}
	add_filter('profile_update', 'cfct_build_save_about_module_preference');
	*/
?>