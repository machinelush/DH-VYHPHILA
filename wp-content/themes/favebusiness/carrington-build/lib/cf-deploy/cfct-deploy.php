<?php
/**
 * Bridge for compatability with CF-Deploy moving content between 
 * two servers that don't have matching post-ID fields.
 */

$build_deploy_callbacks = new cfct_build_deploy_callbacks;
$build_deploy_callbacks->register_deploy_callbacks();
	
class cfct_build_deploy_callbacks {
	protected $name = 'Carrington Build';
	protected $description = 'ID translation of Carrington Build post data';
	
	protected $build;
	protected $module_translation_callbacks;
	
	protected $verbose = false;
	
	public function __construct() {
		global $cfct_build;
		$this->build = $cfct_build;
		$this->id = sanitize_title_with_dashes($this->name);
		add_action('init', array($this, 'register_module_translation_callbacks'), 12);
	}

// Register Callbacks
	
	public function register_deploy_callbacks() {
		cfd_register_deploy_callback($this->name, $this->description, array(
			'send_callback' => array($this, 'build_send_callback'), 
			'receive_callback' => array($this, 'build_receive_callback'),
			'preflight_send_callback' => array($this, 'build_preflight_send_callback'),
			'preflight_check_callback' => array($this, 'build_preflight_check_callback'),
			'preflight_display_callback' => array($this, 'build_merge_batch_messages_callback')
		));
	}
	
	/**
	 * Register callback methods for modules.
	 * By default modules that use the default internal reference data format
	 * do not need a custom translation function and everything is managed here.
	 *
	 * @internal cfct-module-deploy-translation-callbacks - allows for the modification of module callback method pointers
	 * @return void
	 */
	public function register_module_translation_callbacks() {
		$callbacks = array();
		foreach ($this->build->template->modules as $module_key => $module) {
			$callbacks[$module_key] = array(
				'export' => array($this, 'translate_reference_ids_export'), 
				'import' => array($this, 'translate_reference_ids_import'),
				'preflight' => array($this, 'preflight_reference_ids')
			);
		}
		$this->module_translation_callbacks = apply_filters('cfct-module-deploy-translation-callbacks', $callbacks, $this->build);
	}

// Preflight

	public function build_merge_batch_messages_callback($batch_preflight_data) {
		if (!empty($batch_preflight_data['extras'][$this->id])) {
			$build_preflight_data = $batch_preflight_data['extras'][$this->id];
			unset($batch_preflight_data['extras'][$this->id]);
			
			foreach($build_preflight_data as $post_guid => $messages) {
				foreach ($batch_preflight_data['post_types'] as $post_type => &$posts) {
					if (isset($posts[$post_guid])) {
						$batch_preflight_data['post_types'][$post_type][$post_guid] = array_merge($posts[$post_guid], $messages);
					}
				}
			}
		}
		return $batch_preflight_data;
	}

	/**
	 * Same data as sending for full deploy, but more verbose
	 *
	 * @param array $data 
	 * @return array
	 */
	public function build_preflight_send_callback($data) {
		$this->verbose = true;
		return $this->build_send_callback($data);
	}

	public function build_preflight_check_callback($data, $batch_data) {
		$ret = array();
		
		if (!empty($data)) {
			foreach ($data as $post_guid => $modules) {
				if (!empty($modules)) {
					foreach ($modules as $module_id => $module) {
						if (!is_array($module)) {
							continue;
						}
						$method = $this->get_module_translation_method($module['module_type'], 'preflight');

						if (!empty($method)) {
							$_ret = call_user_func($method, $post_guid, $module, $batch_data);
						}

						if (!empty($_ret)) {
							$ret = array_merge_recursive($ret, $_ret);
						}
					}
				}
			}
		}

		return $ret;
	}
	
