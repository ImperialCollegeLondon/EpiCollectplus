<?php
	class Logger
	{
		private $db;
		
		public function __construct($logName)
		{
			try{$this->db = new dbConnection();}catch(Exception $err) {$this->db = false;} 
		}
		
		public function close()
		{
			if($this->db)$this->db->__destruct();
		}
		
		public function write($level, $msg)
		{
			if(!$this->db) return;
			
			$dat = new DateTime('now', new DateTimeZone('UTC'));
			$ts = $dat->getTimestamp();
			
			$level = $this->db->escapeArg($level);
			$msg = $this->db->escapeArg($msg);
			
			$qry = "INSERT INTO logs(`Timestamp`, `Type`, `Message`) VALUES ($ts, '$level', '$msg')";
			$res = $this->db->do_query($qry);
			if($res !== true) throw new ErrorException($this->db->error($res));	
		}
	}
?>