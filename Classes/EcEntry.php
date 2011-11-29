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
		
		public function  __construct($f)
		{
			$this->form = $f;
			$this->formName = $f->name;
			$this->projectName = $f->survey->name;
		}
		
		public function fetch()
		{
			if(!$this->key && $this->values[$this->form->key]) $this->key = $this->values[$this->form->key]; 
			
			$db = new dbConnection();
			$sql = "SELECT * from entry e LEFT JOIN entryvalue v on e.idEntry = v.entry where e.projectName = '{$this->projectName}' AND e.formName = '{$this->formName}' AND v.fieldName = '{$this->form->key}' AND v.value = '{$this->key}'";
			$res = $db->do_query($sql);
			if($res !== true) return $res;
			while($arr = $db->get_row_array())
			{
				$this->id = $arr["idEntry"];
				$this->projectName = $arr["projectName"];
				$this->formName = $arr["formName"];
				$this->deviceId = $arr["DeviceId"];
				$this->created = $arr["created"];
				$this->uploaded = $arr["uploaded"];
				$this->user = $arr["user"];
			}
			
			$sql = "SELECT * from entryvalue where entry = {$this->id}";
			$res = $db->do_query($sql);
			if($res !== true) return $res;
			$this->values = array();
			
			while($arr = $db->get_row_array())
			{
				$this->values[$arr["fieldName"]] = $arr["value"];
			}
			return true;
		}
		
		public function post() // add!
		{
			global $auth;
			
			
			if(!$this->created)
			{
				$dt = new DateTime();
				$this->created = $dt->getTimestamp();
			}
			
			if(!$this->deviceId)
			{
				$this->deviceId = "web upload";
			}
				
			$parent = $this->getParentEntry();
			if($parent !== false && $parent["count"] == 0)
			{
				throw new Exception("Message: The parent of this entry is not present on the server.");
			} 
			
			//TODO: need to get the user details from the phone
			try{
				$db = new dbConnection();
				$res = $db->beginTransaction();
				if($res !== true) return $res;
				//check that the entry doesn't already exist
				$qry = "SELECT e.DeviceID as deviceId, ev.value FROM entryvalue ev Join entry e on (ev.entry = e.idEntry) WHERE ev.projectName = '{$this->projectName}' AND ev.formName = '{$this->formName}' AND ev.fieldName = '{$this->form->keyField}' AND ev.value = " . $db->stringVal($this->values[$this->form->keyField]);
				//echo $qry;
				
				$ents = array();
				$res = $db->do_query($qry);
				if($res !== true) return $res;
				
				while($arr = $db->get_row_array()) array_push($ents, $arr);
				if(count($ents) > 0)
				{
					if($this->form->survey->allowDownloadEdits || $ents[0]["deviceId"] == $this->deviceId)
					{
						$res = $this->put();
						return $res;
					}
					else 
					{
						return "Message : Duplicate Key for {$this->formName} > {$this->form->keyField} > {$this->values[$this->form->keyField]}";
					}
				}
			
				$uid = 0;	
				//insert basic entry data
				//if(preg_match("/(CHROME|FIREFOX)/i", $_SERVER["HTTP_USER_AGENT"]))
					//$uid = 0;
				//else
					//$sql = "SELECT isUsers FROM user WHERE email = {$_GET['email']}";
				
				
				$qry = "INSERT INTO entry (form, projectName, formName, DeviceId, created, uploaded, user) VALUES ({$this->form->id}," . $db->stringVal($this->projectName) . "," . $db->stringVal($this->formName) . "," . $db->stringVal($this->deviceId) . ",{$this->created},now(),0);";
				$res = $db->do_query($qry);
				
				if($res !== true) return $res;
				$this->id = $db->last_id();
				//$this->fetch(); //get id of entry
								
				$qry = "INSERT INTO entryvalue (field, projectName, formName, fieldName, value, entry) ";
				$ins = array();
				foreach(array_keys($this->values) as $key)
				{
					if($this->form->fields[$key])
					{
						if(($this->form->fields[$key]->type == "gps" || $this->form->fields[$key]->type == "location") && !is_string($this->values[$key]))
						{
							array_push($ins, " SELECT {$this->form->fields[$key]->idField}, '{$this->form->survey->name}', '{$this->form->name}', '$key', " . $db->stringVal(json_encode($this->values[$key])) . ", {$this->id}");
						}
						else
						{
							array_push($ins, " SELECT {$this->form->fields[$key]->idField}, '{$this->form->survey->name}', '{$this->form->name}', '$key', " . $db->stringVal($this->values[$key]) . ", {$this->id}");
						}
					}
					else
					{
						$res = $db->rollbackTransaction();
						//echo $res;
						return "field $key is not present in any version of the project definition";
						
					}
				}
				$qry .= join(" UNION ", $ins);
				
				$res = $db->do_query($qry);
				if($res !== true){
					echo $res;
					$res = $db->commitTransaction();
					
					return 0;//$res;
				}
				$res = $db->commitTransaction();
				//echo $res;
				return $res;
			}catch(Exception $e)
			{
				return 0; //$e->getMessage();
			}
		}
		
		public function put() //edit!
		{
			//check that the entry does already exist
			global $auth;
			//TODO: need to get the user details form the phone
			
			$db = new dbConnection();
			$res = $db->beginTransaction();
			if($res !== true) return $res;
			//check that the entry does already exist
			/*$qry = "SELECT * FROM entryvalue WHERE projectName = '{$this->projectName}' AND formName = '{$this->formName}' AND fieldName = '{$this->form->keyField}' AND value = '{$this->values[$this->form->keyField]}'";
			//echo $qry;
			
			$num = 0;
			$res = $db->do_query($qry);
			if($res !== true)
			{
				$db->rollbackTransaction();
				return $res;
			}*/
			
			$ent = $this->form->createEntry();
			$ent->values = $this->values;
			
			$ent->fetch();
			if(!$ent->id) return "Entry does not exist";
			
			$qry = "UPDATE entry SET lastEdited = Now() where idEntry = {$ent->id}";
			$res = $db->do_query($qry);
			if($res !== true)
			{
				$db->rollbackTransaction();
				return $res;
			}
			
			foreach($this->values as $key => $value)
			{
				if(($this->form->fields[$key]->type == "gps" || $this->form->fields[$key]->type == "location") && !is_string($this->values[$key]))
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
			
			$db = new dbConnection();
			
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
				$ents = $tbl->get(array($tbl->key => $this->values[$tbl->key]));
			}
			else 
			{
				$ents = false;
			}
			return $ents;
		}
		
		public function getChildEntries()
		{
			$tbl = $this->form->survey->getNextTable($this->form->name, true);
			if($tbl)
			{
				$children = $tbl->get(array($this->form->key => $this->key));
			}
			else
			{
				return array();
			}
			return $children[$tbl->name];
		}
		
		public function getBranchEntries()
		{
			$branchEntries = array();
			for($i = 0; $i < count($this->form->branches); $i++)
			{
				$branches = $this->form->survey->tables[$this->form->branches[$i]]->get(array($this->form->key => $this->key));
				for($j = 0; $j < count($branches[$this->form->branches[$i]]); $j++)
				{
					array_push($branchEntries, $branches[$this->form->branches[$i]][$j]);
				}
			}
			return $branchEntries;
		}
		
	}
?>