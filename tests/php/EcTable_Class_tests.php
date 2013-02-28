<?php
include '../../db/dbConnection.php';
include '../../Classes/configManager.php';
include('../../Classes/EcProject.php');
include('../../Classes/EcTable.php');
include('../../Classes/EcField.php');
include('../../Classes/EcOption.php');


$DIR = '../../';

$filter_project = '';
$filter_table = '';
$filter_field = '';
$filter_value = '';

class EcProjectTest	 extends PHPUnit_Framework_TestCase
{
	protected $projects = array();
	
	/**
	 * @group internals
	 * @author Chris I Powell (chris.i.powell@gmail.com)
	 * @requires json_encode, dbConnection, configManager, EcProject
	 * 
	 */
	protected function setup()
	{
		global $db, $cfg;
		$cfg = new ConfigManager('../../ec/epicollect.ini');
		$db = new dbConnection();
		$this->projects = EcProject::getPublicProjects();
	}
	
	protected function tearDown()
	{
		global $db;
	}
	
	public function test_fetch()
	{
	
		for($p = 0; $p < count($this->projects); $p++)
		{
			$prj = new EcProject();
			$prj->name = $this->projects[$p]['name'];
			$prj->fetch();
			
			$tbls = array_keys($prj->tables);
			for( $t = 0; $t < count($tbls); $t++ )
			{
				$tbl = $tbls[$t];
			
				$flds = array_keys($prj->tables[$tbl]->fields);
				
				$zerocount = 0;
				
				for( $f = 0; $f < count($flds); $f++ )
				{
					if($prj->tables[$tbl]->fields[$flds[$f]]->position === 0)
					{
						$zerocount++;
						$this->assertLessThanOrEqual($zerocount, 1);
					}
					else
					{
						if(is_int($prj->tables[$tbl]->fields[$flds[$f]]->position))
						{
							$this->assertGreaterThan($prj->tables[$tbl]->fields[$flds[$f]]->position, 0);
						}
						else
						{
							$this->assertRegExp('/^\d+$/', $prj->tables[$tbl]->fields[$flds[$f]]->position);
						}
					}
				}
			}
			
			
		}
		
	}
	
	public function test_ask()
	{
		
		for($p = 0; $p < count($this->projects); $p++)
		{
			
			$prj = new EcProject();
			$prj->name = $this->projects[$p]['name'];
			$prj->fetch();
			
			$tbls = array_keys($prj->tables);
			for( $t = 0; $t < count($tbls); $t++ )
			{
				$tbl = $tbls[$t];
				
				$req = $prj->tables[$tbl]->ask();
				$this->assertEquals(true, $req);
				
				//$obj = $prj->tables[$tbl]->recieve();
				
				
				for($o = 0; $obj = $prj->tables[$tbl]->recieve(); )
				{
					
					$this->assertInternalType('array', $obj);
					$ch = $prj->getNextTable($tbl, true);
					
					//foreach($prj->tables[$tbl]->fields as $k => $v)
					//{
						//$this->assertArrayHasKey($k, $obj[$o]);
					//}
					
					if($ch)
					{
						$this->assertArrayHasKey(sprintf('%s_entries', $ch->name), $obj[$o]);
					}
				}
				
			}
		}
	}
	
	public function test_ask_filtered()
	{
		//printf("\n Testing filtered fetch");
		for($p = 0; $p < count($this->projects); $p++)
		{
			$prj = new EcProject();
			$prj->name = $this->projects[$p]['name'];
			//$prj->name = 'ALSWGAGRIC';
			$prj->fetch();
			
			$tbls = array_keys($prj->tables);
			
			//printf("\nTesting Project :  %s", $prj->name);
			
			for ( $i = 0; $i < count($tbls); $i++ )
			{
				$res = array();
				
				$req = $prj->tables[$tbls[$i]]->ask();
				$this->assertTrue($req);
				
				while($obj = $prj->tables[$tbls[$i]]->recieve())
				{
					array_push($res, $obj[0]);
				}
				
				
				for($d = 0; $d < count($res); $d++)
				{
					foreach($prj->tables[$tbls[$i]]->fields as $name => $fld)
					{
						if( $fld->valueIsObject() || $fld->valueIsFile() ) continue;
						if( array_key_exists($name, $res[$d]) )
						{
							$req = $prj->tables[$tbls[$i]]->ask(array($name => $res[$d][$name]));
							$this->assertTrue($req);
							while($obj = $prj->tables[$tbls[$i]]->recieve())
							{
								
								$this->assertEquals(trim($obj[0][$name]), trim($res[$d][$name]));
								//flush();
							}
						}
					}
					
					$name = 'created';
					$req = $prj->tables[$tbls[$i]]->ask(array($name => $res[$d][$name]));
					$this->assertTrue($req);
					while($obj = $prj->tables[$tbls[$i]]->recieve())
					{
							
						$this->assertEquals($obj[0][$name], $res[$d][$name]);
						//flush();
					}
					//print '.';
				}
				
				
				unset($res);
			}
		}
	}
	
