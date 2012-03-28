<?php
class EcProject{
		public $name = "";
		public $tables = Array();
		public $id = "";
		public $allowDownloadEdits = false;
		public $projectVersion;
		public $uploadToServer = "";
		public $uploadToLocalServer = "";
		public $downloadFromLocalServer= "";
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
			if(array_key_exists("uploadToLocalServer", $arr))$this->uploadToLocalServer = $arr["uploadToLocalServer"];
			if(array_key_exists("downloadFromLocalServer", $arr))$this->downloadFromLocalServer = $arr["downloadFromLocalServer"];
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
				"publicSubmission" => $this->publicSubmission,
				"downloadFromLocalServer" => $this->downloadFromLocalServer,
				"uploadToLocalServer" => $this->uploadToLocalServer
			);
		}
		
		public function fetch()
		{
			$db = new dbConnection();
			if($this->name != "")
			{
				//$res = $db->exec_sp("getProject", array($this->name));
				$res = $db->do_query("SELECT * FROM project WHERE name = '{$this->name}'");
				if($res!== true) return $res;
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
					foreach($this->tables as $tname => $tbl)
					{
						foreach($tbl->branches as $branch)
						{
								$this->tables[$branch]->branchOf = $tname;
						}
					}
					
				}
				else
				{
					return false;
				}
			}		
		}
		
		
		public function parse($xml, $edit=false)
		{
			$root = simplexml_load_string($xml);
			$model = $root->model[0];
			if($model->uploadToLocalServer){
				$this->uploadToLocalServer = (string)$model->uploadToLocalServer[0];
			}
			if($model->downloadFromLocalServer){
				$this->downloadFromLocalServer = (string)$model->downloadFromLocalServer[0];
			}
			
			$adeIsSet = false;
			
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
						try{
								$this->allowDownloadEdits = parseBool((string)$val);
								$adeIsSet = true;
						}
						catch(Exception $e)
						{
								throw new InvalidArgumentException("allowDownloadEdits must be true or false");		
						}
						break;
					case "versionNumber":
						$this->versionNumber = (string)$val;
						break;
				}
			}
			
			if(!$adeIsSet) throw new Exception("allowDownloadEdits must be set for every project.");
			
			//check the version of the xml (version 1 does not contain table tags)
			if($root->description)
			{
				$this->description = (string)$root->description[0];
			}
			
			if($root->form)
			{
				
				$this->ecVersionNumber = "3";
				
				for($t = 0; $t < count($root->form); $t++)
				{
						$atts = $root->form[$t]->attributes();
						
						if(!array_key_exists((string)$atts['name'], $this->tables))
						{
							 $tbl = new EcTable($this);
						}
						elseif($this->tables[(string)$atts['name']]->id)
						{
							$tbl = $this->tables[(string)$atts['name']];
						}
						else
						{
								throw new Exception("Table names must be unique. More that one table called " .(string)$atts['name'] . " in {$this->name}" );
								//$tbl = $this->tables[(string)$atts['name']]; 
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
						if(!array_key_exists((string) $root->table[$t]->name, $this->tables)|| $this->tables[(string) $root->table[$t]->name]->id)
						{
							 $tbl = new EcTable($this);
						}
						else
						{
							 throw new Exception("Table names must be unique. More that one table called " .(string)$atts['name'] . "in {$this->name}" );
								//$tbl = $this->tables[(string)$atts['name']]; 
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
				foreach($tbl->fields as $fld)
				{
						if($fld->title)
						{
								$this->tables[$this->name]->key = $fld->name;
								break;
						}
				}
				
			}
			
			$this->uploadToServer = (string)$model->uploadToServer[0];
			
			foreach($this->tables as $t)
			{
				
				if(!$t->isMain) continue;
				$tn = $this->getNextTable($t->name, true);
				
				if($tn && !array_key_exists($t->key, $tn->fields))
				{
					
					$f = new EcField();
					$f->name = $t->fields[$t->key]->name;
					$f->label = $t->fields[$t->key]->label;
					$f->form = $tn;
					$f->type = 'input';
					$f->fkTable = $t->name;
					$f->fkField = $t->key;
					$tn->fields[$f->name] = $f;
				}
			
			}
			
		}
		
		public function getLastUpdated()
		{
			//$db = new dbConnection();
			global $db;
			$sql = "SELECT max(uploaded) as Uploaded, max(lastEdited) as Edited, count(1) as ttl from entry WHERE projectName = '{$this->name}'";
			$res = $db->do_query($sql);
			
			if($res !== true)
			{
				return $res;
			}
			
			$arr = $db->get_row_array();
			
			$tz = new DateTimeZone('UTC');
			
			$uploaded = new DateTime($arr["Uploaded"], $tz);
			if($arr["Edited"] != "")
			{
				$edited = new DateTime($arr["Edited"],$tz);
			}
			else
			{
				$edited = null;
			}		

			$dat = $uploaded > $edited  ? $uploaded : $edited;
			return $dat->getTimestamp() . $arr["ttl"];
		}
		
		public function checkPermission($uid)
		{
			$db = new dbConnection();
			$res = $db->exec_sp("checkProjectPermission", array($uid?$uid:0, $this->id));
			if($res !== true) die($res);
			if($obj = $db->get_row_object()) // if no one has any permissions on the project
			{
				return $obj->role;
			}
			else 
			{
				return 3;
			}
		}
		
		public function getNextTable($tblName, $mainOnly)
		{
			$num = $this->tables[$tblName]->number + 1;
			
			$tbl = false;
			
			foreach($this->tables as $n => $t)
			{
				if($t->number == $num)
				{
					if($t->isMain === true || !$mainOnly)
					{
						$tbl = $t;
						break;
					}
					else
					{
						$num++;
					}
				}
			}
			return $tbl;
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
		
		public function getPreviousTable($tblName, $mainOnly = false)
		{
			$tbl = false;
			$num = $this->tables[$tblName]->number - 1;
			
			//if there is no parent table return false;
			if($num === 0) return false;
			
			$tbl = false;
				
			foreach($this->tables as $t)
			{
				if($t->number == $num)
				{
					if($t->isMain || !$mainOnly)
					{
						$tbl = $t;
						break;
					}
					else
					{
						$num--;
					}
				}
			}
			return $tbl;
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
		
		public function setManagers($emails)
		{
				return $this->setPermission($emails, 3);
		}
		
		public function setCurators($emails)
		{
				return $this->setPermission($emails, 2);
		}
		
		public function setSubmitters($emails)
		{
				return $this->setPermission($emails, 1);
		}
		public function getManagers()
		{
				return $this->getPermission(3);
		}
		
		public function getCurators()
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

			$res = $db->do_query("INSERT INTO project(name, submission_id, description, image, isPublic, isListed, publicSubmission, uploadToLocalServer, downloadFromLocalServer) VALUES ('{$this->name}', '{$this->submission_id}', '{$this->description}', '{$this->image}', " . ($this->isPublic ? "1" : "0") . ", " . ($this->isListed ? "1" : "0") . ", " . ($this->publicSubmission ? "1" : "0") . ", '{$this->uploadToLocalServer}', '{$this->downloadFromLocalServer}')");
			if($res === true)
			{
				$this->fetch();
				foreach($this->tables as $tbl)
				{
					$r = $tbl->addToDb();
					if($r !== true) return $r;
				}
				
				$qry = "INSERT INTO userprojectpermission (user, project, role) VALUES ({$auth->getEcUserId()}, {$this->id}, 3)";
				$res = $db->do_multi_query($qry);
				if(!$res === true) die($res + " " + $qry);
				return true;
			}
			else
			{
				return $res;
			}
			
		}
		
		public function put($oldName)
		{
			global $auth, $log, $db;
			
			/**
			 * 
			 */
			
			//$log = new Logger('Ec2');
			$log->write('info', 'Starting project update');
			
			//$db = new dbConnection();
			
			if($this->checkPermission($auth->getEcUserId()) == 3)
			{
				$log->write('info', 'User has permission');
				//$res = $db->beginTransaction();
				//if($res !== true) return $res;
				
				$log->write('info', 'transaction started');
				
				$res = $db->do_query("UPDATE project SET description = " . $db->stringVal($this->description).", image = " . $db->stringVal($this->image).",
									 isPublic  = " . $db->boolVal($this->isPublic) . ", isListed = " . $db->boolVal($this->isListed) . ",
									publicSubmission = " . $db->boolVal($this->publicSubmission) . ", uploadToLocalServer = '{$this->uploadToLocalServer}', downloadFromLocalServer = '{$this->downloadFromLocalServer}' WHERE id = {$this->id} AND name = '$oldName'");
				if($res !== true) return $res;
				
				//update form
				$sql = "UPDATE form SET projectName = '{$this->name}' WHERE projectName = '$oldName'";
				$res = $db->do_query($sql);
				if($res !== true) return $res;
				//update fields
				$sql = "UPDATE field SET projectName = '{$this->name}' WHERE projectName = '$oldName'";
				$res = $db->do_query($sql);
				if($res !== true) return $res;
				//update entries
				$sql = "UPDATE entry SET projectName = '{$this->name}' WHERE projectName = '$oldName'";
				$res = $db->do_query($sql);
				if($res !== true) return $res;
				//update entryvalues
				$sql = "UPDATE entryvalue SET projectName = '{$this->name}' WHERE projectName = '$oldName'";
				$res = $db->do_query($sql);
				if($res !== true) return $res;
				
				
				
				$log->write('info', 'Project details updated');
				
				foreach($this->tables as $tbl)
				{
						$log->write('info', "Updating form {$tbl->name}");
				
						$res = $tbl->id ? $tbl->update() : $tbl->addToDb();
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
				$qry = "SELECT f.idForm, f.Name, count(e.idEntry) as entries, count(distinct e.user) as users, count(distinct deviceId) as devices from form f Left JOIN entry e on e.form = f.idForm where f.projectName = '{$this->name}' group by f.idForm, f.Name";
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
			
			$sql = " LEFT JOIN (SELECT count(distinct user) as userTotal, count(1) as entryTotal, DATE_FORMAT(FROM_UNIXTIME(created / 1000), '{$formats[$res][0]}') as Date From entry  WHERE projectName = '{$this->name}' GROUP BY Date) b ON a.date = b.Date";
			
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
				global $SITE_ROOT;
				$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n
<xform>
	<model>
		<submission id=\"{$this->submission_id}\" projectName=\"{$this->name}\" allowDownloadEdits=\"". ($this->allowDownloadEdits ? "true" : "false") . "\" versionNumber=\"{$this->ecVersionNumber}\" />
		<uploadToServer>http://{$_SERVER["HTTP_HOST"]}{$SITE_ROOT}/{$this->name}/upload</uploadToServer>
		<downloadFromServer>http://{$_SERVER["HTTP_HOST"]}{$SITE_ROOT}/{$this->name}/download</downloadFromServer>";
		if($this->uploadToLocalServer) $xml .= "\n\t\t<uploadToLocalServer>{$this->uploadToLocalServer}</uploadToLocalServer>";
		if($this->downloadFromLocalServer) $xml .= "\n\t\t<downloadFromLocalServer>{$this->downloadFromLocalServer}</downloadFromLocalServer>";
	$xml .= "\n\t</model>\n";
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
?>