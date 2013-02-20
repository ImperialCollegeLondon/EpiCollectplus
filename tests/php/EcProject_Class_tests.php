<?php
include '../../db/dbConnection.php';
include '../../Classes/configManager.php';
include('../../Classes/EcProject.php');

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
	
	public function test_list()
	{
		$projects = EcProject::getPublicProjects();
		$this->assertInternalType('array', $projects);
		for( $i = 0; $i < count($projects); $i++ )
		{
			$this->assertInternalType('array', $projects[$i]);
			$this->assertTrue(array_key_exists('name', $projects[$i]));
			$this->assertInternalType('string', $projects[$i]['name']);
			$this->assertTrue(array_key_exists('ttl', $projects[$i]));
			
			$this->assertTrue(array_key_exists('ttl24', $projects[$i]));
			
			$this->assertTrue(array_key_exists('listed', $projects[$i]));
			$this->assertEquals( $projects[$i]['listed'], 'public');
			
			//$this->assertInternalType('int', $projects[0]['ttl']);
			//$this->assertInternalType('int', $projects[0]['ttl24']);
		}
	}
	
	public function test_private_list()
	{
		global $db;
		$db->do_query('select idUsers from User LIMIT 1');
		
		$uid = Null;
		
		while($arr = $db->get_row_array())
		{
			$uid = $arr['idUsers'];
		}
		
		$projects = EcProject::getUserProjects($uid);
		$this->assertInternalType('array', $projects);
		for( $i = 0; $i < count($projects); $i++ )
		{
			$this->assertInternalType('array', $projects[$i]);
			$this->assertTrue(array_key_exists('name', $projects[$i]));
			$this->assertInternalType('string', $projects[0]['name']);
			$this->assertTrue(array_key_exists('ttl', $projects[$i]));
				
			$this->assertTrue(array_key_exists('ttl24', $projects[$i]));
				
			$this->assertTrue(array_key_exists('listed', $projects[$i]));
			$this->assertRegexp('/public|private/', $projects[$i]['listed']);
				
			//$this->assertInternalType('int', $projects[0]['ttl']);
			//$this->assertInternalType('int', $projects[0]['ttl24']);
		}
	}
	
}
?>