	public function test_ask_sorted()
	{
		//printf("\n Testing filtered fetch");
		for($p = 0; $p < count($this->projects); $p++)
		{
			$prj = new EcProject();
			$prj->name = $this->projects[$p]['name'];
			//$prj->name = 'ALSWGAGRIC';
			$prj->fetch();
				
			$tbls = array_keys($prj->tables);
				
			//printf("\nTesting Project :  %s", $prj->name);
				
			for ( $i = 0; $i < count($tbls); $i++ )
			{
				$res = array();
	
				$req = $prj->tables[$tbls[$i]]->ask();
	
				while($obj = $prj->tables[$tbls[$i]]->recieve())
				{
					array_push($res, $obj[0]);
				}
	
	
				for($d = 0; $d < count($res); $d++)
				{
					foreach($prj->tables[$tbls[$i]]->fields as $name => $fld)
					{
						if($fld->valueIsObject() || $fld->valueIsFile() || $fld->type == "group") continue;
						$req = $prj->tables[$tbls[$i]]->ask(null, 0, 0, $name, 'asc');
						$this->assertTrue($req);
						
						$lastVal = '';
						
						while($obj = $prj->tables[$tbls[$i]]->recieve())
						{
							if($lastVal != '')
							{
								if(array_key_exists($name, $prj->tables[$tbls[$i]]->fields) && $prj->tables[$tbls[$i]]->fields[$name]->isInt)
								{
									$this->assertTrue($obj[0][$name] >= $lastVal, "Sorted {$prj->name} > {$tbls[$i]} > '$name'  values where not in order");
								}
								else 
								{
									$this->assertTrue(strcmp($obj[0][$name], $lastVal) <= 0, "Sorted {$prj->name} > {$tbls[$i]} > '$name'  values where not in order");
								}
							}
							//flush();
						}
					}
					
					$lastVal = '';
					$name = 'created';
					$req = $prj->tables[$tbls[$i]]->ask(null, 0, 0, $name, 'asc');
					$this->assertTrue($req);
					while($obj = $prj->tables[$tbls[$i]]->recieve())
					{
							
						if($lastVal != '')
						{
							if($lastVal != '')
								$this->assertTrue($obj[0][$name] >= $lastVal, "Sorted {$prj->name} > {$tbls[$i]} > '$name'  values where not in order");
							$lastVal = $obj[0][$name];
						}
						$lastVal = $obj[0][$name];
						//flush();
					}
					
					$child  = $prj->getNextTable($tbls[$i], true);
					if($child)
					{
						$lastVal = '';
						$name = $child->name . '_entries';
						$req = $prj->tables[$tbls[$i]]->ask(null, 0, 0, $name, 'asc');
						$this->assertTrue($req);
						while($obj = $prj->tables[$tbls[$i]]->recieve())
						{
							if($name == 'Household_entries') print '\n'. $lastVal . '::'	. $obj[0][$name] . '\n';
							if($lastVal != '' && $lastVal != 'null')
								$this->assertTrue($obj[0][$name] >= $lastVal, "Sorted {$prj->name} > {$tbls[$i]} > '$name'  values where not in order");
							$lastVal = $obj[0][$name];
							//flush();
						}
					}
				}
	
				unset($res);
			}
		}
	}
	
	public function test_get_branch_forms()
	{
		for($p = 0; $p < count($this->projects); $p++)
		{
			$prj = new EcProject();
			$prj->name = $this->projects[$p]['name'];
			$prj->fetch();
			
			$tbls = array_values($prj->tables);
			
			for ( $i = 0; $i < count($tbls); $i++ )
			{
				$n_branches = count($tbls[$i]->branchfields);
				
				$branch_forms = $tbls[$i]->getBranchForms();
				
				$this->assertInternalType('array', $branch_forms);
				$this->assertEquals(count($branch_forms), $n_branches);
				for($j = 0; $j < $n_branches; $j++)
				{
					$this->assertInstanceOf('EcTable', $branch_forms[$j]);
				}			
			}
		}
	}
	
	public function test_title_validation()
	{
		for($p = 0; $p < count($this->projects); $p++)
		{
			$prj = new EcProject();
			$prj->name = $this->projects[$p]['name'];
			$prj->fetch();
			
			$tbls = array_keys($prj->tables);

			for ( $i = 0; $i < count($tbls); $i++ )
			{
				$title_fields = array();
				
				$req = $prj->tables[$tbls[$i]]->ask();
				$this->assertTrue($req);
				
				$ttlfields = $prj->tables[$tbls[$i]]->titleFields;
				
				if(count($ttlfields) == 0) continue;
				
				$this->assertInternalType('array', $ttlfields);
				
				while($obj = $prj->tables[$tbls[$i]]->recieve())
				{
					$ttl_vals = array();
					for($f = 0; $f < count($ttlfields); $f++)
					{
						if(array_key_exists($ttlfields[$f], $obj[0]))
						{
							array_push($ttl_vals, $obj[0][$ttlfields[$f]]);
						}						
					}
					 
					array_push($title_fields, $ttl_vals);
				}
				
				for($t = 0; $t < count($title_fields); $t++)
				{
					if (count($title_fields[$t]) == 0) continue;
					$args = array_combine($prj->tables[$tbls[$i]]->titleFields, $title_fields[$t]);
					
					$r = $prj->tables[$tbls[$i]]->ask($args);
					$this->assertTrue($r);
											
					while($prj->tables[$tbls[$i]]->recieve()){ }
					
					$ttl = implode(', ', $title_fields[$t]);
					
					$response = $prj->tables[$tbls[$i]]->validateTitle($ttl);
					/*if($response['valid'] === false)
					{
						print ' xxx ' . $prj->name . ' > ' . $tbls[$i] . ' > ' .$ttl;						
					}*/
					
					$this->assertTrue($response['valid']);
							
				}
			}
				
					

		}
	}
	
}
?>