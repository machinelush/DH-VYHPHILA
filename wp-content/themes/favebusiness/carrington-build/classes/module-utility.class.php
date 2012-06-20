<?php

/* heinous -- we need to include this for wp_terms_checklist.  yet this file
 * (__FILE__) may be included from all sorts of weird paths
 */
require_once ABSPATH.'/wp-admin/includes/template.php';

/**
 * Utility class that will be extended by cfct_build_module
 * Provides helper functions for common actions, inputs, fields, etc.
 */
class cfct_build_module_utility {
	public function __construct() {
		add_action('wp_ajax_cfct_module_ajax', array($this, '_handle_requests'));
	}
	
	/**
	 * Global ajax request handler for utility class provided methods
	 */
	public function _handle_requests() {
		if (!empty($_POST['cf_id_base']) && $_POST['cf_id_base'] == $this->id_base && !empty($_POST['cf_action'])) {
			switch($_POST['cf_action']) {
				case 'cf-global-image-search':
					$this->_global_image_search();
					break;
			}
		}
	}

// Module Admin Tabs

	/**
	 * Standard module tabs
	 * 
	 * $tabs = array( 'tab_id' => 'title' );
	 *	 - tab_id: id of target div to toggle
	 *	 - title: user friendly tab display name
	 *
	 * The selected visible tab will have a `class="active"` attribute
	 * Your tab markup should be:
	 *		<div class="cfct-module-tab-contents">
	 *			<div id="tab-one" class="active">...</tab>
	 *			<div id="tab-two">...</tab>
	 *		</div>
	 *
	 * @param string $tabs_id
	 * @param array $tabs
	 * @param $active_tab
	 * @return string HTML
	 */
	protected function cfct_module_tabs($tabs_id, $tabs = array(), $active_tab = null) {
		$html = '';
		if (count($tabs)) {
			$html = '
				<div id="'.$tabs_id.'" class="cfct-module-tabs">
					<ul>';
			$i = 0;
			foreach ($tabs as $tab_id => $title) {
				$active = ((!empty($active_tab) && $active_tab == $tab_id) || empty($active_tab) && ++$i == 1 ? ' class="active"' : '');
				$html .= '
						<li'.$active.'><a href="#'.$tab_id.'">'.$title.'</a></li>';
			}
			$html .= '
					</ul>
				</div>
			';
		}
		return $html;
	}	

	/**
	 *	Add this JS to your module's addModuleSaveCallback JavaScript
	 */
	protected function cfct_module_tabs_js() {
		return '
			$(".cfct-module-tabs a").click(function(){
				var _this = $(this);
				if (!_this.parent("li").hasClass("active")) {
					_this.parent("li").addClass("active").siblings().removeClass("active");
					// thank IE for this next line
					var hash = _this.attr("href").slice(_this.attr("href").indexOf("#"));
					$(hash).addClass("active").siblings().removeClass("active");
				}
				return false;
			});
		';
	}

// Custom Layout
	
	/**
	 * Allows for quick checking against an internal array of
	 * feature support (in $this->content_support array)
	 * to help determine if/when items should be displayed.
	 * 
	 * Aimed at helping to make base modules more extensible to 
	 * custom situations & module extending.
	 *
	 * @see callout/post-callout modules for implementation
	 * @param string $item 
	 * @return bool
	 */
	public function supports($item) {
		return in_array($item, $this->content_support);
	}
	
	/**
	 * generic layout controls for header-size, content-size and image-alignment
	 *
	 * $controls =  = array('header', 'content', 'image'); or any variation on that combo
	 *
	 * @param array $controls - array of control items to output
	 * @param array $data - module data
	 * @return string HTML
	 */
	function post_layout_controls($controls, $data) {
		if (empty($controls)) {
			return null;
		}
		
		$html = '
			<div class="'.$this->id_base.'-c6-34 cfct-post-layout-controls">';
		if (in_array('header', $controls)) {
			$html .= '
				<p class="cfct-style-title-chooser">'.$this->custom_css_dropdown('style-title', __('Header Size', 'carrington-build'), 'header', $data).'</p>';
		}
		if (in_array('image', $controls)) {
			$html .= '
				<p class="cfct-style-image-chooser">'.$this->custom_css_dropdown('image-alignment', __('Image Alignment', 'carrington-build'), 'image', $data).'</p>';
		}
		if (in_array('content', $controls)) {
			$html .= '
				<p class="cfct-style-content-chooser">'.$this->custom_css_dropdown('style-content', __('Content Size', 'carrington-build'), 'content', $data).'</p>';
		}
		$html .= '
			</div><!--/post-layout-controls-->
		';
		
		return $html;
	}
	
