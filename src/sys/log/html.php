<?php
class Log_HTML {
	private $messages = '';

	function Log_HTML () {
		ob_start (array ($this, 'output'));
	}

	function output ($chunk) {
		$messages = $this->messages;
		$this->messages = '';
		if ($messages) {
			return $messages.$chunk;
		} else {
			return $chunk;
		}
	}

	function write ($timestamp, $message, $level) {
		$this->messages .= sprintf ("<font face=\"sans-serif\" size=\"2\">%s", date ('[Y-m-d H:i:s] '));
		switch ($level) {
		case Log::LEVEL_ERROR:
			$this->messages .= sprintf ("<font color=\"red\">ERROR: ");
			break;
		case Log::LEVEL_WARNING:
			$this->messages .= sprintf ("<font color=\"brown\">WARNING: ");
			break;
		case Log::LEVEL_INFO:
			$this->messages .= sprintf ("<font color=\"green\">INFO:</font><font> ");
			break;
		case Log::LEVEL_DEBUG:
			$this->messages .= sprintf ("DEBUG: <font color=\"gray\">");
			break;
		default:
			$this->messages .= sprintf ("(level #%s): <font>", $level);
		}
		$this->messages .= sprintf ("%s</font></font><br/>", str_replace (' ', '&nbsp;', htmlentities ($message, ENT_COMPAT)));
	}
}
