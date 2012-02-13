<?php 
	require_once "ProviderTemplate.php";

	class LdapProvider extends AuthProvider
	{
		private $ldap;
		private $callbackUrl;
		
		
		public function __construct()
		{
			$this->ldap = ldap_connect("FI--DIDEDC2"); // from settings
		}
		
		public function requestLogin($callbackUrl)
		{
			//Check connection is secure and add LDAP Login form
			return '<p>Please use the form below to log into EpiCollect+</p><form action="loginCallback" method="POST"><p><label for="uname">User name</label><input type="text" name="uname" /></p><p><label for="pwd">Password</label><input type="password" name="pwd" /></p><p><input type="Submit" name="Login" value="Login" /><input type="hidden" name="callback" value="'.$callbackUrl.'"</p></form>';
		}
		
		public function callback()
		{
			global $cfg;
			if($x = @ldap_bind($this->ldap, "{$cfg->settings["security"]["ldap_domain"]}\\{$_POST["uname"]}" , $_POST["pwd"]))
			{
				$res = @ldap_search($this->ldap, "ou=Users,ou=DIDE Users,dc=DIDE,dc=local","sAMAccountName={$_POST["uname"]}");
				if($res)
				{
					$ent = ldap_first_entry($this->ldap, $res);
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