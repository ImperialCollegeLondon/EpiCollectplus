<?php
	abstract Class AuthProvider
	{
		
		public $data;
		public $language;
		
		abstract public function requestLogin($callbackurl);
		abstract public function getDetails();
		public function getCredentialString()
		{
			if(function_exists("json_encode"))
			{
				return json_encode($this->data);
			}
			else
			{
				$str = "{";
				
				foreach($this->data as $k => $v)
				{
					$str .= "\"$k\" : \"$v\",";
				}
				$str = trim($str, ",") . "}";
			}
				
		}
		abstract public function setCredentialString($str);
		abstract public function logout();
	}
?>