	public function preflight_reference_ids($post_guid, $data, $batch_data) {
		$messages = array();

		if (!empty($data)) {
			foreach ($data as $module_id => $field) {
				if (!empty($field) && is_array($field)) {
					if (is_array(current($field))) {
						$field['module_type'] = $data['module_type'];
						$messages = array_merge($messages, $this->preflight_reference_ids($post_guid, $field, $batch_data));
					}
					else {
						switch ($field['type']) {
							case 'post_type':
								$method = 'cfd_get_post_id_by_guid';
								break;
							case 'taxonomy':
								$method = 'cfd_get_term_id_by_guid';
								break;
							case 'author':
							case 'user':
								$method = 'cfd_get_user_id_by_guid';
								break;
						}
					}
					
					if (!empty($field['value'])) {
						$type_name = $field['type_name'];
						if (is_array($field['guid'])) {
							foreach ($field['guid'] as $id_key => $guid) {
								$_id = call_user_func($method, $guid, $type_name);
								if (empty($_id) && !$this->is_in_batch($field['type'], $field['type_name'], $guid, $batch_data)) {
									$messages[$post_guid]['error'][$guid.'-'.$id_key] = $this->empty_item_error($field['type'], $field['type_name'], $field['title'][$id_key]);
								}
								unset($_id);
							}
						}
						else {
							$_id = call_user_func($method, $field['guid'], $type_name);
							if (empty($_id) && !$this->is_in_batch($field['type'], $field['type_name'], $field['guid'], $batch_data)) {
								$messages[$post_guid]['error'][$field['guid'].'-'.$field['value']] = $this->empty_item_error($field['type'], $field['type_name'], $field['title'][$field['value']]);
							}
							unset($_id);
						}
					}
				}
			}
		}

		return $messages;
	}
	
	protected function empty_item_error($type, $type_name, $title) {
		$error = '';
		
		if (!in_array($type, array('post_type', 'taxonomy'))) {
			$error = cfct_build_humanize($type).': ';			
		}
		
		if ($type != $type_name) {
			$error .= cfct_build_humanize($type_name).': ';
		}
		
		$error .= '"'.$title.'" is referenced by a build module but does not exist on remote system and is not included in the batch.';		
		return __($error, 'carrington-build');
	}
	
	protected function is_in_batch($type, $type_name, $guid, $batch) {
		$ret = false;
		
		switch ($type) {
			case 'post_type':
				$ret = isset($batch['post_types'][$type_name][$guid]);
				break;
			case 'taxonomy':
				$ret = isset($batch['taxonomies'][$type_name][$guid]);
				break;
			case 'user':
				$ret = isset($batch['users'][$guid]);
				break;
		}
		
		return $ret;
	}

// Transfer Callback Methods
	
	public function build_send_callback($batch_data) {
		$build_batch_data = array();
		
		if (!empty($batch_data['post_types'])) {
			foreach ($batch_data['post_types'] as $post_type => $posts) {
				if (!empty($posts)) {
					foreach ($posts as $guid => $post) {
						if ($this->cfd_has_build_data($post)) {
							$build_batch_data[$guid] = $this->export_translation_tables($post);
						}
					}
				}
			}
		}

		return $build_batch_data;
	}
	
