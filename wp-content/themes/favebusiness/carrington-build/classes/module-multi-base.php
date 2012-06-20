<?php

if (!class_exists('cfct_module_multi_base')) {
	abstract class cfct_module_multi_base extends cfct_build_module {		
		protected $context_excludes = array(
			'multi-module'
		);
		protected $block_guid = 'cfct-block-1234-multi'; // bad things might happen if you change this. I take no responsibility if you change this value.

// Ajax		
		public function multi_modules_init() {
			$this->row = new cfct_multi_module_row;
			$this->register_ajax_handler('sideload_module_chooser', array($this, 'ajax_sideload_module_chooser'));
			add_action('cfct-ajax-delete-module', array($this, 'delete_module'), 10, 3);
		}
		
		public function ajax_sideload_module_chooser($args) {
			$module_list = $this->_multi_module_module_list();
			$success = !empty($module_list);
			
			return $this->ajax_response($success, $module_list, 'multi-module-module-list');
		}
		
		// @TODO - make an admin callable method
		public function _multi_module_module_list() {
			global $cfct_build;
			$view_state = $cfct_build->get_user_module_chooser_view_state();
			
			$html = '
				<div class="cfct-popup-header">
	                <h2 class="cfct-popup-title">'.__('Choose a Type of Content', 'carrington-build').'</h2>
	                <p class="cfct-popup-subtitle">'.__('Select a module or widget to add to your Build', 'carrington-build').'</p>
	             </div>
				<div class="cfct-popup-content" style="'.$style.'">
					'.$cfct_build->add_module_options_list('multi-module').'
				</div>
				<div class="cfct-popup-actions">
	                <span id="cfct-module-list-toggles">
	                    <a id="cfct-module-list-toggle-detail" class="cfct-module-list-toggle '.($view_state !== 'icon' ? ' active' : '').'" href="#cfct-module-list,#cfct-widgets-list" title="Toggle Detail view">Toggle Detail View</a>
	                    <a id="cfct-module-list-toggle-compact" class="cfct-module-list-toggle'.($view_state == 'icon' ? ' active' : '').'" href="#cfct-module-list,#cfct-widgets-list" title="Toggle Compact View">Toggle Compact View</a>
	                </span>
	                '.$cfct_build->popup_activity_div(__('Loading Module Options','carrington-build').'&hellip;').' 

	                <a href="#" id="cfct-add-module-cancel" class="cancel">'.__('Cancel', 'carrington-build').'</a>
	            </div>';
			return $html;
		}
	
// Display

		protected function build_modules($data) {	
			global $cfct_build;
			
			$html = '';
			
			$modules = $this->get_module_modules($data[$this->get_field_name('block_id')]);
			if (!empty($modules) && is_array($modules)) {
				$count = count($modules);
				$i = 1;
				foreach ($modules as $module) {
					if (!$module['render']) {
						continue;
					}
					$html .= $this->before_module($data, $module, $i, $count).
						$cfct_build->template->get_module($module['module_type'])->html($module).
					$this->after_module($data, $module, $i++, $count);
				}
			} 		
	
			return $html;
		}

		/**
		 * Add extra output to the beginning of each module
		 *
		 * @param array $data module data
		 * @param object $module carrington-build module object
		 * @param int $index 1 based index or module order
		 * @return void
		 */
		public function before_module($data, $module, $index, $count) {
			return '';
		}
		
		/**
		 * Add extra output to the end of each module
		 *
		 * @param array $data module data
		 * @param object $module carrington-build module object
		 * @param int $index 1 based index or module order
		 * @return void
		 */
		public function after_module($data, $module, $index, $count) {
			return '';
		}
	
// Admin
		public function multi_modules_admin($data) {
			global $cfct_build;
			$block_guid = (!empty($data[$this->get_field_name('block_id')]) ? $data[$this->get_field_name('block_id')] : cfct_build_guid($this->id_base, 'block-multi'));
			
			$html = '
				<div class="'.$this->id_base.'-modules">
					<input type="hidden" name="'.$this->gfn('block_id').'" value="'.($block_guid).'" />';
				
			$row_def = array(
				'type' => 'cfct_multi_module_row',
				'guid' => 'cfct-row-1234-multi',
				'blocks' => array(
					'cfct-block-multi-foo' => array(
						'guid' => $block_guid,
						'class' => 'cfct-block->abc cfct-multi-module-block'
					)
				)
			);
			
			$row_data = array(
				'blocks' => array(),
				'modules' => array()
			);
			
			$modules = $this->get_module_modules($data[$this->get_field_name('block_id')]);
			if (!empty($modules)) {	
				$row_data = array(
					'blocks' => array(
						$block_guid => array_keys($modules)
					),
					'modules' => $modules
				);
			}
			$html .= $this->row->admin($row_def, $row_data, $cfct_build->template);
		
			$html .= '
				</div>';
			
			return $html;
		}
		
		protected function get_module_modules($block_id) {
			global $cfct_build;
			
			$post_id = null;
			if (is_admin()) {
				$args = $cfct_build->ajax_decode_json($_POST['args'], true);
				$post_id = $args['post_id'];
			}

			$post_data = $cfct_build->get_postmeta($post_id);

			if (!empty($post_data['data']['blocks'][$block_id])) {
				$modules = array_flip($post_data['data']['blocks'][$block_id]);
				foreach ($modules as $key => &$module) {
					$module = $post_data['data']['modules'][$key];
				}
			}

			return (!empty($modules) ? $modules : array());
		}
		
		public function _admin($mode, $data) {
			if ($mode == 'edit') {
				if (empty($args['sideload'])) {
					add_filter('cfct-module-'.$this->id_base.'-admin-popup-contents', array($this, '_multi_module_sideload'), 10, 1);
					add_filter('cfct-module-form-class', array($this, '_module_form_class'), 10, 2);
				}			
			}
			
			$html = parent::_admin($mode, $data);
			return $html;
		}
		
		public function _module_form_class($classname, $id_base) {
			if ($id_base == $this->id_base) {
				$classname .= ' multi-module-form';
			}
			return $classname;
		}
		
		public function _multi_module_sideload($popup_contents) {
			global $cfct_build;
						
			$popup_contents .= '
				<div class="cfct-module-sideload"></div>';
				
			return $popup_contents;
		}

// Update
		
		/**
		 * When deleting a multi-module module we need to prune its children as well
		 *
		 * @param object $build_admin - cfct_build_admin object
		 * @param array $deleted_module 
		 * @param int $post_id 
		 * @return bool
		 */
		public function delete_module($build_admin, $deleted_module, $post_id) {
			if ($deleted_module['module_id_base'] == $this->id_base) {
				$post_data = $build_admin->get_postmeta($post_id);
				$block_id = $deleted_module[$this->get_field_name('block_id')];
				
				// make sure our block exists
				if (!empty($post_data['data']['blocks'][$block_id])) {

					// iterate over block to kill modules contained within
					foreach ($post_data['data']['blocks'][$block_id] as $module_id) {
						if (!empty($post_data['data']['modules'][$module_id])) {
							unset($post_data['data']['modules'][$module_id]);
						}
					}
					
					// kill the block
					unset($post_data['data']['blocks'][$block_id]);
					
					// save modified data
					if (!$build_admin->set_postmeta($post_id, $post_data)) {
						throw new cfct_row_exception(__('Could not save postmeta for post on multi-module data prune','carrington-build').' (post_id: '.$args['post_id'].')');
					}
					
					return true;
				}
			}
			return false;
		}		
	}
}

/**
 * Create a dummy row for internal use only so that we can custom render the multi-module module
 * Do not register, this is manually invoked as needed, but needs to load in the standard row load order
 *
 */
if (!class_exists('cfct_multi_module_row')) {
	class cfct_multi_module_row extends cfct_build_row {
		protected $private = true;
	
		public function __construct() {
			$config = array(
				'name' => __('Multi Module Row', 'carrington-build'),
				'description' => __('Private row for helping with multi-module display', 'carrington-build'),
				'icon' => '1col/icon.png'
			);
									
			$this->add_classes(array('cfct-row-abc', 'cfct-multi-module-row'));
			$this->push_block(new cfct_block_c4_1234);
			
			parent::__construct($config);
		}
	
		function row_html($admin = false) {
			if ($admin) {
				$html = '
					<div id="{id}" class="{class}">
						<div class="cfct-row-inner">
							'.$this->row_table().'
						</div>
					</div>';
			}
			else {
				$html = '
					{blocks}';
			}
			return $html;
		}
	}
}
?>