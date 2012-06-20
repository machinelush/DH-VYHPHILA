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

class CSS3PIE {
	protected static $_instance;
	var $selectors = array();
	var $pie_url;

	function CSS3PIE() {
		$this->pie_url = trailingslashit(get_bloginfo('template_url')) . 'assets/js/PIE/PIE.php';
		
		// Add PIE behavior to head
		add_action('wp_head', array(&$this, 'render'), 8);
	}
	
	/**
	 * Half-baked singleton (simpleton?)
	 * If you only need one, use get_instance.
	 */
	public static function get_instance() {
		if (!(self::$_instance instanceof self)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Attach CSS PIE behavior to CSS selectors
	 * Prevents exact duplicates.
	 * @param str $selector Comma separated list of CSS selectors to attach behavior to.
	 */
	function enqueue($selector = '') {
		$my_selectors = $this->css_to_array($selector);
		
		// Combine all selectors
		foreach($my_selectors as $selector) {
			if (!in_array($selector, $this->selectors)) {
				$this->selectors[] = $selector;
			}
		}
	}
	
	/**
	 * Remove one or more selectors from selector array
	 * @param str $selector Comma separated list of CSS selectors
	 */
	function remove($selector = '') {
		$my_selectors = $this->css_to_array($selector);
		// Subtract my selectors from global selectors
		$this->selectors = array_diff($this->selectors, $my_selectors);
	}
	
	function css_to_array($selector) {
		$selector = str_replace(array(', ', ",\n", ", \n"), ',', $selector);
		return explode(',', $selector);
	}
	
	function render() {
		$my_selectors = implode(', ', $this->selectors);
		if (!empty($my_selectors)) {
			// Note: Adding zoom:1 to stack prevents during-load jumps.
			echo '
			<!--[if lte IE 8]>
				<style type="text/css" media="screen">
					'.$my_selectors.' { zoom: 1; behavior: url('.$this->pie_url.'); }
				</style>
			<![endif]-->
			';
		}
	}
}

function css3pie_enqueue($selectors = '') {
	$pie = CSS3PIE::get_instance();
	$pie->enqueue($selectors);
}
function css3pie_remove($selectors = '') {
	$pie = CSS3PIE::get_instance();
	$pie->remove($selectors);
}
?>
