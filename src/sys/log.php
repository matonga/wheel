<?php
class Log {
	const LEVEL_ERROR = 1;
	const LEVEL_WARNING = 2;
	const LEVEL_INFO = 3;
	const LEVEL_DEBUG = 4;
	
	private static $listeners = array ();
	
	static function register ($listener, $level = Log::LEVEL_WARNING) {
		Log::$listeners[] = array (
			'listener' => $listener,
			'level' => $level
		);
	}
	
	static function write ($message, $level = Log::LEVEL_DEBUG) {
		$message = str_replace (array (SYS_PATH, APP_PATH), array ('{sys}', '{app}'), $message);
		$timestamp = time ();
		foreach (Log::$listeners as $listener) {
			if ($level <= $listener['level']) {
				$listener['listener']->write ($timestamp, $message, $level);
			}
		}
	}
}
