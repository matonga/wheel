<?php
class Log_Console {
	function write ($timestamp, $message, $level) {
		fwrite (STDERR, date ('[Y-m-d H:i:s] '));
		switch ($level) {
		case Log::LEVEL_ERROR:
			fwrite (STDERR, "\033[31mERROR: ");
			break;
		case Log::LEVEL_WARNING:
			fwrite (STDERR, "\033[33mWARNING: ");
			break;
		case Log::LEVEL_INFO:
			fwrite (STDERR, "\033[32mINFO:\033[0m ");
			break;
		case Log::LEVEL_DEBUG:
			fwrite (STDERR, "DEBUG: \033[1;30m");
			break;
		default:
			fwrite (STDERR, '(level #'.$level.'): ');
		}
		fwrite (STDERR, $message."\033[0m\n");
	}
}
