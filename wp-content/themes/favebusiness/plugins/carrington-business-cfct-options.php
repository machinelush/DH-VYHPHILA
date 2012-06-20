<?php

/**
 * @package favebusiness
 *
 * This file is part of the FaveBusiness Theme for WordPress
 * http://crowdfavorite.com/wordpress/themes/favebusiness/
 *
 * Copyright (c) 2008-2012 Crowd Favorite, Ltd. All rights reserved.
 * http://crowdfavorite.com
 *
 * **********************************************************************
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * **********************************************************************
 */

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

// Custom theme based options additions for Carrington Build to accompany the FaveBusiness Theme

/**
 * Style -> image mapping for style chooser
 * @return array
 */
function cfct_module_admin_theme_style_images($type) {
	$options['general'] = array(
		'style-a' => 'module-callout-previews/box-style-a.png',
		'style-b' => 'module-callout-previews/box-style-b.png',
		'style-c' => 'module-callout-previews/box-style-c.png'
	);
	// post-callout
	$options['post_callout_module'] = array_merge($options['general'], array(
		'style-a' => 'module-callout-previews/box-style-a-loop.png',
		'style-b' => 'module-callout-previews/box-style-b-loop.png'
	));
	// callout, same as post-callout
	$options['cfct_module_callout'] = $options['post_callout_module'];
	// headings
	$options['cfct_module_heading'] = array(
		'cfctbiz-hd-lg-a' => 'module-heading-previews/heading-lg-bold.png',
		'cfctbiz-hd-lg-b' => 'module-heading-previews/heading-lg-underline.png',
		'cfctbiz-hd-md-a' => 'module-heading-previews/heading-md-bold.png',
		'cfctbiz-hd-md-b' => 'module-heading-previews/heading-md-underline.png',
		'cfctbiz-hd-sm-a' => 'module-heading-previews/heading-sm-bold.png',
		'cfctbiz-hd-sm-b' => 'module-heading-previews/heading-sm-underline.png'
	);
	
	return (isset($options[$type]) ? $options[$type] : $options['general']);
}

/**
 * Common function for adding style chooser
 * 
 * @param string $form_html - HTML of module admin form
 * @param array $data - form save data
 * @return string HTML
 */
function cfct_module_admin_theme_chooser($form_html, $data) {
	$type = $data['module_type'];
	$img_url_base = trailingslashit(get_template_directory_uri());		
	
	$style_image_config = cfct_module_admin_theme_style_images($type);
	
	$selected = null;		
	if (!empty($data['cfct-custom-theme-style']) && !empty($style_image_config[$data['cfct-custom-theme-style']])) {
		$selected = $data['cfct-custom-theme-style'];
	}
	
	$onclick = 'onclick="cfct_set_theme_choice(this); return false;"';

	$form_html .= '
		<fieldset class="cfct-custom-theme-style">
			<div id="cfct-custom-theme-style-chooser" class="cfct-custom-theme-style-chooser cfct-image-select-b">
				<input type="hidden" id="cfct-custom-theme-style" class="cfct-custom-theme-style-input" name="cfct-custom-theme-style" value="'.(!empty($data['cfct-custom-theme-style']) ? esc_attr($data['cfct-custom-theme-style']) : '').'" />
				
				<label onclick="cfct_toggle_theme_chooser(this); return false;">Style</label>
				<div class="cfct-image-select-current-image cfct-image-select-items-list-item cfct-theme-style-chooser-current-image" onclick="cfct_toggle_theme_chooser(this); return false;">';
				
	if (!empty($selected) && !empty($style_image_config[$selected])) {
			$form_html .= '
					<div class="cfct-image-select-items-list-item">
						<div style="background: #d2cfcf url('.$img_url_base.'wp-admin/'.$style_image_config[$selected].') 0 0 no-repeat;"></div>
					</div>';
	}
	else {
		$form_html .= '
					<div class="cfct-image-select-items-list-item">
						<div style="background: #d2cfcf url('.$img_url_base.'carrington-build/img/none-icon.png) 50% 50% no-repeat;"></div>
					</div>';	
	}
	
	$form_html .= '
				</div>
						
				<div class="clear"></div>
				
				<div id="cfct-theme-select-images-wrapper">
					<h4>'.__('Select a style...', 'favebusiness').'</h4>
					<div class="cfct-image-select-items-list cfct-image-select-items-list-horizontal cfct-theme-select-items-list">
						<ul class="cfct-image-select-items">
							<li class="cfct-image-select-items-list-item '.(empty($selected) ? ' active' : '').'" data-image-id="0" '.$onclick.'>
								<div style="background: #d2cfcf url('.$img_url_base.'carrington-build/img/none-icon.png) no-repeat 50% 50%;"></div>
							</li>';
							
foreach ($style_image_config as $style => $image) {
	$form_html .= '
							<li class="cfct-image-select-items-list-item'.($selected == $style ? ' active' : '').'" data-image-id="'.$style.'" '.$onclick.'>
								<div style="background: url('.$img_url_base.'wp-admin/'.$image.') 0 0 no-repeat;"></div>
							</li>';
}

$form_html .='			
						</ul>
					</div>
				</div>
			</div>
		</fieldset>
	';

	return $form_html;
}
add_filter('cfct-module-cfct-callout-admin-form', 'cfct_module_admin_theme_chooser', 10, 2);
add_filter('cfct-module-cf-post-callout-module-admin-form', 'cfct_module_admin_theme_chooser', 10, 2);
add_filter('cfct-module-cfct-heading-admin-form', 'cfct_module_admin_theme_chooser', 10, 2);
add_filter('cfct-module-cfct-plain-text-admin-form', 'cfct_module_admin_theme_chooser', 10, 2);
add_filter('cfct-module-cfct-rich-text-admin-form', 'cfct_module_admin_theme_chooser', 10, 2);
add_filter('cfct-module-cfct-module-loop-admin-form', 'cfct_module_admin_theme_chooser', 10, 2);
add_filter('cfct-module-cfct-module-loop-subpages-admin-form', 'cfct_module_admin_theme_chooser', 10, 2);

