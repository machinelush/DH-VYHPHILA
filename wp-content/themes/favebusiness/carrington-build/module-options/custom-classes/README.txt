# Custom Module Classes

This module customization allows for the addition of additional CSS classes to the Module DOM element. This module option will add a single text input which accepts space separated class names.

The module option uses the filter `cfct-build-module-class` to add its classes to the module's existing classes.


## Predefined Classes

This module option also has the ability to include pre-defined class names as options in a fly out menu. By using the filter `cfct-module-predefined-class-options`. Classes added via this method will be available by a dropdown menu in the module extra config area.
	
Example:

	function my_predefined_classes($classes) {
		if (empty($classes)) {
			$classes = array();
		}
		$classes = array_merge($classes, array(
			'cfct-custom-one' => 'Custom class one',
			'cfct-custom-two' => 'Custom class two'
		));
		return $classes;
	}
	add_filter('cfct-module-predefined-class-options','my_predefined_classes');
