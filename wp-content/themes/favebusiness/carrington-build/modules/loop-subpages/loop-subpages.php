<?php
if (!class_exists('cfct_module_loop')) {
	@include_once(dirname(dirname(__FILE__)).'/loop/loop.php');
}

if (!class_exists('cfct_module_loop_subpages') && class_exists('cfct_module_loop')) {
	class cfct_module_loop_subpages extends cfct_module_loop {
		protected $_deprecated_id = 'cfct-module-loop-subpages'; // deprecated property, not needed for new module development

		public function __construct() {
			global $cfct_build;
			
			// We need to enqueue the suggest script so we can use it later for type-ahead search
			$this->enqueue_scripts();

			// don't allow selection of content display in loop
			unset($this->content_display_options['content']);
			
			$opts = array(
				'description' => 'A list of sub-page titles or excerpts.',
				'icon' => 'loop-subpages/icon.png'
			);
			cfct_build_module::__construct('cfct-module-loop-subpages', __('Sub-Pages', 'carrington-build'), $opts);
		}

# Display

		/**
		 * Display the module
		 *
		 * @param array $data - saved module data
		 * @return string HTML
		 */
		public function display($data) {
			// make sure that pages get menu_order applied with fallback to title order
			$this->default_display_args = array_merge($this->default_display_args, array(
				'post_type' => 'any',
				'order' => 'ASC',
				'orderby' => 'menu_order title'
			));
			return parent::display($data);
		}
		
# Admin Form
		
		/**
		 * Output the Admin Form
		 * 
		 * @param array $data - saved module data
		 * @return string HTML
		 */
		public function admin_form($data) {
			return '
				<div id="'.$this->id_base.'-admin-form-wrapper">'.
					$this->admin_form_title($data).
					$this->admin_form_parent_pages($data).
					$this->admin_form_display_options($data).
				'</div>';
		}

		/**
		 * Get a list of pages who have subpages
		 * 
		 * @param array $data
		 * @return string HTML
		 */
		protected function admin_form_parent_pages($data) {
			return '
				<fieldset class="cfct-ftl-border">
					<legend>Page</legend>
					<!-- parent pages -->
					<div class="'.$this->id_base.'-input-wrapper">
						'.$this->get_parent_pages_dropdown($data).'
					</div>
					<div class="clear"></div>
					<!-- parent pages -->
				</fieldset>
				';
		}

# Admin Helpers
		
		/**
		 * Displays a dropdown of all pages that have parent children
		 *
		 * @return string
		 */
		protected function get_parent_pages_dropdown($data) {
			$parent_ids = $this->_get_parent_pages_ids();
			$html = '<label for="'.$this->get_field_id('parent').'">'.__('Parent Page', 'carrington-build').': </label>';
			if (!empty($parent_ids)) {
				$selected = (!empty($data[$this->get_field_name('parent')]) ? $data[$this->get_field_name('parent')] : null);
				$html .= '
					<select name="'.$this->get_field_name('parent').'" id="'.$this->get_field_id('parent').'">
						'.$this->_get_parent_options($parent_ids, $selected).'
					</select>
				';
			}
			else {
				$this->suppress_save = true;
				$disclaimer = 'No parent pages exist. To use this module pages or hierarchical post types that have child pages must exits.';
				$html .= '
					<div class="'.$this->id_base.'-input-replacement">'.__($disclaimer, 'carrington-build').'</div>
					<input type="hidden" name="'.$this->get_field_name('parent').'" value="" />
				';
			}
			return $html;
		}
		
		protected function _get_parent_options($parent_ids, $selected) {
			$pages = get_transient('cfct-build-loop-parent_pages');
			if (empty($pages)) {
				$pages = array();
				$types = $this->_get_subpage_post_types();
				if (count($types)) {
					foreach ($types as $type) {
						$pages_type = get_pages(array(
							'include' => $parent_ids,
							'post_type' => $type,
						));
						if (count($pages_type)) {
							$sorted = $this->_get_page_hierarchy($pages_type);
							$pages = array_merge($pages, $sorted);
						}
					}
				}
				set_transient('cfct-build-loop-parent_pages', $pages, 3600);
			}
			
			$html = ''; // set it just to be sure we're clean
			if (!empty($pages)) {
				$post_type = null;
				foreach ($pages as $page) {
					if ($page->post_parent == 0) {
						$ancestors = array();
					}
					else if (!count($ancestors)) {
						$ancestors[] = $page->post_parent;
					}
					else {
						// check for parent in existing array
						if (in_array($page->post_parent, $ancestors)) {
							// remove parents after this parent
							$i = 0;
							foreach ($ancestors as $ancestor) {
								$i++;
								if ($ancestor == $page->post_parent) {
									$ancestors = array_slice($ancestors, 0, $i);
									break;
								}
							}
						}
					}
					// add this page to ancestor list, list will always be at least 1 item
					$ancestors[] = $page->ID;
					if ($page->post_type != $post_type) {
						if (!is_null($post_type)) {
							$html .= '</optgroup>';
						}
						$html .= '<optgroup label="'.esc_attr($this->humanize($page->post_type)).'">';
						$post_type = $page->post_type;
					}
					$html .= '
						<option value="'.esc_attr($page->ID).'" '.selected($page->ID, $selected, false).'>'.str_repeat('&nbsp;&nbsp;', count($ancestors) - 1).esc_html($page->post_title).'</option>';
				}
				if (!is_null($post_type)) {
					$html .= '</optgroup>';
				}
				
			}
			return $html;
		}
		
		protected function _get_parent_pages_ids($exclude = 0) {
			global $wpdb;
			$types = $this->_get_subpage_post_types();
			if (!count($types)) {
				return array();
			}
			$types_esc = array();
			foreach ($types as $type) {
				$types_esc[] = $wpdb->escape($type);
			}
			return $wpdb->get_col($wpdb->prepare("
				SELECT DISTINCT post_parent
				FROM $wpdb->posts 
				WHERE post_parent != %d 
				AND post_type IN ('".implode("', '", $types_esc)."') 
				ORDER BY post_type, menu_order, post_title
			", $exclude));
		}
		
		protected function _get_subpage_post_types() {
			return apply_filters('cfct-build-sub-pages-post-types', get_post_types(array(
				'hierarchical' => true
			)));
		}
		
// taken from WP core, wp-includes/post.php
// modified to call $this->_page_traverse_name()
		protected function _get_page_hierarchy( &$pages, $page_id = 0 ) {
			if ( empty( $pages ) ) {
				$result = array();
				return $result;
			}
		
			$children = array();
			foreach ( (array) $pages as $p ) {
				$parent_id = intval( $p->post_parent );
				$children[ $parent_id ][] = $p;
			}
		
			$result = array();
			$this->_page_traverse_name( $page_id, $children, $result );
		
			return $result;
		}
		
// taken from WP core, wp-includes/post.php
// modified to return full post objects
		protected function _page_traverse_name( $page_id, &$children, &$result ) {
			if ( isset( $children[ $page_id ] ) ){
				foreach( (array)$children[ $page_id ] as $child ) {
					$result[ $child->ID ] = $child;
					$this->_page_traverse_name( $child->ID, $children, $result );
				}
			}
		}
		
// Content Move Helpers
		
		public function get_referenced_ids($data) {
			$post = get_post($data[$this->gfn('parent')]);
			$reference_data['parent'] = array(
				'type' => 'post_type',
				'type_name' => $post->post_type,
				'value' => $data[$this->gfn('parent')]
			);			
			
			return $reference_data;
		}
		
		public function merge_referenced_ids($data, $reference_data) {
			if (!empty($reference_data['parent']) && !empty($data[$this->gfn('parent')])) {
				$data[$this->gfn('parent')] = $reference_data['parent']['value'];
			}

			return $data;
		}

		/* Do nothing here */
		protected function migrate_data($data) {
			return $data;
		}

	}

	// Register our module...
	cfct_build_register_module('cfct_module_loop_subpages');

	/**
	 * Hook for clearing the page parent options transient
	 * Doesn't fire inside ajax since save_post is run on each module save
	 *
	 * @param int $post_id 
	 * @param object $post 
	 */
	function cfct_module_loop_subpages_post_save($post_id, $post) {
		global $cfct_build;
		if (!$cfct_build->in_ajax() && $post->post_type == 'page') {
			delete_transient('cfct-build-loop-parent_pages');
		}
	}
	add_action('save_post', 'cfct_module_loop_subpages_post_save', 10, 2);
}
?>
