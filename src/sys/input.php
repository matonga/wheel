<?php
class Input {
	private $json = null;

	private function retrieve ($array, $name, $default) {
		if ($name) {
			return isset ($array[$name]) ? $array[$name] : $default;
		} else {
			return $array;
		}
	}
	
	function get ($name = false, $default = null) {
		return $this->retrieve ($_GET, $name, $default);
	}
	
	function post ($name = false, $default = null) {
		return $this->retrieve ($_POST, $name, $default);
	}
	
	function request ($name = false, $default = null) {
		return $this->retrieve ($_REQUEST, $name, $default);
	}
	
	function json ($name = false, $default = null) {
		if (is_null ($this->json)) {
			$this->json = json_decode (file_get_contents ('php://input'), true);
		}
		return $this->retrieve ($this->json, $name, $default);
	}
	
	function file ($name) {
		if (isset ($_FILES[$name]['tmp_name'])) {
			return array (
				'name' => $_FILES[$name]['name'],
				'path' => $_FILES[$name]['tmp_name']
			);
		} else {
			return false;
		}
	}
}
