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
		
		public $branchfields = array();
		
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
				++$i;
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
			
			$qry = "SELECT f.idField as idField, f.key, f.name, f.label, ft.name as type, f.required, f.jump, f.isinteger as isInt, f.isDouble, f.title, f.regex, f.doubleEntry, f.search, f.group_form, f.branch_form, f.display, f.genkey, f.date, f.time, f.setDate, f.setTime, f.min, f.max, f.crumb, f.`match`, f.active, f.defaultValue FROM field f LEFT JOIN fieldtype ft on ft.idFieldType = f.type WHERE ";
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
					if(!$fld->idField) continue;
					$res = $db->do_query("SELECT `index`, `label`, `value` FROM `option` WHERE `field` = {$fld->idField}"); //$db2->exec_sp("getOptions", array($fld->idField));
					if($res !== true) die($res);
					while($arr = $db->get_row_array())
					{
						$opt = new EcOption();
						$opt->fromArray($arr);
						$opt->idx = $arr["index"];
						$fld->options[$opt->value] = $opt;
					}
					if($fld->type == "branch"){
						array_push($this->branches, $fld->branch_form);
						array_push($this->branchfields, $fld->name);
					}
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
					if($fld->type == "branch"){
						array_push($this->branches, $fld->branch_form);
						array_push($this->branchfields, $fld->name);
					}
					++$p;
				
				}
				
			}
			
			if(!array_key_exists($this->key, $this->fields)) throw new Exception("The form {$this->name} does not contain the field {$this->key} which was specified as the primary key.");
			
			$this->fields[$this->key]->key = true;
		}
		
		public function ask($args = false, $offset = 0, $limit = 0, $sortField = 'created', $sortDir = 'asc', $exact = false, $format = 'object', $includeChildCount = false)
		{
			global $db;
			
			if(!$sortField) $sortField = 'created';
			if(!$sortDir) $sortDir = 'asc';
			
			//$db = new dbConnection();
			/*
			 * with fields being pulled from the database and concatinated at that point it makes sense to concatinate them in such a way that post-processing isn't required to
			 * puth the values into the appropriate format.
			 * 
			 * The format of the request therefore needs to be stored by ask() so that recieve() know's what it's outputting.
			 */
			$this->lastRequestFormat = $format;
			
			if($format == 'object')
			{
				$select = 'SELECT e.idEntry as id, e.DeviceID, e.created, e.lastEdited, e.uploaded, GROUP_CONCAT( CONCAT_WS(\'::\', ev.fieldName, ev.value) ORDER BY ev.field SEPARATOR \'~~\') as data ';
			}elseif($format == 'json'){
				$select = 'SELECT CONCAT (\'{\"id\" : \', e.idEntry, \', \"DeviceID\": \"\', e.DeviceID, \'\",\"created\" : \', e.created, \' , \"lastEdited\":\"\', IFNULL(e.lastEdited, \'\'),\'\" , \"uploaded\":\"\', e.uploaded, \'\",\' , GROUP_CONCAT( CONCAT(\'\"\', ev.fieldName, \'\" : \"\', IFNULL(ev.value, \'\'), \'\"\') ORDER BY ev.field  SEPARATOR \',\'),  ';
			}elseif($format == 'xml'){
				$select = 'SELECT CONCAT (\'<entry><id>\', e.idEntry, \'</id><DeviceID>\', e.DeviceID, \'</DeviceID><created>\', e.created, \'</created><lastEdited>\', IFNULL(e.lastEdited, \'\'),\'</lastEdited><uploaded>\', e.uploaded, \'</uploaded>\' , GROUP_CONCAT( CONCAT(\'<\', ev.fieldName, \'>\', REPLACE(REPLACE(ev.value, \'\<\', \'&lt;\'), \'\>\', \'&gt;\'), \'</\', ev.fieldName, \'>\') ORDER BY ev.field  SEPARATOR \'\'),';
			}elseif($format == 'csv'){
				$select = 'SELECT CONCAT_WS (\',\', e.idEntry, e.DeviceID, e.created, IFNULL(e.lastEdited, \'\'),e.uploaded, GROUP_CONCAT(IFNULL(ev.value,\'\') ORDER BY ev.field  SEPARATOR \',\') ';
			}elseif($format == 'tsv'){
				$select = 'SELECT CONCAT_WS (\'\t\', e.idEntry, e.DeviceID, e.created, IFNULL(e.lastEdited, \'\'),e.uploaded, GROUP_CONCAT(IFNULL(ev.value,\'\') ORDER BY ev.field  SEPARATOR \'\t\')';
			}elseif($format == 'kml'){
				throw new Exception ('Format not yet implemented');
			}elseif($format == 'tskv'){
				$select = 'SELECT CONCAT_WS (\'\t\',\'id\', e.idEntry, \'DeviceID\', e.DeviceID, \'created\', e.created, \'lastEdited\',IFNULL(e.lastEdited, \'\'),\'uploaded\',e.uploaded, GROUP_CONCAT(CONCAT_WS(\'\t\',ev.fieldName, ev.value) ORDER BY ev.field SEPARATOR \'\t\') ';
			}else{
				throw new Exception ('Format not specified');
			}
			
			$group = ' GROUP BY e.idEntry, e.DeviceID, e.created, e.lastEdited, e.uploaded ';
			$join = sprintf('FROM entryvalue ev JOIN entry e ON e.idEntry = ev.entry');
			if(count($this->branchfields))
			{
				$where = sprintf(' WHERE ev.fieldName NOT IN (\'%s\') and e.projectName = \'%s\' AND e.formName = \'%s\' ', implode('\',\'', $this->branchfields), $this->survey->name, $this->name);
			}
			else
			{
				$where = sprintf(' WHERE e.projectName = \'%s\' AND e.formName = \'%s\' ', $this->survey->name, $this->name);
			}
			
			if($args)
			{
				foreach($args as $k => $v)
				{
					if(array_key_exists($k, $this->fields) && $this->fields[$k]->type != "")
					{
						$join .= sprintf(' LEFT JOIN entryvalue ev%s on e.idEntry = ev%s.entry', $k, $k);
						if($exact)
						{
							$where .= sprintf(' AND ev%s.value = \'%s\'', $k, $v);
						}
						else
						{
							$where .= sprintf(' AND ev%s.value Like \'%s\'', $k, $v);
						}
					}
				}
			}
			
			for($i = count($this->branchfields); $includeChildCount && $i--;)
			{
				if($format == 'json'){
					$select .= sprintf(' \', \'%s\' : \' , IFNULL(ev%s_entries.count, 0) ,', $this->branchfields[$i], $this->branches[$i]);
				}elseif($format == 'xml'){
					$select .= sprintf(' \'<%s>\', IFNULL(ev%s_entries.count, 0), \'</%s>\',', $this->branchfields[$i], $this->branches[$i], $this->branchfields[$i]);
				}elseif($format == 'csv'){
					$select .= sprintf(', IFNULL(ev%s_entries.count, 0) ', $this->branches[$i]);
				}elseif($format == 'tsv'){
					$select .= sprintf(', IFNULL(ev%s_entries.count, 0)  ', $this->branches[$i]);
				}elseif($format == 'kml'){
					throw new Exception ('Format not yet implemented');
				}elseif($format == 'tskv'){
					$select .= sprintf(',%s , IFNULL(ev%s_entries.count, 0)', $this->branchfields[$i], $this->branches[$i]);
				}elseif($format != 'object'){
					//throw new Exception ('Format not specified');
				}
				
				if(!strstr($join, sprintf('ev%s', $this->key)))
				{
					$join .= sprintf(' LEFT JOIN entryvalue ev%s on ev%s.entry = e.idEntry and ev%s.fieldName = \'%s\'', $this->key,$this->key,$this->key,$this->key);
				}
				$join .= sprintf(' LEFT JOIN (select count(distinct entry) as count, value from entryValue where projectName = \'%s\' AND formName = \'%s\' AND fieldName = \'%s\' group by value) ev%s_entries ON ev%s.value = ev%s_entries.value', 
								$this->survey->name,$this->branches[$i], $this->key, $this->branches[$i], $this->key, $this->branches[$i]);
			}
			
 			if($includeChildCount && $this->survey->getNextTable($this->name, true))
 			{
 				$child = $this->survey->getNextTable($this->name, true);
 				//$select .= ', COUNT(ev{$child->name}_entries.value) as {$child->name}_entries ';
 				
 				if($format == 'json'){
 					$select .= sprintf(' \', "%s_entries" : \' , IFNULL(ev%s_entries.count,0)  ,', $child->name, $child->name);
 				}elseif($format == 'xml'){
 					$select .= sprintf(' \'<%s_entries>\', IFNULL(ev%s_entries.count,0), \'</%s_entries>\',', $child->name, $child->name, $child->name);
 				}elseif($format == 'csv'){
 					$select .= sprintf(',   IFNULL(ev%s_entries.count,0)  ', $child->name);
 				}elseif($format == 'tsv'){
 					$select .=sprintf( ',   IFNULL(ev%s_entries.count,0)  ', $child->name);
 				}elseif($format == 'kml'){
 					throw new Exception ('Format not yet implemented');
 				}elseif($format == 'tskv'){
 					$select .= sprintf(',%s_entries , IFNULL(ev%s_entries.count,0)', $child->name, $child->name);
 				}elseif($format == 'object'){
 					//throw new Exception ('Format not specified');
 				}
 				
 				
 				if(!strstr($join, sprintf('ev%s', $this->key)))
				{
					$join .= sprintf(' LEFT JOIN entryvalue ev%s on ev%s.entry = e.idEntry and ev%s.fieldName = \'%s\'', $this->key, $this->key, $this->key, $this->key);
				}
				if($includeChildCount)
				{
					$join .= sprintf(' LEFT JOIN (select count(entry) as count, value from entryValue where projectName = \'%s\' AND formName = \'%s\' AND fieldName = \'%s\' group by value) ev%s_entries  ON ev%s.value = ev%s_entries.value'
					,$this->survey->name, $child->name, $this->key, $child->name, $this->key, $child->name);
				}
 			}
 			
 			if($format == 'json'){
 				$select .= ' \'}\') as `data` ';
 			}elseif($format == 'xml'){
 				$select .= ' \'</entry>\') as `data` ';
 			}elseif($format == 'csv'){
 				$select .= ') as data ';
 			}elseif($format == 'tsv'){
 				$select .= ') as data ';
 			}elseif($format == 'kml'){
 				throw new Exception ('Format not yet implemented');
 			}elseif($format == 'tskv'){
 				$select .= ') as data ';
 			}elseif($format == 'object'){
 				//throw new Exception ("Format not specified");
 			}

 			$sortIsField = array_key_exists($sortField, $this->fields);
 			
 			if(!strstr($join, sprintf('ev%s', $sortField)) && $sortIsField)
 			{
 				$join .= sprintf(' LEFT JOIN entryvalue ev%s on ev%s.entry = e.idEntry and ev%s.fieldName = \'%s\'', $sortField, $sortField, $sortField, $sortField);
 			}
			
			if($sortIsField)
			{
				$order = sprintf(' ORDER BY ev%s.value %s', $sortField, $sortDir);
			}
			elseif($sortField)
			{
				$order = sprintf(' ORDER BY e.%s %s', $sortField, $sortDir);
			}
			unset($sortIsField);
			
			if($limit && $offset)
			{
				$limit_s = sprintf(' LIMIT %u, %u', $offset, $limit);
			}
			elseif ($limit)
			{
				$limit_s = sprintf(' LIMIT %s', $limit);	
			}
			else 
			{
				$limit_s = '';
			}
			$qry = sprintf('%s %s %s %s %s %s', $select, $join, $where, $group, $order, $limit_s);
			unset($select, $join, $where, $group, $order, $limit_s);
			//echo $qry;
			
			return $db->do_query($qry);
				
		}
		
		function checkExists($keyValue)
		{
			global $db;
			$sql = sprintf('SELECT count(idEntryValue) AS cnt FROM entryValue WHERE projectName = \'%s\' AND formName = \'%s\' AND fieldName= \'%s\' AND value = \'%s\'', $this->projectName, $this->name, $this->key, $keyValue);
			
			$res = $db->do_query($sql);
			$count = 0;
			while($arr = $db->get_row_array()){ $count += intval($arr['cnt']); }
			return $count > 0;			
		}
		
		public function recieve($n = 1)
		{
			global $db;
			$ret = null;
			
			for($i = -1; ($n > ++$i) && ($arr = $db->get_row_array()) ; )
			{
				if($this->lastRequestFormat == 'json')
				{
					//replace is neccesary for tables with GPS fields 
					$ret = str_replace(array('"{' ,'}"'), array('{','}'), $arr['data']); 
				}
				elseif($this->lastRequestFormat == 'object')
				{
					$vals = explode('~~', $arr['data']);
					unset($arr['data']);
					for($j = count($vals); $j--;)
					{
						$kv =explode('::', $vals[$j]);
						if(count($kv) > 1) $arr[$kv[0]] = $kv[1];
					
					} 
					$ret = $arr;
				}
				else
				{
					$ret = $arr["data"];
					$json_objects = array();
					preg_match_all('/\{.*\}/', $ret, $json_objects);
			
					for($j = count($json_objects); $j--;)
					{
						if(count($json_objects[$j]) == 0) continue;
						$obj = json_decode($json_objects[$j][0], true);
						$str = '';
						if($this->lastRequestFormat == 'xml')
						{
							foreach($obj as $key => $value)
							{
								$value = trim($value);
								$str .= "<$key>$value</$key>";
							}
						}
						elseif ($this->lastRequestFormat == 'csv')
						{
							$str = implode(",", array_values($obj));
						}
						elseif($this->lastRequestFormat == 'tsv')
						{
							$str = implode("\t", array_values($obj));
						}
						elseif ($this->lastRequestFormat == 'tskv')
						{
							$k = 0;
							foreach($obj as $key => $value)
							{
								$str .=  (++$k > 1 ? '\t' : '') . sprintf('%s\t%s', $key, $value);
							}
						}
						elseif($this->lastRequestFormat == 'kml')
						{
							
						}
						$ret = str_replace($json_objects[0][0], $str, $ret);
					}
				}
			}
			
			return (is_string($ret) ?  utf8_decode($ret) : $ret);
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
		
		public function parseCSVLine($line)
		{
			$line = trim($line);
			$vals = array('');
			$len = strlen($line);
			
			$curval = '';
			$inquotes = false;
			
			for($k = 0, $v = 0; $k < $len; ++$k)
			{
				if($line[$k] == ',' && !$inquotes)
				{
					++$v;
					$vals[$v] = '';
				}
				elseif($line[$k] == ',' && ($k == ($len - 1) || $line[++$k] == '"'))
				{
					++$v;
					$vals[$v] = '';
					$inquotes = true;
				}
				elseif($line[$k] == '"' && ($k == 0 || $line[$k-1] == ','))
				{
					$inquotes = true;
				}
				elseif($inquotes && $line[$k] == '"')
				{
					if($k == $len - 1 || $line[$k + 1]  == ',') $inquotes =  false;
					else
					{
 						//echo $k,' ! ' ,$len ,  '<br />', $line;
						throw new Exception(sprintf('Values cannot contain quotes (line xx position %u)', $k));
					}
				}
				else
				{
					$vals[$v] .= $line[$k];
				}
			}
			return $vals;
		}
		
		public function parseEntriesCSV($fp)
		{
			//$lines = explode("\r\n", $txt);
			// assumes that the first line is the header
			//$lines[0] = trim($lines[0], ',');
			try{
				$headers = fgetcsv($fp);//$this->parseCSVLine($lines[0]);
			}
			catch(Exception $err)
			{
				throw new Exception(str_replace('xx', '0', $err->getMessage()));
			}
			
			$vals = array();
			$ents = array();
			$fields = array_keys($this->fields);
			$x = 0;
			
			while($vals = fgetcsv($fp))
			{
				$entry = new EcEntry($this);
				$vars = array_keys(get_object_vars($entry));
				
				$varLen = count($vars);
				$ent = array_combine($headers, $vals);
				
				for($v = 0; $v < $varLen; ++$v)
				{
					if(array_key_exists($vars[$v], $ent))
					{
						$entry->$vars[$v] = $ent[$vars[$v]];
					}
				}
				
				
				
				$vlen = count($vals);
				$hlan = count($headers);

				$ttl = count($fields);
				for($f = 0; $f < $ttl; ++$f)
				{
					if($this->fields[$fields[$f]]->type == 'location' || $this->fields[$fields[$f]]->type == 'gps' )
					{
						$lat = sprintf('%s_lat', $fields[$f]);
						$lon = sprintf('%s_lon', $fields[$f]);
						$alt = sprintf('%s_alt', $fields[$f]);
						$acc = sprintf('%s_acc', $fields[$f]);
						$src = sprintf('%s_provider', $fields[$f]);
						
						$entry->values[$fields[$f]] = array(
							'latitude' => $ent[$lat],
							'longitude' => $ent[$lon],
							'altitude' => getValIfExists($ent, $alt),
							'accuracy' => getValIfExists($ent, $acc),
							'provider' => getValIfExists($ent, $src)
						);
					}
					else
					{	
						if(array_key_exists($fields[$f], $ent))
						{
							$entry->values[$fields[$f]] = $ent[$fields[$f]];
						}
					}
				}
				
				$entry->deviceId = 'web upload';
				//echo $entry->created . '<br />\r\n';
				array_push($ents, $entry);	
				
				if(++$x % 100 == 0)
				{
					$entry->postEntries($ents);
					unset($ents);
					$ents = array();
				}							
			}
			if(count($ents) > 0) $entry->postEntries($ents);
			return true;
		}
		
		public function parseEntries($xml) //recieves a table XMLSimpleElement
		{
			
			$res = true;
			for($i = 0; $i <  count($xml->entry); ++$i)
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