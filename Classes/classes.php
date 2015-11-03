<?php
class dbConnection
	{
		private $con;
		private $resSet;
		
		private $username;// = $DBUSER;
		private $password;// = $DBPASS;
		private $server;// = $DBSERVER;
		private $schema;// = $DBNAME;
		private $port;// = 3306;
		
		public function __construct()
		{
			global $DBUSER, $DBPASS, $DBSERVER, $DBNAME;
			
			$this->username = $DBUSER;
			$this->password = $DBPASS;
			$this->server = $DBSERVER;
			$this->schema = $DBNAME;
			$this->port = 3306;
			
			$this->con = mysqli_connect($this->server, $this->username, $this->password, $this->schema,  $this->port);
			$this->con->set_charset('utf-8');
			//mysqli_select_db($this->schema);
		}
		
		public function __destruct()
		{
			mysqli_close($this->con);
		}
		
		public function boolVal($val) {return $val === true || $val === "true" ? "1" : "0";}
		public function boolVal2($val) {return $val === false || $val === "false" ? "0" : "1";}
		public function stringVal($val) {return $val == "" ? "NULL" : "'". mysqli_escape_string($this->con, $val) . "'";}
		public function numVal($val) {return $val && $val !== 0 && $val !== 0.0 ? "NULL" : $val;}
		
		public function beginTransaction()
		{
			if(mysqli_query($this->con, "START TRANSACTION;"))
			{
				return true;
			}
			else
			{
				return "START TRANSACTION;\r\n" . mysqli_errno($this->con) . " : " . mysqli_error($this->con);
			}
		}
		
		public function commitTransaction()
		{
			if(mysqli_query($this->con, "COMMIT;"))
			{
				return true;
			}
			else
			{
				return "COMMIT;\r\n" . mysqli_errno($this->con) . " : " . mysqli_error($this->con);
			}
		}
		
		public function rollbackTransaction()
		{
			if(mysqli_query($this->con, "ROLLBACK;"))
			{
				return true;
			}
			else
			{
				return "ROLLBACK;\r\n" . mysqli_errno($this->con) . " : " . mysqli_error($this->con);
			}
		}
		
		public function affectedRows()
		{
			return mysqli_affected_rows($this->con);
		}
		
		public function escapeArg($arg)
		{
			return mysqli_escape_string($this->con, $arg);
		}
		
		public function do_query($qry)
		{
			if($this->resSet && !is_bool($this->resSet)) mysqli_free_result($this->resSet);
			$this->resSet = mysqli_query($this->con, $qry);
			if($this->resSet)
			{
				return true;
			}
			else
			{
				//echo $qry .  "\r\n" . mysqli_errno($this->con) . " : " . mysqli_error($this->con);
				return $qry .  "\r\n" . mysqli_errno($this->con) . " : " . mysqli_error($this->con);
			}
		}
		
		public function exec_sp($spName, $args = Array())
		{
			//if($this->resSet && !is_bool($this->resSet)) mysqli_free_result($this->resSet);
			for($i = 0; $i < count($args); $i++)
			{
				//$args[$i] = mysqli_escape_string($this->con, $args[$i]);
				
				if((is_string($args[$i]))){ $args[$i] = "'".str_replace("'", "\\\\'", mysqli_escape_string($this->con, $args[$i]))."'"; }
				
				else if(!$args[$i]){
					if(is_int($args[$i]) || is_double($args[$i]) || is_bool($args[$i])) $args[$i] = "0";
					else $args[$i] = "NULL";
				}
			}
			
			$qry = "CALL $spName (" . implode(", ", $args) . ")";
			
			$this->resSet = mysqli_query($this->con, $qry);
			if($this->resSet)
			{
				return true;
			}
			else
			{
				return $qry . "\r\n" .mysqli_errno($this->con) . " : " . mysqli_error($this->con);
			}
			
		}
		
		public function get_row_array()
		{
			return mysqli_fetch_assoc($this->resSet);
		}
		
		public function get_row_object()
		{
			return mysqli_fetch_object($this->resSet);
		}
		
		public function last_id()
		{
			return mysqli_insert_id($this->con);
		}
		
	}


function do_request($url)
	{
		$ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);       
        curl_close($ch);
        return $output;
	}

class EcProject{
		public $name = "";
		public $tables = Array();
		public $id = "";
		public $allowDownloadEdits = false;
		public $uploadToServer = "";
		public $uploadToLocalServer = "";
		public $ecVersionNumber = 2.1;
		public $submission_id;
		
		public $description;
		public $image;
		public $isPublic = true;
		public $isListed = true;
		public $publicSubmission = true;
		