	function post_layout_controls_js() {
		return preg_replace('/^(\t){3}/m', '', '
			cfct_builder.addModuleLoadCallback("'.$this->id_base.'",function(form) {
				$(".cfct-post-layout-controls select.cfct-style-chooser").change(function(){
					var _this = $(this);
					var styles = '.json_encode(array_flip(array_map('strtolower', cfct_class_groups('image', true)))).';
					var tgt = $("#'.$this->get_field_id('post-preview-content').' .'.$this->id_base.'-post-content");

					for (i in styles) {
						tgt.removeClass(styles[i]);
					}
					tgt.addClass(_this.val());				
				});
				
			});
		');
	}
	
	function custom_css_dropdown($name, $title, $type, $data) {
		$options = cfct_class_groups($type);
		$current_setting = (!empty($data[$this->get_field_name($name)]) ? $data[$this->get_field_name($name)] : false);
		
		$ret = '<label for="'.$this->get_field_id($name).'">'.$title.'</label>
			<select class="cfct-style-chooser" name="'.$this->get_field_name($name).'" id="'.$this->get_field_id($name).'"><option value="">'.__('-none-', 'carrington-build').'</option>';
		foreach ($options as $value => $name) {
			//$ret .= '<option '.($current_setting == $value ? 'selected="selected" ' : '').'value="'.strtolower($value).'">'.$name.'</option>';
			$ret .= '<option '.selected($value, $current_setting, false).' value="'.strtolower($value).'">'.$name.'</option>';
		}
		$ret .='</select>';
		
		return $ret;
	}

// Image Selector
	protected $image_selectors = array();
	
	/**
	 * Image selector HTML markup
	 * $args are:
	 *	 - post_id: id of post to pull images from (for the post_image_selector)
	 *	 - field_name: name of form feild to be submitted on module save
	 *	 - selected_image: id of the currently selected image
	 *  - selected_size: id of the currently selected image size
	 *	- parent_class: additional classes to be applied to the parent wrapper
	 *	- image_class: additional classes to be applied to the image wrappers
	 *	- selected_image_class: additional classes to be applied to the
	 *  - direction: control the orientation of the image list, 'horizontal' or 'vertical'
	 *  - select_no_image: by default its a select and go, this allows selecting "no image"
	 *
	 * @param string $type - 'post' or 'global'
	 * @param array $args - array('post_id', 'field_name', 'selected_image', 'parent_class', 'image_class', 'selected_image_class')
	 * @return string html
	 */
	public function image_selector($type = 'post', $args = array()) {
		$args = array_merge(array(
			'post_id' => null,
			'field_name' => null,
			'selected_image' => null,
			'selected_size' => null,
			'allow_multiple' => null,
			'image_size' => 'thumbnail',		
			'parent_class' => null,
			'image_class' => null,			
			'selected_image_class' => null,
			'select_no_image' => false,
			'suppress_size_selector' => false
		), $args);
		if ($type == 'post') {
			return $this->_post_image_selector($args);
		}
		else {
			return $this->_global_image_selector($args);
		}
	}
	
	/**
	 * Method to output a "global" image selector for searching the entire media gallery
	 * Image selector is loaded via ajax based on a search term entered by user
	 *
	 * @see image_selector() for $args descriptions
	 * @param array $args
	 * @return string HTML
	 */
	public function _global_image_selector($args) {
		$value = null;
		
		if (!empty($args['selected_image'])) {
			$image = get_post($args['selected_image']);
			$selected_image = '<div class="cfct-image-select-items-list-item active">'.$this->_image_list_item($image, $args['image_size']).'</div>';
		}
		else {
			$selected_image = '<div class="cfct-image-select-items-list-item cfct-image-select-items-no-image"><div><div class="cfct-image-list-item-title">'.__('(no image)', 'carrington-build').'</div></div></div>';
		}
		$html = '
			<div id="'.$this->id_base.'-'.$args['field_name'].'-global-image-search" class="cfct-global-image-select cfct-image-select-b">
				<div class="'.$this->id_base.'-global-image-select-search">
					<input type="text" name="'.$this->id_base.'-'.$args['field_name'].'-image-search" placeholder="'.__('Search the Image Library', 'carrington-build').'&hellip;" value="" id="'.$this->id_base.'-'.$args['field_name'].'-image-search" class="cfct-global-image-search-field" data-image-size="'.$args['image_size'].'" />
					<input type="hidden" id="'.$this->get_field_id($args['field_name']).'" class="cfct-global-image-select-value" name="'.$this->get_field_name($args['field_name']).'" value="'.$args['selected_image'].'" />
					
					<div class="cfct-image-scroller-group">
						<div class="cfct-global-image-search-current-image cfct-image-select-current-image cfct-image-select-items-list-item">
							'.$selected_image.'
							<p>'.__('Current Selection', 'carrington-build').'</p>
						</div><div class="cfct-global-image-search-results cfct-image-select-items-list '.$this->_image_list_dir_class($args).' cfct-image-select-items-list-b" id="'.$this->id_base.'-'.$args['field_name'].'-live-search-results"></div>
					</div>
				</div>';

		if (empty($args['suppress_size_selector']) || $args['suppress_size_selector'] == false) {
			$html .= $this->_image_selector_size_select($args);
		}
		
		$html .= '
			</div>
			';
		return apply_filters($this->id_base.'-global-image-select-html', $html, $args);
	}
	
	/**
	 * JS for controlling global image selector
	 * Due to markup differences this method targets a different parent wrapper for adding the image id to the hidden field
	 *
	 * @param string $field_name 
	 * @return string HTML
	 */
	public function global_image_selector_js($field_name) {
		$js_base = $this->id_base;
		return '
			cfct_builder.addModuleLoadCallback("'.$this->id_base.'", function(form) {
				// assign search actions
				searches = [];
				$(".cfct-global-image-select").each(function(){
					var _this = $(this);
					var search = new cfctModuleLiveImageSearch("'.$this->id_base.'", _this);
					searches.push(search);
				});
				$.placeholders();
			});
		';
	}
	
	/**
	 * Method to output a simple "post" image selector
	 * Image selector shows images attached to a particular post
	 *
	 * @see image_selector() for $args descriptions
	 * @param array $args
	 * @return string HTML
	 */
	public function _post_image_selector($args) {
		if (empty($args['post_id'])) {
			return false;
		}

		$attachment_args = array(
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'numberposts' => -1,
			'post_status' => 'inherit',
			'post_parent' => $args['post_id'],
			'orderby' => 'title',
			'order' => 'ASC'
		); 

		$attachments = get_posts($attachment_args); 

		if (count($attachments)) {
			$id = $this->id_base.'-'.$args['field_name'].'-image-select-items-list';
			
			$class = 'cfct-post-image-select cfct-image-select-items-list '.$this->_image_list_dir_class($args);
			if (!empty($args['allow_multiple']) && $args['allow_multiple'] == true) {
				$class .= ' cfct-post-image-select-multiple';
				$note = __('Select one or more Images', 'carrington-build');
			}
			else {
				$class .= ' cfct-post-image-select-single';
				$note = __('Select an Image', 'carrington-build');
			}
			
			// push the featured image to the front of the list of images
			$featured_image_id = get_post_meta($args['post_id'], '_thumbnail_id', true);
			if (!empty($featured_image_id)) {
				foreach ($attachments as $key => $attachment) {
					if ($attachment->ID == $featured_image_id) {
						unset($attachments[$key]);
						$attachment->is_featured_image = true;
						array_unshift($attachments, $attachment);
						break;
					}
				}
			}
			
			$html = '
				<p class="cfct-image-select-note">'.$note.'</p>
				<div id="'.$id.'" class="'.$class.'">
					<div>
						'.$this->_image_list($attachments, $args).'
						<input type="hidden" name="'.$this->get_field_name($args['field_name']).'" id="'.$this->get_field_id($args['field_name']).'" value="'.$args['selected_image'].'" />
					</div>
				</div>';
		}
		else {
			$html = '<div class="cfct-image-select-no-images">'.__('No images found for the selected post.', 'carrington-build').'</div>';
		}
		
		if (empty($args['suppress_size_selector']) || $args['suppress_size_selector'] == false) {
			$html .= $this->_image_selector_size_select($args);
		}
		
		return apply_filters($this->id_base.'-image-select-html', $html, $args);
	}
	
	/**
	 * Show a select list of available image sizes as defined in WordPress
	 *
	 * @param array $args - see this::image_selector() for args definition
	 * @return string HTML 
	 */
	protected function _image_selector_size_select($args) {
		$html = '
			<div class="cfct-image-select-size">
				<label for="'.$this->id_base.'-'.$args['field_name'].'-image-select-size">'.__('Image Size', 'carrington-build').'</label>
				'.$this->_image_selector_size_select_node($args).'
			</div>
			<div class="clear"></div>';
		return $html;
	}
	
	/**
	 * Abstracted a select list node of available image sizes as defined in WordPress
	 *
	 * @param array $args - see this::image_selector() for args definition
	 * @return string HTML 
	 */
	protected function _image_selector_size_select_node($args) {
		global $_wp_additional_image_sizes;
		$_sizes = get_intermediate_image_sizes();
		$image_sizes = array();
		foreach ($_sizes as $s) {
			// Hide image sizes prefixed by underscore.
			if (strpos($s, '_') === 0) {
				continue;
			}
			
// taken from wp_generate_attachment_metadata(), wp-admin/includes/image.php
			if ( isset( $_wp_additional_image_sizes[$s]['width'] ) )
				$width = intval( $_wp_additional_image_sizes[$s]['width'] ); // For theme-added sizes
			else
				$width = get_option( "{$s}_size_w" ); // For default sizes set in options
			if ( isset( $_wp_additional_image_sizes[$s]['height'] ) )
				$height = intval( $_wp_additional_image_sizes[$s]['height'] ); // For theme-added sizes
			else
				$height = get_option( "{$s}_size_h" ); // For default sizes set in options
			$size = $width.'&times;'.$height;
			if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )
				$crop = intval( $_wp_additional_image_sizes[$s]['crop'] ); // For theme-added sizes
			else
				$crop = get_option( "{$s}_crop" ); // For default sizes set in options
			if ($crop) {
				$size = sprintf(__('%s crop', 'carrington-build'), $size);
			}
// handle proportional image resizing along only one axis
			if (empty($width)) {
				$size = sprintf(__('%s max height', 'carrington-build'), $height);
			}
			else if (empty($height)) {
				$size = sprintf(__('%s max width', 'carrington-build'), $width);
			}
			$image_sizes[$s] = sprintf(__('%s (%s)', 'carrington-build'), $this->humanize($s, true, array('-')), $size);
		}
		$image_sizes['full'] = __('Full Size', 'carrington-build');
		$image_sizes = apply_filters('cfct-build-image-size-select-sizes', $image_sizes, $this->id_base);
		
		$html = '
				<select name="'.$this->get_field_name($args['field_name']).'-size" id="'.$this->id_base.'-'.$args['field_name'].'-image-select-size">';
		foreach ($image_sizes as $size => $name) {
			$html .= '
					<option value="'.$size.'"'.selected($size, $args['selected_size'], false).'>'.esc_html($name).'</option>';
		}			
		$html .= '
				</select>';
		return $html;
	}
	
