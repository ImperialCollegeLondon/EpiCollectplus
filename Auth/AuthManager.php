<?php
 require "OpenIDProvider.php";
 require "Ldap.php";
 require "LocalAuthProvider.php";
 
 class AuthManager
 {
	private $provider;

	public $firstName;
	public $lastName;
	public $email;
	
	private $openIdEnabled = true;
	private $ldapEnabled = true;
	private $localEnabled = true;
	
	function __construct()
	{
		global $cfg;
		
		if(array_key_exists("use_openID", $cfg->settings["security"]))
		{
			$this->openIdEnabled = $cfg->settings["security"]["use_openID"];
		}

		if(array_key_exists("use_ldap", $cfg->settings["security"]))
		{
			$this->ldapEnabled = $cfg->settings["security"]["use_ldap"];
		}
		
		if(array_key_exists("use_local", $cfg->settings["security"]))
		{
			$this->localEnabled = $cfg->settings["security"]["use_local"];
		}
		
		$this->providers = array();
		
		if($this->openIdEnabled)
	  	{
	   		$this->providers["OPENID"] = new OpenIDProvider("http://test.mlst.net/index.php");
	  	}
	   	if($this->ldapEnabled)
	   	{
	   		$this->providers["LDAP"] = new LdapProvider();
	   	}
	   	if($this->localEnabled)
	   	{
	   		$this->providers["LOCAL"] = new LocalLoginProvider();
	   	}
  	}
  
  	function requestlogin($requestedUrl, $provider = "")
  	{
  		global $cfg;
  		$_SESSION["url"] = $requestedUrl;
  		if($provider != "" && array_key_exists($provider, $this->providers))
  		{
  			return $this->providers[$provider]->requestLogin($requestedUrl);
  		}
  		else
  		{
  			global $url;
  			$frm =  "<p>Please Choose a Method to login</p><a class=\"provider\" href=\"$url?provider=local\">EpiCollect Account</a><a class=\"provider\" href=\"$url?provider=OPENID\">Google/Gmail</a>";
			if(array_key_exists("ldap_domain", $cfg->settings["security"]) && $cfg->settings["security"]["ldap_domain"] != "")
			{
					$frm .= "<a class=\"provider\" href=\"$url?provider=LDAP\">LDAP ({$cfg->settings["security"]["ldap_domain"]})</a>";
			}
			return $frm;
  		}
   		
  	}
  
  	function callback($provider = "")
  	{
  		global  $cfg;
  		
  		if(!array_key_exists($provider, $this->providers)) return false;
  		
  		$db = new dbConnection();
  		$res = $this->providers[$provider]->callback();
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
  	
  	function logout($provider = "")
  	{
  		if(!array_key_exists($provider, $this->providers)) return false;
  		$params = session_get_cookie_params();
	    setcookie(session_name(), '', time() - 42000,
	        $params["path"], $params["domain"],
	        $params["secure"], $params["httponly"]
	    );
  		$this->providers[$provider]->logout();

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
  		
  		$qry = "select user, firstName, lastName, email, serverManager from ecsession left join user on ecsession.user = user.idUsers WHERE ecsession.id = '" . session_id() ."'"; 
  		$res = $db->do_query($qry);
  		if($res !== true) die($res . "\n" . $qry);
  		while ($arr = $db->get_row_array()){ 
  			$this->user = $arr["user"]; 
  			$this->firstName = $arr["firstName"];
  			$this->lastName = $arr["lastName"];
  			$this->email = $arr["email"];
  			$this->serverManager = $arr["serverManager"];
  		}
   		return $this->user !== false;
  	}
  
  	function isServerManager()
  	{
  		return !!$this->serverManager;
  	}
  	
  	function makeServerManager($email)
  	{
  		global $db;
  		$qry = "select serverManager from user WHERE email = '$email'";
  		$res = $db->do_query($qry);
  		$r=0;$u=0;
  		while ($arr = $db->get_row_array())
  		{
  			$u++;
  			$r += $arr["serverManager"];
  		}
  		
  		if($u == 0) return 0;
  		if($r > 0) return -1;
  		
  		$qry = "UPDATE user SET serverManager = 1 WHERE email = '$email'";
  		if($db->do_query($qry) !== true) die("oops"); 
  		return 1;
  	}
  	
  	function removeServerManager($email)
  	{
  		global $db;
  		$qry = "UPDATE user SET serverManager = 0 WHERE email = '$email'";
  		if($db->do_query($qry) !== true) die("oops");
  	}
  	
  	function getServerManagers()
  	{
  		global $db;
  		
  		$qry = "SELECT firstName, lastName, Email FROM User WHERE serverManager = 1";
  		$res = $db->do_query($qry);
  		if($db->do_query($qry) !== true) die("$res");
  		
  		$men = array();
  		while($arr = $db->get_row_array())
  		{
  			array_push($men, $arr);
  		}
  		return $men;
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
	  
	  function createUser($username, $pass, $email, $firstName, $lastName, $language)
	  {
	  	if($this->localEnabled)
	  	{
	  	 	return $this->provider->createUser($username, $pass, $email, $firstName, $lastName, $language);
	  	}
	  	else
	  	{
	  		return false;
	  	}
	  }
	  
	  function getUserNickname()
	  {
		   //$arr = $this->openid->getAttributes();
		   
		   return "{$this->firstName} {$this->lastName}";
	  }
	  
	  function getEcUserId()
	  {
	   		return $this->user;
	  }
	  
	  function getUserEmail()
	  {
		  	//$arr = $this->openid->getAttributes();
			//   echo $arr["contact/email"];
		   return $this->email;
	  }
}
?>