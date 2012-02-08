<?php
 require "OpenIDProvider.php";
 require "Ldap.php";

 if (isset($_REQUEST['_SESSION'])) die("Computer says no!");

 class AuthTypes
 {
 	const ANY = 0;
 	const ANON = 1;
 	const OPENID = 2;
 	const LDAP = 3;
 }
 
 class AuthManager
 {
	private $provider;
	  
	function __construct($prv)
	{
		if($prv == "OPENID")
	  	{
	   		$this->provider = new OpenIDProvider("http://test.mlst.net/index.php");
	  	}
	   	else if($prv == "LDAP")
	   	{
	   		$this->provider = new LdapProvider();
	   	}
	   	else
	   	{
	   		$this->provider = null;
	   	}
  	}
  
  	function requestlogin($requestedUrl)
  	{
  		global $cfg;
  		$_SESSION["url"] = $requestedUrl;
  		if($this->provider)
  		{
  			return $this->provider->requestLogin($requestedUrl);
  		}
  		else
  		{
  			global $url;
  			$frm =  "<p>Please Choose a Method to login</p><a class=\"provider\" href=\"$url?provider=OPENID\">Google/Gmail</a>";
			if(array_key_exists("ldap_domain", $cfg->settings["security"]) && $cfg->settings["security"]["ldap_domain"] != "")
			{
					$frm .= "<a class=\"provider\" href=\"$url?provider=LDAP\">LDAP ({$cfg->settings["security"]["ldap_domain"]})</a>";
			}
			return $frm;
  		}
   		
  	}
  
  	function callback()
  	{
  		global  $cfg;
  		$db = new dbConnection();
  		$res = $this->provider->callback();
  		if($res === true)
  		{
  			$uid = false;
  			$sql = "SELECT idUsers FROM user where details = '" . $this->provider->getCredentialString() . "'";
  		  	
  			$res = $db->do_query($sql);
  			if($res !== true) die($res . "\n" . $sql);
  			while($arr = $db->get_row_array())
  			{
  				$uid = $arr["idUsers"];
  			}
  			if(!$uid)
  			{
  				$sql = "INSERT INTO user (FirstName, LastName, Email, details, language) VALUES ('{$this->provider->firstName}','{$this->provider->lastName}','{$this->provider->email}','" . $this->provider->getCredentialString() . "','{$this->provider->language}')";
  				$db->do_query($sql);
  				$uid = $db->last_id();
  			}
  			
  			$dat = new DateTime();
  			$dat = $dat->add(new DateInterval("PT{$cfg->settings["security"]["session_length"]}S"));
  			$sql = "INSERT INTO ecsession (id, user, expires) VALUES ('" . session_id() . "', $uid, " . $dat->getTimestamp() . ");";
  			
  			$res = $db->do_query($sql);
  			if($res !== true) die($res . "\n" . $sql);
  			header("location: {$_SESSION["url"]}");
  		}
  		else
  		{
  			echo $res;
  		}
  	}
  	
  	function logout()
  	{
  		$params = session_get_cookie_params();
	    setcookie(session_name(), '', time() - 42000,
	        $params["path"], $params["domain"],
	        $params["secure"], $params["httponly"]
	    );
  		$this->provider->logout();

  	}
  
  	function isLoggedIn()
  	{
  		global $db;
  		if(!$db)
  		{
  			try 
  			{
  				$db = new dbConnection();
  			}
  			catch(Exception $e)
  			{
  				return false;
  			}
  			
  		}
  		$dat = new DateTime();
  		$qry = "DELETE FROM ecsession WHERE expires < ". $dat->getTimestamp();
  		$res = $db->do_query($qry);
  		if($res !== true) die($res . "\n" . $sql);
  		
  		$this->user = false;
  		
  		$qry = "select user, firstName, lastName, email from ecsession left join user on ecsession.user = user.idUsers WHERE ecsession.id = '" . session_id() ."'"; 
  		$res = $db->do_query($qry);
  		if($res !== true) die($res . "\n" . $sql);
  		while ($arr = $db->get_row_array()){ 
  			$this->user = $arr["user"]; 
  			$this->provider->firstName = $arr["firstName"];
  			$this->provider->lastName = $arr["lastName"];
  			$this->provider->email = $arr["email"];
  		}
   		return $this->user !== false;
  	}
  
  	private function populateSesssionInfo()
  	{
	   $db = new dbConnection();
	   $qry = "SELECT idUsers as userId, FirstName, LastName, Email, language FROM user WHERE openId = '{$_SESSION['openid']}'";
	   $err = $db->do_query($qry);
	   if($err === true)
	   {
			if($arr = $db->get_row_array())
			{
	 			foreach(array_keys($arr) as $key)
	 			{
	  				$_SESSION[$key] = $arr[$key];
	 			}
			}
   		}
	  }
	  
	  function getUserNickname()
	  {
		   //$arr = $this->openid->getAttributes();
		   
		   return "{$this->provider->firstName} {$this->provider->lastName}";
	  }
	  
	  function getEcUserId()
	  {
	   		return $this->user;
	  }
	  
	  function getUserEmail()
	  {
		  	//$arr = $this->openid->getAttributes();
			//   echo $arr["contact/email"];
		   return $this->provider->email;
	  }
}
?>