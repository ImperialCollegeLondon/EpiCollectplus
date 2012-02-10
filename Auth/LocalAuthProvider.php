<?php
	require_once "ProviderTemplate.php";
	
	class LocalLoginProvider extends AuthProvider
	{
		private $db;
		
		public function __construct()
		{
			$this->db = new dbConnection();
		}
		
		public function requestLogin($callbackUrl)
		{
			return '<form action="loginCallback" method="POST"><p><label for="uname">User name</label><input type="text" name="uname" /></p><p><label for="pwd">Password</label><input type="password" name="pwd" /></p><p><input type="Submit" name="Login" value="Login" /><input type="hidden" name="callback" value="'.$callbackUrl.'"</p></form>';
		}
		
		public function callback()
		{
			global $cfg;
			//don't use MD5!
			$salt = $cfg->settings["security"]["salt"];		
			
			$data =  $this->db->escapeArg($_POST["pwd"]);
			$username =  $this->db->escapeArg($_POST["uname"]);
			$enc_data = crypt($data, "$2a$08${$salt}$");
			$data = "{\"username\" : \"$username\" \"auth\" : \"$enc_data\" }";
			
			$res = $this->db->do_query("SELECT id FROM user WHERE details = $data;");
			if($res !== true) die($res);
			
			if($res = $this->db->fetch_row_array())
			{
				return true;
			}
			return false;			
		}
		
		public function createUser($username, $pass, $email, $firstName, $lastName, $language)
		{
			global $cfg;
			//don't use MD5!
			
			$username = $this->db->escapeArg($username);
			$pass = $this->db->escapeArg($pass);
			$email = $this->db->escapeArg($email);
			$firstName = $this->db->escapeArg($firstName);
			$lastName = $this->db->escapeArg($lastName);
			$language = $this->db->escapeArg($language);
			
			$salt = $cfg->settings["security"]["salt"];
			$enc_data = crypt($pass, "$2a$08${$salt}$");
			$data = "{\"username\" : \"{$username}\" \"auth\" : \"$enc_data\" }";
			
			$res = $this->db->do_query("INSERT INTO user (FirstName, LastName, Email, details, language) VALUES ()");
			if($res !== true) die($res);
			return true;
		}
		
		public function logout(){
		}
		public function setCredentialString($str){
		}
		public function getDetails(){
		}
	}
?>