		private function fromArr($arr)
		{
			if(array_key_exists("id", $arr)) $this->id = $arr["id"];
			if(array_key_exists("submission_id", $arr)) $this->submission_id = $arr["submission_id"];
			if(array_key_exists("name", $arr))$this->name = $arr["name"];
			if(array_key_exists("description", $arr))$this->description = $arr["description"];
			if(array_key_exists("image", $arr))$this->image = $arr["image"];
			if(array_key_exists("isPublic", $arr))$this->isPublic = $arr["isPublic"];
			if(array_key_exists("isListed", $arr))$this->isListed = $arr["isListed"];
			if(array_key_exists("publicSubmission", $arr))$this->publicSubmission = $arr["publicSubmission"];
			
		}
		
		private function toArr()
		{
			return array(
				"id" => $this->id,
				"name" => $this->name, 
				"description" => $this->description,
				"image" => $this->image,
				"isPublic" => $this->isPublic,
				"isListed" => $this->isListed,
				"publicSubmission" => $this->publicSubmission
			);
		}
		
		public function fetch()
		{
			$db = new dbConnection();
			if($this->name != "")
			{
				$db->exec_sp("getProject", array($this->name));
				if($arr = $db->get_row_array())
				{
					$this->fromArr($arr);
				}
				$db = new dbConnection();
				//get forms	
				$res = $db->exec_sp("getForms", array($this->name));
				
				if($res === true)
				{
						
					while($arr = $db->get_row_array())
					{
						
						$frm = new EcTable($this);
						$frm->fromArray($arr);
						//get fields
						
						$frm->fetch();
						//get options
						$this->tables[$frm->name] = $frm;
						
					}
					
				}
				else
				{
					return false;
				}
			}		
		}
		
		
		public function parse($xml)
		{
			$root = simplexml_load_string($xml);
			$model = $root->model[0];
			foreach($model->submission[0]->attributes() as $name => $val)
			{
				switch($name)
				{
					case "id":
						$this->submission_id = (string)$val;
						break;
					case "projectName" :
						$this->name = (string)$val;
						break;
					case "allowDownloadEdits":
						$this->allowDownloadEdits = (string)$val == "true";
						break;
					case "versionNumber":
						$this->versionNumber = (string)$val;
						break;
				}
			}
			
			//check the version of the xml (version 1 does not contain table tags)
			
			if($root->form)
			{
				
				$this->ecVersionNumber = "3";
				if($model->uploadToLocalServer){
						$this->uploadToLocalServer = (string)$model->uploadToLocalServer[0];
				}
				for($t = 0; $t < count($root->form); $t++)
				{
						$atts = $root->form[$t]->attributes();
						if(!array_key_exists((string)$atts['name'], $this->tables))
						{
							 $tbl = new EcTable($this);
						}
						else
						{
								
							 $tbl = $this->tables[(string)$atts['name']];
						}
						
						$tbl->parse($root->form[$t]);
						$this->tables[$tbl->name] = $tbl;
				}
				
			}
			elseif($root->table)
			{
				//parse version 2 tables
				$this->ecVersionNumber = "2";
				if($model->uploadToLocalServer){
					$this->uploadToLocalServer = (string)$model->uploadToLocalServer[0];
				}
				for($t = 0; $t < count($root->table); $t++)
				{
						if(!array_key_exists((string) $root->table[$t]->name, $this->tables))
						{
							 $tbl = new EcTable($this);
						}
						else
						{
							 $tbl = $this->tables[$root->table[$t]->name];
						}
						$tbl->parse($root->table[$t]);
						$tbl->version = $this->versionNumber;
						$this->tables[$tbl->name] = $tbl;
				}
			}
			else
			{
				//parse version 1 table
				$this->ecVersionNumber = "1";
				$tbl = new EcTable($this);
				$tbl->parse($root);
				$tbl->projectName = $this->name;
				$this->tables[$this->name] = $tbl;
			}
			
			$this->uploadToServer = (string)$model->uploadToServer[0];
			
		}
		
		public function checkPermission($uid)
		{
			$db = new dbConnection();
			$res = $db->exec_sp("checkProjectPermission", array($uid?$uid:0, $this->id));
			if($res !== true) echo $res;
			$obj = $db->get_row_object();
			return $obj ? $obj->role : $this->isPublic;
		}
		
		private function getPermission ($lvl)
		{
				
				if(!is_numeric($this->id) || strstr($this->id, ".")) return "ID {$this->id} is not properly set";
				
				global $auth;
				
				$db = new dbConnection();
				if($this->checkPermission($auth->getEcUserId()) == 3)
				{
						$sql = "SELECT upp.role, u.email FROM userprojectpermission upp join user u on upp.user = u.idUsers WHERE upp.role = $lvl and upp.project = {$this->id}";
						$res = $db->do_query($sql);
						if($res === true)
						{
								$returnarr = array();
								while ($arr = $db->get_row_array())
								{
										array_push($returnarr, $arr["email"]);
								}
								return $returnarr;
						}
						else
						{
								return $res;
						}
						
				}
				else
				{
						return "You do not have permission to update this project";
				}
		}
		
