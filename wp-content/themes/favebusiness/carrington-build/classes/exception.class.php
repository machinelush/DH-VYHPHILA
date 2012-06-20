<?php

class cfct_exception extends Exception {
	function __construct($message, $errorCode = null) {
		parent::__construct($message, $errorCode);
		$this->dbg(__CLASS__, $message);
	}
	
	function getHTML() {
		return '
			<div class="cfct-error">
				<p>'.__($this->getMessage()).'</p>
			</div>
		';
	}
	
	/**
	 * log message to the debugger
	 *
	 * @param string $method - method logging the message
	 * @param string $message - log message
	 * @return bool
	 */
	function dbg($method, $message) {
		if (!CFCT_BUILD_DEBUG) { return false; }
		if (class_exists('cfct_build_debug')) {
			return cfct_build_debug::log($method, $message);
		}
	}
}

class cfct_row_exception extends cfct_exception {}
class cfct_template_exception extends cfct_exception {}
class cfct_module_exception extends cfct_exception {}

?>