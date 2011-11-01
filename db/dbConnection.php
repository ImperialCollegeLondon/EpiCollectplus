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
		
		public function __construct($un = false, $pwd = false)
		{
			global $DBUSER, $DBPASS, $DBSERVER, $DBNAME;
			if($un)
			{
				$this->username = $un;
				$this->password = $pwd;
			}
			else
			{
				$this->username = $DBUSER;
				$this->password = $DBPASS;
			}
			$this->server = $DBSERVER;
			$this->schema = $DBNAME;
			$this->port = 3306;
			
			$this->con = mysqli_connect($this->server, $this->username, $this->password, NULL,  $this->port);
			$this->con->set_charset('utf-8');
			try{
				mysqli_select_db($this->con, $this->schema);
			}catch(Exception $e){}
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
		
		public function do_multi_query($qry)
		{
			if($this->resSet && !is_bool($this->resSet)) mysqli_free_result($this->resSet);
			$this->resSet = mysqli_multi_query($this->con, $qry);
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

?>