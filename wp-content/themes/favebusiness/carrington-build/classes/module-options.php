<?php
/**
 * This file contains a default subset of Advanced Module Options
 */

// Module Options Singleton
class cfct_module_options {
	
	static $_instance;
	private $module_extra_buttons = array();
	private $module_extras = array();
			
	public function options_list($module_type) {
		$ret = '';
		if (count($this->module_extras)) {
			$ret .= '
				<div class="cfct-build-options cfct-build-module-options">';
			foreach ($this->module_extra_buttons as $extra) {
				// check the form output. If empty, don't list the extra
				$_extra_form = $extra->form(array(), $module_type);
				if (!empty($_extra_form)) {
					$ret .= $extra->button();
				}
				unset($_extra_form);
			}
			$ret .= '
					<h2 class="cfct-build-options-header"><a class="module-options-button" href="#cfct-advanced-options-list">Advanced Options</a></h2>
					<ul class="cfct-build-options-list cfct-build-module-options-list">';
			foreach ($this->module_extras as $extra) {
				// check the form output. If empty, don't list the extra
				$_extra_form = $extra->form(array(), $module_type);
				if (!empty($_extra_form)) {
					$ret .= $extra->menu_item();
				}
				unset($_extra_form);
			}
			$ret .= '</ul>
				</div>
				';
		}
		return $ret;
	}
	
	public function options_html($data, $module_type) {
		$ret = '';
		if (count($this->module_extras)) {
			$ret = '<div id="cfct-popup-advanced-actions" class="cfct-popup-advanced-actions" style="display: none;">';
			foreach ($this->module_extras as $extra) {
				$option_data = (!empty($data[$extra->id_base]) ? $data[$extra->id_base] : null);
				$ret .= $extra->_form($option_data, $module_type);
			}
			foreach ($this->module_extra_buttons as $extra) {
				$option_data = (!empty($data[$extra->id_base]) ? $data[$extra->id_base] : null);
				$ret .= $extra->_form($option_data, $module_type);
			}
			$ret .= '</div>';
		}
		return $ret;
	}
	
	public function options_layout_html($data, $options_data, $module_type) {
		$ret = '';
		if (count($this->module_extras)) {
			foreach ($this->module_extras as $extra) {
				$option_data = (!empty($options_data[$extra->id_base]) ? $options_data[$extra->id_base] : null);
				$ret .= $extra->_layout_html($data, $option_data, $module_type);
			}
			foreach ($this->module_extra_buttons as $extra) {
				$option_data = (!empty($options_data[$extra->id_base]) ? $options_data[$extra->id_base] : null);
				$ret .= $extra->_layout_html($data, $option_data, $module_type);
			}
		}
		if (trim($ret) != '') {
			$ret = '<div class="cfct-module-options-layout">' . $ret . '</div>';
		}
		return $ret;
	}
	
	public function update($new_data, $old_data) {
		$ret = array();
		if (count($this->module_extras)) {
			foreach ($this->module_extras as $extra) {
				if (!empty($new_data[$extra->id_base])) {
					$old_data = (!empty($old_data[$extra->id_base]) ? $old_data[$extra->id_base] : null);
					$ret[$extra->id_base] = $extra->update($new_data[$extra->id_base], $old_data);
				}
			}
		}
		if (count($this->module_extra_buttons)) {
			foreach ($this->module_extra_buttons as $extra) {
				if (!empty($new_data[$extra->id_base])) {
					$old_data = (!empty($old_data[$extra->id_base]) ? $old_data[$extra->id_base] : null);
					$ret[$extra->id_base] = $extra->update($new_data[$extra->id_base], $old_data);
				}
			}
		}
		return $ret;
	}
	
	/**
	 * Return any custom module-extra JS for the front end 
	 *
	 * @return void
	 */
	public function js($admin = false) {
		$js = '';
		if (count($this->module_extras)) {
			foreach ($this->module_extras as $extra) {
				$method = ($admin ? 'admin_' : null).'js';
				$js .= PHP_EOL.PHP_EOL.$extra->$method();
			}
		}
		if (count($this->module_extra_buttons)) {
			foreach ($this->module_extra_buttons as $extra) {
				$method = ($admin ? 'admin_' : null).'js';
				$js .= PHP_EOL.PHP_EOL.$extra->$method();
			}
		}
		return $js;
	}
	
