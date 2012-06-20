<?php

// client side class only
class cfct_build extends cfct_build_common {
	
	protected $postmeta_key;
	protected $post_id;
		
	protected $_filter_cache = array();
	
	/**
	 * Storage var for Carrington Build postmeta retrieved for post_id 
	 *
	 * @var array
	 */
	public $template;
	protected $data;
	
	/**
	 * Construct
	 * prep and validate config
	 *
	 * @param int $post_id 
	 */
	public function __construct() {
		parent::__construct();
		
		add_action('init', array($this, 'request_handler'), 11);
		wp_enqueue_style('cfct-build-css',site_url('/?cfct_action=cfct_css'), array(), CFCT_BUILD_VERSION, 'screen');
		wp_enqueue_script('cfct-build-js',site_url('/?cfct_action=cfct_js'), array('jquery'), CFCT_BUILD_VERSION);
	}
	
	public function request_handler() {
		if (isset($_GET['cfct_action'])) {
			switch ($_GET['cfct_action']) {
				case 'cfct_js':
					$this->js();
					break;
				case 'cfct_css':
					$this->css();
					break;
			}
		}
	}
	
	/**
	 * Display
	 *
	 * @param bool $echo 
	 * @param int $post_id
	 * @param bool $html
	 * @return mixed - bool/string HTML
	 */
	public function display($echo = false, $post_id = null, $html = true) {
		$this->_init($post_id);
		do_action('cfct-build-pre-build', $this);
		
		if ($this->can_do_build()) {
			if ($html) {
				$this->cache_filters_state();
				$this->add_carrington_framework_filters(); // @carrington-framework
				$this->ret = '
					<div id="'.apply_filters('cfct-build-display-id', 'cfct-build-'.$this->post_id).'" class="'.apply_filters('cfct-build-display-class', 'cfct-build grid hideoverflow').'">
						'.$this->template->html($this->data).'
					</div>
					';
				$this->remove_carrington_framework_filters(); // @carrington-framework
				$this->restore_filters_state();
			}
			else {
				$this->ret = $this->template->text($this->data);
			}
		}
		else {
			$this->ret = false;
		}
		
		do_action('cfct-build-post-build', $this);
		$ret = $this->ret;
		$ret = apply_filters('cfct-build-content', $ret);
				
		if ($echo) {
			echo $ret;
		}
		else {
			return $ret;
		}
	}
	
	/**
	 * Display Plain Text Version
	 *
	 * @param bool $echo 
	 * @param int $post_id
	 * @return mixed - bool/string HTML
	 */
	public function text($echo = false, $post_id = null) {
		return $this->display($echo, $post_id, false);
	}
	
	public function js() {
		header('Content-type: text/javascript');
		$js = '';
		// safety wrap the included JS so we can safely use $()
		$js .= '
;(function($) {

			';
		$js .= $this->get_module_extras('js');
		$js .= '

})(jQuery);		
			';		
		$js = apply_filters('cfct-build-js', $js);
		echo $js;
		exit;
	}
	
	/**
	 * Output Front End CSS
	 *
	 * @param string $css 
	 * @return void
	 */
	public function css() {
		header('Content-type: text/css');

		$css = '';

		$css .= file_get_contents(
			CFCT_BUILD_DIR.'css/cfct-build-client.css'
		);
		$css .= $this->get_module_extras('css');
		$css .= $this->get_row_extras('css');

		$css = apply_filters('cfct-build-css', $css);
		echo $css;
		exit;
	}
	
# Filter Caching

	/**
	 * Running modules that excercise the_content & the_excerpt filters can reset
	 * the current filter pointers and orphan late running filters on the_content
	 * 
	 * Here we store the current state of the global filters so that they can be restored later
	 *
	 * @see restore_filters_and_actions_state()
	 * @return void
	 */
	function cache_filters_state() {
		global $wp_filter, $merged_filters, $wp_current_filter;
		$this->_filter_cache = array(
			'_wp_filter' => $wp_filter,
			'_merged_filters' => $merged_filters,
			'_wp_current_filter' => $wp_current_filter
		);
	}
	
	/**
	 * Restore the global filters state to how it was before we started the build process
	 *
	 * @return void
	 */
	function restore_filters_state() {
		global $wp_filter, $merged_filters, $wp_current_filter;
		$wp_filter = $this->_filter_cache['_wp_filter'];
		$merged_filters = $this->_filter_cache['_merged_filters'];
		$wp_current_filter = $this->_filter_cache['_wp_current_filter'];
		$this->_filter_cache = array();
	}
	
	
# Carrington Framework Integration

	public function get_current_module_type() {
		return apply_filters('cfct-build-current-module', $this->template->get_current_module_type());
	}

	// @carrington-framework
	public function add_carrington_framework_filters() {
		if (defined('CFCT_CORE_VERSION')) {
			add_filter('cfct_context', array($this, 'cfct_context'), 10, 1);
			add_filter('cfct_single_match_order', array($this, 'cfct_single_match_order'), 11, 1);
		}
	}

	// @carrington-framework
	public function remove_carrington_framework_filters() {
		if (defined('CFCT_CORE_VERSION')) {
			remove_filter('cfct_context', array($this, 'cfct_context'), 10, 1);
			remove_filter('cfct_single_match_order', array($this, 'cfct_single_match_order'), 11, 1);	
		}
	}

	/**
	 * Return a context of "module"
	 *
	 * @carrington-framework
	 * @param string $context 
	 * @return string
	 */
	public function cfct_context($context) {
		return 'module';
	}
	
	// @carrington-framework
	function cfct_single_match_order($order) {
		array_unshift($order, 'module');
		return $order;
	}
}

?>