	/**
	 * Helper function to determine the direction of the image list based on the passed in args
	 *
	 * @param array $args - the same args array that was passed to the selection output method
	 * @return string classname
	 */
	protected function _image_list_dir_class($args) {
		if (!empty($args['direction']) && in_array($args['direction'], array('horizontal', 'vertical'))) {
			$class = 'cfct-image-select-items-list-'.$args['direction'];
		}
		else {
			$class = 'cfct-image-select-items-list-horizontal';
		}
		return $class;
	}
	
	/**
	 * Common method for building image lists
	 *
	 * @param array $attachments - list of objects describing wp_posts table attachment items
	 * @param array $args
	 * @return string HTML
	 */
	public function _image_list($attachments, $args) {
		// push the selected image to the front of the list of images
		if (isset($args['selected_image']) && $args['selected_image'] != false) {
			$selected_images = (!empty($args['selected_image']) ? explode(',', $args['selected_image']) : 0);
			$_attachments = $attachments;
			foreach($_attachments as $key => $attachment) {
				if (in_array($attachment->ID, $selected_images)) {
					unset($attachments[$key]);
					array_unshift($attachments, $attachment);
					if (empty($args['allow_multiple'])) {
						break;
					}
				}
			}
			unset($_attachments);
		}
		else {
			$selected_images = false;
		}
		
		$html  = '			
			<ul class="cfct-image-select-items">';
		
		if (($selected_images !== false && empty($args['allow_multiple'])) || (!empty($args['select_no_image']) && $args['select_no_image'] == true)) {
			$html .= '
				<li class="cfct-image-select-items-list-item cfct-image-select-items-no-image'.(empty($selected_images) ? ' active' : '').'" data-image-id="0">
					<div>
						<div class="cfct-image-list-item-title">'.__('(no image)', 'carrington-build').'</div>
					</div>
				</li>';
		}
		foreach ($attachments as $attachment) {
			$active = (is_array($selected_images) && in_array($attachment->ID, $selected_images) ? ' active' : null);
			$featured = (!empty($attachment->is_featured_image) ? ' featured' : null);
			$html .= '<li class="cfct-image-select-items-list-item'.$active.$featured.'" data-image-id="'.$attachment->ID.'">'.$this->_image_list_item($attachment, $args['image_size']).'</li>';
		}
		
		$html .= '
			</ul>';
		return $html;
	}
	