		private function setPermission ($emails, $lvl)
		{
				if(!preg_match('/([a-z0-9\._%+-]+@[a-z0-9\.-]+\.[a-z]{2,4}\,?)+$/i',$emails)) return "invalid email $emails";
				if(!is_numeric($this->id) || strstr($this->id, ".")) return "ID {$this->id} is not properly set";
				
				global $auth;
				$emails = rtrim(strtolower($emails), ","); //as emails are case insensitive we will make them all lowercase to make comparison easier
				
				$db = new dbConnection();
				if($this->checkPermission($auth->getEcUserId()) == 3)
				{
						//add any new emails
						$newUsers = str_replace(",", "' as Email UNION SELECT '", $emails);
						$sql = "INSERT INTO user (Email) SELECT * FROM (SELECT '$newUsers' as Email) a where a.email NOT IN (SELECT Email from user);";
						//echo $sql;
						$res = $db->do_query($sql);
						if($res !== true) return $res;
						
						$sql = "delete from userprojectpermission where project = {$this->id} and role = $lvl";
						$res = $db->do_query($sql);
						if($res===true)
						{
								$db = new dbConnection();
								$emails = str_replace(",", "','", $emails);
								$sql = "INSERT INTO userprojectpermission (user, project, role) SELECT idUsers, {$this->id}, $lvl From user where email in ('{$emails}')";
								$res = $db->do_query($sql);
								
						}
						return $res;
				}
				else
				{
						return "You do not have permission to update this project";
				}
		}
		
		public function setAdmins($emails)
		{
				return $this->setPermission($emails, 3);
		}
		
		public function setUsers($emails)
		{
				return $this->setPermission($emais, 2);
		}
		
		public function setSubmitters($emails)
		{
				return $this->setPermission($emails, 1);
		}
		public function getAdmins()
		{
				return $this->getPermission(3);
		}
		
		public function getUsers()
		{
				return $this->getPermission(2);
		}
		
		public function getSubmitters()
		{
				return $this->getPermission(1);
		}
		public function post()
		{
			global $auth;
			$db=new dbConnection();
			$uid = (int) $auth->getEcUserId();
			$res = $db->exec_sp("addProject", array($this->name, $this->submission_id, $this->description, $this->image, $this->isPublic, $this->isListed, $this->publicSubmission, $uid));
			if($res === true)
			{
				$this->fetch();
				foreach($this->tables as $tbl)
				{
					$r = $tbl->addToDb();
					if($r !== true) return $r;
				}
				return true;
			}
			else
				return $res;
			
		}
		
