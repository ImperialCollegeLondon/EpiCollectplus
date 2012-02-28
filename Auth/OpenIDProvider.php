<?php 
	require "openid.php";
	require_once "ProviderTemplate.php";
	
	class OpenIDProvider extends AuthProvider
	{
	
		public function __construct($url)
		{
			global $SITE_ROOT;
			
			$this->openid = new LightOpenID("$SITE_ROOT/loginCallback");
			$this->openid->identity = array_key_exists("openid", $_SESSION) ? $_SESSION["openid"] : "";
			$this->openid->required = array('namePerson/first', 'namePerson/last', 'contact/email', 'contact/country/home', 'pref/language');
		}
		
		public function requestLogin($callbackUrl, $firstLogin = false)
		{
			/*if(!$this->openid->mode)
			{*/
				if(!$this->openid->identity)$this->openid->identity = 'https://www.google.com/accounts/o8/id';
				header('Location: ' . $this->openid->authUrl());
				return false;
			/*}
			else if ($this->openid->mode === "cancel")
			{
				return false;
			}
			else
			{
				return true;
			}*/
		}
		
		public function callback()
		{
			if($this->openid->validate())
			{
				$this->data = array();
				$arr = $this->openid->getAttributes();
				
				$this->data["openid"] = $this->openid->identity;
				$this->firstName = $arr["namePerson/first"];
				$this->lastName = $arr["namePerson/last"];
				$this->email = $arr["contact/email"];
				$this->language = $arr["pref/language"];
// 				$_SESSION["openid"] = $this->openid->identity;
// 				$this->populateSesssionInfo();
			
// 				if(!isset($_SESSION["Email"]) || $_SESSION["Email"] == "")
// 				{
// 					$arr = $this->openid->getAttributes();
// 					$db2 = new dbConnection();
// 					$qry = "INSERT INTO user (FirstName, LastName, Email, openId, language) VALUES ('{$arr["namePerson/first"]}','{$arr["namePerson/last"]}','{$arr["contact/email"]}','{$_SESSION["openid"]}','{$arr['pref/language']}') " .
// 				   "ON DUPLICATE KEY UPDATE FirstName = '{$arr["namePerson/first"]}', LastName = '{$arr["namePerson/last"]}', openId = '{$_SESSION["openid"]}', language = '{$arr['pref/language']}'";
// 					$res = $db2->do_query($qry);
// 					if($res !== true) echo $res;
// 					//$db2->__destruct();
// 					$this->populateSesssionInfo();
// 				}
				return true;
			}
			else
			{
				return false;
			}
		}
		
		public function logout(){}
		public function setCredentialString($str){}
		public function getDetails(){}
	}
?>