	protected function _image_list_item($image, $size = 'thumbnail') {
		if (!empty($image)) {
			$img_src = wp_get_attachment_image_src($image->ID, $size);
			$url = $img_src[0];
			$title = $image->post_title;
		}
		return '<div style="background: url('.$url.') 0 50% no-repeat;"><div class="cfct-image-list-item-title">'.(!empty($image->is_featured_image) && $image->is_featured_image ? '<span class="featured-icon"> ★</span>' : '').$title.'</div></div>';
	}

	protected function _global_image_search() {
		$term = trim(stripslashes($_POST['term']));
		
		$images = query_posts(array(
			's' => $term,
			'posts_per_page' => 20,
			'post_type' => 'attachment', 
			'post_mime_type' => 'image',
			'post_status' => 'inherit',
			'order' => 'ASC'
		));

		$args = array(
			'image_size' => (!empty($_POST['image_size']) ? esc_attr($_POST['image_size']) : 'thumbnail')
		);

		$html = '<div>';
		if (count($images)) {
			$html .= $this->_image_list($images, $args);
		}
		else {
			$html .= '
				<ul class="'.$this->id_base.'-image-select-items">
					<li class="cfct-image-select-items-list-item cfct-image-select-items-no-image" data-image-id="0">
						'.sprintf(__('No images found<br />for term "%s"', 'carrington-build'), esc_html($_POST['term'])).'
					</li>
				</ul>';
		}
		$html .= '</div>';
		
		$ret = array(
			'success' => (count($images) ? true : false),
			'term' => esc_html($_POST['term']),
			'html' => $html
		);
		
		header('content-type: text/javascript charset=utf8');
		echo cfcf_json_encode($ret);
		exit;
	}

// Authors
	
