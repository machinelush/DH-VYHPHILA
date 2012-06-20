<?php

class cfct_no_module_module extends cfct_build_module {
	protected $editable = false;
	protected $_truncate = false;
	protected $warning_message;
	protected $original_id;
	
	public function __construct($id = null) {
		parent::__construct('cfct-no-module-module', __('No Module', 'carrington-build'), array());
		$this->warning_message = '<p class="cfct-build-warning cfct-build-missing-plugin">'.__('Requested module', 'carrington-build').' (<span class="cfct-build-missing-module-name">'.$id.'</span>) '.__('is missing!', 'carrington-build').'</p>';
		
	}
	
	public function display($data) {
		$ret = '';
		return $ret;
	}
	
	public function admin_form($data) {
		$ret = $this->warning_message;
		return $ret;
	}
	
	public function admin_preview($data) {
		$ret = $this->warning_message;
		return $ret;	
	}
	
	public function admin_text($data) {
		$ret = $this->warning_message;
		return $ret;
	}
	
	public function text($data) {
		return '';
	}
	
	public function update($new_data, $old_data) {
		return $new_data;
	}
}

// @TODO - integrate this elsewhere
function cfct_default_modules_init() {
	do_action('cfct-modules-loaded');
}
add_action('init', 'cfct_default_modules_init');
