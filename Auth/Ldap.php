<?php 
	require_once "ProviderTemplate.php";

	class LdapProvider extends AuthProvider
	{
		private $ldap;
		private $callbackUrl;
		
		
		public function __construct()
		{
			global $cfg;
			$this->ldap = ldap_connect($cfg->settings["security"]["ldap_server_uri"]); // from settings
		}
		
		public function requestLogin($callbackUrl, $firstLogin = false)
		{
			global $SITE_ROOT;
			//Check connection is secure and add LDAP Login form
			return '<p>Please use the form below to log into EpiCollect+</p><form action="'.$SITE_ROOT.'/loginCallback" method="POST"><p><label for="uname">User name</label><input type="text" name="uname" /></p><p><label for="pwd">Password</label><input type="password" name="pwd" /></p><p><input type="Submit" name="Login" value="Login" /><input type="hidden" name="callback" value="'.$callbackUrl.'"</p></form>';
		}
		
		public function callback()
		{
			global $cfg;
			if(ldap_bind($this->ldap, $cfg->settings["security"]["ldap_bind_user"] , $cfg->settings["security"]["ldap_bind_pwd"]))
			{
				$searchfilter = "(&(" . $cfg->settings["security"]["ldap_username_attr"].'='.$_POST["uname"].")".$cfg->settings["security"]["ldap_search_filter"].")";
				$found = ldap_search($this->ldap, $cfg->settings["security"]["ldap_search_base"], $searchfilter);
				$results =  ldap_get_entries($this->ldap, $found);
				if($results["count"] == 1)
				{
					$dn = $results[0]["dn"];

					//Bind as user
					if(ldap_bind($this->ldap, $dn, $_POST["pwd"]))
					{
						$this->data["sAMAccountName"] = $_POST["uname"];
                                                $this->firstName = $results[0]['givenname'][0];
                                                $this->lastName = $results[0]['sn'][0];
                                                $this->email = $results[0]['mail'][0];
                                                $this->language = "en";
                                                               
                                                return true;	
						/*
						$grps = ldap_get_values($this->ldap, $ent, "memberOf");
					
						foreach($grps as $grp){
							if(preg_match("/{$cfg->settings["security"]["ldap_ug"]}/", $grp))
							{
								$this->data = array();
							
								$this->data["sAMAccountName"] = $_POST["uname"];
								$grps = ldap_get_values($this->ldap, $ent, "givenName");
								$this->firstName = $grps[0];
								$grps = ldap_get_values($this->ldap, $ent, "sn");
								$this->lastName = $grps[0];
								$grps = ldap_get_values($this->ldap, $ent, "mail");
								$this->email = $grps[0];
								$this->language = "en";
								
								return true;
							}	
						}
						return "Not in group";
						*/
					}
					else
					{	
						return "Bad Credentials";
					}
				}
				else
				{
					return "User Not Found";
				}
				ldap_unbind($this->ldap);
			}
			else
			{
				return "Bad Credentials";
			}
			
		}
		
		public function logout(){}
		public function setCredentialString($str){}
		public function getDetails(){}
		
		
	}
?>