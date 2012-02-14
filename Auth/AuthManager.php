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
	public $language = "en";
	
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
  		
  		$provider = strtoupper($provider);
  		
  		$_SESSION["url"] = $requestedUrl;
  		if($provider != "" && array_key_exists($provider, $this->providers))
  		{
  			return $this->providers[$provider]->requestLogin($requestedUrl);
  		}
  		else
  		{
  			global $url;
  			$frm =  "<p>Please Choose a Method to login</p>";
  			if($this->localEnabled)$frm .= "<a class=\"provider\" href=\"$url?provider=LOCAL\">EpiCollect Account</a>";
  			if($this->openIdEnabled) $frm .= "<a class=\"provider\" href=\"$url?provider=OPENID\">Google/Gmail</a>";
			if($this->ldapEnabled && array_key_exists("ldap_domain", $cfg->settings["security"]) && $cfg->settings["security"]["ldap_domain"] != "")
			{
					$frm .= "<a class=\"provider\" href=\"$url?provider=LDAP\">LDAP ({$cfg->settings["security"]["ldap_domain"]})</a>";
			}
			return $frm;
  		}
   		
  	}
  
  	function callback($provider = "")
  	{
  		global  $cfg, $db;
  		
  		if(array_key_exists("provider", $_SESSION))
  		{
  			$provider = $_SESSION["provider"];
  		}
  		
  		if(!array_key_exists($provider, $this->providers)) {
  			return false;
  			//echo "provider error";
  		}
  		  		
  		$res = $this->providers[$provider]->callback();
  		//echo "***$res***";
  		if($res === true)
  		{
  			$uid = false;
  			$sql = "SELECT idUsers FROM user where details = '" . $this->providers[$provider]->getCredentialString() . "'";
  			
  			$this->firstName = $this->providers[$provider]->firstName;
  			$this->lastName = $this->providers[$provider]->lastName;
  			$this->email = $this->providers[$provider]->email;
  			$this->language = $this->providers[$provider]->language;
  			
  			$res = $db->do_query($sql);
  			if($res !== true) die($res . "\n" . $sql);
  			while($arr = $db->get_row_array())
  			{
  				$uid = $arr["idUsers"];
  			}
  			if(!$uid)
  			{
  				$sql = "INSERT INTO user (FirstName, LastName, Email, details, language, serverManager) VALUES ('{$this->firstName}','{$this->lastName}','{$this->email}','" . $this->providers[$provider]->getCredentialString() . "','{$this->language}', " . (count($this->getServerManagers()) == 0) . ")";
  				$res = $db->do_query($sql);
  				if($res !== true) die($res);
  				$uid = $db->last_id();
  				if(!$uid) die("user creation failed $sql");
  			}
  			
  			$dat = new DateTime();
  			$dat = $dat->add(new DateInterval("PT{$cfg->settings["security"]["session_length"]}S"));
  			$sql = "INSERT INTO ecsession (id, user, expires) VALUES ('" . session_id() . "', $uid, " . $dat->getTimestamp() . ");";
  			
  			$res = $db->do_query($sql);
  			if($res !== true && !preg_match("/Duplicate Key/i", $res)) die($res . "\n" . $sql);
  			header("location: {$_SESSION["url"]}");
  		}
  		else
  		{
  			flash("Login failed, please try again");
  			header("location: {$_SERVER["REQUEST_URI"]}");
  		}
  	}
  	
  	function logout($provider = "")
  	{
  		//if(!array_key_exists($provider, $this->providers)) return false;
  		global $db;
  		if(!$db) $db = new dbConnection();
  		  		
  		$res = $db->do_query("DELETE FROM ecsession WHERE id = '" . session_id() . "'");
  		if(!$res) die("$res - $sql");
  		
  		$params = session_get_cookie_params();
	    setcookie(session_name(), '', time() - 42000,
	        $params["path"], $params["domain"],
	        $params["secure"], $params["httponly"]
	    );
  		//$this->providers[$provider]->logout();

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
  		if($res !== true) return false;
  		
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
  		try{
  			$men = array();
  			if($db)
  			{
		  		$qry = "SELECT firstName, lastName, Email FROM User WHERE serverManager = 1";
		  		$res = $db->do_query($qry);
		  		if($db->do_query($qry) !== true) die("$res");
		  		
		  		
		  		while($arr = $db->get_row_array())
		  		{
		  			array_push($men, $arr);
		  		}
  			}
  			
	  		return $men;
  		}
	  	catch(ErrorException $err)
	  	{
	  		return array();
	  	}
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
	  	 	return $this->providers["LOCAL"]->createUser($username, $pass, $email, $firstName, $lastName, $language);
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