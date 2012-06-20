# Carrington Build CF-Deploy integration

Carrington Build modules sometimes store ID pointers to other content on the system (ie: `post_id`, `term_id`). When this data is transferred using CF Deploy those ID fields need to be updated to match the IDs on the remote system.

Carrington Build supplies a generic way for modules to return a list of reference IDs that get translated during the CF Deploy transfer process. There is also a way to override the default functions and run custom functions.

## Default Data Format for Translation Data

If your module stores ID references then you need to define 2 new methods in your module.

1. `get_referenced_ids`
	- @param `$data` - the module's build data
	- @return array - array of reference data information from the module data
2. `merge_referenced_ids`
	- @param `$data` - the module's build data
	- @param `$reference_data`
	- @return array - modified `$data` array with new reference data imported

There are 3 variants to the default data format.

- Single value:  
	$reference_data['my_field'] = array(
		'type' => '[post_type|taxonomy|user]',
		'type_name' => '[(post-type-name|any)|taxonomy-name]',
		'value' => int
	);
- Multiple Values
	$reference_data['my_field'] = array(
		'type' => '[post_type|taxonomy|user]',
		'type_name' => '[(post-type-name|any)|taxonomy-name]',
		'value' => array(
			int,
			int,
			// etc...
		)
	);
- Multiple Keyed Values
	$reference_data['my_field'] = array(
		'key' => array(
			'type' => '[post_type|taxonomy|user]',
			'type_name' => '[(post-type-name|any)|taxonomy-name]',
			'value' => int
		),
		'key2' => array(
			'type' => '[post_type|taxonomy|user]',
			'type_name' => '[(post-type-name|any)|taxonomy-name]',
			'value' => int
		),
		// etc...
	);

Before being transmitted via CF Deploy the `module_type` is added to the data and the appropriate GUID values are attached to each item. Upon receiving this data on the destination server those GUID values are translated in to the local ID values and passed back to the module to be merged back in to the module data.

## Example Code

Several built in Carrington Build modules make for good examples. Reference the modules below for examples of the different default data send formats:

- Image
- Loop/Loop Subpages
- Callout/Post Callout
- Gallery
- Carousel
- Hero

## Custom Data Translation Callbacks

If it is simply impossible to follow the default data format then you can add custom callback methods to handle the export and import of the module data. An example of registering custom callback methods is as follows:

	function register_my_custom_module_data_callbacks($callbacks) {
		$callbacks['my_module_classname'] = array(
			'export' => 'my_module_translation_table_export_callback',
			'import' => 'my_module_translation_table_import_callback'
		);
		return $callbacks;
	}
	add_filter('cfct-module-deploy-translation-callbacks', 'register_my_custom_module_data_callbacks');