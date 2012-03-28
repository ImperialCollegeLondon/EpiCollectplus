<?php
	class EcTable{
		public $id;
		public $name = "";
		public $projectName;
		public $number = 0;
		public $key = "";
		public $fields = Array();
		public $conString = "";
		public $survey = "";
		public $version = 1.0;
		public $isMain = true;
		public $branches = array();
		public $branchOf = false;
		public $group = false;
		
		public function __construct($s = null)
		{
			$this->survey = $s;
		}
		
		public function hasGPS()
		{
			foreach ($this->fields as $name => $fld) {
				if($fld->type == "gps" || $fld->type == "location")
					return true;
			}
			return false;
		}
		
		public function fromArray($arr)
		{
			foreach(array_keys($arr) as $key)
			{
				if($key == "isMain")
				{
					$this->$key = $arr[$key] == 1 ? "true" : "false";	 
				}
				else
				{
					$this->$key = $arr[$key];
				}
			}
			$this->number = $this->table_num;
			$this->id = $this->idForm;
		}
		
		public function toXML()
		{
			$xml = "\n\t<form num=\"{$this->number}\" name=\"{$this->name}\" key=\"{$this->key}\" main=\"". ($this->isMain ? "true" : "false")."\" " . ($this->group ? "group=\"{$this->group}\"" : "") . "  > ";
			foreach($this->fields as $fld)
			{
				if($fld->active)
					$xml .= $fld->toXML();
			}
			$xml .= "\n\t</form>";
			return $xml;
		}
		
		public function toJson()
		{
			$xml = "\n\t{
				\"num\" : {$this->number},
				\"name\" : \"{$this->name}\",
				\"key\" : \"{$this->key}\",
				\"fields\": [";
				$i =0;
			foreach($this->fields as $fld)
			{
				$xml .= ($i > 0 ? "," : "") . $fld->toJson();
				$i++;
			}
			$xml .= "]\n\t}";
			return $xml;
		}
		
		public function createEntry()
		{
			$ent = new EcEntry($this);
			foreach(array_keys($this->fields) as $key)
			{
				$ent->values[$key] = false;
			}
			return $ent;
		}
		
		public function fetch()
		{
			$db = new dbConnection();
			//global $db;
			
			$qry = "SELECT * from form WHERE";
			if(is_numeric($this->id))
			{
				$qry = "$qry idForm = {$this->id}";
			}
			else
			{
				$qry = "$qry projectName = '{$this->survey->name}' AND name = '{$this->name}'";
			}
			
			$res = $db->do_query($qry);
			if($res === true)
			{
				while ($arr = $db->get_row_array())
				{
					$this->id = $arr["idForm"];
					$this->key = $arr["keyField"];
					$this->name = $arr["name"];
					$this->number = $arr["table_num"];
					$this->version = $arr["version"];
					$this->group = $arr["group"];
					$this->isMain = $arr["isMain"] == "1";
				}
				
			}
			
			$qry = "SELECT f.idField as idField, f.key, f.name, f.label, ft.name as type, f.required, f.jump, f.isinteger as isInt, f.isDouble, f.title, f.regex, f.doubleEntry, f.search, f.group_form, f.branch_form, f.display, f.genkey, f.date, f.time, f.setDate, f.setTime, f.min, f.max, f.crumb, f.`match`, f.active FROM field f LEFT JOIN fieldtype ft on ft.idFieldType = f.type WHERE ";
			if(is_numeric($this->id))
			{
				$qry = "$qry f.form = {$this->id} ORDER BY f.position";
			}
			else
			{
				$qry = "$qry f.projectName = '{$this->survey->name}' AND f.formname = '{$this->name}' ORDER BY f.position";
			}
			
			$res = $db->do_query($qry);
			if($res === true)
			{
				
				while ($arr = $db->get_row_array())
				{
					if(!array_key_exists($arr["name"],$this->fields))
					{
						$fld = new EcField();
					}
					else
					{
						$fld = $this->fields[$arr["name"]];
					} 
					
					$fld->form = $this;
					$fld->fromArray($arr);
					$this->fields[$fld->name] = $fld;
					if($fld->key) $this->key = $fld->name;
				}
				
				foreach($this->fields as $fld)
				{
					//$db = new dbConnection();
					$res = $db->do_query("SELECT `index`, `label`, `value` FROM `option` WHERE `field` = {$fld->idField}"); //$db2->exec_sp("getOptions", array($fld->idField));
					if($res !== true) die($res);
					while($arr = $db->get_row_array())
					{
						$opt = new EcOption();
						$opt->fromArray($arr);
						$opt->idx = $arr["index"];
						$fld->options[$opt->value] = $opt;
					}
					if($fld->type == "branch") array_push($this->branches, $fld->branch_form);
					unset($db2);
				}
				
				return true;
			}
			else
			{
				return $res;
			}
		}
		
		public function addToDb()
		{
			global $auth, $db;
			//$db = new dbConnection();
			
			//print_r($this->fields);
			
			$res = $db->do_query("INSERT INTO form(project, projectName, version, name, table_num, keyField, isMain, `group`) VALUES({$this->survey->id}, '{$this->survey->name}', {$this->version} , '{$this->name}', '{$this->number}', '{$this->key}', " . ($this->isMain ? "1" : "0") . ", " . $db->numVal($this->group) . ")");
			if($res === true)
			{
				$this->fetch(); //need to get the form's id
				$r = true;
				foreach($this->fields as $fld)
				{
					$r = $fld->addToDb();
					if($r !== true) return $r;
				}
			}
			else
			{
				$r = $res;
			}
			
			return $r;
		}
		
		public function parse($xml)
		{
			if(((string)$xml->getName()) == "form")
			{
				foreach($xml->attributes() as $name => $val)
				{
					switch($name)
					{
						case 'num':
							$this->number = (int)$val;
							break;
						case 'name':
							$this->name = (string)$val;
							break;
						case 'key':
							$this->key = (string)$val;
							break;
						case 'main':
							$this->isMain = parseBool((string)$val);
							break;
						case 'group':
							$this->group = (int)$val;
							break;
					}
				}
			}
			// if the xml provided is a EcV2 xml definition
			elseif((string)$xml->getName() == "table")
			{
				//parse out table data for a v2 definition
				foreach($xml->table_data[0]->attributes() as $name => $val)
				{
//					echo $name;
					switch($name)
					{
						case 'table_num':
							$this->number = (int)$val;
							break;
						case 'table_name':
							$this->name = (string)$val;
							break;
						case 'table_key':
							$this->key = (string)$val;
							break;
						case 'main':
							try{
								$this->isMain = parseBool((string)$val);
							}
							catch(Exception $e)
							{
								throw new Exception("The main attribute for {$this->name} must be true or false.");	
							}
							break;
					}
				}
			}
			else
			{
				
				$this->number = 1;
				$this->name = "table";
				$this->key = "";
			}
			
			if(!$this->name || $this->name == "") throw new Exception("All forms must have a name,");
			if(!$this->key || $this->key == "") throw new Exception("No key field specified for {$this->name}");
			
		
			//parse elements
			$p = 0;
			foreach($xml->children() as $field)
			{
				
				if(preg_match('/^(input|select1?|radio|textarea|photo|gps|location|barcode|audio|video|group|branch)$/', $field->getName()))
				{
					$atts = $field->attributes();
			
					if(!isset($atts['ref']) || trim((string)$atts['ref']) == "")
					{
						throw new Exception("Every form field must have a ref attribute, which cannot be blank");
					}
					
					if(!array_key_exists((string)$atts['ref'], $this->fields))
					{
						$fld = new EcField();
					}
					elseif($this->fields[(string)$atts['ref']]->idField)
					{
						$fld = $this->fields[(string)$atts['ref']];
					}
					else
					{
						throw new Exception("duplicate field name " . (string)$atts['ref'] . " in the form {$this->name}");
					}
					
					$fld->parse($field);
					
					$fld->form = $this;
					foreach($this->survey->tables as $tbl)
					{
						if($tbl->key == $fld->name)
						{
							$fld->fkTable = $tbl->name;
							$fld->fkField = $tbl->key;
						}
					}
					$fld->active = true;
					$fld->position = $p;
					$this->fields[$fld->name] = $fld;
					if($fld->type == "branch") array_push($this->branches, $fld->branch_form);
					$p++;
				
				}
				
			}
			
			if(!array_key_exists($this->key, $this->fields)) throw new Exception("The form {$this->name} does not contain the field {$this->key} which was specified as the primary key.");
			
			$this->fields[$this->key]->key = true;
		}
		
		public function ask($args = false, $offset = 0, $limit = 0, $sortField = "created", $sortDir = "asc", $exact = false, $format = "json")
		{
			global $db;
			
			if(!$sortField) $sortField = "created";
			if(!$sortDir) $sortDir = "asc";
			
			$db = new dbConnection();
			$select = "SELECT e.idEntry as id, e.DeviceID, e.created, e.lastEdited, e.uploaded ";
			$group = " GROUP BY e.idEntry, e.DeviceID, e.created, e.lastEdited, e.uploaded ";
			$join = "FROM entry e ";
			$where = " WHERE e.projectName = '{$this->survey->name}' AND e.formName = '{$this->name}' ";
			foreach($this->fields as $name => $field)
			{
				if($field->active)
				{
					$select .= ", ev$name.value as `$name` ";
					$group .= ", ev$name.value";
					$join .= "LEFT JOIN entryValue ev$name ON e.idEntry = ev$name.entry and ev$name.fieldName = '$name' " ;
				}
			}
			
			if($args)
			{
				foreach($args as $k => $v)
				{
					if(array_key_exists($k, $this->fields))
					{
						if($exact)
						{
							$where .= " AND ev$k.value = '$v'";
						}
						else
						{
							$where .= " AND ev$k.value LIKE '%$v%'";
						}
					}
				}
			}
 			if($this->survey->getNextTable($this->name, true))
 			{
 			
 				$child = $this->survey->getNextTable($this->name, true);
 				$select .= ", COUNT(ev{$child->name}_entries.value) as {$child->name}_entries ";
 				$join .= "LEFT JOIN (select value from entryValue where projectName = '{$this->survey->name}' AND formName = '{$child->name}' AND fieldName = '{$this->key}') ev{$child->name}_entries ON ev{$child->name}_entries.value = ev{$this->key}.value ";	
 			}
 			else 
 			{
 				$group = "";
 			}
			
			if(array_key_exists($sortField, $this->fields))
			{
				$order = " ORDER BY ev$sortField.value $sortDir";
			}
			elseif($sortField)
			{
				$order = " ORDER BY e.$sortField $sortDir";
			}
			if($limit && $offset)
			{
				$limit = " LIMIT  $offset, $limit";
			}
			elseif ($limit)
			{
				$limit = " LIMIT $limit";	
			}
			else 
			{
				$limit = "";
			}
			$qry = "$select $join $where $group $order $limit";

			return $db->do_query($qry);
				
		}
		
		public function recieve($n = 1)
		{
			global $db;
			$ret = null;
			
			for($i = 0; ($n > $i) && ($arr = $db->get_row_array()) ; $i++)
			{
				
				//print_r($arr);
				if($format == "object")
				{
					if($ret == null) $ret = array();
					array_push($ret, $arr);						
				}
				elseif($format == "tsv")
				{
					if($ret == null) $ret = "";
					foreach($arr as $key => $val)
					{
						if(strlen($ret) != 0 && $ret[strlen($ret)-1] != "\n") $ret .=  "\t";
						if(array_key_exists($key, $this->fields) && ($this->fields[$key]->type == "location" || $this->fields[$key]->type == "gps" ))
						{
							if($val != "")
							{
								$val = json_decode($val);
								$ret .=  str_replace("\n", " ","{$val->latitude}\t{$val->longitude}\t{$val->altitude}\t{$val->accuracy}\t{$val->provider}");
							}
							else
							{
								$ret .= "0\t0\t0\t-1\tNone";
							}
						}
						else
						{
							$ret .= str_replace("\n", " ", $val);
						}
					}
					$ret .= "\n";
				}  
				elseif($format == "csv") 
				{
					if($ret == null) $ret = "";
					foreach($arr as $key => $val)
					{
						if(strlen($ret) != 0 && $ret[strlen($ret)-1] != "\n") $ret .=  ",";
						if(array_key_exists($key, $this->fields) && ($this->fields[$key]->type == "location" || $this->fields[$key]->type == "gps" ))
						{
							if($val != "")
							{
								$val = json_decode($val);
								$ret .=  str_replace("\n", " ","{$val->latitude},{$val->longitude},{$val->altitude},{$val->accuracy},{$val->provider}");
							}
							else
							{
								$ret .= "0,0,0,-1,None";
							}
						}
						else
						{
							$ret .=  str_replace("\n", " ",$val);
						}
					}
					$ret .= "\n";
				}
				elseif($format == "json")
				{
					if($ret == null) $ret = "";
					if($i > 0) $ret .= ",";
					$ret .= "{";
				
					foreach($arr as $key => $val)
					{
						if(array_key_exists($key, $this->fields) && ($this->fields[$key]->type == "location" || $this->fields[$key]->type == "gps" ))
						{
							if(!$val) $val = "null";
							$ret .= "\"$key\" : $val,";
						}
						else
						{
							$ret .= "\"$key\" : \"$val\",";
						}
					}
					
					$ret = trim($ret, ",") . "}";
					
				}
				elseif($format == "xml")
				{
					if($ret == null) $ret = "";
					$ret .= "<entry>";
					foreach($arr as $key => $val)
					{
						if(array_key_exists($key, $this->fields) && ($this->fields[$key]->type == "location" || $this->fields[$key]->type == "gps" ))
						{
							if($val != "")
							{
								$val = json_decode($val);
								$ret .= "<{$key}_lat>{$val->latitude}</{$key}_lat><{$key}_lon>{$val->longitude}</{$key}_lon><{$key}_alt>{$val->altitude}</{$key}_alt><{$key}_acc>{$val->accuracy}</{$key}_acc><{$key}_provider>{$val->provider}</{$key}_provider>";
							}
							else
							{
								$ret .= "<{$key}_lat>0</{$key}_lat><{$key}_lon>0</{$key}_lon><{$key}_alt>0</{$key}_alt><{$key}_acc>-1</{$key}_acc><{$key}_provider>None</{$key}_provider>";
							}
						}
						else 
						{
							$ret .= "<$key>$val</$key>";
						}
					}
					$ret .= "</entry>";
				}
			}
			
			return $ret;
		}
		
		public function get($args = false, $offset = 0, $limit = 0, $sortField = "created", $sortDir = "asc", $exact = false)
		{
			//global $auth;
			global $db;
			//$db = new dbConnection();
			if(preg_match("/created|deviceId|lastEdited|uploaded/i", $sortField))
			{
				$sql = "SELECT DISTINCT e.idEntry as id, e.DeviceID, e.created, e.lastEdited, e.uploaded FROM entry e {{joinclause}} WHERE e.projectName = '{$this->survey->name}' AND e.formName = '{$this->name}' {{whereclause}} ORDER BY e.$sortField $sortDir";
			}
			elseif (preg_match("/" .$this->survey->getNextTable($this->name, true)->name . "Entries/i", $sortField))
			{
				$childForm = $this->survey->getNextTable($this->name, true);
				$sql = "SELECT DISTINCT idEntry as id, e.DeviceID, e.created, e.lastEdited, e.uploaded, c.childEntries FROM entry e LEFT JOIN entryvalue ev ON ev.entry = e.idEntry LEFT JOIN (SELECT Value, count(1) as childEntries FROM EntryValue where projectName = '{$this->survey->name}' AND formName = '{$childForm->name}' and fieldName = '{$this->key}' GROUP BY value) c ON c.value = ev.value {{joinclause}} WHERE ev.projectName = '{$this->survey->name}' AND ev.formName = '{$this->name}' and ev.fieldName = '{$this->key}' {{whereclause}} ORDER BY c.childEntries $sortDir";
				
			}
			else
			{
				$sql = "SELECT DISTINCT idEntry as id, e.DeviceID, e.created, e.lastEdited, e.uploaded FROM entry e LEFT JOIN entryvalue ev ON ev.entry = e.idEntry {{joinclause}} WHERE ev.projectName = '{$this->survey->name}' AND ev.formName = '{$this->name}' AND ev.fieldName = '{$sortField}'  {{whereclause}} ORDER BY ev.Value $sortDir";
			}
			
			$sql2 = "SELECT count(DISTINCT entry) as ttl FROM entryvalue WHERE projectName = '{$this->survey->name}' AND formName = '{$this->name}'";
			
			if(is_array($args) && count($args) > 0)
			{
				//If we have search criteria
				$sql2 .= "AND (";
				$joinClause = " ";
				$whereClause = " ";
				foreach($args as $k => $v)
				{
					$joinClause .= " JOIN entryvalue ev$k on e.idEntry = ev$k.Entry ";
					if($exact)
					{
						$whereClause .= "AND (ev$k.fieldName = '$k' AND ev$k.value Like '$v') ";
						$sql2 .= "(fieldName = '$k' AND value Like '$v') OR";
					}
					else
					{
						$whereClause .= "AND (ev$k.fieldName = '$k' AND ev$k.value Like '%$v%') ";
						$sql2 .= "(fieldName = '$k' AND value Like '%$v%') OR";
					}
				}
				$whereClause = substr($whereClause, 0, count($whereClause) - 3). ")";
				$sql2 = substr($sql2, 0, count($sql2) - 3). ");";
				$sql = str_replace("{{joinclause}}", $joinClause, $sql);
				$sql = str_replace("{{whereclause}}", $whereClause, $sql);
			}
			elseif(is_string($args))
			{
				$sql = str_replace("{{joinclause}}", " JOIN entryvalue ev ON e.idEntry = ev.Entry ", $sql);
				$sql = str_replace("{{whereclause}}", " AND ev.fieldName = '{$this->key}' AND ev.value = '{$args}'", $sql);
			}
			else
			{
				//otherwise
				$sql = str_replace("{{joinclause}}", "", $sql);
				$sql = str_replace("{{whereclause}}", "", $sql);
			}

			if($limit > 0)
			{
				if($offset > 0)
				{
					$sql = "$sql LIMIT $offset, $limit";
					
				}
				else
				{
					$sql = "$sql LIMIT $limit";
				}
			}
			
			$ents = array();
			
			$res = $db->do_query($sql);
			
			if($res === true)
			{
				while($arr = $db->get_row_array())
				{
					$ents[$arr["id"]] = $arr;
				}
			}
			else
			{
				return $res;
			}
			
			if(count($ents) == 0)
			{
				return array("count" => 0, $this->name => array());
			}
			
			$entries = implode(",", array_keys($ents));
			
			
			
			$sql = "SELECT * FROM entryvalue WHERE Entry in ($entries) ORDER BY fieldName";
				
			$res = $db->do_query($sql);
			if($res === true)
			{
				while($arr = $db->get_row_array())
				{
					if(array_key_exists($arr["entry"], $ents))
					{
						$ents[$arr["entry"]][$arr["fieldName"]] = $arr["value"];
					}
				}
				
				// Get numbers of Child and branch entries
				$formToField = array();
				
				foreach($this->fields as $fld)
				{
					if($fld->type == "branch")
					{
						$formToField[$fld->branch_form] = $fld->name;
					}
				}
				
		
				if($this->survey->getNextTable($this->name, true))
				{
					$sql = "SELECT FormName, value, count(1) as count from entryvalue WHERE projectName = '{$this->survey->name}' AND formName = '" . $this->survey->getNextTable($this->name, true)->name . "' AND fieldName = '{$this->key}' Group By FormName, value";
					
					
					$res = $db->do_query($sql);
					if($res !== true) return $res;
					while($arr = $db->get_row_array())
					{
						foreach(array_keys($ents) as $ent)
						{
							//echo ($ents[$ent][$this->key] . " - ". $arr["value"] . "\n");
							try{
							if(preg_match("/^{$arr["value"]}$/i", $ents[$ent][$this->key]))
							{	
								//echo "\n";
								if(array_key_exists($arr["FormName"], $formToField))
								{
									$ents[$ent][$formToField[$arr["FormName"]]] = $arr["count"];
								}
								else
								{
									$ents[$ent][$this->survey->getNextTable($this->name, true)->name . "Entries"] = $arr["count"];
								}
							}
							}catch(Exception $e) { print $e->getMessage() ; }
						}
					}
				}

			
				$count = 0;
				$res = $db->do_query($sql2);
				if($res !== true) return $res;
				while($arr = $db->get_row_array())
				{
					$count = $arr["ttl"];
				}
				return array("count" => $count, $this->name => array_values($ents)); // we want a pure array not an assocciative array
			}
			else
			{
				return $res;
			}
		}
		
		public function update()
		{
			global $db;// = new dbConnection();
			
			$db->beginTransaction();
			$sql = "UPDATE form set version = {$this->version}, name = '{$this->name}', keyField = '{$this->key}', isMain = " . ($this->isMain ? 1 : 0) . ", `group` = " . $db->numVal($this->group)  . " WHERE project = {$this->survey->id} AND table_num = {$this->number};";
			$res = $db->do_query($sql);
			if($res !== true){
				$db->rollbackTransaction();
				return $res;
			}
			
			$sql = "UPDATE field set formName = '{$this->name}', active = 0 WHERE projectName = '{$this->survey->name}' AND form = {$this->id}";
			$res = $db->do_query($sql);
			if($res !== true){
				$db->rollbackTransaction();
				return $res;
			}
			
			foreach($this->fields as $fld)
			{
				if($fld->idField){
					$res = $fld->update();
				}
				else
				{
					$res = $fld->addToDb();
				}
				if($res !== true){
					$db->rollbackTransaction();
					return $res;
				}
			}
			
			$db->commitTransaction();
			return $res;
		}
		
		function getSummary($args, $exact = false)
		{
			//TODO : filter summary based on $args
			global $db;
			//$qry = "SELECT count(1) as ttl, max(created) as lastCreated, max(uploaded) as uploaded, max(lastEdited) as lastEdited, count(DISTINCT deviceID) as devices, count(distinct user) as users from entry where projectName = '{$this->projectName}' and formName = '{$this->name}' Group By projectName, formName";
			
			$sql2 = "SELECT count(DISTINCT entry) as ttl FROM entryvalue WHERE projectName = '{$this->survey->name}' AND formName = '{$this->name}'";
			if(is_array($args) && count($args) > 0)
			{
				//If we have search criteria
				$sql2 .= "AND (";
				$joinClause = " ";
				$whereClause = " ";
				foreach($args as $k => $v)
				{
					$joinClause .= " JOIN entryvalue ev$k on e.idEntry = ev$k.Entry ";
					if($exact)
					{
						$whereClause .= "AND (ev$k.fieldName = '$k' AND ev$k.value Like '$v') ";
						$sql2 .= "(fieldName = '$k' AND value Like '$v') OR";
					}
					else
					{
						$whereClause .= "AND (ev$k.fieldName = '$k' AND ev$k.value Like '%$v%') ";
						$sql2 .= "(fieldName = '$k' AND value Like '%$v%') OR";
					}
				}
				$whereClause = substr($whereClause, 0, count($whereClause) - 3). ")";
				$sql2 = substr($sql2, 0, count($sql2) - 3). ");";
			}
			
			$res = $db->do_query($sql2);
			
			if($res !== true) return $res;
			$arr = array();	
			while($a = $db->get_row_array()){$arr = $a;}

			return $arr;
		}
		
		function getUsage($res = "day", $from = NULL, $to = NULL)
		{
			$formats = array(
				"hour" => "%H %d-%m-%Y",
				"day" => "%d-%m-%Y",
				"week" => "%u %Y",
				"month" => "%m-%Y" ,
				"year"  => "%Y"
			);
			
			$sql = " LEFT JOIN (SELECT formName, count(distinct user) as userTotal, count(1) as entryTotal, DATE_FORMAT(FROM_UNIXTIME(created / 1000), '{$formats[$res]}') as Date From entry  WHERE projectName = '{$this->surve->name}' GROUP BY Date, formName) b ON a.date = b.Date";
			
			$periods = array();
			
			
			//$db = new dbConnection();
			global $db; 
			$db->do_query($sql);
		}
		
		public function parseEntriesCSV($txt)
		{
			$lines = explode("\r\n", $txt);
			
			// assumes that the first line is the header
			$lines[0] = trim($lines[0], ",");
			$headers = explode(",", $lines[0]);
			
			for($i = count($headers); $i--;)
			{
				$headers[$i] = trim($headers[$i]);
			}
			
			$len = count($lines);
			
			for ($i = $len; --$i;)
			{
				if(preg_match("/^,*$/", trim($lines[$i]))) continue;
				$lines[$i] = trim($lines[$i], ",");
				$vals = explode(",", $lines[$i]);

				$entry = new EcEntry($this);
				
				$vlen = count($vals);

				for($j = $vlen; $j--;)
				{
					if($headers[$j] == "") continue;
					if($vals[$j] != "") $entry->values[$headers[$j]] = trim($vals[$j]);
				}

				$res = $entry->post();
				if($res !== true) return $res;
				
			}
			return true;
		}
		
		public function parseEntries($xml) //recieves a table XMLSimpleElement
		{
			
			$res = true;
			for($i = 0; $i <  count($xml->entry); $i++)
			{
				$ent = $xml->entry[$i];
				$entry = new EcEntry($this);
				$entry->deviceId = (string)$ent->ecPhoneID;
				$entry->created = (string)$ent->ecTimeCreated;
				//$entry->form = $this->id;
				$entry->project = $this->project;
				$entry->values = array();
				
				foreach($this->fields as $key => $fld){
					//if($val->getName() != "ecPhoneID" && $val->getName() != "ecTimeCreated" && $this->fields[$val->getName()]->type != 'gps')
					 //  $entry->values[$val->getName()] = (string)$val;
					//elseif($this->fields[$val->getName()]->type != 'gps')
					//{}
					if($fld->type == 'gps' || $fld->type == 'location')
					{
						$lat = "{$key}_lat";
						$lon = "{$key}_lon";
						$alt = "{$key}_alt";
						$acc = "{$key}_acc";
						$src = "{$key}_provider";
						
						$entry->values[$key] = array(
							'latitude' => (string)$ent->$lat,
							'longitude' => (string)$ent->$lon,
							'altitude' => (string)$ent->$alt,
							'accuracy' => (string) $ent->$acc, 
							'provider' => (string)$ent->$src
						);
					}
					else
					{
						$entry->values[$key] = (string)$ent->$key;
					}
				;	
					
				}
				
				//TODO: need to check field names in the xml against fields in the form, and possibly
				//alert users to form version errors.
				
				$res = $entry->post();
				if($res !== true) return $res;
			}
			return $res;
		}
		
		public function autoComplete($field, $val)
		{
			global $db;
			
			$fld = "";
			if(array_key_exists($field, $this->fields))
			{
				$qry = "SELECT DISTINCT value FROM entryValue WHERE projectName = '{$this->projectName}' AND formName = '{$this->name}' AND fieldName = '$field' AND Value LIKE '$val%'";
				$fld = "value";
			}
			else
			{
				$qry = "SELECT DISTINCT $field FROM entry WHERE projectName = '{$this->projectName}' AND formName = '{$this->name}' AND $field LIKE '$val%'";
				$fld = $field;
			}
			
			$res = $db->do_query($qry);
			if($res !== true) return "[]";
			
			$result = "[";
			while($arr = $db->get_row_array())
			{
				if($result != "[") $result .= ",";
				if($this->fields[$field]->type == "location" || $this->fields[$field]->type == "gps")
				{
					$result .= "{$arr[$fld]}";
				}
				else
				{
					$result .= "\"{$arr[$fld]}\"";
				}
			}
			
			$result .= "]";
			return $result;
		}
		
	}
?>