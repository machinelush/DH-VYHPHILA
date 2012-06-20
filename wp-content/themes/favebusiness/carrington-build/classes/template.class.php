<?php

/**
 * Template
 *
 * @package cfct_build
 */
class cfct_build_template implements Iterator {
		
	private $data;
	
	private $template;
	
	public $rows = array();
	public $registered_rows = array();
	private $legacy_row_ids = array();
	
	public $modules = array();
	public $registered_modules = array();
	private $legacy_module_ids = array();

	private $types = array();
	
	protected $html;
	protected $current_row = null;
	
	protected $admin;
			
	public function __construct() {
		$default_types = array('row', 'module');
		$this->types = apply_filters('cfct-template-valid-types', $default_types);
		
		$this->set_is_admin(is_admin());
		add_action('init', array($this, 'init'), 999999);
	}

	public function init() {
		$this->init_types();
		do_action('cfct-template-init', $this);
	}

	public function set_template($template) {
		if (!$template || empty($template)) {
			// start a new template
			$template = $this->new_template();
			$template = apply_filters('cfct-default-template', $template);
		}
		elseif (is_int($template)) {
			// @TODO pull structure from database?
		}
		$this->template = apply_filters('cfct-build-template', $template);		
		return true;
	}
	
	public function get_template() {
		return $this->template;
	}
	
	public function get_row_data($row_id) {
		if (isset($this->template['rows'][$row_id]) && is_array($this->template['rows'][$row_id])) {
			return $this->template['rows'][$row_id];
		}
		return false;
	}
	
	/**
	 * display the template
	 *
	 * @param array $data 
	 * @return string html
	 */
	public function html(array $data) {
		$this->data = $data;		
		$this->html = '';
		foreach ($this->template['rows'] as $row_id => $row) {			
			$this->current_row = $row_id;
			$this->html .= $this->row($row);
		}
		
		return apply_filters('cfct-build-template-html', $this->html, $this);
	}
	
	public function text(array $data) {
		$this->return_format = 'text';
		return $this->html($data);
	}
	
	public function describe_template(array $data) {
		$this->data = $data;
		$this->html = '<ul style="list-style: disc outside; margin-left: 1.5em;">';
		foreach($this->template['rows'] as $row_id => $row) {
			$this->current_row = $row_id;
			if (!($_row = $this->get_row($row['type']))) {
				continue;
			}
			$this->html .= $_row->describe($row, $this->data, $this);
		}
		$this->html .= '</ul>';
		return $this->html;
	}
	
	public function add_row(array $row) {
		if (!isset($this->rows[$row['type']])) {
			throw new cfct_row_exception('Class for row type <code>'.$row['type'].'</code> does not exist.');
		}
		
		$row = $this->rows[$row['type']]->process_new($row);
		$html = $this->row($row, true);		
		$this->template['rows'][$row['guid']] = $row;
		return array('html' => $html, 'args' => $row);
	}
	
	public function remove_row($row_id) {
		if (isset($this->template['rows'][$row_id])) {
			unset($this->template['rows'][$row_id]);
		}
		else {
			throw new cfct_row_exception('Cannot delete row. Row id <code>'.$row_id.'</code> does not exist.');
		}
		return true;
	}
	
	public function reorder_rows(array $new_order) {
		if (count($new_order) != count($this->template['rows'])) {
			throw new cfct_row_exception('Reorder row count does not match current row count.');
		}
		return $this->template['rows'] = array_merge(array_flip($new_order), $this->template['rows']);
	}
	
	public function have_rows() {
		return (is_array($this->template['rows']) && count($this->template['rows']) > 0);
	}
	
	public function row(array $row, $new = false) {
		if (!($_row = $this->get_row($row['type']))) {
			return false;
		}
		
		$this->current_row = $_row;

		if (isset($this->return_format) && $this->return_format == 'text') {
			$ret = PHP_EOL.$_row->text($row,($new ? array() : $this->data), $this).PHP_EOL;
		}
		elseif ($this->get_is_admin()) {
			$ret = $_row->admin($row, ($new ? array() : $this->data), $this);
		}
		else {
			$ret = $_row->html($row,($new ? array() : $this->data), $this);
		}
		
		$this->current_row = null;
		
		return $ret;
	}
	
	private function row_class() {
		// TBD - pick row type here
		// ie: a, a-bc, ab-c, a-b-c
		$class = 'cfct-build-row';
		return apply_filters('cfct-row-class', $class);
	}
	
	private function block($block) {
		foreach ($block as $module) {
			if ($module = $this->get_type('module', $module['module_name'])) {
				$func = !$this->is_admin ? '_html' : '_admin';
				return $module->$func($this->data[$module['guid']]);
			}
			else {
				return false;
			}
		}
	}
	
	/**
	 * Start a blank template
	 * Trivial, but centralized
	 *
	 * @return array
	 */
	public function new_template() {
		$template = array(
			'from_template_id' => false,
			'rows' => array()
		);
		return $template;
	}
	
	/**
	 * Sanitize a template - 
	 *
	 * @param string $template 
	 * @return void
	 */
	public function sanitize_template($template) {
		// strip previous template_id association
		if (isset($template['from_template_id'])) {
			unset($template['from_template_id']);
		}
		
		// sanitize rows
		foreach ($template['rows'] as &$row) {
			if (isset($row['post_id'])) {
				unset($row['post_id']);
			}
		}
		
		return $template;
	}
	
// Formatting object retrieval 
	
