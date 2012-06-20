<?php

class cfct_build_common {
	private $postmeta_key = CFCT_BUILD_POSTMETA;
	
	protected $active_state = null;
	
	protected $in_ajax = false;
	protected $ajax_hanlders = array();
	
	public $template;
	public $registered_modules_dirs = array();
	public $registered_module_options_dirs = array();
	public $enable_custom_attributes;
	public $module_urls = array();
	public $loaded_modules = array();
	
// Init & Config
	
	public function __construct() {
		$this->template = new cfct_build_template();
		add_action('cfct-modules-loaded', array($this, 'import_included_modules'), 10);
		add_action('cfct-modules-loaded', array($this, 'import_module_options'), 11);
		add_action('cfct-rows-loaded', array($this, 'import_included_rows'));
		$this->enable_custom_attributes = apply_filters('cfct-enable-custom-attributes', true);
	}
	
	/**
	 * Prep the object for action.
	 * Can be forced to prep a specific post_id if needed.
	 *   - forced post_id is used by the admin module save to build the post_content when a module is saved 
	 * 
	 * @param $post_id - optional, init a specific post_id
	 * @return bool
	 */
	public function _init($post_id = null, $force = false) {
		if(!empty($this->init_done) && !$force) {
			return;
		}

		$this->set_post_id($post_id);
		$this->postmeta_key = apply_filters('cfct-build-postmeta-key', $this->postmeta_key);		

		if ($postmeta = $this->get_postmeta()) {
			$this->data = !is_array($postmeta['data']) ? array() : $postmeta['data'];
			$this->active_state = !empty($postmeta['active_state']) ? $postmeta['active_state'] : null;
			$this->template->set_template($postmeta['template']);
		}
		else {
			// new post or non-build post
			$this->data = array();
			$this->template->set_template(array());
		}
		
		$this->init_done = true;
	}
	