		public function push()
		{
			global $auth, $log, $db;
			
			//$log = new Logger('Ec2');
			$log->write('info', 'Starting project update');
			
			$db = new dbConnection();
			if($this->checkPermission($auth->getEcUserId()) >= 2)
			{
				$log->write('info', 'User has permission');
				$res = $db->beginTransaction();
				if($res !== true) return $res;
				
				$log->write('info', 'transaction started');
				
				$res = $db->do_query("UPDATE Project SET description = " . $db->stringVal($this->description).", image = " . $db->stringVal($this->image).",
									 isPublic  = " . $db->boolVal($this->isPublic) . ", isListed = " . $db->boolVal($this->isListed) . ",
									publicSubmission = " . $db->boolVal($this->publicSubmission) . "	WHERE id = {$this->id} AND name = '{$this->name}'");
				if($res !== true) return $res;
				
				$log->write('info', 'Project details updated');
				
				foreach($this->tables as $tbl)
				{
						$log->write('info', "Updating form {$tbl->name}");
						$res = $tbl->update();
						if($res !== true) {
								$log->write('error', "Updating form {$tbl->name} failed $res");
								$db->rollbackTransaction();
								return $res;
						}
						$log->write('info', "Updated form {$tbl->name}");
				}
				$db->commitTransaction();
				$log->write('info', "Update done");
				return true;
			}	
			else
			{
				return "You do not have permission to update this project";
			}
		}
		
		function getSummary()
		{
				global $auth;
				
				if(!$this->isPublic && $this->checkPermission($auth->getEcUserId()) < 2) return "You do not have permission to view this data";
				
				$db = new dbConnection();
				$qry = "SELECT f.idForm, f.Name, count(e.idEntry) as entries, count(distinct e.user) as users, count(distinct deviceId) as devices from Form f Left JOIN Entry e on e.form = f.idForm where f.projectName = '{$this->name}' group by f.idForm, f.Name";
				$res = $db->do_query($qry);
				
				if($res !== true) return $res;
				
				$out = array();
				
				while ($arr = $db->get_row_array())
				{
					array_push($out, $arr);
				}
				
				return $out;
		}
		
		function getUsage($res = "month", $from = NULL, $to = NULL)
		{
		
		    if(!$from || !is_object($from) || !get_class($from) == "DateTime")
			{
				$from = new DateTime('now', new DateTimeZone('UTC'));
				$from->sub(new DateInterval("P12M"));
			}
				
		    if(!$to|| !is_object($to) || !get_class($to) == "DateTime")
			{
				$to = new DateTime();
				//$to->add(new DateInterval("P6M"));
			}
			$formats = array(
				"hour" => array("%H %d/%m/%Y", "PT1H", "H d/m/Y"),
				"day" => array("%d/%m/%Y", "P1D", "d/m/Y"),
				"week" => array("%u %Y", "P1W",  "W Y"),
				"month" => array( "%m/%Y", "P1M", "m/Y") ,
				"year"  => array("%Y", "P1Y", "Y")
			);
			
			$sql = " LEFT JOIN (SELECT count(distinct user) as userTotal, count(1) as entryTotal, DATE_FORMAT(FROM_UNIXTIME(created / 1000), '{$formats[$res][0]}') as Date From Entry  WHERE projectName = '{$this->name}' GROUP BY Date) b ON a.date = b.Date";
			
			$periods = array();
			
			
			for($dat = $from; $dat <= $to; $dat = $dat->add(new DateInterval($formats[$res][1])))
			{
				array_push($periods, $dat->format($formats[$res][2]));
			}
			$sql = "SELECT a.date as dateField, IFNULL(b.userTotal, 0) as userTotal, IFNULL(b.entryTotal, 0)  as entryTotal FROM (SELECT '" . implode("' as date UNION SELECT '", $periods) . "') a $sql";
			
			$db = new dbConnection();
			$res = $db->do_query($sql);
			if($res === true)
			{
				$resArr = array();
				while($arr = $db->get_row_array())
				{
						$arr["userTotal"] = (int) $arr["userTotal"];
						$arr["entryTotal"] = (int) $arr["entryTotal"];
						array_push($resArr, $arr);
				}
				return json_encode(array("results" => $resArr));
			}
			else
			{
				return $res;
			}
			
		}
		
		public function toSQL()
		{
				$sql = "";
				
				foreach($this->tables as $tbl)
				{
					$sql .= $tbl->toSQL() . "\r\n";
				}
				
				return $sql;
		}
		
		public function toXML()
		{
				global $API_ROOT;
				$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n
<xform>
	<model>
		<submission id=\"{$this->submission_id}\" projectName=\"{$this->name}\" allowDownloadEdits=\"". ($this->allowDownloadEdits ? "true" : "false") . "\" versionNumber=\"{$this->ecVersionNumber}\" />
		<uploadToServer>http://{$_SERVER["HTTP_HOST"]}{$API_ROOT}/{$this->name}/upload</uploadToServer>
		<downloadFromServer>http://{$_SERVER["HTTP_HOST"]}{$API_ROOT}/{$this->name}/download</downloadFromServer>
	</model>\n";
				foreach($this->tables as $tbl)
				{
					$xml .= $tbl->toXML();
				}
				
				$xml.="\n</xform>";
				return $xml;
		}
		
		//Function to accept an XML upload from a phone or recovery
		public function parseEntries($xml)
		{
				$success = true;			
				
				$doc = simplexml_load_string($xml);
				
				foreach(libxml_get_errors() as $err)
				{
						$success = ($success === true ? $err : "$success\n$err") ;
				}
				//echo $success;
				if($success !== true) return $success;
				
				foreach($doc->table as $tbl)
				{
						$table = $this->tables[(string)$tbl->table_name[0]];
						if(!$table) return "table '{$tbl->table_name[0]}' not found";
						$res = $table->parseEntries($tbl);
						if($res !== true) return $res;
				}
				return $res;
		}
		
		
	}
  
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
		
		public function __construct($s = null)
		{
				$this->survey = $s;
		}
		
		public function fromArray($arr)
		{
			foreach(array_keys($arr) as $key)
			{
				$this->$key = $arr[$key];
			}
			$this->number = $this->table_num;
			$this->id = $this->idForm;
		}
		
		public function toXML()
		{
			$xml = "\n\t<form num=\"{$this->number}\" name=\"{$this->name}\" key=\"{$this->key}\"> ";
			foreach($this->fields as $fld)
			{
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
			
			$qry = "SELECT * from Form WHERE";
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
				}
				
			}
			