	public function get_module($classname) {
		$module = $this->get_type('module', $classname);
		if (!$module) {
			$module = new cfct_no_module_module($classname);
		}
		return apply_filters('cfct-build-template-get-module', $module, $classname);
	}
	
	public function get_row($classname) {
		$row = $this->get_type('row', $classname);
		return apply_filters('cfct-build-template-get-row', $row, $classname);
	}
	
	protected function _get_legacy_id($type, $name) {
		$_legacy_objects = 'legacy_'.$type.'_ids';
		return !empty($this->{$_legacy_objects}[$name]) ? $this->{$_legacy_objects}[$name] : false;
	}
	
	/**
	 * Get a specific module or row
	 * Module & row classes are not data specific, so we can keep an array of 
	 * objects that we can re-use instantiated classes instead of using unique
	 * objects for each instance
	 *
	 * @param string $type - 'module' or 'row'
	 * @param string $classname
	 * @return object
	 */
	private function get_type($type, $classname) {
		global $cfct_build;

		$registered_objects = 'registered_'.$type.'s';
		$objects = $type.'s';

		if (!isset($this->$registered_objects) || !isset($this->{$registered_objects}[$classname])) {
			$_classname = $classname;
			if (!($classname = $this->_get_legacy_id($type, $classname))) {
				return false;
			}
			$this->dbg(__METHOD__, 'Fetched legacy id for '.$_classname.'. Please update the '.$type.' definition to new 1.1 registration spec. Apply legacy id in class definition if necessary');
		}
		
		if (isset($this->{$objects}[$classname]) && !($this->{$objects}[$classname] instanceof $this->{$registered_objects}[$classname]['classname'])) {
			$this->{$objects}[$classname] = new $this->{$registered_objects}[$classname];
		}
		return $this->{$objects}[$classname];
	}

// Type Registration
	public function register_type($type, $classname, $args = array()) {
		if (!class_exists($classname)) {
			return false;
		}

		$registered_objects = 'registered_'.$type.'s';
		$objects = $type.'s';
		
		if (!isset($this->$objects)) {
			return false;
		}
		
		// for widgets we use the id passed in instead of the classname
		$id = (!empty($args['module_id']) ? $args['module_id'] : $classname);
		
		// register
		$this->{$registered_objects}[$id] = array( 
			'classname' => $classname, 
			'args' => $args
		);

		return true;
	}
	
	public function deregister_type($type, $id) {
		$registered_objects = 'registered_'.$type.'s';
		$objects = $type.'s';
		$_legacy_objects = 'legacy_'.$type.'_ids';

		if (!isset($this->$objects) && !isset($this->$_legacy_objects)) {
			return false;
		}
		
		if (isset($this->{$registered_objects}[$id])) {
			if (isset($this->{$objects}[$id])) {
				unset($this->{$objects}[$id]);
			}
			unset($this->{$registered_objects}[$id]);
		}
		
		return true;		
	}
	
	private function init_types() {
		foreach ($this->types as $type) {
			$registered_objects = 'registered_'.$type.'s';
			$objects = $type.'s';
			$_legacy_objects = 'legacy_'.$type.'_ids';
			
			$this->$registered_objects = apply_filters('cfct-build-template-pre-'.$type.'-init', $this->$registered_objects);
			
			// modules can throw an exception during instantiation to abort construction
			foreach ($this->$registered_objects as $id => $params) {
				try {
					$this->{$objects}[$id] = new $params['classname']($params['args']);
					if ($_legacy_id = $this->{$objects}[$id]->_legacy_id()) {
						$this->{$_legacy_objects}[$_legacy_id] = $id;
					}
					unset($_legacy_id);
				}
				catch(Exception $e) {
					$this->dbg(__METHOD__, $e->getMessage);
				}
			}
			$this->$objects = cfct_array_sort_by_key($this->$objects, 'name');
		}
		return true;
	}

// Accessors & Helpers 
	
	public function row_type_exists($id) {
		return isset($this->registered_rows[$id]);
	}
	
	public function module_type_exists($id) {
		$found = isset($this->registered_modules[$id]);
		if (!$found) {
			$found = isset($this->legacy_module_ids[$id]);
		}
		return $found;
	}
	
	/**
	 * Get admin setting
	 *
	 * @return void
	 */
	public function get_is_admin() {
		return $this->admin;
	}

	/**
	 * Override the admin setting
	 *
	 * @param string $bool 
	 * @return void
	 */
	public function set_is_admin($bool = null) {
		if (!is_null($bool) && is_bool($bool)) {
			$this->admin = $bool;
		}
		return $this->admin;
	}
	
	public function get_current_module_type() {
		return $this->current_row->current_module->id_base;
	}

// Iterator - Allows us to for/foreach the object

	public function next() {
		return (next($this->rows) !== FALSE);
	}

	public function rewind() {
		return reset($this->rows);
	}

	public function key() {
		return key($this->rows);
	}

	public function current() {
		return current($this->rows);
	}

	public function valid() {
		return !is_null(key($this->rows));
	}
	
	/**
	 * log message to the debugger
	 *
	 * @param string $method - method logging the message
	 * @param string $message - log message
	 * @return bool
	 */
	function dbg($method, $message) {
		if (!CFCT_BUILD_DEBUG) { return false; }
		if (class_exists('cfct_build_debug')) {
			return cfct_build_debug::log($method, $message);
		}
	}	
}

?>