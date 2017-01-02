<?php
class View {
	private $path;
	private $vars = array ();

	function View ($path) {
		$this->path = $path;
	}
	
	function __isset ($name) {
		return isset ($this->vars[$name]);
	}
	
	function __get ($name) {
		return $this->vars[$name];
	}
	
	function __set ($name, $value) {
		$this->vars[$name] = $value;
	}
	
	function render () {
		extract ($this->vars);
		require_once (APP_PATH.'/view/'.$this->path.'.php');
	}
	
	function __toString () {
		ob_start ();
		$this->render ();
		return ob_get_clean ();
	}
	
	static function base () {
		$base = $_SERVER['SCRIPT_NAME'];
		if (substr ($base, -9) == 'index.php') {
			$base = substr ($base, 0, -9);
		}
		if (substr ($base, -1) != '/') {
			$base .= '/';
		}
		return $base;
	}
	
	static function not_found () {
		header ('HTTP/1.1 404 Not Found');
		if (is_file (APP_PATH.'/view/404.php')) {
			if (is_file (APP_PATH.'/view/template/index.php')) {
				ob_start ();
				require_once (APP_PATH.'/view/404.php');
				$content = ob_get_clean ();
				require_once (APP_PATH.'/view/template/index.php');
			} else {
				require_once (APP_PATH.'/view/404.php');
			}
			die ();
		} else {
			die ('<!DOCTYPE html><html><body>404 - Not Found</body></html>');
		}
	}
	
	static function redirect ($to) {
		if (!preg_match ('#^https?://#', $to)) {
			$to = View::base().$to;
		}
		header ('HTTP/1.1 302 Moved Temporarily');
		header ('Location: '.$to);
		header ('Refresh: 0;url='.$to);
		die ('<!DOCTYPE html><html><body><a href="'.$to.'">302 - Moved Temporarily</a></body></html>');
	}
}
