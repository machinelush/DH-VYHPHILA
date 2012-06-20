<?php

/**
 * Module
 * Borrows heavily from WP_Widget
 *
 * @package cfct_build
 */
class cfct_build_module extends cfct_build_module_utility {
	
	public $id_base;
	public $name;
	public $opts;
	public $module_options;
	
	protected $focus_target = null; // CSS3 selector of field to set focus to, if not set focus is set to the first visible field
		
	protected $view = 'view.php';
	protected $available = true;
	protected $context_excludes = array();
	protected $editable = true;
	protected $_truncate = true;
	protected $admin_form_fullscreen = false;
	
	protected $errors = array();
	
	protected $suppress_save = false;
	
	/**
	 * Construct
	 */
	function __construct($id_base = false, $name, $opts = array()) {
		$this->basename = $this->get_basename();

		// store widget type if available
		if (isset($opts['widget_type'])) {
			$this->get_widget($opts['widget_type']);
		}
		$this->id_base = $id_base;
		$this->name = $name;
		$this->opts = $opts;
		$this->admin_text_length = 25;
		
		if (isset($opts['is_content']) && !$opts['is_content']) {
			$this->is_content = false;
		}
		
		if (!empty($opts['url'])) {
			$this->url = $opts['url'];
		}
		
		if (!empty($opts['view'])) {
			$this->view = $opts['view'];
		}
		parent::__construct();
		$this->module_options = cfct_module_options::get_instance();
	}
	
	public function list_admin($context = null) {
		if (isset($this->available) && $this->available === false) {
			return false;
		}
		elseif(!empty($this->context_excludes) && in_array($context, $this->context_excludes)) {
			return false;
		}
		return true;
	}
	
