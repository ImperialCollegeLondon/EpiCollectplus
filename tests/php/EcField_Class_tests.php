<?php
include '../../db/dbConnection.php';
include '../../Classes/configManager.php';
include('../../Classes/EcField.php');

$DIR = '../../';

class EcProjectTest	 extends PHPUnit_Framework_TestCase
{
	/**
	 * @group internals
	 * @author Chris I Powell
	 * @requires json_encode
	 * 
	 */
	protected function setup()
	{
		global $db, $cfg;
		$cfg = new ConfigManager('../../ec/epicollect.ini');
		$db = new dbConnection();
	}
	
	protected function tearDown()
	{
		global $db;
		unset($db);
	}
	
	public function test_object_detection()
	{
		$f = new EcField();
		
		$f->type = "gps";
		$this->assertTrue($f->valueIsObject());
		
		$f->type = "location";
		$this->assertTrue($f->valueIsObject());
		
		$f->type = "text";
		$this->assertFalse($f->valueIsObject());
		
		$f->type = "select";
		$this->assertFalse($f->valueIsObject());
		
		$f->type = "select1";
		$this->assertFalse($f->valueIsObject());
		
		$f->type = "radio";
		$this->assertFalse($f->valueIsObject());
		
		$f->type = "photo";
		$this->assertFalse($f->valueIsObject());
		
	}
	
	public function test_file_detection()
	{
		$f = new EcField();
	
		$f->type = "video";
		$this->assertTrue($f->valueIsFile());
	
		$f->type = "audio";
		$this->assertTrue($f->valueIsFile());
	
		$f->type = "photo";
		$this->assertTrue($f->valueIsFile());
	
		$f->type = "select";
		$this->assertFalse($f->valueIsFile());
	
		$f->type = "select1";
		$this->assertFalse($f->valueIsFile());
	
		$f->type = "radio";
		$this->assertFalse($f->valueIsFile());
	
		$f->type = "location";
		$this->assertFalse($f->valueIsFile());
	
	}
	
}
?>