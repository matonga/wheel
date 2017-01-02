<?php
class Session {
	function __construct () {
		if (!isset ($_SESSION)) {
			session_start ();
		}
	}
	
	function __isset ($name) {
		return isset ($_SESSION[$name]);
	}
	
	function __set ($name, $value) {
		$_SESSION[$name] = $value;
	}
	
	function __get ($name) {
		return isset ($_SESSION[$name]) ? $_SESSION[$name] : null;
	}
	
	function __unset ($name) {
		unset ($_SESSION[$name]);
	}
	
	function delete () {
		unset ($_SESSION);
		session_destroy ();
	}
	
	function commit () {
		session_write_close ();
	}
}
