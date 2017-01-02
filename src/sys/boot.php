<?php
if (!defined ('SYS_PATH')) {
	define ('SYS_PATH', __DIR__);
}
if (!defined ('APP_PATH')) {
	define ('APP_PATH', realpath (__DIR__.'/../app'));
}
class Boot {
	static $errors = null;
	static function errors () {
		if (!Boot::$errors) {
			Boot::$errors = array ();
			foreach (get_defined_constants() as $k => $v) {
				if (substr ($k,0, 2) != 'E_') continue;
				Boot::$errors[$v] = array (
					'str' => ucfirst (strtolower (str_replace ('_', ' ', substr ($k, 2)))),
					'const' => $k
				);
			}
		}
		return Boot::$errors;
	}
	static function exception_handler ($e) {
		try {
			if (method_exists ($e, 'getSeverity')) {
				$severity = $e->getSeverity ();
				$errors = Boot::errors ();
				if (isset ($errors[$severity]['const'])) {
					$const = $errors[$severity]['const'];
					if (strpos ($const, 'ERROR')) {
						$level = Log::LEVEL_ERROR;
					} else if (strpos ($const, 'WARN') || strpos ($const, 'DEPRECATED')) {
						$level = Log::LEVEL_WARNING;
					} else {
						$level = Log::LEVEL_INFO;
					}
				}
			}
			if (!isset ($level)) {
				$level = Log::LEVEL_ERROR;
			}
			if (get_class ($e) == 'ErrorException') {
				$text = sprintf ("%s in %s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
			} else {
				$text = sprintf ("PHP Fatal Error: Uncaught exception '%s' with message '%s' in %s:%d", get_class ($e), $e->getMessage(), $e->getFile(), $e->getLine());
			}
			Log::write ($text, $level);
			foreach (explode ("\n", $e->getTraceAsString()) as $text) {
				Log::write ("  ".$text, $level);
			}
		} catch (Exception $e) {
			die ();
		}
	}
}
require_once ('log.php');	// requerido por spl_autoload_register()
spl_autoload_register (function ($class_name) {
	$class_path = str_replace ("_", "/", strtolower ($class_name)) . '.php';
	foreach (array (APP_PATH, SYS_PATH) as $path) {
		if (file_exists ($path.'/'.$class_path)) {
			Log::write ('Autoload class '.$class_name.' from '.$path.'/'.$class_path);
			require_once ($path.'/'.$class_path);
			return;
		}
	}
	Log::write ('Class not found: '.$class_name.' (search '.$class_path.')', Log::LEVEL_WARNING);
});
set_exception_handler (array ('Boot', 'exception_handler'));
set_error_handler (function ($errno, $errstr, $errfile, $errline, $errcontext) {
	Boot::exception_handler (new ErrorException ($errstr, 0, $errno, $errfile, $errline));
	$errors = Boot::errors ();
	if (isset ($errors[$errno]['const']) && in_array ($errors[$errno]['const'], array (
		'E_ERROR', 'E_CORE_ERROR', 'E_COMPILE_ERROR', 'E_USER_ERROR', 'E_RECOVERABLE_ERROR'
	))) {
		die ();
	}
});
foreach (glob (APP_PATH.'/config/*.php') as $config) {
	Log::Write ('Include config '.$config);
	require_once ($config);
}
if (isset ($_SERVER['PATH_INFO'])) {
	$path = explode ('/', trim ($_SERVER['PATH_INFO'], '/'));
	if (!$path[0]) {
		$path[0] = 'default';
	}
}
if (isset ($path[0])) {
	if (!isset ($path[1])) {
		$path[1] = 'index';
	}
	$class_name = 'Controller_'.str_replace (' ', '_', ucwords (str_replace ('_', ' ', $path[0])));
	if (class_exists ($class_name)) {
		$class_instance = new $class_name ();
	} else {
		/*if (class_exists ('Controller_Error')) {
			$class_name = 'Controller_Error';
		} else*/ {
			View::not_found ();
		}
	}
	$class_instance->input = new Input ();
	if (file_exists (APP_PATH.'/view/'.$path[0].'/'.$path[1].'.php')) {
		Log::write ('Autoload view '.$path[0].'/'.$path[1]);
		$class_instance->view = new View ($path[0].'/'.$path[1]);
	}
	$class_action = 'action_'.$path[1];
	if (method_exists ($class_instance, $class_action)) {
		Log::write ('Call '.$class_name.'->'.$class_action);
		$json = call_user_func_array (array ($class_instance, $class_action), array_slice ($path, 2));
	} else {
		Log::write ('Class '.$class_name.' has no action '.$path[1], Log::LEVEL_ERROR);
		View::not_found ();
	}
	if (!is_null ($json)) {
		if (isset ($_GET['json_pretty_print'])) {
			die ('<pre>'.json_encode ($json, JSON_PRETTY_PRINT));
		} else {
			die (json_encode ($json));
		}
	} else if ($class_instance->template) {
		Log::write ('Render template');
		$class_instance->template->content = $class_instance->view;
		$class_instance->template->render ();
	} else if ($class_instance->view) {
		Log::write ('Render view');
		$class_instance->view->render ();
	}
}
