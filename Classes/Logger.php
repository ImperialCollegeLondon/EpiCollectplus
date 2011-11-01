<?php
	class Logger
	{
		private $fn;
		private $fp;
		
		public function __construct($logName)
		{
			$this->fp = fopen ("./ec/logs/{$logName}.log" , "a");
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
?>