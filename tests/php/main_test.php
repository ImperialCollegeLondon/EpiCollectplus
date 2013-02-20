<?php

ob_start();
$PHP_UNIT = true;
$_SERVER['HTTP_HOST'] = 'test.domain.tld';

$_SESSION = array();

class MainTest extends PHPUnit_Framework_TestCase
{
	protected function setup()
	{
		parent::setup();		
		$_SERVER['REQUEST_URI'] = '/';
		include "./main.php";
	}
	
	protected function tearDown()
	{
		header_remove();
		parent::tearDown();
	}
	
	public function test_DIR()
	{
		global $DIR;
		$this->assertEquals($DIR, getcwd());
	}
	
	public function test_get_val()
	{
		$arr = array();
		$this->isNull(getValIfExists($arr, 'foo'));
		
		$arr["foo"] = "bar";
		$this->assertEquals(getValIfExists($arr, 'foo'), 'bar');
	}
	
	
}
?>