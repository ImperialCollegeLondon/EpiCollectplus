<?php
class EcField{
		public $idField;
		public $name = "";
		public $projectName;
		public $formName;
		public $label = "";
		public $type = "";
		public $options = array();
		public $required = false;
		public $title = false;
		//public $jumpCondition = "";
		//public $jumpTarget = "";
		public $jump = false;
		public $isInt = false;
		public $isDouble = false;
		public $language = "EN";
		public $regex = "";
		public $doubleEntry = false;
		public $local=false;
		
		public $search = false;
		public $group_form = false;
		public $branch_form = false;
		public $display = true;
		
		public $crumb = false;
		public $match = false;
		
		public $genkey= false;
		public $upperCase = false;
		
		public $position = 0;
		public $date = false;
		public $time = false;
		public $setDate = false;
		public $setTime = false;
		public $min;
		public $max;
		public $defaultValue;
		
		public $otherAttributes = array(); 
	
		public $form;
		
		public $chart = false;
		public $key = false;
		
		public $fkTable = false;
		public $fkField = false;
		
		public $active = true;
		
		public $dateTimeBlocks = array(
				"dd" => "d",
				"MM" => "m",
				"yyyy" => "Y",
				"yy" => "y",
				"HH" => "H",
				"mm" => "i",
				"ss" => "s"
		); // xml => php
		
		public $datTimeSeps = array ("/", ":", ".", "-", "|", "\\", "~", ",");
		
		public static function dtConvert($str)
		{
				$pStr = "";
				
				$a = array();
				$b = array();
				$c = 0; // max number of chunks
				$mi = -1; // index of max number of chunks;
				
				for($i = 0; $i < count($dateTimeSeps); $i++)
				{
						$a = explode($datTimeSeps[0], $str);
						$b[$i] = $a;
						if(count($a) > $c)
						{
								$c = count($a);
								$mi = $i;
						}
				}
				
				$d = $b[$mi];
				$r = "";
				
				for($i = 0; $i < $c; $i++)
				{
						$r .= $dateTimeBlocks[$d[$i]];
				}
				
				return $r;
		}
		
