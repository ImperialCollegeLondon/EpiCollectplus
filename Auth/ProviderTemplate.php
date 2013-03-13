<?php
	abstract Class AuthProvider
	{
		
		public $data;
		public $language;
		
		abstract public function requestLogin($callbackurl, $firstLogin = false);
		abstract public function getDetails();
		abstract public function getType();
		
		public function getCredentialString()
		{
			if(is_string($this->data))
			{
				return $this->data;
			}
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
                
                public function getEmail()
                {
                    return $this->email;
                }
                
		abstract public function setCredentialString($str);
		abstract public function logout();
	}
?>