	/**
	 * Public facing output
	 * return html and not echo
	 * Proxy for child class ::display() method
	 * @return html
	 */
	public function html($data) {
		global $cfct_build;
		
		if (empty($data)) { 
			// no funny stuff if we're not passed any display data
			return '';
		}

		$module_class = apply_filters('cfct-build-module-class', 'cfct-module '.$this->id_base, $data);
		
		// get display html & apply generic filter
		$module_display = apply_filters('cfct-module-display', $this->display($data), $this->id_base, $data);
		// apply more module specific filters to output
		$module_display = apply_filters('cfct-module-'.$this->id_base.'-display', $module_display, $data);
		
		$ret = '
			<div class="'.$module_class.'">
				'.$module_display.'
			</div>';
		
		return apply_filters('cfct-module-'.$this->id_base.'-html', $ret, $data);
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
	public function load_view($data, $params = array()) {
		$view = apply_filters('cfct-module-'.$this->id_base.'-view', $this->view, $data);

		// find file
		$view_path = '';
		if (is_file($view)) {
			// full path to view given
			$view_path = $view;
		}
		else {
			// look for view in module folder
			global $cfct_build;
			$path = dirname($cfct_build->get_module_path($this->basename));
			if (is_file(trailingslashit($path).$view)) {
				$view_path = trailingslashit($path).$view;
			}
			else {
				// last ditch, try the immediate parent module
				$parent_class = get_parent_class($this);
				if ($parent_class != 'cfct_build_module') {
					$parent = $cfct_build->template->get_module($parent_class);
					$parent_path = dirname($cfct_build->get_module_path($parent->get_basename()));
					if (is_file(trailingslashit($parent_path).$view)) {
						$view_path = trailingslashit($parent_path).$view;
					}
				}
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
	
	/**
	 * function to output to admin page so that we can wrap the form in lightbox actions
	 * proxy for child class ::admin_form() and ::admin_preview() methods
	 *
	 * @param array $data 
	 * @return string html
	 */
	public function _admin($mode = 'details', $data = array()) {
		global $cfct_build;

		// reset admin_success each time
		$this->suppress_save = false;
		
		// get admin form html & apply generic filter
		$module_form =  apply_filters('cfct-module-admin-form', $this->admin_form($data), $this->id_base, $data);
		// apply module specific admin form html filters
		$module_admin_content = apply_filters('cfct-module-'.$this->id_base.'-admin-form', $module_form, $data);

		if ($mode == 'edit') {
			$popup_contents = '
					<div class="cfct-popup-header cfct-popup-header-has-icon">
						<img class="cfct-popup-icon" src="'.$this->get_icon().'" alt="'.$this->get_name().'" />
						<h2 class="cfct-popup-title">'.$this->name.'</h2>';
			
			if (isset($this->opts['description'])) {
				$popup_contents .= '<p class="cfct-popup-subtitle">' . $this->opts['description'] . '</p>';
			}
			
			if ($this->do_custom_attributes()) {
				$popup_contents .= $this->module_options->options_list($this->get_type());		
			}
			
			$popup_contents .= '
					</div>';

			$guid = isset($data['module_id']) && !empty($data['module_id']) ? $data['module_id'] : cfct_build_guid($this->id_base, 'module');
			$render = (int) (isset($data['render']) ? $data['render'] : 1);
			
			$style = '';
			if (isset($data['max-height'])) {
				$this->max_height = floor($data['max-height']);
				$style = ' style="max-height: '.$this->max_height.'px; overflow: auto;'.($this instanceof cfct_module_multi_base ? ' height: '.$this->max_height.'px' : '').'"';
			}

			// yank custom attributes from data
			if (isset($data['custom_attributes']) && is_array($data['custom_attributes'])) {
				$custom_attributes = $data['custom_attributes'];
				unset($data['custom_attributes']);
			}
			
			$popup_contents .= '
					<div class="'.apply_filters('cfct-module-form-class', 'cfct-module-form', $this->id_base).'">
						<form id="'.$this->id_base.'-edit-form" class="cfct-module-edit-form" name="'.$this->id_base.'"'.($this->suppress_save ? ' onsubmit="return false;"' : '').'>';

			if ($this->do_custom_attributes()) {
				$module_options = array();
				if (isset($data['cfct-module-options'])) {
					$module_options = $data['cfct-module-options'];
					unset($data['cfct-module-options']);
				}
				$popup_contents .= $this->module_options->options_html($module_options, $this->get_type());
			}

			$popup_contents .= '
							<div class="cfct-popup-content'.(!empty($this->admin_form_fullscreen) && $this->admin_form_fullscreen == true ? ' cfct-popup-content-fullscreen' : '').'"'.$style.'>
								<fieldset>
									'.$module_admin_content.'
								</fieldset>';
			$popup_contents .= '
							</div>
							<div class="cfct-popup-actions">';
			if (!$this->suppress_save) {
				$popup_contents .= '
								'.($cfct_build instanceof cfct_build_admin ? $cfct_build->popup_activity_div(__('Saving Module&hellip;', 'carrington-build')) : '').'
				
								<input type="submit" name="module-'.$this->id_base.'-submit" id="module-'.$this->id_base.'-submit" class="cfct-button cfct-button-dark cfct-button-action" value="'.__('Save', 'carrington-build').'"/>
								<span class="cfct-or"> or </span>';
			}
			$popup_contents .= '
								<a href="#" id="cfct-edit-module-cancel" class="cancel">'.__('cancel', 'carrington-build').'</a>';
			if (!empty($data['sideload']) && !empty($data['parent_module_id'])) {
				$popup_contents .= '
								<input type="hidden" name="parent_module_id" value="'.$data['parent_module_id'].'" />';
				if (!empty($data['parent_module_id_base'])) {
					$popup_contents .= '<input type="hidden" name="parent_module_id_base" value="'.$data['parent_module_id_base'].'" />';
				}
								
			}
			$popup_contents .= '
								<input type="hidden" name="module_id_base" value="'.$this->id_base.'" />
								<input type="hidden" name="module_type" value="'.$this->get_type().'" />
								<input type="hidden" name="module_id" value="'.$guid.'" />
								<input type="hidden" name="render" value="'.$render.'" />
							</div>
						</form>
					</div>';
					
			// wrap it all up nice and neat
			$html = '
				<div class="'.$this->id_base.'-edit cfct-popup">
					<div class="cfct-popup-inner-wrap">
					'.apply_filters('cfct-module-'.$this->id_base.'-admin-popup-contents', $popup_contents, $this).'
					</div>
				</div>';
		}
		else {
			$text = $this->admin_text($data);
			if (!empty($text) && $this->_truncate) {
				$hellip = strlen($text) > $this->admin_text_length ? '&hellip;' : '';
				$text = substr(strip_tags($text), 0, $this->admin_text_length).$hellip;
			}
			else {
				$text = $this->name;
			}
			
			$options_layout_html = '';
			if ($this->do_custom_attributes()) {
				$module_options = array();
				if (isset($data['cfct-module-options'])) {
					$module_options = $data['cfct-module-options'];
					unset($data['cfct-module-options']);
				}
				$options_layout_html = $this->module_options->options_layout_html($data, $module_options, $this->get_type());
			}

			$html = '
				<div id="'.$data['module_id'].'" class="cfct-module cfct-module-'.$this->id_base.'">
					<dl class="cfct-module-content">
						<dt class="cfct-module-content-title">
						<img class="cfct-module-content-icon" src="'.$this->get_icon().'" alt="'.$this->get_name().'" />';
			/* Disabled in 1.2 */
			/* $html .= '
					<div class="cfct-module-edit-clear cfct-module-rendering">
							<a href="#'.$data['module_id'].'" class="cfct-module-toggle-render">'.__((!isset($data['render']) || $data['render']) ? 'Enabled' : 'Disabled', 'carrington-build').'</a>
					</div>';
			*/
			$html .= '
							<small class="cfct-module-content-type">'.$this->name.'</small>
							'.esc_html($text).'
						</dt>
						<dd class="cfct-module-edit-clear">';
			if ($this->editable) {
				$html .= '<a href="#'.$data['module_id'].'" class="cfct-module-edit">'.__('Edit', 'carrington-build').'</a> ';
			}
			$html .= '<a href="#'.$data['module_id'].'" class="cfct-module-clear">'.__('Delete', 'carrington-build').'</a>
						</dd>
					</dl>'.
					$options_layout_html 
					.'
				</div>';
		}
		
		return apply_filters('cfct-module-'.$this->id_base.'-admin', $html, $mode);
	}
	
	public function _text($data) {
		$module_text = $this->text($data);
		return apply_filters('cfct-module-'.$this->id_base.'-text', $module_text, $data);
	}
	
	public function admin_form($data) {
		trigger_error('::admin_form() should be overriden in child class. Do not call this parent method directly.', E_USER_ERROR);
	}
	public function display($data) {
		trigger_error('::display() should be overriden in child class. Do not call this parent method directly.', E_USER_ERROR);
	}
	public function text($data) {
		trigger_error('::text() should be overridden in child class to return the main module content. Do not call this parent method directly', E_USER_ERROR);
	}
	public function admin_text($data) {
		return $this->text($data);
	}
	public function icon() {
		return isset($this->opts['icon']) ? $this->opts['icon'] : false;
	}
	
	/**
	 * Get the module icon.
	 * Icon can be defined in $opts['icon'].
	 * Alternately the icon() method can be overridden to return a path if special operations are needed
	 *
	 * @return string - icon url
	 */
	public function get_icon() {
		if ($path = $this->icon()) {
			$icon = $path;			
			if (!preg_match('/^(http)/', $icon)) {
				$icon = trailingslashit(dirname($this->get_url())).preg_replace('/^(\\/)/', '', $icon);
			}
		}
		else {
			// provide generic icon
			$icon = CFCT_BUILD_URL.'img/default-icon.png';
		}
		return apply_filters('cfct-'.$this->id_base.'-module-icon', $icon);
	}

	public function get_description() {
		return $this->opts['description'];
	}
	
	public function get_name() {
		return esc_html($this->name);
	}
	
	public function get_id() {
		return $this->id_base;
	}
	
	public function get_post_id() {
		global $cfct_build;
		
		if ($cfct_build->in_ajax() && !empty($_POST['args'])) {
			$args = $cfct_build->ajax_decode_json($_POST['args'], true);
			$post_id = intval($args['post_id']);
		}
		else {
			$post_id = $cfct_build->get_post_id();
		}
		return $post_id;
	}
	
	/**
	 * Simple data-getter
	 * 
	 * @param string $field_name 
	 * @param array $data 
	 * @return mixed
	 */
	public function get_data($field_name, $data = null, $default = null) {
		# maybe some day $this->data
		#if (!empty($this->data) && !empty($this->data[$this->get_field_name($field_name)])) {
		#	return $this->data[$this->get_field_name($field_name)];
		#}
		#else
		$ret = $default;
		if (!empty($data) && isset($data[$this->get_field_name($field_name)])) {
			$ret = $data[$this->get_field_name($field_name)];
		}
		return $ret;
	}
	
	/**
	 * Handle pre-1.1 legacy ID attributes that were used to identify modules and rows
	 *
	 * @return string/bool
	 */
	public function _legacy_id() {
		$legacy_id = !empty($this->_deprecated_id) ? $this->_deprecated_id : false;
		return apply_filters('cfct-'.$this->id_base.'-deprecated-id', $legacy_id);
	}

	/**
	 * Update data, standard is to just return the new data
	 *
	 * @param array $new_data 
	 * @param array $old_data 
	 * @return array
	 */	
	function update($new_data, $old_data) {
		return $new_data;
	}
	
	/**
	 * Process the data for update
	 * Protect our custom-attributes from alteration by child module
	 *
	 * @param array $new_data 
	 * @param array $old_data 
	 * @return array
	 */
	function _update($new_data, $old_data) {
		// preprocess the extra attributes and keep them away from the individual module's update function
		if ($this->do_custom_attributes()) {
			$module_options_new = $module_options_old = array();
			
			if (!empty($new_data['cfct-module-options'])) {
				$module_options_new = $new_data['cfct-module-options'];
				unset($new_data['cfct-module-options']);
			}
			
			if (!empty($old_data['cfct-module-options'])) {
				$module_options_old = $old_data['cfct-module-options'];
				unset($old_data['cfct-module-options']);
			}
		}
		
		$processed = $this->update($new_data, $old_data);
		$processed = apply_filters('cfct-module-'.$this->id_base.'-update', $processed, $new_data, $old_data);
				
		if ($this->do_custom_attributes()) {
			$processed['cfct-module-options'] = $this->module_options->update($module_options_new, $module_options_old);
		}

		// wp_filter_post_kses 
		if (current_user_can('unfiltered_html') == false) {
			$processed = $this->apply_wp_kses($processed);
		}

		return $processed;
	}
	
	/**
	 * filter data from users who cannot post unfiltered html
	 * Recurses down in to nested arrays & objects
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	private function apply_wp_kses($data) {
		if (is_array($data)) {
			foreach ($data as &$item) {
				if (is_array($item) || is_object($item)) {
					$item = $this->apply_wp_kses($item);
				}
				else {
					$item = wp_filter_post_kses($item);
				}
			}
		}
		elseif (is_object($data)) {
			foreach (get_object_vars($data) as $var) {
				if (is_array($data->$var) || is_object($data->$var)) {
					$data->$var = $this->apply_wp_kses($data->$var);
				}
				else {
					$data->$var = wp_filter_post_kses($data->$var);
				}
			}
		}
		else {
			$data = wp_filter_post_kses($data);
		}
		return $data;
	}
	
	function error($field, $message) {
		// add ability to log errors for return to user
	}
	
	/**
	 * JS & CSS functions
	 * should return, not echo, for inclusion in a conglomerated file built on a Request Handler
	 */
	function js() {
		// client side js
		return null;
	}
	function css() {
		// client side css
		return null;
	}
	function _admin_js() {
		// admin js
		$js = null;
		if (method_exists($this, 'admin_js')) {
			$js = $this->admin_js();
		}
		if (!empty($this->focus_target)) {
			$js .= '
// set focus to declared target field
cfct_builder.addModuleLoadCallback("'.$this->id_base.'", function(form) {
	$("'.$this->focus_target.'").focus();
});
			';
		}
		else {
			$js .= '
// set focus to first visible field
cfct_builder.addModuleLoadCallback("'.$this->id_base.'", function(form) {
	$("#cfct-edit-module form:visible:first:has(:input:visible) :input[type!=checkbox][type!=radio][type!=file]:not(:submit):not(:button):visible:first").focus();
});
			';
		}
		return $js;
	}
	function _admin_css() {
		// admin css
		$css = null;
		if (method_exists($this, 'admin_css')) {
			$css = $this->admin_css();
		}
		return $css;
	}
	
	/**
	 * Get the a field name for this module's data
	 * Function namespaces the module data to avoid name conflicts in the save data
	 *
	 * @param string $field_name 
	 * @param int $index 
	 * @return mixed
	 */
	function get_field_name($field_name) {
		$name = $this->id_base.'-'.$field_name;
		return $name;
	}
	
	/**
	 * Alias for get_field_name
	 * @see cfct_build_module::get_field_name for definition
	 */
	function gfn($field_name) {
		return $this->get_field_name($field_name);
	}

	/**
	 * Get the a field ID for this module
	 * Function namespaces the module data to avoid name conflicts in form elements
	 *
	 * @param string $field_name 
	 * @param int $index 
	 * @return mixed
	 */
	function get_field_id($field_name, $index = 0) {
		$id = $this->id_base.'-'.$field_name;
		if($index != 0) {
			$id .= '-'.$index;
		}
		return $id;
	}
	
	/**
	 * Alias for get_field_id
	 * @see cfct_build_module::get_field_id for definition
	 */
	function gfi($field_name, $index = 0) {
		return $this->get_field_id($field_name, $index);
	}
	
	/**
	 * Error Handling Helpers
	 * @TODO - provide error handling functionality to module save. Currently its an all or nothing affair.
	 */
	function set_error($field, $message) {
		return $this->errors[$field] = $message;
	}
	function get_error($field) {
		return isset($this->errors[$field]) ? $this->errors[$field] : false;
	}
	function get_errors() {
		return is_array($this->errors) && count($this->errors) ? $this->errors : false;
	}
	
	protected function do_custom_attributes() {
		global $cfct_build;
		return $cfct_build->enable_custom_attributes && ($this->module_options instanceof cfct_module_options);
	}
	
	public function admin_success() {
		return $this->admin_success;
	}
	
	/**
	 * Register an ajax handler with the parent build object
	 *
	 * @param string $key 
	 * @param string $func 
	 * @return void
	 */
	protected function register_ajax_handler($key, $func) {
		global $cfct_build;
		$cfct_build->register_ajax_handler($key, $func);
	}
	
	/**
	 * Return a properly formatted cfct-ajax response
	 *
	 * @param bool $success 
	 * @param string $html 
	 * @param string $message 
	 * @return object cfct_message
	 */
	protected function ajax_response($success, $html, $message = null) {
		if (empty($message)) {
			$message = $this->basename;
		}
		
		return new cfct_message(array(
			'success' => (bool) $success,
			'html' => $html,
			'message' => $message
		));
	}
	
	/**
	 * Filepath & URL helpers
	 * Only call from child classes
	 */
	public function get_url() {
		if (empty($this->url)) {
			global $cfct_build;
			$url = $cfct_build->get_module_url($this->basename);
			$this->url = apply_filters('cfct-module-'.$this->id_base.'-url', $url, $this->basename);
		}
		return $this->url;
	}
	
	public function get_path() {
		global $cfct_build;
		$path = dirname($cfct_build->get_module_path($this->basename));
		return apply_filters('cfct-module-'.$this->id_base.'-path', $path, $this->basename);
	}
	
	/**
	 * Get the basename of this module for help locating this module in the filesystem
	 * Use ReflectionClass 'cause its the most reliable way to inspect the parent
	 * 
	 * @return string
	 */
	public function get_basename() {
		if (empty($this->basename)) {
            $rc = new ReflectionClass($this);
			$this->basename = basename($rc->getFilename(),'.php');
			unset($rc);
		}
		return $this->basename;
	}
	
	/**
	 * Get the module type (module class name) for this module
	 * Use ReflectionClass 'cause its the most reliable way to inspect the parent
	 *
	 * @return string
	 */
	public function get_type() {
		if (empty($this->module_type)) {
			$rc = new ReflectionClass($this);
			$this->module_type = $rc->getName();
			unset($rc);
		}
		return $this->module_type;
	}
	
	public function get_charset() {
		$charset = DB_CHARSET;
		$known_charset_translations = array(
			'utf8' => 'utf-8'
		);
		if (array_key_exists(DB_CHARSET, $known_charset_translations)) {
			return $known_charset_translations[DB_CHARSET];
		}
		else {
			return DB_CHARSET;
		}
	}
	
	/**
	 * Placeholder for build parent post id
	 *
	 * @var int
	 */
	protected $_build_post_id;

	/**
	 * Before doing a loop we need to cache the global
	 * post_id so that we can re-do setup_postdata after
	 * our internal loop fires.
	 * 
	 * Use in conjunction with cfct_build_module::reset_global_post()
	 * if your module needs to be destructive to the global post
	 *
	 * @return bool
	 */
	public function cache_global_post() {
		global $cfct_build;
		return $this->_build_post_id = $cfct_build->get_post_id();
	}
	
	/**
	 * Reset the global post after our loop
	 * 
	 * Uses cached value from cfct_build_module::cache_global_post()
	 *
	 * @return bool
	 */
	public function reset_global_post() {
		global $post;
		$post = get_post($this->_build_post_id);
		setup_postdata($post);
		return;
	}
	
	/**
	 * Widget Helpers
	 */
	private $widget;
	private $widget_type;
	
	function get_widget($widget_type) {
		if (!class_exists($widget_type)) {
			return false;
		}
		if (!($this->widget instanceof $widget_type)) {
			return false;
		}
		$this->widget = new $widget_type($this->id_base = false, $this->name, $this->opts = array());
		return true;
	}
	
	public function is_widget() {
		return !empty($this->_widget_id);
	}
	
// Carrington Framework Compat

	/**
	 * When loading partial module content over ajax Carrington Framework
	 * compat will not be sufficiently available. This inits the compat
	 * functionality and mimics the normal 'current_module' check to return
	 * the id of the module that is responding to the ajax request
	 *
	 * @return void
	 */
	protected function ajax_set_carrington_framework_filters() {
		global $cfct_build;
		$cfct_build->add_carrington_framework_filters();
		add_filter('cfct-build-current-module', array($this, 'ajax_current_module'), 10, 1);
	}
	
	/**
	 * Uset the ajax filters for Carrington Framework compat
	 *
	 * @return void
	 */
	protected function ajax_remove_carrington_framework_filters() {
		global $cfct_build;
		remove_filter('cfct-build-current-module', array($this, 'ajax_current_module'), 10, 1);
		$cfct_build->remove_carrington_framework_filters();
	}

	/**
	 * Return the id_base of the current module
	 * Only used in conjunction with Carrington Framework in an Ajax load capacity
	 *
	 * @param string $current_module 
	 * @return string
	 */
	public function ajax_current_module($current_module) {
		return $this->id_base;
	}
	
// Helper function for outword compatability

	/**
	 * Return an array of all post-IDs refernced in this module's data
	 * Required for compat with CF-Deploy & any other plugins that might
	 * need to know about IDs referenced in the data.
	 *
	 * Recommended return data format is:
	 * @example return 	array(
	 * 		// single field values
	 * 		'field_name' => array(
	 * 			'type' => 'post_type',
	 * 			'type_name' => 'my-custom-post-type',
	 * 			'value' => 123,
	 * 		),
	 * 		'field_name' => array(
	 * 			'type' => 'taxonomy',
	 * 			'type_name' => 'category'
	 * 			'value' => array(123, 456)
	 * 		),
	 * 		
	 * 		// nested field values, key names are optional
	 * 		'field_name' => array(
	 * 			'key' => array(
	 * 				'type' => 'post_type',
	 * 				'type_name' => 'post'
	 * 				'value' => 123,
	 * 			),
	 * 			'key2' => array(
	 * 				'type' => 'post_type',
	 * 				'type_name' => 'page',
	 * 				'value' => 456,
	 * 			)
	 * 		)
	 * 	)
	 * 
	 * @param array $data 
	 * @return array
	 */
	public function get_referenced_ids($data) {
		return array();
	}
	
	/**
	 * Return the save data with modified reference IDs
	 *
	 * @see cfct_build_module::get_referenced_ids() for $ids data format
	 * @param array $data - standard module save data (will vary on a per-module basis)
	 * @param array $ids 
	 * @return array $data
	 */
	public function merge_referenced_ids($data, $reference_data) {
		return $data;
	}
}

?>
