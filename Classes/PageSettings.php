<?php
	class PageRule
	{
	 public $redirect;
	 public $handler;
	 public $login;
	 
	 public function __construct($r = false, $h = false, $l = false)
	 {
		$this->redirect = $r;
		$this->handler = $h;
		$this->login = $l;
	 }
	 
	}
?>