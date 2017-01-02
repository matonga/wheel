<?php

class Controller_Hello extends Controller {
	function __construct () {
		parent::__construct ();
		$this->template = new View ("template/default");
		$this->template->title = "Wheel Sample App";
	}
	
	function action_world () {
		$this->view->message = "Hello world!";
	}
}
