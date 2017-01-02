<?php
class Log_File {
	private $fp;
	
	function Log_File ($filename) {
		$this->fp = fopen ($filename, 'a');
		if (!$this->fp) {
			throw new Exception ('Log_File('.json_encode($filename).'): could not open file for appending');
		}
	}
	
	function write ($timestamp, $message, $level) {
		if (!$this->fp) {
			return;
		}
		fwrite ($this->fp, date ('[Y-m-d H:i:s] '));
		switch ($level) {
		case Log::LEVEL_ERROR:
			fwrite ($this->fp, 'ERROR: ');
			break;
		case Log::LEVEL_WARNING:
			fwrite ($this->fp, 'WARNING: ');
			break;
		case Log::LEVEL_INFO:
			fwrite ($this->fp, 'INFO: ');
			break;
		case Log::LEVEL_DEBUG:
			fwrite ($this->fp, 'DEBUG: ');
			break;
		default:
			fwrite ($this->fp, '(level #'.$level.'): ');
		}
		fwrite ($this->fp, $message."\n");
	}
}
