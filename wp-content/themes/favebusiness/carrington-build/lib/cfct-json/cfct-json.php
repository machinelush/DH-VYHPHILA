<?php
/**
 * JSON ENCODE and DECODE for PHP < 5.2.0
 * Checks if json_encode is not available and defines json_encode & json_decode
 *
 * @version 1.0
 * @uses the Pear Class Services_JSON - http://pear.php.net/package/Services_JSON
 */
 
if (!function_exists('json_encode') && !class_exists('Services_JSON')) {
	require_once('JSON.php');
}	

/**
 * cfct_json_encode
 *
 * @param array/object $json 
 * @return string json
 */
if (!function_exists('cfcf_json_encode')) {
	function cfcf_json_encode($data) {
		if (function_exists('json_encode')) {
			return json_encode($data);
		}
		else {
			global $cfct_json_object;
			if (!($cfct_json_object instanceof Services_JSON)) {
				$cfct_json_object = new Services_JSON();
			}
			return $cfct_json_object->encode($data);
		}
	}
}

/**
 * cfct_json_decode
 *
 * @param string $json 
 * @param bool $array - toggle true to return array, false to return object  
 * @return array/object
 */
if (!function_exists('cfcf_json_decode')) {
	function cfcf_json_decode($json, $array) {
		if (function_exists('json_decode')) {
			$ret = json_decode($json, $array);
		}
		else {
			global $cfct_json_object;
			if (!($cfct_json_object instanceof Services_JSON)) {
				$cfct_json_object = new Services_JSON();
			}
			$cfct_json_object->use = $array ? SERVICES_JSON_LOOSE_TYPE : 0;
			$ret = $cfct_json_object->decode($json);
		}
		
		// la de da
		if ($array == true && is_object($ret)) {
			$ret = cf_json_object_to_array($ret);
		}
		
		return $ret;
	}
}

/**
 * Due to certain "conditions" under WordPress 2.9 we may see an object come back 
 * from json_decode when we've requested arrays. This function decodes nested objects
 * in to nested arrays. See /wp-includes/compat.php, line 141 to find out why.
 *
 * Simply typecasting an object to an array is not enough - it must be done recursively.
 *
 * @param string $data 
 * @return array
 */
if (!function_exists('cf_json_object_to_array')) {
	function cf_json_object_to_array($data) {
		if (!is_object($data) && !is_array($data)) {
			return $data;
		}
		if (is_object($data)) {
			$data = (array) $data;
		}
		return array_map('cf_json_object_to_array', $data);
	}
}

/**
 * Decode JSON data acquired via Ajax
 *
 * @param string $json 
 * @param bool $array 
 * @return array
 */
if (!function_exists('cf_ajax_decode_json')) {
	function cf_ajax_decode_json($json, $array = false) {
		if (!get_magic_quotes_gpc()) {
			$json = stripslashes($json);
		}
		return cfcf_json_decode($json, $array);
	}
}
?>