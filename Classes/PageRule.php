<?php
	class PageRule
	{
	 public $redirect;
	 public $handler;
	 public $login;
	 public $secure;
	 
	 public function __construct($r = false, $h = false, $l = false, $s = false)
	 {
		$this->redirect = $r;
		$this->handler = $h;
		$this->login = $l;
		$this->secure = $s;
	 }
	 
	}
?>