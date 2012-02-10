<?php 

class ConfigManager
{
	public $settings = array();
	private $fn;
	
	function __construct($filename)
	{
		$this->fn = $filename;
		if(file_exists($filename))
		{
			$_f = file_get_contents($filename);
		}
		else 
		{
			$fp = fopen($filename, "w+");
			fclose($fp);
			return;
		}
		
		$ls = explode("\n", $_f);
		$sec = false;
		
		for($i = 0; $i < count($ls); $i++)
		{
			$val = trim($ls[$i]);
			if(preg_match("/^\[.*\]$/", $val))
			{
				$val = substr($val, 1, strlen($val) - 2);
				$this->settings[$val] = array();
				$sec = $val;
			}
			else
			{
				$kv = explode("=", $val);
				if(count($kv) == 2) $this->settings[$sec][trim($kv[0])] = trim($kv[1]);
			}
		}
	}
	
	function writeConfig()
	{
		$str = "";
		
		foreach($this->settings as $k => $v)
		{
			$str .= "[$k]\n";
			foreach ($v as $ek => $ev)
			{
				$str .= "$ek = $ev\n";
			}
		}
		
		$_f = fopen($this->fn, "w");
		fwrite($_f, $str);
		fclose($_f);
	}
}

?>