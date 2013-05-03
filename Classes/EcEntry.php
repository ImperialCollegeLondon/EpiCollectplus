<?php
	class EcEntry{
		public $id;
		/*
		 * The value of the key field for this entry
		 */
		public $key;
		public $form;
		public $projectName;	//name of the project
		public $formName;		//name of the form
		public $deviceId;
		public $created = false;
		public $uploaded;
		public $user;
		public $values = array(); //associative array of the entry values
		public $insert_key = '';
		public $parent = null;
		public $numberOfChildren = 0;	
		
		public function  __construct($f)
		{
			$this->form = $f;
			$this->formName = $f->name;
			$this->projectName = $f->survey->name;
		}
		
		public function fetch()
		{
			global $db;
			if(!$this->key && $this->values[$this->form->key]) $this->key = $this->values[$this->form->key]; 
			
			//$db = new dbConnection();
			$sql = "SELECT * from entry e LEFT JOIN entryvalue v on e.idEntry = v.entry where e.projectName = '{$this->projectName}' AND e.formName = '{$this->formName}' AND v.fieldName = '{$this->form->key}' AND v.value = '{$this->key}'";
			$res = $db->do_query($sql);
			if($res !== true) return $res;
			while($arr = $db->get_row_array())
			{
				$this->id = $arr['idEntry'];
				$this->projectName = $arr['projectName'];
				$this->formName = $arr['formName'];
				$this->deviceId = $arr['DeviceId'];
				$this->created = EcTable::formatCreated($arr['created']);
				$this->uploaded = $arr['uploaded'];
				$this->user = $arr['user'];
			}
			
			$sql = "SELECT * from entryvalue where entry = {$this->id}";
			$res = $db->do_query($sql);
			if($res !== true) return $res;
			$this->values = array();
			
			while($arr = $db->get_row_array())
			{
				$this->values[$arr['fieldName']] = $arr['value'];
			}
			return true;
		}
		
		public function post() // add!
		{
			global $db;
			
			if(!$this->created)
			{
				$dt = new DateTime();
				$this->created = $dt->getTimestamp();
			}
                        elseif(!is_numeric($this->created))
                        {
                            $this->created = EcTable::unformatCreated($this->created);
                        }
                        
			
			
			
			$this->uploaded = getTimestamp('Y-m-d H:i:s');
			
			
			if(!$this->deviceId)
			{
				$this->deviceId = 'web upload';
			}
			
			if(!$this->key) $this->key = trim($this->values[$this->form->key]);
			if(!$this->key || trim($this->key) == '')
			{
				throw new Exception('Message: The key field cannot be left blank.');
			}
		
			if($this->form->isMain && $this->form->number > 1){
				$this->parent = $this->checkParentExists();
			
				if(!$this->parent)
				{
					throw new Exception('Message: The parent of this entry is not present on the server.');
				}
			} 
			
			//TODO: need to get the user details from the phone
			//try{
				//$db = new dbConnection();
				$res = $db->beginTransaction();
				if($res !== true) return $res;
				//check that the entry doesn't already exist
				$qry = sprintf('SELECT e.DeviceID as deviceId, ev.value FROM entryvalue ev Join entry e on (ev.entry = e.idEntry) WHERE ev.projectName = \'%s\' AND ev.formName = \'%s\' AND ev.fieldName = \'%s\' AND ev.value = %s' ,$this->projectName, $this->formName, $this->form->keyField,$db->stringVal($this->values[$this->form->keyField]) );
				//echo $qry;
				
				$ents = array();
				$res = $db->do_query($qry);
				if($res !== true) return $res;
				
				while($arr = $db->get_row_array()) array_push($ents, $arr);
				if(count($ents) > 0)
				{
					if(($this->form->survey->allowDownloadEdits || $ents[0]['deviceId'] == $this->deviceId) && $this->deviceId != 'web')
					{
						$res = $this->put();
						return $res;
					}
					else 
					{
						$res = $db->rollbackTransaction();
						return sprintf('Message : Duplicate Key for %s > %s > %s', $this->formName, $this->form->keyField, $this->values[$this->form->keyField]);
					}
				}
			
				$uid = 0;	
				//insert basic entry data
				//if(preg_match("/(CHROME|FIREFOX)/i", $_SERVER["HTTP_USER_AGENT"]))
					//$uid = 0;
				//else
					//$sql = "SELECT isUsers FROM user WHERE email = {$_GET['email']}";
				
				
				$qry = sprintf('INSERT INTO entry (form, projectName, formName, DeviceId, created, uploaded, user) VALUES (%s, %s, %s, %s,%s,\'%s\',0);',
						$this->form->id, 
						$db->stringVal($this->projectName), 
						$db->stringVal($this->formName),  
						$db->stringVal($this->deviceId),
						$this->created, 
						$this->uploaded );
				$res = $db->do_query($qry);
				
				if($res !== true) return $res;
				$this->id = $db->last_id();
				//$this->fetch(); //get id of entry
								
				$qry = 'INSERT INTO entryvalue (field, projectName, formName, fieldName, value, entry) VALUES ';
				$ins = array();
				$keys = array_keys($this->values);
				$length = count($keys);
				for($i = 0; $i < $length; ++$i)
				{
					if($this->form->fields[$keys[$i]])
					{
						if(($this->form->fields[$keys[$i]]->type == 'gps' || $this->form->fields[$keys[$i]]->type == 'location') && !is_string($this->values[$keys[$i]]))
						{
							array_push($ins, sprintf(' ( %s, \'%s\', \'%s\', \'%s\', %s, %s )', 
									$this->form->fields[$keys[$i]]->idField, 
									$this->form->survey->name, 
									$this->form->name, 
									$keys[$i],
									$db->stringVal(json_encode($this->values[$keys[$i]])), 
									$this->id));
						}
						else
						{
							array_push($ins, sprintf('( %s, \'%s\', \'%s\', \'%s\', %s, %s)', 
									$this->form->fields[$keys[$i]]->idField, $this->form->survey->name, $this->form->name, $keys[$i], $db->stringVal($this->values[$keys[$i]]), $this->id));
						}
					}
					else
					{
						$res = $db->rollbackTransaction();
						//echo $res;
						return sprintf('field %s is not present in any version of the project definition',$key);
						
					}
				}
				$qry .= join(' , ', $ins);
				
				$res = $db->do_query($qry);
				if($res !== true){
					//echo $res;
					$res = $db->rollbackTransaction();
					return $res;
				}
				$res = $db->commitTransaction();
				//echo $res;
				return $res;
			//}catch(Exception $e)
			//{
			//	return $e->getMessage();
			//}
		}
		
		public static function postEntries($entries)
		{
				global $db;
				
				$qry = 'INSERT INTO entry (form, projectName, formName, DeviceId, created, uploaded, user, bulk_insert_key) VALUES ';
				
				$len = count($entries);
				$sessId =  session_id();
				
				$prj = new EcProject();
				$prj->name = $entries[0]->projectName;
				$prj->fetch();
				
				$keyfield = $prj->tables[$entries[0]->formName]->key;
				
				for( $i = 0; $i < $len; ++$i)
				{
					if( !$entries[$i]->created || $entries[$i]->created == "NULL") { $entries[$i]->created = getTimestamp(); }
                                        else if(!is_numeric($entries[$i]->created )) {$entries[$i]->created = EcTable::unformatCreated($entries[$i]->created);}
					
					if($prj->tables[$entries[$i]->formName]->checkExists($entries[$i]->values[$keyfield]))
					{
						throw new Exception(sprintf('Your data could not be uploaded, there was a duplicate key for entry %s on line %s of your CSV file', $entries[$i]->values[$keyfield], $i + 2));
					}
					
					$entries[$i]->insert_key = sprintf('%s%s', $sessId, $i);  
					$qry .= sprintf('%s (%s, %s, %s, %s, %s, \'%s\', 0, \'%s\')', 
							($i > 0 ? ',' : ''),
							$entries[$i]->form->id, 
							$db->stringVal($entries[$i]->projectName), 
							$db->stringVal($entries[$i]->formName),  
							$db->stringVal($entries[$i]->deviceId), 
							$entries[$i]->created, 
							getTimestamp("Y-m-d H:i:s"), 
							$entries[$i]->insert_key);
					
					//echo $_SERVER['REQUEST_TIME'] . '<br />\r\n';
				}
				$res = $db->do_query($qry);
				if($res !== true) die($res);
				
				$qry = sprintf('SELECT bulk_insert_key, idEntry FROM  entry where bulk_insert_key Like \'%s%%\'', $sessId);				
				$res = $db->do_query($qry);
				if($res !== true) die($res);
			
				$insert_keys = array();
				while($arr = $db->get_row_array())
				{
					$insert_keys[$arr["bulk_insert_key"]] = $arr["idEntry"];
				}
				
				$qry = 'INSERT INTO entryvalue (field, projectName, formName, fieldName, value, entry) VALUES ';
				for($i = 0; $i < $len; ++$i)
				{
					if(trim($entries[$i]->values[$entries[$i]->form->key]) == '') return 'Key values cannot be blank'; 
					
					$keys = array_keys($entries[$i]->values);
					$length = count($keys);
					
					for($j = 0; $j < $length; ++$j)
					{
						
						if($entries[$i]->form->fields[$keys[$j]])
						{
							if(($entries[$i]->form->fields[$keys[$j]]->type == 'gps' || $entries[$i]->form->fields[$keys[$j]]->type == 'location') && !is_string($entries[$i]->values[$keys[$j]]))
							{
								$qry .= sprintf('%s ( %s, \'%s\', \'%s\', \'%s\', %s, %s )', 
										($i > 0 || $j > 0 ? ',' : ''),
										$entries[$i]->form->fields[$keys[$j]]->idField, 
										$entries[$i]->form->survey->name, 
										$entries[$i]->form->name, 
										$keys[$j],
										$db->stringVal(json_encode($entries[$i]->values[$keys[$j]])), 
										$insert_keys[$entries[$i]->insert_key]);
							}
							else
							{
								$qry .= sprintf('%s ( %s, \'%s\', \'%s\', \'%s\', %s, %s)', 
										($i > 0 || $j > 0 ? ',' : ''),
										$entries[$i]->form->fields[$keys[$j]]->idField, 
										$entries[$i]->form->survey->name, 
										$entries[$i]->form->name, 
										$keys[$j], 
										$db->stringVal($entries[$i]->values[$keys[$j]]), 
										$insert_keys[$entries[$i]->insert_key]);
							}
						}
					}
				}
				$res = $db->do_query($qry);
				if($res !== true) die($res);
				
				$qry = sprintf('UPDATE entry SET  bulk_insert_key = NULL WHERE bulk_insert_key Like \'%s%%\'', $sessId);				
				$res = $db->do_query($qry);
				return $res;
		}
		
		public function put() //edit!
		{
			//check that the entry does already exist
			global $auth, $db;
			//TODO: need to get the user details form the phone
			
			//$db = new dbConnection();
			$res = $db->beginTransaction();
			if($res !== true) return $res;

			$ent = $this->form->createEntry();
			$ent->values = $this->values;
			
			$ent->fetch();
			if(!$ent->id) return "Entry does not exist";
			
			
			if(!$this->key) $this->key = trim($this->values[$this->form->key]);
			if(!$this->key || trim($this->key) == '')
			{
				throw new Exception('Message: The key field cannot be left blank.');
			}
			
			if($this->form->isMain && $this->form->number > 1){
				$this->parent = $this->checkParentExists();
					
				if(!$this->parent)
				{
					throw new Exception('Message: The parent of this entry is not present on the server.');
				}
			}
			
			$qry = "UPDATE entry SET lastEdited = Now() where idEntry = {$ent->id}";
			$res = $db->do_query($qry);
			if($res !== true)
			{
				$db->rollbackTransaction();
				return $res;
			}
			
			foreach($this->values as $key => $value)
			{
				if(!array_key_exists($key, $ent->values))
				{
					$qry = 'INSERT INTO entryvalue (field, projectName, formName, fieldName, value, entry) VALUES ';
					$in = '';
					$keys = array_keys($this->values);
					$length = count($keys);
					if(($this->form->fields[$key]->type == 'gps' || $this->form->fields[$key]->type == 'location') && !is_string($this->values[$key]))
					{
						$in = sprintf(' ( %s, \'%s\', \'%s\', \'%s\', %s, %s )',
								$this->form->fields[$key]->idField,
								$this->form->survey->name,
								$this->form->name,
								$keys[$i],
								$db->stringVal(json_encode($this->values[$key])),
								$ent->id);
					}
					else
					{
						$in = sprintf('( %s, \'%s\', \'%s\', \'%s\', %s, %u)',
								$this->form->fields[$key]->idField, $this->form->survey->name, $this->form->name, $key, $db->stringVal($this->values[$key]), $ent->id);
					}
					$qry .= $in;
					$res = $db->do_query($qry);
				}
				elseif(($this->form->fields[$key]->type == "gps" || $this->form->fields[$key]->type == "location") && !is_string($this->values[$key]))
				{
					$qry = "UPDATE entryvalue SET value = " . $db->stringVal(json_encode($value)) ." WHERE projectName = '{$this->projectName}' AND formName = '{$this->formName}' AND fieldName = '$key' AND entry = {$ent->id}";
				}
				else 
				{
					$qry = "UPDATE entryvalue SET value = " . $db->stringVal($value) . " WHERE projectName = '{$this->projectName}' AND formName = '{$this->formName}' AND fieldName = '$key' AND entry = {$ent->id}";
				}
				$res = $db->do_query($qry);
				if($res !== true){
					$db->rollbackTransaction();
					return $res;
				}
				
				/*if($db->affectedRows() == 0)
				{
					$qry = "INSERT INTO EntryValue (field, projectName, formName, fieldName, value, entry) VALUES  ({$this->form->fields[$key]->idField},'{$this->projectName}','{$this->formName}','$key','$value',{$this->id})";
					$res = $db->do_query($qry);
					if($res !== true) {
						$db->rollbackTransaction();
						return $res;
					}
				}*/
				
			}
			if($res === true)
			{
				$res = $db->commitTransaction();
			}
			else
			{
				$db->rollbackTransaction();
			}
			return $res;
		}
		
		public function delete()
		{
			
			//$db = new dbConnection();
			global $db;
			
			if(count($this->getChildEntries()) > 0 || count($this->getBranchEntries()) > 0)
			{
				throw new Exception("Message: Cannot delete entry {$this->key}, the entry has child or branch entries associated with it.");
			}
			echo "...";
			$sql = "DELETE from entryvalue where Entry = {$this->id}";
			$db->do_query($sql);
			$sql = "DELETE from entry where idEntry = {$this->id}";
			$db->do_query($sql);
		}
		
		
		/*
		 * returns the parent entry for the proposed entry. Either the entry from the main table with the next lowest number, or the form of which this table is a branch.
		 * 
		 * if the entry's table has no parent the function returns false, if the entry should have a parent and doesn't then the function returns array( count => 0, parentTableName => array());
		 */
		public function getParentEntry()
		{
			if($this->form->branchOf)
			{
				$tbl = $this->form->survey->tables[$this->form->branchOf];
			}
			else
			{
				$tbl = $this->form->survey->getPreviousTable($this->form->name, true);
			}
			if($tbl)
			{
				$tbl->ask(array($tbl->key => $this->values[$tbl->key]),0,1,"created", "asc", true);
				$ents = $tbl->recieve();
			}
			else 
			{
				$ents = false;
			}
			return $ents;
		}
		
		public function checkParentExists()
		{
			if($this->form->branchOf)
			{
				$tbl = $this->form->survey->tables[$this->form->branchOf];
			}
			else
			{
				$tbl = $this->form->survey->getPreviousTable($this->form->name, true);
			}
			
			if($tbl)
			{
				return $tbl->checkExists($this->values[$tbl->key]);
			}
			else
			{
				return null;
			}
		}
		
		public function getChildEntries()
		{
			$tbl = $this->form->survey->getNextTable($this->form->name, true);
			if($tbl)
			{
				$children = array();
                                $tbl->ask(array($this->form->key => $this->key));
                                while($res = $tbl->recieve())
                                {
                                    array_push($children, $res);
                                }
                                
			}
			else
			{
				return array();
			}
			return $children;
		}
		
		public function getBranchEntries()
		{
			$branchEntries = array();
			for($i = 0; $i < count($this->form->branches); $i++)
			{
				$branches = array();
                                $this->form->survey->tables[$this->form->branches[$i]]->ask(array($this->form->key => $this->key));
                                while($res = $this->form->survey->tables[$this->form->branches[$i]]->recieve())
                                {
                                    array_push($branches, $res);
                                }
                                        
				for($j = 0; $j < count($branches[$this->form->branches[$i]]); $j++)
				{
					array_push($branchEntries, $branches[$this->form->branches[$i]][$j]);
				}
			}
			return $branchEntries;
		}
		
	}
?>