	/**
	 * Return any custom module-extra CSS for the front end
	 *
	 * @return string
	 */
	public function css($admin = false) {
		$css = '';
		if (count($this->module_extras)) {
			foreach ($this->module_extras as $extra) {
				$method = ($admin ? 'admin_' : null).'css';		
				$css .= PHP_EOL.PHP_EOL.$extra->$method();
			}
		}
		if (count($this->module_extra_buttons)) {
			foreach ($this->module_extra_buttons as $extra) {
				$method = ($admin ? 'admin_' : null).'css';		
				$css .= PHP_EOL.PHP_EOL.$extra->$method();
			}
		}
		return $css;			
	}
	
	/**
	 * Register an extra
	 *
	 * @param $id
	 * @param $classname
	 * @return bool
	 */
	public function register($classname) {
		if (!class_exists($classname)) {
			return false;
		}
		$_mo = new $classname;
		if ($_mo->is_button()) {
			$this->module_extra_buttons[$classname] = $_mo;
		}
		else {
			$this->module_extras[$classname] = $_mo;
		}
		unset($_mo);
		return true;
	}
	
	/**
	 * De-register an extra
	 *
	 * @param $id
	 * @param $classname
	 * @return bool
	 */
	public function deregister($classname) {
		if (isset($this->module_extras[$classname]) && ($this->module_extras[$classname] instanceof $classname)) {
			unset($this->module_extras[$classname]);
			return true;
		}
		return false;
	}
	
	/**
	 * Singleton
	 *
	 * @return void
	 */
	public static function get_instance() {
		if (empty(self::$_instance) || !(self::$_instance instanceof cfct_module_options)) {
			self::$_instance = new cfct_module_options;
		}
		return self::$_instance;
	}
}

// Standard Module Options class
class cfct_module_option {
	public $name;
	public $id_base;
	public $is_header_row_button;
	
	public function __construct($name, $id_base, $button = false) {
		$this->name = $name;
		$this->id_base = $id_base;
		$this->is_header_row_button = $button;
	}
	
	public function is_button() {
		return $this->is_header_row_button && method_exists($this, 'button');
	}
	
	public function menu_item() {
		return '<a href="#cfct-popup-'.$this->id_base.'">'.$this->name.'</a>';
	}
	
	public function _form($data, $module_type) {
		$ret = '
			<div id="cfct-popup-'.$this->id_base.'">
				<a href="#" class="close">close</a>
				'.$this->form($data, $module_type).'
			</div>';
		return $ret;
	}
	
	public function _layout_html($data, $options_data, $module_type) {
		$layout_html = $this->layout_html($data, $options_data, $module_type);
		if (trim($layout_html) == '') {
			return '';
		}
		$ret = '
			<div id="cfct-module-options-layout-'.$this->id_base.'">
				'.$this->layout_html($data, $options_data, $module_type).'
			</div>';
		return $ret;
	}
	
	public function update($new_data, $old_data) {
		return $new_data;
	}
	
	public function form($data) {
		return null;
	}
	
	public function layout_html($data, $options_data, $module_type) {
		return null;
	}
	
	public function button() {
		if ($this->is_button()) {
			return null;
		}
		return false;
	}
	
	function get_field_name($field_name) {
		return 'cfct-module-options['.$this->id_base.']['.$field_name.']';
	}

	function get_field_id($field_name) {
		return $this->id_base.'-'.$field_name;
	}
			
	public function js() {
		return null;
	}
	
	public function css() {
		return null;
	}
	
	public function admin_js() {
		return null;
	}
	
	public function admin_css() {
		return null;
	}
	
	/**
	 * Load the view 
	 * 
	 * $params is an associative array that will be extracted for the view
	 * All keys in the array will become available variables in the view in
	 * addition to the $data variable
	 *
	 * @param string $view 
	 * @param string $params - additional params to be made available to the template 
	 * @return void
	 */
	public function load_view($view, $params = array(), $data = null) {
		global $cfct_build;
		
		$view = apply_filters('cfct-module-options-'.$this->id_base.'-view', $view, $data);

		// find file
		$view_path = '';
		if (is_file($view)) {
			// full path to view given
			$view_path = $view;
		}
		else {
			// look for view in module folder
			global $cfct_build;
			$path = dirname($cfct_build->get_module_options_path($this->id_base));
			if (is_file(trailingslashit($path).$view)) {
				$view_path = trailingslashit($path).$view;
			}
		}
		// render
		if (!empty($view_path)) {	
			extract($params);
			ob_start();
		
			include($view_path);
		
			$buffer = ob_get_clean();
			return $buffer;
		}
		else {
			return null;
		}
	}

}

?>