/**
 * Register a filter for each widget module loaded
 * 
 * @param string $widget_id - standard wordpress widget_id
 * @param string $module_id - id of module in build
 * @return void
 */
function cfct_widget_modules_register_theme_admin_form($widget_id, $module_id) {
	add_filter('cfct-module-'.$module_id.'-admin-form', 'cfct_module_admin_theme_chooser', 10, 2);
}
add_action('cfct-widget-module-registered', 'cfct_widget_modules_register_theme_admin_form', 10, 2);

/**
 * CSS for Theme Chooser in individual Module Admin Screens
 *
 * @param string $css
 * @return string
 */
function cfct_module_admin_theme_chooser_css($css) {
	$css .= preg_replace('/^(\t){2}/m', '', '
		/* Theme Chooser Additions */
		#cfct-custom-theme-style-chooser .cfct-image-select-current-image {
			display: block;
			height: 100px;
			width: auto;
		}
		#cfct-custom-theme-style-chooser .cfct-image-select-current-image p {
			text-align: left;
			font-size: 1em;
		}
		#cfct-custom-theme-style-chooser .cfct-image-select-current-image,
		#cfct-custom-theme-style-chooser .cfct-image-select-current-image>div {
			cursor: pointer;
		}
		#cfct-custom-theme-style-chooser .cfct-image-select-current-image .cfct-image-select-items-list-item,
		#cfct-custom-theme-style-chooser .cfct-image-select-current-image .cfct-image-select-items-list-item>div {
			height: 55px;
		}

		#cfct-custom-theme-style-chooser .cfct-theme-style-chooser-current-image {
			height: 75px;
		}
		#cfct-custom-theme-style-chooser label {
			float: left;
			display: block;
			width: 120px;
			margin-top: 25px;
		}
		#cfct-custom-theme-style-chooser #cfct-theme-select-images-wrapper {
			display: none;
		}
		.cfct-popup-content.cfct-popup-content-fullscreen fieldset.cfct-custom-theme-style {
			margin: 12px;
		}
		#cfct-theme-select-images-wrapper h4 {
			color: #666;
			font-weight: normal;
			margin: 0 0 5px;
		}
	');
	return $css;
}
add_filter('cfct-get-extras-modules-css-admin', 'cfct_module_admin_theme_chooser_css', 10, 1);

/**
 * JS for Theme Chooser in individual Module Admin Screens
 *
 * @param string $js
 * @return string
 */
function cfct_module_admin_theme_chooser_js($js) {
	$js .= preg_replace('/^(\t){2}/m', '', '
	
		cfct_set_theme_choice = function(clicked) {
			_this = $(clicked);
			_this.addClass("active").siblings().removeClass("active");
			_wrapper = _this.parents(".cfct-custom-theme-style-chooser");
			_val = _this.attr("data-image-id");
			_background_pos = (_val == "0" ? "50% 50%" : "0 0");
			
			$("input:hidden", _wrapper).val(_val);
			
			$(".cfct-image-select-current-image .cfct-image-select-items-list-item > div", _wrapper)
				.css({"background-image": _this.children(":first").css("backgroundImage"), "background-position": _background_pos});
				
			$("#cfct-theme-select-images-wrapper").slideToggle("fast");
			return false;
		};
		
		cfct_toggle_theme_chooser = function(clicked) {
			$("#cfct-theme-select-images-wrapper").slideToggle("fast");
			return false;
		}
		
	');
	return $js;
}
add_filter('cfct-get-extras-modules-js-admin', 'cfct_module_admin_theme_chooser_js', 10, 1);

/**
 * Apply the custom theme style 
 * 
 * @param string $class_string - base module wrapper classes
 * @param array $data - module save data
 * @return string
 */
function cfct_module_wrapper_classes($class, $data) {
	$type = $data['module_type'];
	
	$classes = explode(' ', $class);
	
	// see if we have a custom theme style to apply
	if (!empty($data['cfct-custom-theme-style'])) {
		$classes[] = esc_attr($data['cfct-custom-theme-style']);
	}

	$class = trim(implode(' ', $classes));
	return $class;
}
add_filter('cfct-build-module-class', 'cfct_module_wrapper_classes', 10, 2);
?>