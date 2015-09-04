<?php
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
	
	function parseBool($val)
	{
		if(gettype($val) == gettype("string"))
		{
			if($val === "true")
			{
				return true;
			}
			elseif($val === "false")
			{
				return false;
			}
			else
			{
				throw new Exception("parseBool value argument must be true or false.");	
			}
		}
		else
		{
			throw new Exception("parseBool only accepts strings");	
		}
	}
?>