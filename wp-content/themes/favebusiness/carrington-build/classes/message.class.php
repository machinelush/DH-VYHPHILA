<?php

/**
 * Standard return message class to help ensure
 * consistent handling of json return messages 
 * across the system.
 *
 * @package cfct_build
 */
class cfct_message {
	private $_html;
	private $_message;
	private $_success;
	private $_extra;
	
	public function __construct(array $args = array('success' => false, 'html' => null, 'message' => null, 'extra' => null)) {
		$this->add($args);
	}
	
// Setters
	public function add(array $args = array()) {
		$args = array_merge(array('success' => false, 'html' => null, 'message' => null, 'extra' => null), $args);
		$this->_success = (bool) $args['success'];
		$this->_html = strval($args['html']);
		$this->_message = strval($args['message']);
		
		// make sure we import this as an array
		if (!empty($args['extra']) && count($args['extra'])) {
			foreach ($args['extra'] as $key => $value) {
				$this->_extra[$key] = $value;
			}
		}
	}

// Getters
	public function get_results() {
		$ret = array(
			'success' => (bool) trim($this->_success),
			'html' => trim($this->_html),
			'message' => trim($this->_message)
		);
		// merge extras in to output data
		if (!empty($this->_extra)) {
			$ret = array_merge($ret, $this->_extra);
		}
		return $ret;
	}
	
	public function get_json() {
		return cfcf_json_encode($this->get_results());
	}

	public function __toString() {
		return $this->get_json();
	}
	
	public function success() {
		return $this->_success;
	}
	
	public function html() {
		return $this->_html;
	}
	
	public function message() {
		return $this->_message;
	}

// Delivery
	/**
	 * Deliver the JSON and get out of the page load.
	 *
	 * @return void
	 */
	public function send() {
		header('Content-type: application/json');
		echo $this->get_json();
		exit;
	}
}

?>
