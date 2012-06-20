<?php
/**
 * Currently, this class is only concerned with templates, not data.
 * In future we want to change the block class to include data in instances
 * as well.
 * Data might include:
 * 	Modules
 * 	...?
 */
class cfct_block {
	/**
	 * Groups of classes (array of hashes)
	 * The two you're likely to have are:
	 *    general (used everywhere)
	 *    admin (additional classes for admin)
	 */
	protected $classes_groups = array();
	public $attrs = array();
	protected $filter_key_mod = '';
	protected $client_template = '';
	protected $admin_template = '';
	
	public function __construct($classes = array()) {
		if (!$this->get_filter_mod()) {
			$this->set_filter_mod($this->generate_filter_mod());
		}
		
		// Add specified classes
		$this->add_classes($classes);
		
		/* Add backwards-compat cfct-block admin class
		Lots of JS currently ties into this class in
		cfct-build-admin.js, so we're not going to break that */
		$this->add_classes(array('cfct-block'), 'admin');

		// Allow classes to be filtered
		$filter_key = $this->get_filter_mod().'-classes';
		$classes = apply_filters(
			$filter_key, $this->get_classes(), $this
		);
		$this->set_classes($classes);
		
		if (!$this->client_template) {
			$this->set_client_template('<div class="{class}">{modules}</div>');
		}
		if (!$this->admin_template) {
			$this->set_admin_template('
			<td id="{id}" class="{class}" {attrs}>
				<div class="cfct-block-modules">
					{modules}
				</div>
			</td>');
		}
	}
	
	public function set_admin_template($template) {
		// Deprecated filter...
		$template = apply_filters(
			'cfct-block-admin-html',
			$template,
			/* Pass as string to avoid breaking backwards compatibility
			for this filter. */
			$this->make_classname('admin')
		);
		// New filter...
		$this->admin_template = apply_filters(
			'cfct-block-admin-template',
			$template,
			$this
		);
		return $this;
	}
	
	public function set_client_template($template) {
		// Deprecated filter...
		$template = apply_filters(
			'cfct-block-html',
			$template,
			/* Pass as string to avoid breaking backward compatibility
			for this filter */
			$this->make_classname()
		);
		// New filter...
		$this->client_template = apply_filters(
			'cfct-block-template',
			$template,
			$this
		);
		return $this;
	}
	
	/**
	 * Process template with default template processing tokens.
	 * You can add tokens and replacements using the $replacements array
	 */
	public function process_template($template, $replacements = array()) {
		$default_replacements = array(
			'{class}' => $this->make_classname(),
			'{attrs}' => cfct_tpl::to_attr($this->attrs)
		);
		$replacements = array_merge($default_replacements, $replacements);
		return strtr($template, $replacements);
	}
	
	/**
	 * Render client-side HTML template.
	 */
	public function as_client_html($replacements = array()) {
		return $this->process_template(
			$this->client_template,
			$replacements
		);
	}
	
	/**
	 * Render WordPress-side HTML template.
	 */
	public function as_admin_html($replacements = array()) {
		$default_replacements = array(
			'{class}' => $this->make_classname('admin')
		);
		$replacements = array_merge($default_replacements, $replacements);
		return $this->process_template(
			$this->admin_template,
			$replacements
		);
	}
	
	/**
	 * Use this for backwards-compat with old array-based API.
	 */
	public function as_old_array() {
		return array(
			'class' => $this->make_classname()
		);
	}
	
	/**
	 * Auto-generate filter key modifier for making filters unique.
	 * Based on classname.
	 * @return string
	 */
	protected function generate_filter_mod() {
		$class_name = get_class($this);
		$key = strtr($class_name, array(
			'\\' => '',
			'_' => '-'
		));
		return strtolower($key);
	}
	
	/**
	 * Customize the filter key modifier. Useful if you don't want the
	 * auto-generated filter modifier. We use it in our row-types to maintain
	 * backwards compatibility with filter keys.
	 * @param string $key Keep it sane, please. All lowercase and dashes instead of spaces, in most cases.
	 */
	protected function set_filter_mod($key) {
		$this->filter_key_mod = $key;
		return $this;
	}
	
	/**
	 * Gets the current class and turns it into a key that you can add
	 * to your filters to make them unique to the row.
	 * @return string
	 */
	public function get_filter_mod() {
		return $this->filter_key_mod;
	}

	/**
	 * Add additional classes to a group (default = general)
	 */
	public function add_classes($classes = array(), $group = 'general') {
		if (!empty($this->classes_groups[$group])) {
			$classes = array_merge($this->classes_groups[$group], $classes);
		}
		$this->classes_groups[$group] = cfct_tpl::clean_classes($classes);
		return $this;
	}
	
	/**
	 * Set a group of classes (default = general)
	 */
	public function set_classes($classes = array(), $group = 'general') {
		$this->classes_groups[$group] = cfct_tpl::clean_classes($classes);
		return $this;
	}
	
	/**
	 * Get a group of classes (default = general)
	 */
	public function get_classes($group = null) {
		$classes = array();
		if ( $group && !empty($this->classes_groups[$group]) ) {
			$classes = array_merge($classes, $this->classes_groups[$group]);
		}
		if ( !empty($this->classes_groups['general']) ) {
			$classes = array_merge($classes, $this->classes_groups['general']);
		}
		return cfct_tpl::clean_classes($classes);
	}
	
	public function make_classname($group = null) {
		return cfct_tpl::to_classname( $this->get_classes($group) );
	}
	
	public function add_attrs($attr = array()) {
		$this->attrs = array_merge($this->attrs, $attr);
		return $this->attrs;
	}
	
	public function attrs_as_string() {
		return cfct_tpl::to_attr($this->attrs);
	}
}

/* For now, subclasses don't do much except give you a safe way to distinguish the type of a block, independent of it's HTML classes (use instanceof, typeof). This is better than setting a $type property, because it's future-thinking and gives us a more expressive checking syntax. */

/* Full width */
class cfct_block_c4_1234 extends cfct_block {
	public function __construct($classes = array()) {
		$this->add_classes(array('c4-1234'));
		parent::__construct($classes);
	}
}

/* Halves */
class cfct_block_c4_12 extends cfct_block {
	public function __construct($classes = array()) {
		$this->add_classes(array('c4-12'));
		parent::__construct($classes);
	}
}
class cfct_block_c4_34 extends cfct_block {
	public function __construct($classes = array()) {
		$this->add_classes(array('c4-34'));
		parent::__construct($classes);
	}
}

/* Thirds */
class cfct_block_c6_12 extends cfct_block {
	public function __construct($classes = array()) {
		$this->add_classes(array('c6-12'));
		parent::__construct($classes);
	}
}
class cfct_block_c6_34 extends cfct_block {
	public function __construct($classes = array()) {
		$this->add_classes(array('c6-34'));
		parent::__construct($classes);
	}
}
class cfct_block_c6_56 extends cfct_block {
	public function __construct($classes = array()) {
		$this->add_classes(array('c6-56'));
		parent::__construct($classes);
	}
}

/* 2 Thirds */
class cfct_block_c6_1234 extends cfct_block {
	public function __construct($classes = array()) {
		$this->add_classes(array('c6-1234'));
		parent::__construct($classes);
	}
}
class cfct_block_c6_3456 extends cfct_block {
	public function __construct($classes = array()) {
		$this->add_classes(array('c6-3456'));
		parent::__construct($classes);
	}
}

/* Multi blocks */
class cfct_multi_block extends cfct_block {
	public function __construct($classes) {
		$this->add_classes(array('cfct-multi-module-block'));
		parent::__construct($classes);
	}
}
class cfct_multi_block_c4_1234 extends cfct_multi_block {
	public function __construct($classes = array()) {
		$this->add_classes(array('c4-1234'));
		parent::__construct($classes);
	}
}

?>