	/**
	 * Returns a label and dropdown for a list of authors
	 * (using the same method as the core WP author list)
	 *
	 * @param array $data data set in the module
	 * @param string $post_type @deprecated @since 1.2
	 * @return void
	 */
	protected function get_author_dropdown($data = array(), $post_type = null) {
		if (!is_null($post_type)) {
			_deprecated_argument(__FUNCTION__, '1.2' , 'Use of the <code>$post_type</code> parameter has been deprecated.  The author dropdown includes all authors, similar to the WordPress admin author dropdown.');
		}

		$dropdown_args = array(
			'who' => 'authors',
			'name' => $this->get_field_name('author'), 
			'selected' => isset($data[$this->get_field_name('author')]) ? $data[$this->get_field_name('author')] : null,
			'include_selected' => true,
			'echo' => 0,
			'class' => null,
			'show_option_all' => __('Any Author', 'carrington-build'),
		);
		$html = '
			'.wp_dropdown_users($dropdown_args).'
		';
		return $html;
	}

	protected function get_taxonomy_selector($args) {
		if(is_int($args['taxonomy'])) {
			throw new Exception("Need to query the taxonomy here >.<");
		}

		$post_id = $args['post_id'];
		$taxonomy = $args['taxonomy'];
		$selected_cats = $args['selected_cats'] ? $args['selected_cats'] : null;

		// Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
		$name = ( $taxonomy->name == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy->name . ']';

		ob_start();
		wp_terms_checklist($post_id, array( 
			'taxonomy' => $taxonomy->name, 
			'selected_cats' => $selected_cats 
		));
		
		$checklist = ob_get_clean();

		if(empty($checklist)) {
			$checklist = '<li>'.__('No terms in taxonomy','carrington-build').'</li>';
		}

		$result = '
			<div class="cfct-multiselect">
				<ul id="'.$taxonomy->name.'checklist" class="'.$taxonomy->name.' categorychecklist">
					'.$checklist.'
				</ul>
			</div>';

		return $result;
	} // function get_taxonomy_selector()

// Text

	/**
	 * Text Input boilerplate
	 */
	protected function input_text($field_name, $label_text, $value, $args = array()) {
		$defaults = array(
			'prefix' => null,
			'class' => 'widefat',
			'wrapper_class' => '',
		);
		$args = array_merge($defaults, $args);
		extract($args);
	
		$id = (is_null($prefix)) ? $this->get_field_id($field_name) : $prefix.$field_name;
		$name = (is_null($prefix)) ? $this->get_field_name($field_name) : $prefix.$field_name;
		$wrapper_class = (!empty($wrapper_class)) ? ' class="'.$wrapper_class.'"' : '';
		$class = (!empty($class)) ? ' class="'.$class.'"' : '';
		$html = '
			<label for="'.$id.'">'.esc_html($label_text).'</label>
			<input'.$class.' id="'.$id.'" name="'.$name.'" type="text" value="'.esc_attr($value).'" />
		';
		return $html;
	}

// Utility
	public function humanize($str, $titlecase = true, $replace_extras = array()) {
		return cfct_build_humanize($str, $titlecase, $replace_extras);
	}

	/**
	 * Does basic WP text formatting (texturize, autop, etc.)
	 *
	 * @param string
	 * @return string
	 */
	public function wp_formatting($str) {
		$str = wptexturize($str);
		$str = convert_smilies($str);
		$str = convert_chars($str);
		$str = wpautop($str);
		return $str;
	}
	
	/**
	 * Helper function that if you know the post_id &
	 * the module_id you can get the module data directly
	 * from postmeta
	 *
	 * @param int $post_id 
	 * @param string $module_id 
	 * @return mixed array/bool - false on failure to find data
	 */
	public function get_module_build_data($post_id, $module_id) {
		$meta = get_post_meta($post_id, CFCT_BUILD_POSTMETA, true);
		if (!empty($meta['data']['modules'][$module_id])) {
			return $meta['data']['modules'][$module_id];
		}
		else {
			return false;
		}
	}
}

/**
 * for the time being we'll filter in the JS needed to do live searches
 */
function cfct_module_utility_add_live_image_search_js($js) {
	$file = CFCT_BUILD_DIR.'js/cfct-live-search.js';
	if (is_file($file)) {
		$js .= PHP_EOL.file_get_contents($file).PHP_EOL;
	}
	return $js;
}
add_filter('cfct-get-extras-modules-js-admin', 'cfct_module_utility_add_live_image_search_js');

?>
