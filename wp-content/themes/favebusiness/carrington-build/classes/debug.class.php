<?php

/**
 * simple debug logger
 */
class cfct_build_debug {
	public static $messages;
	
	public static function log($method, $value) {
		if (!is_array(self::$messages)) { self::$messages = array(); }
		if (CFCT_BUILD_DEBUG_ERROR_LOG) { error_log('dbg: '.$method.' -- '.$value); }
		return self::$messages[] = $method.' -- '.$value;
	}
	
	public static function display() {
		if (!count(self::$messages) || (defined('AFPS_DEBUG_DISPLAY_ERRORS') && !CFCT_BUILD_DEBUG_DISPLAY_ERRORS)) { return false; }
		
		echo '<div style="border: 1px solid orange; background: yellow; padding: 10px; font-size: 11px; text-align: left; position: relative;">
			  <p><b>Debug:</b></p>
			  <pre>'.implode(' '.PHP_EOL,self::$messages).'</pre>
			  </div>';
		
		return true;
	}
}

?>