<?php

/**
 * Responsive Design Classes
 *
 * @package Carrington Build
 */
class cfct_module_option_responsive_design extends cfct_module_option {
	
	public function __construct() {
		global $cfct_build;
		parent::__construct('Responsive Design', 'responsive-design');
		add_filter('cfct-build-module-class', array($this, 'apply_classes'), 10, 2);
		$cfct_build->register_ajax_handler('responsive_update', array($this, 'ajax_responsive_update'));
	}

	/**
	 * Non-standard module options method to filter in our custom classes in to the
	 * module's class attribute. Uses a standard filter in CB
	 *
	 * @param string $class
	 * @param array $data
	 * @return string
	 */
	public function apply_classes($class, $data) {
		if (!empty($data['cfct-module-options'][$this->id_base]['responsive-classes'])) {
			$classes = cfct_tpl::extract_classes($class);
			$responsive_classes = $data['cfct-module-options'][$this->id_base]['responsive-classes'];
			
			$class = cfct_tpl::to_classname(
				$classes, 
				$responsive_classes
			);
		}
		return $class;
	}

	/**
	 * Responsive design CSS classes and descriptions
	 * Use filter `cfct-build-responsive-design-classes` to modify.
	 * 
	 * @return array
	 **/
	public function available_classes() {
		$classes = array(
			'responsive-extra-wide' => 'Extra-wide',
			'responsive-narrow' => 'Narrow',
			'responsive-extra-narrow' => 'Extra-narrow'
		);
		$classes = apply_filters('cfct-build-responsive-design-classes', $classes);
		
		$safe_classes = array();
		foreach ($classes as $css_class => $title) {
			$css_class = sanitize_title_with_dashes(trim(strip_tags($css_class)));
			$safe_classes[$css_class] = $title;
		}
		
		return $safe_classes;
	}

	public function ajax_responsive_update($args) {
		global $cfct_build;
		$post_data = $cfct_build->get_postmeta($args['post_id']);
		if (empty($post_data['data']['modules'][$args['module_id']])) {
			throw new cfct_row_exception(__('Could not get postmeta for post on responsive class update','carrington-build').' (post_id: '.$args['post_id'].')');
		}
		if (!isset($post_data['data']['modules'][$args['module_id']]['cfct-module-options'])) {
			$post_data['data']['modules'][$args['module_id']]['cfct-module-options'] = array($this->id_base => array($this->id_base => array()));
		}
		
		if (!isset($post_data['data']['modules'][$args['module_id']]['cfct-module-options'][$this->id_base])) {
			$post_data['data']['modules'][$args['module_id']]['cfct-module-options'][$this->id_base] = array('responsive-classes' => array());
		}

		if (!isset($post_data['data']['modules'][$args['module_id']]['cfct-module-options'][$this->id_base]['responsive-classes'])) {
			$post_data['data']['modules'][$args['module_id']]['cfct-module-options'][$this->id_base]['responsive-classes'] = array();
		}
		
		$responsive_classes = $post_data['data']['modules'][$args['module_id']]['cfct-module-options'][$this->id_base]['responsive-classes'];
		
		$available_classes = $this->available_classes(true);
		
		foreach ($args['class_data'] as $css_class => $state) {
			$css_class = sanitize_title_with_dashes(trim(strip_tags($css_class)));
			
			if ($state) {
				if (!in_array($css_class, $responsive_classes)) {
					$responsive_classes[] = $css_class;
				}
			}
			else {
				$found_keys = array_keys($responsive_classes, $css_class);
				if (!empty($found_keys)) {
					foreach ($found_keys as $key) {
						unset($responsive_classes[$key]);
					}
				}
			}
		}
		
		$responsive_classes = apply_filters('cfct-build-responsive-design-update-classes', $responsive_classes, $post_data['data']['modules'][$args['module_id']]);
		
		$responsive_classes = array_intersect($responsive_classes, array_keys($available_classes));
		
		$post_data['data']['modules'][$args['module_id']]['cfct-module-options'][$this->id_base]['responsive-classes'] = $responsive_classes;

		if (!$cfct_build->set_postmeta($args['post_id'], $post_data)) {
			throw new cfct_row_exception(__('Could not save postmeta for post on responsive class update','carrington-build').' (post_id: '.$args['post_id'].')');
		}
		$cfct_build->set_post_content($args['post_id']);

		$ret = new cfct_message(array(
			'success' => true,
			'html' => __('CSS classes updated.', 'carrington-build'),
			'message' => 'module_id '.$args['module_id'].' '.sprintf(__('Responsive Design CSS classes: %s', 'carrington-build'), implode(' ', $responsive_classes)),
			'extra' => array(
				'module_id' => $args['module_id'],
				'row_id' => $args['row_id'],
				'block_id' => $args['block_id'],
				'css_classes' => $responsive_classes
			)
		));
		return $ret;
	}
	
	public function layout_html($data, $options_data, $module_type) {
		$responsive_classes = isset($options_data['responsive-classes']) ? (array) $options_data['responsive-classes'] : array();
		$class_select = '';
		foreach ($this->available_classes() as $css_class => $name) {
			$class_select .= '<li><input type="checkbox" name="' . esc_attr($css_class) . '"' . (in_array($css_class, $responsive_classes) ? ' checked="checked"' : '') . '/><span> ' . esc_html($name) . '</span></li>' . PHP_EOL;
		}
		
		$html = '';
		if (!empty($class_select)) { 
			$html .= '
					<div id="cfct-responsive-' . $data['module_id'] . '" class="cfct-responsive" >
						<a href="#'.$data['module_id'].'" class="cfct-responsive-trigger">&raquo;</a>
						<div id="cfct-responsive-inner-' . $data['module_id'] . '" class="cfct-responsive-inner">
							<ul>' .
								$class_select 
							. '</ul>
						</div>
					</div>';
		}
		
		return $html;
	}
	
	public function form($data, $module_type) {
		$value = null;
		if (!empty($data['responsive-classes'])) {
			$value = implode(' ', array_map('esc_attr', $data['responsive-classes']));
		}

		return '<input type="hidden" name="'.$this->get_field_name('responsive-classes').'" id="'.$this->get_field_id('responsive-classes').'" value="'.$value.'/>';
	}
	
	public function menu_item() {
		return '';
	}
	
	public function admin_css() {
		return $this->load_view('view-admin-css.php');
	}

	public function admin_js() {
		return $this->load_view('view-admin-js.php');
	}
	
	public function update($new_data, $old_data) {
		$ret = array();
		
		$classes = explode(' ', $new_data['responsive-classes']);
		if (is_array($classes)) {
			foreach($classes as $class) {
				$ret['responsive-classes'][] = sanitize_title_with_dashes(trim(strip_tags($class)));
			}
		}
		
		return $ret;
	}
}


/* Disabled in 1.2 */
/* cfct_module_register_extra('cfct_module_option_responsive_design'); */
	
?>