	/**
	 * Override the template object with a pre-instantiated template object
	 * $template must be a cfct_build_template object or it will be rejected
	 *
	 * @param object $template 
	 * @return void
	 */
	public function set_template($template) {
		if (is_object($template) && $template instanceof cfct_build_template) {
			$this->template = $template;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Set the internal post_id. Revert to current global post if no post_id given
	 *
	 * @param int $post_id 
	 * @return void
	 */
	public function set_post_id($post_id = null) {
		if (empty($post_id)) {
			global $post;
			$post_id = (is_object($post)) ? $post->ID : 0;
		}
		$this->post_id = $post_id;
	}
	
	public function get_post_id() {
		return $this->post_id;
	}
	
	/**
	 * Query wether we have the data we need to do a build
	 *
	 * @return void
	 */
	public function can_do_build() {
		if (!is_array($this->data) || count($this->data) == 0) {
			return false;
		}
		if (!is_array($this->template->get_template()) || count($this->template->get_template()) == 0) {
			return false;
		}
		if (!empty($this->active_state) && $this->active_state != 'build') {
			return false;
		}
		return true;
	}
	
	/**
	 * Helper function to allow others to determine if this
	 * object is responding to an Ajax call
	 *
	 * @return bool
	 */
	public function in_ajax() {
		return $this->in_ajax;
	}

// Included Modules

	/**
	 * Accessor function for module_urls array
	 *
	 * @return array
	 */
	function get_module_urls() {
		return $this->module_urls;
	}
	
	/**
	 * Get the url for a loaded module
	 *
	 * @param string $basename
	 * @return void
	 */
	function get_module_url($basename) {
		$url = null;
		if (!empty($this->module_urls[$basename])) {
			$url = $this->module_urls[$basename]; 
		}
		return $url;
	}
	
	function get_module_path($basename) {
		$path = null;
		if (!empty($this->loaded_modules[$basename])) {
			$path = $this->loaded_modules[$basename];
		}
		return $path;
	}

	function get_module_options_path($basename) {
		$path = null;
		$loaded_module_options = $this->included_module_options(true);
		if (!empty($loaded_module_options[$basename])) {
			$path = $loaded_module_options[$basename];
		}
		return $path;
	}
	
	/**
	 * See if we can determine the url of the included module
	 * We natively support a few locations:
	 *  - wp-content/plugins/carrington-build/modules/
	 *  - wp-content/themes/theme-name/carrington-build/modules
	 *  - wp-content/themes/theme-name/modules
	 *
	 * @param string $file_key 
	 * @param string $module 
	 * @return array
	 */
	function set_module_url($file_key, $module) {
		switch (true) {
			case strpos($module, 'wp-content/plugins/carrington-build/modules') !== false: 
			case strpos($module, 'wp-content/mu-plugins/carrington-build/modules') !== false:
				$url = trailingslashit(CFCT_BUILD_URL).'modules/'.$file_key.'/';
				break;
			case strpos($module, 'wp-content/themes/'.get_stylesheet().'/carrington-build/modules') !== false:
				$url = trailingslashit(get_stylesheet_directory_uri()).'carrington-build/modules/'.$file_key.'/';
				break;
			case strpos($module, 'wp-content/themes/'.get_stylesheet().'/modules') !== false:
				$url = trailingslashit(get_stylesheet_directory_uri()).'modules/'.$file_key.'/';
				break;
			case strpos($module, 'wp-content/themes/'.get_template().'/carrington-build/modules') !== false:
				$url = trailingslashit(get_template_directory_uri()).'carrington-build/modules/'.$file_key.'/';
				break;
			case strpos($module, 'wp-content/themes/'.get_template().'/modules') !== false:
				$url = trailingslashit(get_template_directory_uri()).'modules/'.$file_key.'/';
				break;
			case strpos($module, 'wp-content') !== false:
				# this is a pretty good guess... let's go with it.
				$url = trailingslashit(get_bloginfo('siteurl').substr(dirname($module), strpos($module, 'wp-content') - 1, strlen($module) -1));
				break;
			default:
				# if we've gotten this far then we haven't determined a usable url, give the developer a chance to recover
				$url = '';
				$url = apply_filters('cfct-build-module-url-unknown', $url, dirname($module), $file_key);
		}
		return apply_filters('cfct-build-module-url', $url, $module, $file_key);
	}
	
	/**
	 * Import modules by directory
	 * Sort modules alphabetically by key before import
	 *
	 * @return bool
	 */
	public function import_included_modules() {
		$modules = $this->included_modules();
		$module_urls = array();
		
		foreach ($modules as $dir) {
			if (is_array($dir)) {
				foreach($dir as $file => $module) {
					$this->loaded_modules[$file] = $module;
					$module_paths[$file] = $module;
					$module_urls[$file] = $this->set_module_url($file, $module);
					include_once($module);
				}
			}
		}
		$this->module_urls = apply_filters('cfct-build-module-urls', $module_urls);
		do_action('cfct-modules-included', $modules);
		return true;
	}
	
	public function included_modules() {
		if ($modules = wp_cache_get('cfct-build-included-modules', 'cfct_build')) {
			return $modules;
		}
		
		$paths = $this->get_include_module_dirs();
		$modules = array();
		foreach ($paths as $path) {
			if (is_dir($path) && $handle = opendir($path)) {
				while (false !== ($file = readdir($handle))) {
					$path = trailingslashit($path);
					if (is_dir($path.$file) && is_file($path.$file.'/'.$file.'.php')) {
						$modules[$path][$file] = $path.$file.'/'.$file.'.php';
					}
				}
			}
			if (is_array($modules[$path])) {
				ksort($modules[$path]);
			}
		}

		wp_cache_set('cfct-build-included-modules', $modules, 'cfct_build', 3600);
		return $modules;
	}
	
	public function get_include_module_dirs() {
		static $dirs;
		if (is_null($dirs)) {
			$dirs = apply_filters('cfct-module-dirs', array_merge(array(trailingslashit(CFCT_BUILD_DIR).'modules'), $this->registered_modules_dirs));
		}
		return $dirs;
	}

	/**
	 * Check to see if a module exists for use
	 *
	 * @param string $module_id_base 
	 * @return bool
	 */
	public function module_exists($module_id_base) {
		return array_key_exists($module_id_base, $this->template->modules);
	}
	
	/**
	 * Check to see if a module is used in the currently loaded data set
	 * WARNING: calling before data is loaded can yeild false results
	 *
	 * @param string $module_id_base 
	 * @return bool
	 */
	public function module_used($module_id_base) {
		if (empty($this->data['modules']) || !is_array($this->data['modules'])) {
			return false;
		}
		
		foreach ($this->data['modules'] as $module) {
			if ($module['module_id_base'] == $module_id_base) {
				return true;
			}
		}
		
		return false;
	}
	
// Included Module Options

	public function import_module_options() {
		$modules = $this->included_module_options();
		foreach ($modules as $module) {
			include_once($module);
		}
		return true;
	}

	public function included_module_options($get_lookup = false) {
		if ($modules = wp_cache_get('cfct-build-included-module-options-lookup', 'cfct_build')) {
			if ($get_lookup) {
				return $modules;
			}
			return array_values($modules);
		}
	
		$paths = apply_filters('cfct-module-option-dirs', array_merge(array(trailingslashit(CFCT_BUILD_DIR).'module-options'), $this->registered_module_options_dirs));
		$modules = array();
		foreach ($paths as $path) {
			if (is_dir($path) && $handle = opendir($path)) {
				while (false !== ($file = readdir($handle))) {
					$path = trailingslashit($path);
					if (is_dir($path.$file) && is_file($path.$file.'/'.$file.'.php')) {
						$modules[$file] = $path.$file.'/'.$file.'.php';
					}
				}
			}
		}

		wp_cache_set('cfct-build-included-module-options-lookup', $modules, 'cfct_build', 3600);
		if ($get_lookup) {
			return $modules;
		}
		return array_values($modules);
	}

// Included Rows
	
	public function import_included_rows() {
		$rows = $this->included_rows();
		foreach ($rows as $row) {
			include_once($row);
		}
		do_action('cfct-rows-included', $rows);
		return true;
	}
	
	public function included_rows() {
		if ($rows = wp_cache_get('cfct-build-included-rows', 'cfct_build')) {
			return $rows;
		}
		
		$paths = apply_filters('cfct-row-dirs', array(trailingslashit(CFCT_BUILD_DIR).'rows'));
		$rows = array();
		
		foreach ($paths as $path) {
			if (is_dir($path) && $handle = opendir($path)) {
				while (false !== ($file = readdir($handle))) {
					$path = trailingslashit($path);
					if (is_dir($path.$file) && is_file($path.$file.'/'.$file.'.php')) {
						$rows[] = $path.$file.'/'.$file.'.php';
					}
				}
			}
		}
		wp_cache_set('cfct-build-included-rows', $rows, 'cfct_build', 3600);
		return $rows;		
	}
	
// Ajax Handlers
	
	/**
	 * Register an Ajax handler function
	 * Function can come from anywhere, only restriction is that the function must be callable
	 * 
	 * $func can be string `funcname` or `array($obj, 'methodname')` 
	 *
	 * @param string $key 
	 * @param string/array $func 
	 * @return bool
	 */
	public function register_ajax_handler($key, $func) {
		if (is_callable($func)) {
			return $this->ajax_handlers[$key] = $func;
		}
		return false;
	}
	
	/**
	 * Remove a registered ajax handler
	 *
	 * @param string $key 
	 * @return bool
	 */
	public function deregister_ajax_handler($key) {
		if ($this->is_ajax_handler($key)) {
			unser($this->ajax_handlers[$key]);
		}
		return true;
	}
	
	/**
	 * Check wether a keyname is a valid ajax handler
	 *
	 * @param string $key 
	 * @return bool
	 */
	public function is_ajax_handler($key) {
		return isset($this->ajax_handlers[$key]);
	}
	
	/**
	 * Get a specific ajax handler
	 *
	 * @param string $key 
	 * @return mixed
	 */
	public function get_ajax_handler($key) {
		if ($this->is_ajax_handler($key)) {
			return array(
				'key' => $key,
				'func' => $this->ajax_handlers[$key]
			);
		}
		return false;
	}
	
// Postmeta
	
	/**
	 * Get postmeta for a post.
	 * Defaults to current post if no post_id given.
	 * Returns a default empty config if no-config found in database.
	 *
	 * @param string $post_id 
	 * @return array
	 */
	public function get_postmeta($post_id = null) {
		if (is_null($post_id)) {
			$post_id = $this->post_id;
		}
		
		// maybe_unserialize added to safeguard against WordPress double serialization on data import
		$post_data = maybe_unserialize(get_post_meta($post_id, $this->postmeta_key, true));
		if (empty($post_data)) {
			$post_data = apply_filters('cfct-default-data', array(
				'timestamp' => null,
				'data' => null,
				'template' => null,
				'active_state' => null
			));
		}		
		return apply_filters('cfct-get-postmeta', $post_data, $post_id);
	}
	
	/**
	 * Save post meta
	 *
	 * @param int $post_id 
	 * @param array $post_data 
	 * @return bool
	 */
	public function set_postmeta($post_id, $post_data) {
		$post_data['timestamp'] = time();		
		return update_post_meta($post_id, CFCT_BUILD_POSTMETA, $post_data);
	}
	
	/**
	 * Prune postmeta for orphan multi-module data
	 *
	 * @param string $post_id 
	 * @return bool
	 */
	public function prune_postmeta($post_id) {
		$pruned = false;
		$post_data = $this->get_postmeta($post_id);
		
		if (!empty($post_data['data']['modules'])) {
			foreach ($post_data['data']['modules'] as $module_id => $module) {
				// check if module is a multi-module orphan
				if (!empty($module['parent_module_id']) && empty($post_data['data']['modules'][$module['parent_module_id']])) {
					// remove orphaned block data
					if (!empty($post_data['data']['blocks'][$module['block_id']])) {
						unset($post_data['data']['blocks'][$module['block_id']]);
					}
					// remove module data
					unset($post_data['data']['modules'][$module_id]);
					$pruned = true;
				}
			}
		}
		
		// re-init if we did a prune action
		if ($pruned) {
			$this->set_postmeta($post_id, $post_data);
			$this->_init($post_id, true);
		}
		
		return true;
	}

// Module/Row specified CSS & JS
	
	/**
	 * Get custom JS or CSS from modules
	 *
	 * $type can be 'js' or 'css'
	 * if $admin is set to true then admin specific functions will be called
	 * 
	 * @param string $type 
	 * @param string $admin 
	 * @return string
	 */
	public function get_module_extras($type, $admin = false) {		
		$ret = $this->get_extras($type, $admin, 'modules');
		if ($this->enable_custom_attributes) {
			$module_options = cfct_module_options::get_instance();
			$ret .= $module_options->$type($admin);
		}
		return apply_filters('cfct-module-extras', $ret, $type, $admin);
	}
	
	/**
	 * Get custom row CSS
	 * Currently only supports $type = 'css' - no functional reason to support row JS at this time
	 *
	 * @param $type 
	 * @return string
	 */
	public function get_row_extras($type, $admin = false) {
		$ret = $this->get_extras($type, $admin, 'rows');	
		return apply_filters('cfct-row-extras', $ret, $type, $admin);
	}
	
	/**
	 * Common function for pulling row/module extras
	 *
	 * $type can be 'css' or 'js'
	 * $admin is boolean
	 * $from can be 'rows' or 'modules'
	 *
	 * @param string $type
	 * @param bool $admin
	 * @param string $from
	 * @return string
	 */
	public function get_extras($type, $admin, $from) {
		// figure out the function we're gonna run
		$func = ($admin ? '_admin_' : null).strtolower($type);

		$ret = PHP_EOL.PHP_EOL.'/* ADDED CUSTOM '.strtoupper($type).' */'.PHP_EOL;
		
		$this->template->init();
		foreach ($this->template->$from as $obj) {
			if (method_exists($obj, $type)) {
				$r = $obj->$func();
				if (!empty($r)) {
					$ret .= PHP_EOL.trim($r).PHP_EOL;					
				}
			}
		}
		// ie: `cfct-get-extras-modules-js-admin`, `cfct-get-extras-modules-css`, etc...
		$ret = apply_filters('cfct-get-extras-'.$from.'-'.$type.($admin ? '-admin' : null), $ret);
		return trim($ret);		
	}
}

?>
