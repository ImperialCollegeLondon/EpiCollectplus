<?php
	
	class dbConnection
	{
		private $con;
		private $resSet;
		private $numRows;
		private $username;// = $DBUSER;
		private $password;// = $DBPASS;
		private $server;// = $DBSERVER;
		private $schema;// = $DBNAME;
		private $port;// = 3306;
		
		public function __construct($un = false, $pwd = false)
		{
			global $cfg;
			
			if($un)
			{
				$this->username = $un;
				$this->password = $pwd;
			}
			else
			{
				$this->username = $cfg->settings["database"]["user"];
				$this->password = $cfg->settings["database"]["password"];
			}
			$this->server = $cfg->settings["database"]["server"];
			$this->schema = $cfg->settings["database"]["database"];;
			$this->port = $cfg->settings["database"]["port"];
			
			$this->con = new mysqli($this->server, $this->username, $this->password, NULL,  $this->port);
			$this->con->set_charset('utf-8');
			try{
				$this->con->select_db($this->schema);
			}catch(Exception $e){}
		}
		
		public function __destruct()
		{
			$this->con->close();
		}
		
		public function boolVal($val) {return $val === true || $val === "true" ? "1" : "0";}
		public function boolVal2($val) {return $val === false || $val === "false" ? "0" : "1";}
		public function stringVal($val) {return $val == "" ? "NULL" : "'". mysqli_escape_string($this->con, $val) . "'";}
		public function numVal($val) {return !$val && $val !== 0 && $val !== 0.0 ? "NULL" : "$val";}
		
		public function beginTransaction()
		{
			if($this->con->query("START TRANSACTION;"))
			{
				return true;
			}
			else
			{
				return "START TRANSACTION;\r\n" . $this->con->errno . " : " . $this->con->error;
			}
		}
		
		public function commitTransaction()
		{
			if($this->con->query("COMMIT;"))
			{
				return true;
			}
			else
			{
				return "COMMIT;\r\n" . $this->con->errno . " : " .$this->con->error;
			}
		}
		
		public function rollbackTransaction()
		{
			if($this->con->query( "ROLLBACK;"))
			{
				return true;
			}
			else
			{
				return "ROLLBACK;\r\n" . $this->con->errno . " : " .$this->con->error;
			}
		}
		
		public function affectedRows()
		{
			return $this->numRows;
		}
		
		public function escapeArg($arg)
		{
			return $this->con->escape_string($arg);
		}
		
		public function do_query($qry)
		{
			if($this->resSet && !is_bool($this->resSet)) mysqli_free_result($this->resSet);
			$this->resSet = $this->con->query($qry);
			$this->numRows = $this->con->affected_rows;
			if($this->resSet)
			{
				
				return true;
			}
			else
			{
				//echo $qry .  "\r\n" . mysqli_errno($this->con) . " : " . mysqli_error($this->con);
				return $qry .  "\r\n" . $this->con->errno. " : " .$this->con->error;
			}
		}
		
		public function do_multi_query($qry)
		{
			if($this->resSet && !is_bool($this->resSet)) mysqli_free_result($this->resSet);
			$this->resSet = $this->con->multi_query($qry);
			if($this->resSet)
			{
				return true;
			}
			else
			{
				//echo $qry .  "\r\n" . mysqli_errno($this->con) . " : " . mysqli_error($this->con);
				return $qry .  "\r\n" . $this->con->errno . " : " .$this->con->error;
			}
		}
		
		public function exec_sp($spName, $args = Array())
		{
			//if($this->resSet && !is_bool($this->resSet)) mysqli_free_result($this->resSet);
			for($i = 0; $i < count($args); $i++)
			{
				//$args[$i] = mysqli_escape_string($this->con, $args[$i]);
				
				if((is_string($args[$i]))){ $args[$i] = "'".str_replace("'", "\\\\'",$this->con->escape_string($args[$i]))."'"; }
				
				else if(!$args[$i]){
					if(is_int($args[$i]) || is_double($args[$i]) || is_bool($args[$i])) $args[$i] = "0";
					else $args[$i] = "NULL";
				}
			}
			
			$qry = "CALL $spName (" . implode(", ", $args) . ")";
			
			$this->resSet = $this->con->query($qry);
			if($this->resSet)
			{
				return true;
			}
			else
			{
				return $qry . "\r\n" .$this->con->errno . " : " . $this->con->error;
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
			return $this->con->insert_id;
		}
		
	}

?>