			$qry = "SELECT f.idField as idField, f.key, f.name, f.label, ft.name as type, f.required, f.jump, f.isinteger as isInt, f.isDouble, f.title, f.regex, f.doubleEntry, f.search, f.group_form, f.branch_form, f.display, f.genkey, f.date, f.time, f.setDate, f.setTime FROM field f LEFT JOIN fieldType ft on ft.idFieldType = f.type WHERE ";
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
					$db = new dbConnection();
					$res = $db->exec_sp("getOptions", array($fld->idField));
					while($res === true && $arr = $db->get_row_array())
					{
						$opt = new EcOption();
						$opt->fromArray($arr);
						$opt->idx = $arr["index"];
						$fld->options[$opt->value] = $opt;
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
			global $auth;
			$db = new dbConnection();
			
			//print_r($this->fields);
			
			$res = $db->do_query("INSERT INTO FORM(project, projectName, version, name, table_num, keyField) VALUES({$this->survey->id}, '{$this->survey->name}', {$this->version} , '{$this->name}', '{$this->number}', '{$this->key}')");
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
			//$db->__destruct();
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
					}
				}
			}
			else
			{
				
				$this->number = 0;
				$this->name = "table";
				$this->key = "";
			}
		
			//parse elements
			$p = 0;
			foreach($xml->children() as $field)
			{
				
				if(preg_match('/^(input|select1?|radio|textarea|photo|gps|barcode|audio|video|group|branch)$/', $field->getName()))
				{
					$atts = $field->attributes();
					if(!array_key_exists((string)$atts['name'], $this->fields))
					{
						$fld = new EcField();
					}else{
						$fld = $this->fields[(string)$atts['name']];
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
					
					/*if( $fld->type == "gps" || preg_match('/^gps$/i', $fld->name))
					{
						$components = Array("_lat","_lon","_alt","_acc");
						$componentLbls = Array("Latitude","Longitude","Altitude","Accuracy");
						$nm = $fld->name;
						$lbl = $fld->label;
						$fld->numeric = true;
						for($c = 0; $c < count($components); $c++)
						{
							$fld = new EcField();
							$fld->form = $this;
							$fld->parse($field);
							$fld->name = "$nm{$components[$c]}";
							$fld->label = "{$lbl} {$componentLbls[$c]}";
							$this->fields[$fld->name] = $fld;
							
						}
					}
					else
					{*/
						$fld->position = $p;
						$this->fields[$fld->name] = $fld;
						$p++;
					//}
				}
				
			}
			
			if($this->key) $this->fields[$this->key]->key = true;
		}
		
		public function get($args = false, $offset = 0, $limit = 0)
		{
			global $auth;
			
			$db = new dbConnection();
			
			$sql = "SELECT DISTINCT entry FROM entryvalue WHERE projectName = '{$this->survey->name}' AND formName = '{$this->name}' ORDER BY Entry";
			$sql2 = "SELECT count(DISTINCT entry) as ttl FROM entryvalue WHERE projectName = '{$this->survey->name}' AND formName = '{$this->name}'";
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
					array_push($ents, $arr['entry']);
				}
			}
			else
			{
				return $res;
			}
			
			$entries = implode(",", $ents);
			
			$sql = "SELECT idEntry as id, DeviceID, created, lastEdited, uploaded FROM Entry WHERE idEntry in ($entries) ORDER BY created";
			
			
			
			
			$res = $db->do_query($sql);
			if($res === true)
			{
				$resArr = array();
				
				while($arr = $db->get_row_array())
				{
					$resArr[$arr["id"]] =  $arr;
				}
				
				
				$sql = "SELECT * FROM EntryValue WHERE Entry in ($entries) ORDER BY fieldName";
				
				$res = $db->do_query($sql);
				if($res === true)
				{
					while($arr = $db->get_row_array())
					{
						if(array_key_exists($arr["entry"], $resArr))
						{
							$resArr[$arr["entry"]][$arr["fieldName"]] = $arr["value"];
						}
					}
					
					$count = 0;
					$res = $db->do_query($sql2);
					if($res !== true) return $res;
					while($arr = $db->get_row_array())
					{
						$count = $arr["ttl"];
					}
					return array("count" => $count, $this->name => array_values($resArr)); // we want a pure array not an assocciative array
				}
				else
				{
					return $res;
				}
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
			$sql = "UPDATE form set version = {$this->version}, name = '{$this->name}', keyField = '{$this->key}' WHERE project = {$this->survey->id} AND table_num = {$this->number};";
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
		
		public function post($args) //$args should be an assocaitive array of arguments
		{
			
		}
		
		public function delete($args, &$response)
		{
			
			
		}
		
		public function toSQL()
		{
			
		}
		
		function getSummary()
		{
			
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
			
			$sql = " LEFT JOIN (SELECT formName, count(distinct user) as userTotal, count(1) as entryTotal, DATE_FORMAT(FROM_UNIXTIME(created / 1000), '{$formats[$res]}') as Date From Entry  WHERE projectName = '{$this->surve->name}' GROUP BY Date, formName) b ON a.date = b.Date";
			
			$periods = array();
			
			switch($res)
			{
				case "hour":
					break;
				case "day":
					break;
				case "week":
					break;
				case "month":
					break;
				case "year":
					break;
			}
			
			$db = new dbConnection();
			$sql->do_query($sql);
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
					if($fld->type == 'gps')
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
					
					
				}
				
				//TODO: need to check field names in the xml against fields in the form, and possibly
				//alert users to form version errors.
				
				$res = $entry->post();
				if($res !== true) return $res;
			}
			return $res;
		}
		
	}
	class EcEntry{
		public $id;
		public $key;
		public $form;
		public $projectName;	//name of the project
		public $formName;		//name of the form
		public $deviceId;
		public $created;
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
			$db = new dbConnection();
			$sql = "SELECT * from Entry e LEFT JOIN EntryValue v on e.idEntry = v.entry where e.projectName = '{$this->projectName}' AND e.formName = '{$this->formName}' AND v.fieldName = '{$this->form->key}' AND v.value = '{$this->key}'";
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
			
			$sql = "SELECT * from EntryValue where entry = {$this->id}";
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
			//TODO: need to get the user details form the phone
			
			$db = new dbConnection();
			$res = $db->beginTransaction();
			
			if($res !== true) return $res;
			//check that the entry doesn't already exist
			$qry = "SELECT * FROM EntryValue WHERE projectName = '{$this->projectName}' AND formName = '{$this->formName}' AND fieldName = '{$this->form->keyField}' AND value = '{$this->values[$this->form->keyField]}'";
			//echo $qry;
			
			$num = 0;
			$res = $db->do_query($qry);
			if($res !== true) return $res;
			
			while($db->get_row_array()) $num++;
			if($num > 0) return "Duplicate Key for {$this->formName} > {$this->form->keyField} > {$this->values[$this->form->keyField]}";
		
			$uid = 0;	
			//insert basic entry data
			if(preg_match("/(CHROME|FIREFOX)/i", $_SERVER["HTTP_USER_AGENT"]))
				$uid = $auth->getEcUserId();
			else
				$sql = "SELECT isUsers FROM user WHERE email = {$_GET['email']}";
			
			$qry = "INSERT INTO entry (form, projectName, formName, DeviceId, created, uploaded, user) VALUES ({$this->form->id},'{$this->projectName}','{$this->formName}','{$this->deviceId}',{$this->created},now(),$uid);";
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
					if($this->form->fields[$key]->type == "gps")
					{
						array_push($ins, " SELECT {$this->form->fields[$key]->idField}, '{$this->form->survey->name}', '{$this->form->name}', '$key', '" . json_encode($this->values[$key]) . "', {$this->id}");
					}
					else
					{
						array_push($ins, " SELECT {$this->form->fields[$key]->idField}, '{$this->form->survey->name}', '{$this->form->name}', '$key', '{$this->values[$key]}', {$this->id}");
					}
				}
				else
				{
					$res = $db->rollbackTransaction();
					echo $res;
					return "field $key is not present in any version of the project definition";
					
				}
			}
			$qry .= join(" UNION ", $ins);
			
			$res = $db->do_query($qry);
			if($res !== true){
				$res = $db->commitTransaction();
				//echo $res;
				return $res;
			}
			$res = $db->commitTransaction();
			//echo $res;
			return $res;
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
			$qry = "SELECT * FROM EntryValue WHERE projectName = '{$this->projectName}' AND formName = '{$this->formName}' AND fieldName = '{$this->form->keyField}' AND value = '{$this->values[$this->form->keyField]}'";
			//echo $qry;
			
			$num = 0;
			$res = $db->do_query($qry);
			if($res !== true)
			{
				$db->rollbackTransaction();
				return $res;
			}
			
			while($db->get_row_array()) $num++;
			if($num == 0) return "Entry does not exist";
			
			$qry = "UPDATE ENTRY SET lastEdited = Now() where idEntry = {$this->id}";
			$res = $db->do_query($qry);
			if($res !== true)
			{
				$db->rollbackTransaction();
				return $res;
			}
			
			foreach($this->values as $key => $value)
			{
				$qry = "UPDATE EntryValue SET value = '$value' WHERE projectName = '{$this->projectName}' AND formName = '{$this->formName}' AND fieldName = '$key' AND entry = {$this->id}";
				$res = $db->do_query($qry);
				if($res !== true){
					$db->rollbackTransaction();
					return $res;
				}
				
				if($db->affectedRows() == 0)
				{
					$qry = "INSERT INTO EntryValue (field, projectName, formName, fieldName, value, entry) VALUES  {$this->form->fields[$key]->id},'{$this->projectName}','{$this->formName}','$key','$value',{$this->id}";
					$res = $db->do_query($qry);
					if($res !== true) {
						$db->rollbackTransaction();
						return $res;
					}
				}
				
			}
			if($res === true)
			{
				$res = $db->commitTransaction();
			}else
			{
				$db->rollbackTransaction();
			}
			return $res;
		}
		
		public function delete()
		{
			$db = new dbConnection();
			$sql = "DELETE from EntryValue where Entry = {$this->id}";
			$db->do_query($sql);
			$sql = "DELETE from Entry where idEntry = {$this->id}";
			$db->do_query($sql);
		}
		
		public function parse()
		{
			
		}
		
		public function toXML()
		{
			
		}
		
		public function toJSON()
		{
			
		}
		
	}
	
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
		
		public $search = false;
		public $group_form = false;
		public $branch_form = false;
		public $display = true;
		
		public $genkey= false;
		
		public $position = 0;
		public $date = false;
		public $time = false;
		public $setDate = false;
		public $setTime = false;
		
		public $form;
		
		public $chart = false;
		public $key = false;
		
		public $fkTable = false;
		public $fkField = false;
		
		public function toXML()
		{
			$xml = "\n\t\t<{$this->type} name=\"{$this->name}\"";
			if($this->required) $xml .= ' required="true"';
			if($this->isInt) $xml .= ' integer="true"';
			if($this->isDouble) $xml .= ' double="true"';
			if($this->regex) $xml .= " regex=\"{$this->regex}\"";
			if($this->title) $xml .= ' title="true"';
			if($this->doubleEntry) $xml .= ' verify="true"';
			if($this->jump) $xml .= " jump=\"{$this->jump}\"";
			if($this->search) $xml .= " search=\"true\"";
			if($this->group_form) $xml .= " group_form=\"{$this->group_form}\"";
			if($this->branch_form) $xml .= " branch_form=\"{$this->branch_form}\"";
			if(!$this->display) $xml .= " display=\"false\"";
			if($this->genkey) $xml .= " genkey=\"true\"";
			if($this->date) $xml .= " date=\"{$this->date}\"";
			if($this->time) $xml .= " time=\"{$this->time}\"";
			if($this->setDate) $xml .= " setdate=\"{$this->setDate}\"";
			if($this->setTime) $xml .= " settime=\"{$this->setTime}\"";
			$xml.= ">\n\t\t\t<label>{$this->label}</label>\n\t\t";
			foreach($this->options as $opt)
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
			if($this->search) $xml .= " \"search\":\"true\",";
			if($this->group_form) $xml .= " \"group_form\":\"{$this->group_form}\",";
			if($this->branch_form) $xml .= " \"branch_form\":\"{$this->branch_form}\",";
			if(!$this->display) $xml .= " \"display\":\"false\",";
			if($this->genkey) $xml .= " \"genkey\":\"true\",";
			if($this->date) $xml .= " \"date\":\"{$this->date}\",";
			if($this->time) $xml .= " \"time\":\"{$this->time}\",";
			if($this->setDate) $xml .= " \"setdate\":\"{$this->date}\",";
			if($this->setTime) $xml .= " \"settime\":\"{$this->setTime}\",";
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
				$qry = "SELECT idFieldType FROM FieldType where name = '{$this->type}'";
				$db->do_query ($qry);
				while($arr = $db->get_row_array())
				{
					$fieldType = $arr["idFieldType"];
				}
				
				$sql = "UPDATE field SET type = {$fieldType}, name = " . $db->stringVal($this->name) .", label = " . $db->stringVal($this->label) .", language = " . $db->stringVal($this->language) .", regex = " . $db->stringVal($this->regex) .", title = " . $db->boolVal($this->title) . "
						, `key` = " . $db->boolVal($this->key) . ", isinteger= " . $db->boolVal($this->isInt) . ", isdouble= " . $db->boolVal($this->isDouble) . ", active = 1, doubleentry = " . $db->boolVal($this->doubleEntry) . ", jump= " . $db->stringVal($this->jump) . ", required = " . $db->boolVal($this->required) . ", search = " . $db->boolVal($this->search) . ",
						group_form=  " . $db->stringVal($this->group_form) . ", branch_form=  " . $db->stringVal($this->branch_form) . ", display= " . $db->boolVal2($this->display) . ", genkey = " . $db->boolVal($this->genkey) . ", date = " . $db->stringVal($this->date) .", time = " . $db->stringVal($this->time) .",
						setdate  = " . $db->stringVal($this->setDate) .", settime  = " . $db->stringVal($this->setTime) .", position = {$this->position} WHERE idField = {$this->idField};";
				$res = $db->do_query($sql);
				if($res !== true) return $res;
				if($db->affectedRows() == 0) return "field not fouund";
				
				if(count($this->options) != 0){
				
						$sql = "DELETE FROM `option` WHERE field = {$this->idField}";
						$res = $db->do_query($sql);
						if($res !== true) return $res;
										
						foreach($this->options as $opt)
						{
							$res = $db->exec_sp("addOption", array(
								$this->form->survey->name,
								$this->form->name,
								$this->name,
								$opt->idx,
								$opt->label,
								$opt->value
							));
							if($res !== true) return $res;
						}
				}
				return true;
		}
		
		public function addToDb()
		{
				global $db;
			if(!$db) $db = new dbConnection();
			$qry = "SELECT idFieldType FROM FieldType where name = '{$this->type}'";
			$db->do_query ($qry);
			while($arr = $db->get_row_array())
			{
				$fieldType = $arr["idFieldType"];
			}
			
			$lbl = mysql_escape_string($this->label);
			
			$qry ="INSERT INTO Field (form, projectName, formName, type, name, label, language, regex, title, `key`, isinteger, isdouble, active, doubleentry, jump, required, search, group_form, branch_form, display, genkey, date, time, setdate, settime, position) VALUES
								 ({$this->form->id}, '{$this->form->survey->name}', '{$this->form->name}', $fieldType, '{$this->name}','{$lbl}', '{$this->language}',";
			$qry .= ($this->regex != "" ? "'{$this->regex}'," : "NULL,");
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
			$qry .= ($this->date ? "'{$this->date}'," : "NULL,");
			$qry .= ($this->time ? "'{$this->time}'," : "NULL,");
			$qry .= ($this->setDate ? "'{$this->setDate}'," : "NULL,");
			$qry .= ($this->setTime ? "'{$this->setTime}'," : "NULL,");
			
			$qry .= "{$this->position})";
			
			$res = $db->do_query($qry);
			
			if($res === true){
				foreach($this->options as $opt)
				{
					$res = $db->exec_sp("addOption", array(
						$this->form->survey->name,
						$this->form->name,
						$this->name,
						$opt->idx,
						$opt->label,
						$opt->value
					));
					if($res !== true) return $res;
				}
			}	
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
							$this->required = (string)$val == "true";
							break;
						case 'title':
							$this->title = (string)$val == "true";
							break;
						case 'jump':
							//$bits = explode(',', (string)$val);
							//$this->jumpTarget = $bits[0];
							$this->jump = (string)$val;
							break;
						case 'numeric':
							$this->isInt = ((string)$val == "true" | $this->isInt);
							break;
						case 'double':
							$this->isDouble = ((string)$val == "true" | $this->isDouble);
							break;
						case 'chart':
							$this->chart = (string)$val;
							break;
						case 'key':
							$this->key = (string)$val == "true";
							break;
						case 'regex':
							$this->regex = (string)$val;
							break;
						case 'doubleEntry':
							$this->doubleEntry = false;
							break;
						case 'search' :
								$this->search = (string)$val == "true";
								break;
						case 'group_form':
								$this->group_form = (string)$val;
								break;
						case 'branch_form':
								$this->branch_form = (string)$val;
								break;
						case 'display' :
								$this->display = (string)$val != 'false';
								break;
						case 'genkey' :
								$this->genkey = (string)$val == 'true';
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
				} //end switch
				
			}//end foreach
			
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
					$oIdx++;
				}
			}
		}
	}
	
		class EcOption{
		public $label;
		public $value;
		public $idx;
		
		public function fromArray($arr)
		{
			foreach(array_keys($arr) as $key)
			{
				$this->$key = $arr[$key];
			}
		}
	}
	
	class Logger
	{
		private $fn;
		private $fp;
		
		public function __construct($logName)
		{
			$this->fp = fopen ("./logs/{$logName}.log" , "a");
		}
		
		public function close()
		{
			fclose($this->fp);
		}
		
		public function write($level, $msg)
		{
			$dat = new DateTime('now', new DateTimeZone('UTC'));
			
			$logstring = $dat->format("d/M/y h:m:s") . "\t$level\t" . str_replace("\n", "", $msg) . "\n";
			
			fwrite($this->fp, $logstring);
		}
		
	
	}
	
	class PageRule
	{
	 public $redirect;
	 public $handler;
	 public $login;
	 
	 public function __construct($r = false, $h = false, $l = false)
	 {
		$this->redirect = $r;
		$this->handler = $h;
		$this->login = false;
	 }
	 
	}
	?>