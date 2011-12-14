<?php
	class Logger
	{
		private $db;
		
		public function __construct($logName)
		{
			$this->db = new dbConnection(); 
		}
		
		public function close()
		{
			$this->db->__destruct();
		}
		
		public function write($level, $msg)
		{
			$dat = new DateTime('now', new DateTimeZone('UTC'));
			$ts = $dat->getTimestamp();
			
			$level = $this->db->escapeArg($level);
			$msg = $this->db->escapeArg($msg);
			
			$qry = "INSERT INTO Logs(`Timestamp`, `Type`, `Message`) VALUES ($ts, '$level', '$msg')";
			$res = $this->db->do_query($qry);
			if($res !== true) throw new ErrorException($db->error($res));	
		}
	}
?>