		public function toXML()
		{
			$xml = "\n\t\t<{$this->type} ref=\"{$this->name}\"";
			if( $this->required ) $xml .= ' required="true"';
			if( $this->isInt ) $xml .= ' integer="true"';
			if( $this->isDouble ) $xml .= ' decimal="true"';
			if( $this->regex ) $xml .= " regex=\"{$this->regex}\"";
			if( $this->title ) $xml .= ' title="true"';
			if( $this->doubleEntry ) $xml .= ' verify="true"';
			if( $this->jump ) $xml .= " jump=\"{$this->jump}\"";
			if( $this->search ) $xml .= " search=\"true\"";
			if( $this->group_form ) $xml .= " group_num=\"{$this->group_form}\"";
			if( $this->branch_form ) $xml .= " branch_form=\"{$this->branch_form}\"";
			if( !$this->display ) $xml .= " display=\"false\"";
			if( $this->genkey ) $xml .= " genkey=\"true\"";
			if( $this->upperCase ) $xml .= " uppercase=\"true\"";
			if( $this->date ) $xml .= " date=\"{$this->date}\"";
			if( $this->time ) $xml .= " time=\"{$this->time}\"";
			if( $this->setDate ) $xml .= " setdate=\"{$this->setDate}\"";
			if( $this->setTime ) $xml .= " settime=\"{$this->setTime}\"";
			if( $this->min ) $xml .= " min=\"{$this->min}\"";
			if( $this->max ) $xml .= " max=\"{$this->max}\"";
			if( $this->defaultValue ) $xml .= " default=\"{$this->defaultValue}\"";
			if( $this->crumb ) $xml .= " crumb=\"{$this->crumb}\"";
			if( $this->match ) $xml .= " match=\"{$this->match}\"";
			
			if(is_object($this->otherAttributes))
			{
				foreach( $this->otherAttributes as $att => $val ) {  $xml = sprintf('%s %s="%s"', $xml, $att, $val); }
			}
			
			$xml.= ">\n\t\t\t<label>{$this->label}</label>\n\t\t";
			foreach( $this->options as $opt )
			{
				$xml .= "\n\t\t\t\t<item>\n\t\t\t\t\t<label>{$opt->label}</label>\n\t\t\t\t\t<value>{$opt->value}</value>\n\t\t\t\t</item>";
			}
			$xml.= "</{$this->type}>";
			return $xml;
		}
		public function toJson()
		{
			$json = "\n\t\t{\"type\" : \"{$this->type}\", \"name\":\"{$this->name}\",";
			if($this->required) $json .= ' "required" :true,';
			if($this->isInt) $json .= ' "integer":true,';
			if($this->isDouble) $json .= ' "double":true,';
			if($this->regex) $json .= " \"regex\":\"{$this->regex}\",";
			if($this->title) $json .= ' "title":true,';
			if($this->doubleEntry) $json .= ' "verify":true,';
			if($this->jump) $json .= " \"jump\":\"{$this->jump}\",";
			if($this->search) $json .= " \"search\":\"true\",";
			if($this->group_form) $json .= " \"group\":\"{$this->group_form}\",";
			if($this->branch_form) $json .= " \"branch_form\":\"{$this->branch_form}\",";
			if(!$this->display) $json .= " \"display\":\"false\",";
			if($this->genkey) $json .= " \"genkey\":\"true\",";
			if($this->upperCase) $json .= " \"uppercase\":\"true\",";
			if($this->date) $json .= " \"date\":\"{$this->date}\",";
			if($this->time) $json .= " \"time\":\"{$this->time}\",";
			if($this->setDate) $json .= " \"setdate\":\"{$this->date}\",";
			if($this->setTime) $json .= " \"settime\":\"{$this->setTime}\",";
			if($this->min) $json .= " \"min\":\"{$this->min}\",";
			if($this->max) $json .= " \"max\":\"{$this->max}\",";
			if($this->crumb) $json .= " \"crumb\":\"{$this->crumb}\",";
			if($this->match) $json .= " \"crumb\":\"{$this->match}\",";
			if($this->defaultValue) $json .= " \"default\":\"{$this->defaultValue}\",";
			
			foreach( $this->otherAttributes as $att => $val ) {
				$json = sprintf('%s "%s" : "%s",', $xml, $att, $val);
			}
			
			$json.= "\n\t\t\t\"label\" : \"{$this->label}\",\n\t\t\"options\":[";
			$i =0;
			
			foreach($this->options as $opt)
			{
				$json .= ($i > 0 ? "," : "") . "\n\t\t\t\t{\n\t\t\t\t\t\"label\":\"{$opt->label}\",\n\t\t\t\t\t\"value\" : \"{$opt->value}\"\n\t\t\t}";
				$i++;
			}
			$json.= "]}";
			
			return $json;
		}
		
		public function fromArray($arr)
		{
			foreach(array_keys($arr) as $key)
			{
				//print_r($arr);
				$this->$key = $arr[$key];
			}
		}
		
		public function update()
		{
				global $db;
				//$db = new dbConnection();
//				print_r($this);
				$qry = "SELECT idFieldType FROM fieldtype where name = '{$this->type}'";
				$db->do_query ($qry);
				
				$fieldType = 1;
				
				while($arr = $db->get_row_array())
				{
					$fieldType = $arr["idFieldType"];
				}
				
				$sql = "UPDATE field SET type = {$fieldType}, name = " . $db->stringVal($this->name) .", label = " . $db->stringVal($this->label) .", language = " . $db->stringVal($this->language) .", regex = " . $db->stringVal($this->regex) .", title = " . $db->boolVal($this->title) . 
						", `key` = " . $db->boolVal($this->key) . ", isinteger= " . $db->boolVal($this->isInt) . ", isdouble= " . $db->boolVal($this->isDouble) . ", active = 1, doubleentry = " . $db->boolVal($this->doubleEntry) . ", jump= " . $db->stringVal($this->jump) . ", required = " . $db->boolVal($this->required) . ", search = " . $db->boolVal($this->search) . 
						",group_form=  " . $db->stringVal($this->group_form) . ", branch_form=  " . $db->stringVal($this->branch_form) . ", display= " . $db->boolVal2($this->display) . ", genkey = " . $db->boolVal($this->genkey) . ", upperCase = " . $db->boolVal($this->upperCase) . ", date = " . $db->stringVal($this->date) .", time = " . $db->stringVal($this->time) .
						",setdate  = " . $db->stringVal($this->setDate) .", settime  = " . $db->stringVal($this->setTime) .", position = {$this->position}, min = " . $db->numVal($this->min) . ", max = " . $db->numVal($this->max) . ", defaultValue = " . $db->stringVal($this->defaultValue) . ", active = " . $db->boolVal($this->active). 
						", otherFieldProperties = " . $db->stringVal(json_encode($this->otherAttributes)) . " WHERE idField = {$this->idField};";
				$res = $db->do_query($sql);
				if($res !== true) return $res;
				//if($db->affectedRows() == 0) return "field {$this->name} ({$this->idField}) not found -- $sql";
				
				//$this->fetchId();
				
				$sql = "DELETE FROM `option` WHERE field = {$this->idField}";
				$res = $db->do_query($sql);
				if($res !== true) return $res;
				
				$optcount = count($this->options);
				if($optcount != 0){
					$optqry = 'INSERT INTO `option` (`index`, `label`, `value`, `field`) VALUES';
					
					for($x = 0; $x < $optcount; ++$x)
					{
						$optqry = sprintf('%s%s (%s, %s, %s, %s)', 
								$optqry, 
								($x > 0 ? ',' : ''), 
								intval($this->options[$x]->idx),
								$db->stringVal($this->options[$x]->label),
								$db->stringVal($this->options[$x]->value),
								intval($this->idField));
						
						//$res = $db->exec_sp("addOption", array(
						//	$this->form->survey->name,
						//	$this->form->name,
						//	$this->name,
						//	$opt->idx,
						//	$opt->label,
						//	$opt->value
						//));
						//if($res !== true) return $res;
					}
					$res = $db->do_query($optqry);
					if($res !== true) print $res; 
					return $res;
					
				}
				return true;
		}
		
