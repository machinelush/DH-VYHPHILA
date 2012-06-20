# Carrington Build for Developers

---


## Enabling Build on Other Post Types

By default the Carrington Build admin is only enabled on pages. To enable the Carrington Build admin on posts or on custom post types.

	function my_build_admin_filter($types) {
		$types = array_merge($types, array('post', 'my-post-type'));
		return $types;
	}
	add_filter('cfct-build-enabled-post-types', 'my_build_admin_filter');

---


## Disabling Carrington Build

Carrington Build can be disabled by defining a constant `CFCT_BUILD_DISABLE` and setting that constant to true. The constant needs to be defined BEFORE WordPress `init`.

---


## WordPress VIP

WordPress VIP has issues (we'll call them that to make ourselves feel better) with enqueuing admin scripts to a CDN for caching and whatnot. This doesn't play well with our dynamic gathering of JS & CSS resources. The enqueing of scripts to the admin can be overridden by defining a function that manually puts the admin script tags in to the head of the document. For example:

	if (!function_exists('cfct_build_admin_scripts')) {
		function cfct_build_admin_scripts() {
			echo '
				<script type="text/javascript" src="'.admin_url('?cfct_action=cfct_admin_js&amp;ver='.CFCT_BUILD_VERSION).'"></script>
				<link rel="stylesheet" href="'.admin_url('?cfct_action=cfct_admin_css&amp;ver='.CFCT_BUILD_VERSION).'" type="text/css" media="screen" /> 
			';
		}
	}
	
The function name `cfct_build_admin_scripts` is the important part, you can do whatever you want inside of it. The function must be defined before `init`.

---