	public function build_receive_callback($translation_data) {
		// default return status
		$success = true;
		$message = __('No translation data to process', 'carrington-build');
		
		if (!empty($translation_data)) {
			cfd_tmp_dbg('build-module-data.txt', '');
			cfd_tmp_dbg('build-translation-data.txt', $translation_data, 'print');
			
			foreach ($translation_data as $post_guid => $modules) {
				$post_id = cfd_get_post_id_by_guid($post_guid);
				$build_data = get_post_meta($post_id, CFCT_BUILD_POSTMETA, true);				
				if (empty($build_data) || empty($build_data['data']['modules'])) {
					continue;
				} 
				
				$build_translation_data = $this->import_translation_tables($modules, $build_data);
				
				cfd_tmp_dbg('build-module-data.txt', '# Before: '.PHP_EOL.print_r($build_data['data']['modules'], true).PHP_EOL.PHP_EOL, 'print', true);				

				if (!empty($build_translation_data)) {
					foreach ($build_translation_data as $module_id => $module_translation_data) {
						$module_data = $build_data['data']['modules'][$module_id];
						$module = $this->build->template->get_module($module_data['module_type']);
						$build_data['data']['modules'][$module_id] = $module->merge_referenced_ids($build_data['data']['modules'][$module_id], $module_translation_data);
					}
				}

				cfd_tmp_dbg('build-module-data.txt', '# After: '.PHP_EOL.print_r($build_data['data']['modules'], true).PHP_EOL.PHP_EOL, 'print', true);								
			}
			
			$res = update_post_meta($post_id, CFCT_BUILD_POSTMETA, $build_data);
			if (!$res) {
				$check = get_post_meta($post_id, CFCT_BUILD_POSTMETA, false);
				if (is_array($check) && !empty($check)) {
					if (maybe_serialize($check[0]) === maybe_serialize($build_data)) {
						$res = true;
					}
				}
			}
			if ($res) {
				$success = true;
				$message = __('Build data successfully updated', 'carrington-build');
			}
			else {
				$success = false;
				$message = __('Error updating build postmeta', 'carrington-build');
			}
		}
				
		return array(
			'success' => $success,
			'message' => $message
		);
	}
	
// Helpers 

	/**
	 * Retrieve the module translation method for a particular module
	 *
	 * @param string $module_type 
	 * @param string $translation_type 
	 * @return mixed string/array function/method reference
	 */
	protected function get_module_translation_method($module_type, $translation_type) {
		$method = null;
		if (!empty($this->module_translation_callbacks[$module_type]) && !empty($this->module_translation_callbacks[$module_type][$translation_type])) {
			$_method = $this->module_translation_callbacks[$module_type][$translation_type];
			if (is_array($_method) && method_exists($_method[0], $_method[1])) {
				$method = $_method;
			}
			elseif (function_exists($method)) {
				$method = $_method;
			}
		}
		return $method;
	}
	
	/**
	 * Check if incoming cf-deploy data has build data
	 *
	 * @param array $post 
	 * @return bool
	 */
	protected function cfd_has_build_data($post) {
		return !empty($post['meta'][CFCT_BUILD_POSTMETA]);
	}
	
	/**
	 * Retrieve the build data from incoming cf-deploy data
	 *
	 * @param array $post 
	 * @return array
	 */
	protected function cfd_get_build_data($post) {
		$build_data = false;
		if ($this->cfd_has_build_data($post)) {
			$build_data = $post['meta'][CFCT_BUILD_POSTMETA];
		}
		return $build_data;
	}
	
	protected function get_post_title($post_id) {
		$post = get_post($post_id);
		return $post->post_title;
	}
	
	protected function get_term_name($term_id, $taxonomy) {
		$term = get_term($term_id, $taxonomy);
		return $term->name;
	}
	
	protected function get_user_name($user_id) {
		$user = get_userdata($user_id);
		return $user->user_nicename;
	}

// Data Translation
	
	/**
	 * EXPORT: export translation tables
	 *
	 * @param array $post 
	 * @return array
	 */
	protected function export_translation_tables($post) {
		$translation_table = array();
		
		if ($this->verbose) {
			$translation_table['post_type'] = $post['post']['post_type'];
			$translation_table['post_title'] = $post['post']['post_title'];
		}
		
		$build_data = $this->cfd_get_build_data($post);
		if (!empty($build_data['data']['modules'])) {
			foreach ($build_data['data']['modules'] as $module_id => $module_data) {
				$module = $this->build->template->get_module($module_data['module_type']);
				$_trans = $module->get_referenced_ids($module_data);
				if (!empty($_trans)) {
					$translation_method = $this->get_module_translation_method($module_data['module_type'], 'export');
					if (!empty($translation_method)) {
						$translation_table[$module_id] = call_user_func($translation_method, $_trans, $this->verbose);
						$translation_table[$module_id]['module_type'] = $module_data['module_type'];
					}					
				}
			}
		}
		
		return $translation_table;
	}
	
