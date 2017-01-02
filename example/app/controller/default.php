<?php

class Controller_Default extends Controller {
	function action_index () {
		View::redirect ('hello/world');
	}
}