		public function addToDb()
		{
			global $db;
			if(!$db) $db = new dbConnection();
			$qry = "SELECT idFieldType FROM fieldtype where name = '{$this->type}'";
			$db->do_query ($qry);
			
			while($arr = $db->get_row_array())
			{
				$fieldType = $arr["idFieldType"];
			}
			
			$lbl = $db->escapeArg($this->label);
			
			$qry ="INSERT INTO field (form, projectName, formName, type, name, label, language, regex, title, `key`, isinteger, isdouble, active, doubleentry, jump, required, search, group_form, branch_form, display, genkey, upperCase, date, time, setdate, settime, `min`, `max`, `match`, crumb, defaultValue, position, otherFieldProperties) VALUES
								 ({$this->form->id}, '{$this->form->survey->name}', '{$this->form->name}', $fieldType, '{$this->name}','{$lbl}', '{$this->language}',";
			$qry .= ($this->regex != "" ? $db->stringVal($this->regex) . "," : "NULL,");
			$qry .= ($this->title ? "1," : "0,");
			$qry .= ($this->key ? "1," : "0,");
			$qry .= ($this->isInt ? "1," : "0,");
			$qry .= ($this->isDouble ? "1," : "0,");
			$qry .= "1,";
			$qry .= ($this->doubleEntry ? "1," : "0,");
			$qry .= ($this->jump ? "'{$this->jump}'," : "NULL,");
			$qry .= ($this->required ? "1," : "0,");
			$qry .= ($this->search ? "1," : "0,");
			$qry .= ($this->group_form ? "'{$this->group_form}'," : "NULL,");
			$qry .= ($this->branch_form ? "'{$this->branch_form}'," : "NULL,");
			$qry .= ($this->display ? "1," : "0,");
			$qry .= ($this->genkey ? "1," : "0,");
			$qry .= ($this->upperCase ? "1," : "0,");
			$qry .= ($this->date ? "'{$this->date}'," : "NULL,");
			$qry .= ($this->time ? "'{$this->time}'," : "NULL,");
			$qry .= ($this->setDate ? "'{$this->setDate}'," : "NULL,");
			$qry .= ($this->setTime ? "'{$this->setTime}'," : "NULL,");
			$qry .= ($this->min ? "{$this->min}," : "NULL,");
			$qry .= ($this->max ? "{$this->max}," : "NULL,");
			$qry .= ($this->match ? $db->stringVal($this->match) . ',' : "NULL,");
			$qry .= ($this->crumb ? "'{$this->crumb}'," : "NULL,");
			$qry .= ($this->defaultValue ? $db->stringVal($this->defaultValue). "," : "NULL,");
			$qry .= "{$this->position},";
			$qry .= $db->stringVal(json_encode($this->otherAttributes)) . ")";
			
			$res = $db->do_query($qry);
			
			if($res === true){
				$this->idField = $db->last_id();
				
				$optcount = count($this->options);
				if($optcount > 0){
					$optqry = 'INSERT INTO `option` (`index`, `label`, `value`, `field`) VALUES';
					
					//print_r($this->options);
					
					for($x = 0; $x < $optcount; ++$x)
					{
						$lab = $db->stringVal($this->options[$x]->label);
						$val = $db->stringVal($this->options[$x]->value);
						
						if($lab == 'NULL') throw new Exception (sprintf('The label for option %d of field %s cannot be null.', $x, $this->name));
						if($val == 'NULL') throw new Exception (sprintf('The value of option %d of field %s cannot be null.', $x, $this->name));
						
						$optqry = sprintf('%s%s (%s, %s, %s, %s)', 
								$optqry, 
								($x > 0 ? ',' : ''), 
								intval($this->options[$x]->idx),
								$lab,
								$val,
								intval($this->idField));
						
						//$res = $db->exec_sp("addOption", array(
						//	$this->form->survey->name,
						//	$this->form->name,
						//	$this->name,
						//	$opt->idx,
						//	$opt->label,
						//	$opt->value
						//));
						//if($res !== true) return $res;
					}
					$res = $db->do_query($optqry);
					if($res !== true) return $res;
					
				}
			}	
			//echo "$qry\n";
			return $res;
		}
		
		public function parse($xml)
		{
			$this->type = (string)$xml->getName();
			
			foreach($xml->attributes() as $name => $val)
			{
	
				switch($name)
				{
					case 'ref':
							$this->name = (string)$val;
							break;
					case 'name':
							$this->name = (string)$val;
							break;
					case 'required':
							$this->required = parseBool((string)$val);
							break;
					case 'title':
							$this->title = parseBool((string)$val);
							break;
					case 'jump':
							$this->jump = (string)$val;
							break;
					case 'integer':
							$this->isInt = parseBool((string)$val);
							break;
					case 'decimal':
							$this->isDouble = parseBool((string)$val);
							break;
					case 'regex':
							$rx = (string)$val;
							try
							{
								preg_match("/$rx/", "");
							}
							catch(Exception $e)
							{
								throw new Exception("The regex argument for the field {$this->name} in the form {$this->form->name} is not valud");
							}
							$this->regex = $rx;
							break;
					case 'verify':
							$this->doubleEntry = parseBool((string)$val);
							break;
					case 'search' :
							$this->search = parseBool((string)$val);
							break;
					case 'group_num':
							$this->group_form = (string)$val;
							break;
					case 'branch_form':
							$this->branch_form = (string)$val;
							break;
					case 'display' :
							$this->display = parseBool((string)$val);
							break;
					case 'genkey' :
							$this->genkey = parseBool((string)$val);
							break;
					case 'uppercase' :
							$this->upperCase = parseBool((string)$val);
							break;
					case 'date':
							$this->date = (string)$val;
							break;
					case 'time':
							$this->time = (string)$val;
							break;
					case 'setdate':
							$this->setDate =(string)$val;
							break;
					case 'settime':
							$this->setTime = (string)$val;
							break;
					case 'edit' :
							$this->edit = parseBool((string)$val);
							break;
					case 'min' :
							$this->min = (string)$val;
							break;
					case 'max' :
							$this->max = (string)$val;
							break;
					case 'match' :
						$this->match = (string)$val;
						break;
					case 'crumb' :
						$this->crumb = (string)$val;
						break;
					case 'default' : 
						$this->defaultValue = (string) $val;
						break;
					default : 
						$this->otherAttributes[$name] = (string) $val;
						break;
				} //end switch
				
			}//end foreach
			
			$this->options = array();
			
			foreach($xml->children() as $opt)
			{
				$oIdx = count($this->options);
				if($opt->getName() == 'label')
				{
					$this->label = (string)$opt;
				}
				else if($opt->getName() == 'item')
				{
					$this->options[$oIdx] = new EcOption();
					$this->options[$oIdx]->label = (string)$opt->label[0];
					$this->options[$oIdx]->value = (string)$opt->value[0];
					$this->options[$oIdx]->idx = $oIdx;
		
					if($this->options[$oIdx]->label == '') throw new Exception(sprintf('Option number %d for the field %s has no label, the label cannot be empty', $oIdx, $this->label)); 
					if($this->options[$oIdx]->value == '') throw new Exception(sprintf('Option number %d for the field %s has no value, the value cannot be empty', $oIdx, $this->name));
					$oIdx++;
				}
			}
			
			//check that only one of isInt, isDouble, date, time, setdate, settime, regex or match is set
			$vcheck = 0;
			$vlist = "";
				//PHP var => xml attribute
			$vtype = array("isInt" => "integer", "isDouble" => "decimal" , "date" => "date",  "time" => "time", "setDate" => "setdate", "setTime" => "settime", "regex" => "regex", "match" => "match");	
			
			foreach($vtype as $var => $att)
			{
				if($this->$var && $this->$var != "")
				{
					if($vlist != "") $vlist = "$vlist,";
					$vcheck++;
					$vlist = "$vlist $att";
				}
			}
			
			if($vcheck > 1){
				//echo $xml->asXML();
				if($vcheck == 2 && $vlist == " regex, match")
				{
					
				}
				else
				{
					throw new Exception("$vlist are all set on the field {$this->name} only one of these attributes may be set at once.");
				}
			}
			
			//check that min and max are only set for numerics
			if(($this->min || $this->max) && !($this->isInt || $this->isDouble))
			{
				throw new Exception ("Error with {$this->name}: the min and max attributes should only be set on integer or decimal fields");
			}
			
			if($this->isInt && (!preg_match("/^[0-9]*$/", $this->min) || !preg_match("/^[0-9]*$/", $this->max)))
			{
				throw new Exception ("Error with {$this->name}: the field is set as an integer, therefore min and max must both be integers");
			}
			
			if($this->isDouble && (!preg_match("/^[0-9]*$/", $this->min) || !preg_match("/^[0-9]*$/", $this->max)))
			{
				throw new Exception ("Error with {$this->name}: the field is set as an decimal, therefore min and max must both be decimal numbers");
			}
			
			if($this->min && $this->max && doubleval($this->min) >= doubleval($this->max)) throw new Exception ("Error with {$this->name}: min must be less than max"); //only need to use double val as it will work with ints as well.
			
			//check that default complies to the perscribed conditions above
			if(isset($this->defaultValue))
			{
				switch($vlist) //if the code reaches this point then there should only be 0 or one flags in vlist
				{
						case "integer":
								if(!preg_match("/^[0-9]+$/", $this->defaultValue)) throw new Exception("The field {$this->name} is an integer therefore the default value must be an integer");
								$d = intval($this->defaultValue, 10);
								if($d > intval($this->max, 10) || $d < intval($this->min, 10)) throw new Exception("A min and/or max has been specified by {$this->name}, the default value must fall within or be equal to these numbers.");
								break;
						case "decimal":
								if(!preg_match("/^[0-9]+$/", $this->defaultValue)) throw new Exception("The field {$this->name} is an integer therefore the default value must be an integer");
								$d = doubleval($this->defaultValue, 10);
								if($d > doubleval($this->max, 10) || $d < doubleval($this->min, 10)) throw new Exception("A min and/or max has been specified by {$this->name}, the default value must fall within or be equal to these numbers.");
								break;
						case "date":
								try
								{
									date_create_from_format($this->dtConvert($this->date), $this->defaultValue);
								}
								catch(Exception $e)
								{
									throw new Exception("The field {$this->name} has a default that confilicts with its date attribute.");
								}
						case "setdate":
								throw new Exception("The field {$this->name} has setdate and default attributes set, setdate implies a default of the current date and so default is not valid. If you wish to set a default please use date.");
						case "time":
								try{
										date_create_from_format($this->dtConvert($this->time), $this->defaultValue);
								}
								catch(Exception $e)
								{
										throw new Exception("The field {$this->name} has a default that confilicts with its time attribute.");
								}
						case "settime":
								throw new Exception("The field {$this->name} has settime and default attributes set, settime implies a default of the current date and so default is not valid. If you wish to set a default please use time.");
						case "regex":		
								if(!preg_match($this->regex, $this->defaultValue)) throw new Exception("The field {$this->name} has a default that does not comply with it's regex attribute.`");
						default : break; // should only be reached if there are no validation rules	
				}
			}
		}

		public function valueIsObject()
		{
			$object_fields = array('gps', 'location');//fields encoded as objects
			return array_search($this->type, $object_fields) !== false;
		}
		
		public function valueIsFile()
		{
			$object_fields = array('photo', 'audio', 'video');//fields that represent files
			return array_search($this->type, $object_fields) !== false;
		}
	}
?>