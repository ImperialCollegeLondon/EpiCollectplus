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
		public $titleFields = array();
		public $branchfields = array();
		
		static $STATIC_FIELDS = array('created', 'lastEdited', 'uploaded', 'DeviceId', 'id'); //list of fields that are relevant to all entries, and so are in the entry table
		static $DECODE_OP_SQL = array(
                     'eq' => '=', 
                     'lt' => '<', 
                     'gt' => '>',
                     'lte' => '<=',
                     'gte' => '>=',
                     'not' => '<>',
                     'like' => 'LIKE'/*,
                     'in',
                     'notin'*/
                );


                /**
		 * An associative array where the keys are the properties in the JSON object and the values are the column head suffixes
		 * 
		 * @var array
		 */
		static $GPS_FIELDS = array("latitude" => "_lat","longitude" => "_lon", "altitude" => "_alt","accuracy" => "_acc","provider" => "_provider","bearing" => "_bearing");
		
		/**
		 * 
		 * @param string $s
		 */
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
		
                public static function formatCreated($val)
                {
                     $created = new DateTime();
                     $created->setTimestamp(intval($val));
                     return $created->format('Y-m-d H:i:s');
                }
                
                public static function unformatCreated($val)
                {
                     $created = DateTime::createFromFormat('Y-m-d H:i:s', $val);
                     return $created->getTimestamp();
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
		
		public function getBranchForms()
		{
			$forms = array();
			for( $i = 0; $i < count($this->branches); $i++ )
			{
				array_push($forms, $this->survey->tables[$this->branches[$i]]);
			}
			return $forms;
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
			$this->titleFields = array();
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
			
			$qry = "SELECT f.idField as idField, f.key, f.name, f.label, ft.name as type, f.required, f.jump, f.isinteger as isInt, f.isDouble, f.title, f.regex, f.doubleEntry, f.search, f.group_form, f.branch_form, f.display, f.genkey, f.date, f.time, f.setDate, f.setTime, f.min, f.max, f.crumb, f.`match`, f.active, f.defaultValue, f.otherFieldProperties, f.upperCase, f.position FROM field f LEFT JOIN fieldtype ft on ft.idFieldType = f.type WHERE ";
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
					$fld->otherAttributes = json_decode($arr['otherFieldProperties']);
					$this->fields[$fld->name] = $fld;
					if($fld->key) $this->key = $fld->name;
					if($fld->title && $fld->active) array_push($this->titleFields, $fld->name);
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
						array_push($fld->options, $opt);
					}
					if($fld->type == "branch"){
						array_push($this->branches, $fld->branch_form);
						array_push($this->branchfields, $fld->name);
					}
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
			
			$this->titleFields = array();
			//parse elements
			$p = 0;
			$keyFieldParsed = false;
			
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
					
					if($fld->name == $this->key) $keyFieldParsed = true;
					
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
					
					if($fld->type == "branch")
					{
						if(!$keyFieldParsed)
						{
							throw new Exception(sprintf('The key field "%s" must be positioned before the branch form "%s" ', $this->key, $fld->name));
						}
						array_push($this->branches, $fld->branch_form);
						array_push($this->branchfields, $fld->name);
					}
					if($fld->title) array_push($this->titleFields, $fld->name);
					++$p;
				
				}
				
			}
			
			foreach($this->fields as $name => $field)
			{
				if($field->name == '' || !$field->name)
				{
					unset($this->fields[$name]);
				}
			}
			
			if( !array_key_exists($this->key, $this->fields) && $this->number > 0 ) throw new Exception("The form {$this->name} does not contain the field {$this->key} which was specified as the primary key.");
			
			if( array_key_exists($this->key, $this->fields) ) $this->fields[$this->key]->key = true;
		}
		
		
		/**
		 * Function to run a query on the server and get the handle for the resultset.
		 * @author Chris I Powell
		 * @return mysqli_result
		 * 
		 */
		public function ask($args = false, $offset = 0, $limit = 0, $sortField = 'created', $sortDir = 'asc', $exact = false, $format = 'object', $includeChildCount = true)
		{
			global $db;
			
			if(!$sortField) $sortField = 'created';
			if(!$sortDir) $sortDir = 'asc';
			$qry = '';
			
			$fields = '\'' . implode('\',\'', array_keys($this->fields)) . '\'';
			
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
				$select = 'SELECT CONCAT_WS (\'","\', e.idEntry, e.DeviceID, e.created, IFNULL(e.lastEdited, \'\'),e.uploaded, GROUP_CONCAT(IFNULL(ev.value,\'\') ORDER BY ev.field  SEPARATOR \'","\') ';
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
				foreach($args as $k_sep => $v)
				{
                                    $op = 'eq';
                                        /*
                                         * Search Operators (op::fieldname)
                                         * 
                                         * eq
                                         * lt - less than
                                         * gt - greather than
                                         * lte - less than or equal to
                                         * gte - greater than or equal to
                                         * not - not
                                         * like - like
                                         * in
                                         * notin
                                         */
                                        
                                    $seppos = strpos($k_sep, '::');
                                    
                                    //if the separator is in there then remove the seperator to get the key
                                    $k = $k_sep;
                                    
                                    if($seppos !== false){
                                        $k = substr($k_sep, $seppos >= 0 ? $seppos + 2 : 0);
                                        $op = substr($k_sep, 0, $seppos);
                                    }
                                    
                                    $s_k = str_replace('.', '_', $k);
                                        
                                    if( $v == '' ) continue;
                                    if( array_key_exists($k, $this->fields) && $this->fields[$k]->type != "" )
                                    {
                                        
                                            $join .= sprintf(' LEFT JOIN entryvalue `ev%s` on e.idEntry = `ev%s`.entry AND `ev%s`.projectName = \'%s\' AND `ev%s`.formName = \'%s\' AND `ev%s`.fieldName = \'%s\'', $s_k, $s_k, $s_k, $this->projectName, $s_k, $this->name, $s_k, $k);
                                            if( $exact === true )
                                            {
                                                    
                                                    $where .= sprintf(' AND `ev%s`.value ' . EcTable::$DECODE_OP_SQL[$op] . ' \'%s\'', $s_k, $v);
                                            }
                                            else
                                            {
                                                    $where .= sprintf(' AND `ev%s`.value Like \'%s\'', $s_k, $v);
                                            }
                                    }
                                    elseif(array_search($k, EcTable::$STATIC_FIELDS) !== false)
                                    {
                                        $where .= sprintf(' AND `e`.%s ' . EcTable::$DECODE_OP_SQL[$op] . ' \'%s\'', $s_k, $v);
                                    }
                                    elseif($k == 'modified')
                                    {
                                         $where .= sprintf(' AND (`e`.uploaded ' . EcTable::$DECODE_OP_SQL[$op] . ' \'%s\' OR `e`.lastEdited ' . EcTable::$DECODE_OP_SQL[$op] . ' \'%s\')', $v, $v);
                                    }
				}
                                
			}
			
			if(!strstr($join, sprintf('ev%s', $this->key)))
			{
				$k = $this->key;
				$s_k = str_replace('.', '_', $k);
					
				$join = sprintf('%s LEFT JOIN entryvalue `ev%s` on e.idEntry = `ev%s`.entry AND `ev%s`.projectName = \'%s\' AND `ev%s`.formName = \'%s\' AND `ev%s`.fieldName = \'%s\'', $join, $s_k, $s_k, $s_k, $this->projectName, $s_k, $this->name, $s_k, $k);
				
			}
			
			for($i = count($this->branchfields); $i-- && $includeChildCount;)
			{
				$bf =  str_replace('.', '_', $this->branchfields[$i]);
				
				if($format == 'json'){
					$select .= sprintf(' \', "%s" : \' , COUNT(distinct `ev%s_entries`.entry) ,', $bf, $this->branches[$i]);
				}elseif($format == 'xml'){
					$select .= sprintf(' \'<%s>\', COUNT(distinct `ev%s_entries`.entry), \'</%s>\',', $bf, $this->branches[$i], $bf);
				}elseif($format == 'csv'){
					$select .= sprintf(', COUNT(distinct `ev%s_entries`.entry) ', $this->branches[$i]);
				}elseif($format == 'tsv'){
					$select .= sprintf(', COUNT(distinct `ev%s_entries`.entry)  ', $this->branches[$i]);
				}elseif($format == 'kml'){
					throw new Exception ('Format not yet implemented');
				}elseif($format == 'tskv'){
					$select .= sprintf(',%s , COUNT(distinct `ev%s_entries`.entry)', $bf, $this->branches[$i]);
				}elseif($format != 'object'){
					$select .= sprintf(', COUNT(distinct `ev%s_entries`.entry) as %s_entries', $this->branches[$i], $this->branches[$i]);
				}
				
				if(!strstr($join, sprintf('ev%s', $this->key)))
				{
					$join .= sprintf(' LEFT JOIN entryvalue `ev%s` on `ev%s`.entry = e.idEntry and `ev%s`.fieldName = \'%s\'', $this->key,$this->key,$this->key,$this->key);
				}
				
				$join .= sprintf(' LEFT JOIN entryValue `ev%s_entries`  ON `ev%s`.value = `ev%s_entries`.value  AND `ev%s_entries`.projectName = \'%s\' AND `ev%s_entries`.formName = \'%s\' AND `ev%s_entries`.fieldName = \'%s\'',
						$this->branches[$i],
						$this->key, 
						$this->branches[$i],
						$this->branches[$i],
						$this->survey->name,
						$this->branches[$i], 
						$this->branches[$i], 
						$this->branches[$i], 
						$this->key);
			}
			
			$child = $this->survey->getNextTable($this->name, true);
			
 			if(!!$child && $includeChildCount)
 			{
 				$qry = sprintf('CREATE TEMPORARY TABLE IF NOT EXISTS `%s_c_entries` (entries int NOT NULL, value varchar(1000) NULL, entry int NOT NULL, PRIMARY KEY (entry)) select count(1) as entries, a.value , b.entry 
 					FROM entryvalue a, entryvalue b 
 					WHERE a.projectName = \'%s\' and a.formName =\'%s\' and a.fieldName = \'%s\' and a.value = b.value and b.formName = \'%s\' 
 					and b.fieldName = \'%s\' GROUP BY a.value, b.entry ORDER BY a.value;',
 					$child->name,
 					$this->projectName,
 					$child->name,
 					$this->key,
 					$this->name,
 					$this->key
 				);
 				
 				//$res = $db->do_query($qry);
 				//if($res !== true) die($res);
 				
 				if($format == 'json'){
 					$select .= sprintf(' \', "%s_entries" : \' , IFNULL(`%s_c_entries`.`entries`, 0)  ,', $child->name, $child->name);
 				}elseif($format == 'xml'){
 					$select .= sprintf(' \'<%s_entries>\',   IFNULL(`%s_c_entries`.entries, 0), \'</%s_entries>\',', $child->name, $child->name, $child->name);
 				}elseif($format == 'csv'){
 					$select .= sprintf(', IFNULL(`%s_c_entries`.entries, 0)  ', $child->name);
 				}elseif($format == 'tsv'){
 					$select .=sprintf( ', IFNULL(`%s_c_entries`.entries, 0) ', $child->name);
 				}elseif($format == 'kml'){
 					throw new Exception ('Format not yet implemented');
 				}elseif($format == 'tskv'){
 					$select .= sprintf(',`%s_entries `,  IFNULL(`%s_c_entries`.`entries`, 0)', $child->name, $child->name);
 				}elseif($format == 'object'){
 					$select .= sprintf(', IFNULL(`%s_c_entries`.`entries`, 0) as `%s_entries`', $child->name, $child->name);
 				}
 				
 				if(!strstr($join, sprintf('ev%s', $this->key)))
				{
					$join .= sprintf(' LEFT JOIN `%s_c_entries` on `%s_c_entries`.entry = e.idEntry',  $child->name, $child->name);
				}
				
				if(!strstr($join, sprintf('ev%s', $child->name)))
 				{
 					$join .= sprintf(' LEFT JOIN `%s_c_entries` on `%s_c_entries`.entry = e.idEntry',  $child->name, $child->name);
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
 				$s_sortField = str_replace('.', '_', $sortField);
 				$join .= sprintf(' LEFT JOIN entryvalue `ev%s` on `ev%s`.entry = e.idEntry and `ev%s`.fieldName = \'%s\'', $s_sortField, $s_sortField, $s_sortField, $sortField);
 			}
			
			if($sortIsField)
			{
				$order = sprintf(' ORDER BY `ev%s`.value %s', str_replace('.', '_', $sortField), $sortDir);
			}
			elseif($child && $sortField == $child->name . '_entries')
			{
				$order = sprintf(' ORDER BY `%s_c_entries`.entries %s', str_replace('.', '_', $child->name), $sortDir);
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
			
			
			$qry = sprintf('%s %s %s %s AND ev.fieldName in (%s) %s %s %s ', $qry, $select, $join, $where, $fields, $group, $order, $limit_s);
			//echo $qry;
			//return;
			unset($select, $join, $where, $group, $order, $limit_s);
			
			$res = $db->do_multi_query($qry);
			if($res !== true) return $res;
			
			return $db->getLastResultSet();
				
		}
		
		function checkExists($keyValue)
		{
			global $db;
			$sql = sprintf('SELECT entry, count(idEntryValue) AS cnt FROM entryvalue WHERE projectName = \'%s\' AND formName = \'%s\' AND fieldName= \'%s\' AND value = \'%s\' COLLATE utf8_bin', $this->projectName, $this->name, $this->key, $keyValue);
			
			$res = $db->do_query($sql);
			$count = 0;
			while($arr = $db->get_row_array()){ $count = intval($arr['entry']); }
			return $count;			
		}
		
		public function recieve($n = 1, $full_urls = false)
		{
			global $db, $SITE_ROOT;
			$ret = array();
			
			for($i = -1; ($n > ++$i) && ($arr = $db->get_row_array()) ; )
			{
				$vals = explode('~~', $arr['data']);
                               // $arr['created'] = EcTable::formatCreated($arr['created']);
				unset($arr['data']);
				for($j = count($vals); $j--;)
				{
					$kv =explode('::', $vals[$j]);
					if(count($kv) > 1 && array_key_exists($kv[0], $this->fields)) 
					{
						if($this->fields[$kv[0]]->valueIsObject())
						{
							$arr[$kv[0]] = json_decode($kv[1], true);
						}
						elseif($full_urls && $this->fields[$kv[0]]->type == "photo" && $kv[1] != '')
						{
							$arr[$kv[0]] = sprintf('http://%s/%s%s/%s/__getImage?img=%s', $_SERVER['HTTP_HOST'], trim($SITE_ROOT, '/') . '/', $this->name, $this->projectName, $kv[1]);
						}
						elseif ($full_urls && $this->fields[$kv[0]]->valueIsFile() && $kv[1] != '')
						{
							$arr[$kv[0]] = makeUrl($this->survey->name . '~' . $kv[1]);
						}
                                                else
						{
                                                    $arr[$kv[0]] = $kv[1];
						}
					}
				} 
				array_push($ret, $arr);	
			}
			
			return $ret;
		}
		
		public function update()
		{
			global $db;// = new dbConnection();
			
			$db->beginTransaction();
			$sql = "UPDATE form set version = {$this->version}, name = '{$this->name}', keyField = '{$this->key}', table_num = {$this->number}, isMain = " . ($this->isMain ? 1 : 0) . ", `group` = " . $db->numVal($this->group)  . " WHERE project = {$this->survey->id} AND idForm = " . $this->id;
			
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
				else if($fld->name)
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
		
		/**
		 * Retrieves the number of records that match a certain criteria
		 */
		function getSummary($args, $exact = true)
		{
			//TODO : filter summary based on $args
			global $db;
			//$qry = "SELECT count(1) as ttl, max(created) as lastCreated, max(uploaded) as uploaded, max(lastEdited) as lastEdited, count(DISTINCT deviceID) as devices, count(distinct user) as users from entry where projectName = '{$this->projectName}' and formName = '{$this->name}' Group By projectName, formName";
			if(array_key_exists('prevForm', $args)) unset($args['prevForm']);
			$sql2 = "SELECT count(a.entry) as ttl from (SELECT entry, count(value) as count FROM entryvalue WHERE projectName = '{$this->survey->name}' AND formName = '{$this->name}'";
			if(is_array($args) && count($args) > 0)
			{
				//If we have search criteria
				$sql2 .= "AND (";
				
				foreach($args as $k => $v)
				{
					if($exact)
					{
						$sql2 .= "(fieldName = '$k' AND value Like '$v') OR";
					}
					else
					{
						$sql2 .= "(fieldName = '$k' AND value Like '%$v%') OR";
					}
				}
				
				$sql2 = substr($sql2, 0, count($sql2) - 3). ") GROUP BY entry) a where a.count = " . count($args);
			}
			else
			{
				$sql2 = "SELECT COUNT(idEntry) as ttl from entry WHERE projectName = '{$this->survey->name}' AND formName = '{$this->name}'";
			}

			$res = $db->do_query($sql2);
			
			if($res !== true) throw new Exception($res);
			$arr = array();	
			while($a = $db->get_row_array()){$arr = $a;}

			return $arr;
		}
                
                function getLastActivity()
                {
                    global $db;
                    
                    $sql = "SELECT max(created) as lastCreated, max(uploaded) as uploaded, max(lastEdited) as lastEdited from  entry where projectName = '{$this->projectName}' and formName = '{$this->name}' Group By projectName, formName";
                    
                    $res = $db->do_query($sql);
                    if($res !== true) throw new Exception($res);
                    
                    $dict = array();
                    
                    for($arr = $db->get_row_array(); $arr; $arr = $db->get_row_array())
                    {
                        //converted values to dates
                        $created = new DateTime();
                        $created->setTimestamp(intval($arr['lastCreated']));
                        
                        $dict['created'] = $created->format('Y-m-d H:i:s');
                        $dict['edited'] = $arr['lastEdited'];
                        $dict['uploaded'] = $arr['uploaded'];
                        
                        
                    }
                    return $dict;
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

				$ttl = count($fields);
				
				for($f = 0; $f < $ttl; ++$f)
				{
					if( $this->fields[$fields[$f]]->type == 'location' || $this->fields[$fields[$f]]->type == 'gps' )
					{
						$lat = sprintf('%s_lattitude', $fields[$f]);
						$lon = sprintf('%s_longitude', $fields[$f]);
						$alt = sprintf('%s_alttitude', $fields[$f]);
						$acc = sprintf('%s_accuarcy', $fields[$f]);
						$src = sprintf('%s_provider', $fields[$f]);
						$bearing = sprintf('%s_bearing', $fields[$f]);
						
						$entry->values[$fields[$f]] = array(
							'latitude' => getValIfExists($ent, $lat),
							'longitude' => getValIfExists($ent, $lon),
							'altitude' => getValIfExists($ent, $alt),
							'accuracy' => getValIfExists($ent, $acc),
							'provider' => getValIfExists($ent, $src),
							'bearing' => getValIfExists($ent, $bearing)
						);
					}
					elseif ( ( $this->fields[$fields[$f]]->type == "photo" || $this->fields[$fields[$f]]->type == "video" || $this->fields[$fields[$f]]->type == "audio" ) 
							&& preg_match('/^https?:\/\//', $ent[$fields[$f]]) )
					{
						$newfn = sprintf('%s_%s_%s', $this->projectName, $this->name, $ent[$this->key]);
						$entry->values[$fields[$f]] = str_replace('~tn', '', $newfn);
						
						/*$mqueue->writeMessage('getFile', array($ent[$fields[$f]], $newfn));*/
					}
					else
					{	
						if(array_key_exists($fields[$f], $ent))
						{
							$entry->values[$fields[$f]] = $ent[$fields[$f]];
						}
					}
				}
				
				
				if( !preg_match('/^[0-9]+$/', $entry->created) )
				{
					$date = false;
					try{
						$date = new DateTime($entry->created, new DateTimeZone('UTC'));
					}
					catch(Exception $ex){
						$date = new DateTime('now', new DateTimeZone('UTC'));
					}
					$entry->created = $date->getTimestamp();
				}
				
				$entry->deviceId = 'web upload';
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
				$entry->project = $this->project;
				$entry->values = array();
				
				foreach($this->fields as $key => $fld){
			
					if($fld->type == 'gps' || $fld->type == 'location')
					{
						$lat = "{$key}_lat";
						$lon = "{$key}_lon";
						$alt = "{$key}_alt";
						$acc = "{$key}_acc";
						$src = "{$key}_provider";
						$bearing = "{$key}_bearing";
						
						$entry->values[$key] = array(
							'latitude' => (string)$ent->$lat,
							'longitude' => (string)$ent->$lon,
							'altitude' => (string)$ent->$alt,
							'accuracy' => (string) $ent->$acc, 
							'provider' => (string)$ent->$src,
							'bearing' => (string)$ent->$bearing
						);
					}
					else
					{
						$entry->values[$key] = (string)$ent->$key;
					}					
				}
				
				
				if( !preg_match('/^[0-9]+$/', $entry->created) )
				{
					$date = false;
					try{
						$date = new DateTime($entry->created, new DateTimeZone('UTC'));
					}
					catch(Exception $ex){
						$date = new DateTime('now', new DateTimeZone('UTC'));
					}
					$entry->created = $date->getTimestamp();
				}
				//TODO: need to check field names in the xml against fields in the form, and possibly
				//alert users to form version errors.
				
				$res = $entry->post();
				if($res !== true) return $res;
			}
			return $res;
		}
		
		public function autoComplete($field, $val, $secondaryField = Null, $secondaryValue = Null)
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
		
		public function autoCompleteTitle($val, $secondaryField = Null, $secondaryValue = Null)
		{
			global $db;
			
			$ents = '';
			
			if($secondaryField && $secondaryValue)
			{
				$select = sprintf('SELECT entry from entryValue WHERE projectname = \'%s\' AND formName = \'%s\' AND fieldName=\'%s\' AND value=\'%s\' ', $this->projectName, $this->name, $secondaryField, $secondaryValue);
				$res = $db->do_query($select);
				if($res !== true) die($res);
				
				while($row = $db->get_row_array())
				{

					if($ents != '') $ents .= ',';
					$ents .= $row['entry'];	
				}
				
				if($ents == '') return '[]';
			}
			
			if($ents == '')
			{
				$select = sprintf('SELECT title FROM (SELECT entry, GROUP_CONCAT(IFNULL(value,\'\') ORDER BY field  SEPARATOR \', \') as title FROM entryValue where projectname = \'%s\' AND formName = \'%s\' and fieldName IN (\'%s\') GROUP BY entry) a where title like \'%s%%\'' , $this->projectName, $this->name, implode('\',\'', $this->titleFields), $val );
			}
			else
			{
				$select = sprintf('SELECT title FROM (SELECT entry, GROUP_CONCAT(IFNULL(value,\'\') ORDER BY field  SEPARATOR \', \') as title FROM entryValue where projectname = \'%s\' AND formName = \'%s\' and fieldName IN (\'%s\') and entry in(%s) GROUP BY entry) a where title like \'%s%%\'' , $this->projectName, $this->name, implode('\',\'', $this->titleFields), $ents, $val );
			}
			$res = $db->do_query($select);
			if($res === true)
			{
				$res = "[";
				while($row = $db->get_row_array())
				{
					$res .= sprintf('"%s",', $row['title']);
				}
				if(strlen($res) > 1) $res = substr($res, 0, -1);
				$res .= "]";
			}
			else {
				die($res);
			}
			return $res;							
		}
		
		
		/**
		 * @author Chris I Powell
		 * 
		 * @param string $val
		 * @param string $secondaryField
		 * @param string $secondaryValue
		 * @return array 
		 * 
		 * Altered, to account for the fact that commas break it!
		 */
		public function validateTitle($val, $secondaryField = Null, $secondaryValue = Null)
		{			
			$output = array('valid' => false);
			
			global $db;
			
			$ents = '';
			
			if($secondaryField && $secondaryValue)
			{
				$select = sprintf('SELECT entry from entryValue WHERE projectname = \'%s\' AND formName = \'%s\' AND fieldName=\'%s\' AND value=\'%s\' ', $this->projectName, $this->name, $secondaryField, $secondaryValue);
				$res = $db->do_query($select);
				if($res !== true) die($res);
				
				while($row = $db->get_row_array())
				{

					if($ents != '') $ents .= ',';
					$ents .= $row['entry'];	
				}
				
				if($ents == '') return '[]';
			}
			
			if($ents == '')
			{
				$select = sprintf('SELECT title FROM (SELECT entry, GROUP_CONCAT(IFNULL(value,\'\') ORDER BY field  SEPARATOR \', \') as title FROM entryValue where projectname = \'%s\' AND formName = \'%s\' and fieldName IN (\'%s\') GROUP BY entry) a where title like \'%s\'' , $this->projectName, $this->name, implode('\',\'', $this->titleFields), $db->escapeArg($val) );
			}
			else
			{
				$select = sprintf('SELECT title FROM (SELECT entry, GROUP_CONCAT(IFNULL(value,\'\') ORDER BY field  SEPARATOR \', \') as title FROM entryValue where projectname = \'%s\' AND formName = \'%s\' and fieldName IN (\'%s\') and entry in(%s) GROUP BY entry) a where title = \'%s\'' , $this->projectName, $this->name, implode('\',\'', $this->titleFields), $ents, $db->escapeArg($val) );
			}
			
			$res = $db->do_query($select);
			if($res === true)
			{
				while($row = $db->get_row_array())
				{
					
					if($row['title'] == $val)
					{
						$output = array('valid' => true);
						break;
					}	
				}
			}
			else {
				die($res);
			}
			
			return $output;
		}
		
		public function getTitleFromKey($key)
		{
			$args = array( $this->key => $key );

			$tstr = '';
			$cnt = count($this->titleFields);
			if($cnt == 0 || ($cnt == 1 && $this->key == $this->titleFields[0])) return $key;
			
			$req = $this->ask($args, 0, 0, 'created', 'asc', true, 'object', false);
			while($obj = $this->recieve())
			{
				for($i = 0; $i < $cnt; $i++)
				{
					if($i > 0) $tstr .= ", ";
					if(array_key_exists($this->titleFields[$i], $obj))
					{
						$tstr .= $obj[$this->titleFields[$i]];
					}
				}
				if($tstr == '') $tstr = $obj[$this->key]; 
			}
			return $tstr;
		}
		
	}
?>