	/**
	 * EXPORT: translate id values in to corresponding guid values
	 * & attach to data
	 *
	 * @param array $id_data 
	 * @return array
	 */
	public function translate_reference_ids_export($id_data, $verbose = false) {
		foreach ($id_data as $key => &$data) {
			if (!empty($data) && is_array($data) && is_array(current($data))) {
				// treat as nested data
				$data = $this->translate_reference_ids_export($data, $verbose);
			}
			elseif (!empty($data) && is_array($data)) {
				switch ($data['type']) {
					case 'post_type':
						$method = 'cfd_get_post_guid';
						break;
					case 'taxonomy':
						$method = 'cfd_get_taxonomy_term_guid';
						break;
					case 'user':
						$method = 'cfd_get_user_guid';
						break;
				}

				if (!empty($method) && !empty($data['value'])) {
					if (is_array($data['value'])) {
						foreach ($data['value'] as $_id) {
							$data['guid'][$_id] = $method($_id, $data['type_name']);
						}
					}
					else {
						$data['guid'] = $method($data['value'], $data['type_name']);
						
						if ($verbose) {
							$data = array_merge($data, $this->get_verbose_data($data));
						}
					}
				}
			}
		}
		return $id_data;
	}
	
	protected function get_verbose_data($data) {
		$_verbose = array();
		
		switch ($data['type']) {
			case 'post_type':
				$method = 'get_post_title';
				break;
			case 'taxonomy':
				$method = 'get_term_name';
				break;
			case 'user':
				$method = 'get_user_name';
				break;
		}
		
		$ids = $data['value'];
		if (!is_array($ids)) {
			$ids = array($ids);
		}
		
		foreach ($ids as $id) {
			$_verbose['title'][$id] = call_user_func(array($this, $method), $id, $data['type_name']);
		}
		
		return $_verbose;
	}
	
	/**
	 * IMPORT: process translation tables
	 * Remove guid in process since its data we don't care about after translation
	 * 
	 * @param array $post_modules 
	 * @return array
	 */
	public function import_translation_tables($post_modules, $build_data) {
		foreach ($post_modules as $module_id => &$module_data) {
			if (!empty($module_data)) {
				$build_module_data = $build_data['data']['modules'][$module_id];
				$translation_method = $this->get_module_translation_method($build_module_data['module_type'], 'import');
				$module_data = call_user_func($translation_method, $module_data);
			}
		}

		return $post_modules;
	}
	
	/**
	 * IMPORT: translate guids to corresponding ID values
	 *
	 * @param array $id_data 
	 * @return array
	 */
	public function translate_reference_ids_import($id_data) {
		foreach ($id_data as $key => &$data) {
			if (!empty($data) && is_array($data) && is_array(current($data))) {
				// treat as nested data
				$data = $this->translate_reference_ids_import($data);
			}
			elseif (!empty($data) && is_array($data)) {
				switch ($data['type']) {
					case 'post_type':
						$method = 'cfd_get_post_id_by_guid';
						break;
					case 'taxonomy':
						$method = 'cfd_get_term_id_by_guid';
						break;
					case 'author':
					case 'user':
						$method = 'cfd_get_user_id_by_guid';
						break;
				}
				
				if (!empty($method)) {
					if (is_array($data['guid'])) {
						foreach ($data['guid'] as $_id => $_guid) {
							$_replace = array_search($_id, $data['value']);
							$_translated = $method($_guid, $data['type_name']);
							if (!empty($_translated)) {
								$data['value'][$_replace] = $_translated;
							}
						}
					}
					else {
						$_translated = $method($data['guid'], $data['type_name']);
						if (!empty($_translated)) {
							$data['value'] = $_translated;
						}
					}
					unset($data['guid']);
				}
				else {
					error_log('Unknown data type encountered: '.$data['type'].'::'.$data['type_name']);
				}
			}
		}
		return $id_data;
	}
}
?>
