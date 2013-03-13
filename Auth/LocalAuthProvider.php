<?php
	@include "./ProviderTemplate.php";
	
	class LocalLoginProvider extends AuthProvider
	{
		private $db;
		
		public function __construct()
		{
			global $db;
			$this->db = $db;
		}

		function getType(){return "LOCAL";}
		
		function resetPassword($uid)
		{
			global $db, $cfg;
			
			$str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
			$str = str_shuffle($str);
			$str = substr($str, -10);
			
			$qry = "select details from user where idUsers = $uid";
			$res = $db->do_query($qry);
			if($res !== true) return $res;
			
			$creds = null;
			
			while($arr = $db->get_row_array())
			{
				$creds = json_decode($arr["details"]);
			}
			
			$salt = $cfg->settings["security"]["salt"];
			$enc_data = crypt($str, "$2a$08$".$salt ."$");
			
			$creds->auth = $enc_data; 
			
			$data = "{\"username\" : \"{$creds->username}\", \"auth\" : \"$enc_data\" }";
			
			$qry = "UPDATE user SET details = '$data' WHERE idUsers = $uid";
			if($db->do_query($qry) == true)
			{
				return $str;
			} 
		}
		
		function setPassword($uid, $password)
		{
			global $db, $cfg;
							
			$qry = "select details from user where idUsers = $uid";
			$res = $db->do_query($qry);
			if($res !== true) return $res;
				
			$creds = "";
				
			while($arr = $db->get_row_array())
			{
				$creds = json_decode($arr["details"]);
			}
				
			$salt = $cfg->settings["security"]["salt"];
			$enc_data = crypt($password, "$2a$08$".$salt ."$");
				
			$creds->auth = $enc_data;
				
			$data = "{\"username\" : \"{$creds->username}\", \"auth\" : \"$enc_data\" }";
				
			$qry = "UPDATE user SET details = '$data' WHERE idUsers = $uid";
			if($db->do_query($qry) == true)
			{
				return true;
			}
			else
			{
				return false;
			}
		}		
		
		function getUserName($uid)
		{
			global $db;
				
			$qry = "select details from user where idUsers = $uid";
			$res = $db->do_query($qry);
			if($res !== true) return $res;
			
			while($arr = $db->get_row_array())
			{
				$creds = json_decode($arr["details"]);
			}
			
			return $creds->username;
		}
		
                public function requestSignup()
                {
                    global $cfg;
                    if($cfg->settings["misc"]["public_server"] === "true")
                    {
			
                    return '<p>Please enter your details to register to use this server</p>
                     <form method="POST" action="admin">
                             <p>
                                     <label for="fname">First Name</label>
                                     <input type="text" name="fname" />
                             </p>
                             <p>
                                     <label for="lname">Last Name</label>
                                     <input type="text" name="lname" />
                             </p>
                             <p>
                                     <label for="email">Email</label>
                                     <input type="email" name="email" />
                             </p>

                             <p>
                                     <label for="username">User Name</label>
                                     <input type="text" name="username" />
                             </p>

                             <p>
                                     <label for="password">Password</label>
                                     <input type="password" name="password" />
                             </p>
                             <p>
                                     <label for="password_check">Repeat Password</label>
                                     <input type="password" name="password_check" />
                             </p>
                             <p> 
                                     <input type="hidden" name="provider" value="LOCAL" />
                                     <input type="submit" value="Create User"/>
                             </p>
                     </form>';	
                    }
                    else
                    {
                        return "<p>This server is not public</p>";
                    }
			
                }
                
                
		public function requestLogin($callbackUrl, $firstLogin = false)
		{
		
			if($firstLogin)
			{
				return '<p>Please create the account for the server administrator</p>
			<form method="POST" action="admin">
				<p>
					<label for="fname">First Name</label>
					<input type="text" name="fname" />
				</p>
				<p>
					<label for="lname">Last Name</label>
					<input type="text" name="lname" />
				</p>
				<p>
					<label for="email">Email</label>
					<input type="email" name="email" />
				</p>

				<p>
					<label for="username">User Name</label>
					<input type="text" name="username" />
				</p>

				<p>
					<label for="password">Password</label>
					<input type="password" name="password" />
				</p>
				<p>
					<label for="password_check">Repeat Password</label>
					<input type="password" name="password_check" />
				</p>
				<p> 
                                        <input type="hidden" name="provider" value="LOCAL" />
					<input type="submit" value="Create User"/>
				</p>
			</form>';	
			}
			else
			{
				global $SITE_ROOT;
				return '<p>Please use the form below to log into EpiCollect+</p><form action="'.$SITE_ROOT.'/loginCallback" method="POST"><p><label for="uname">User name</label><input type="text" name="uname" /></p><p><label for="pwd">Password</label><input type="password" name="pwd" /></p><p><input type="Submit" name="Login" value="Login" /><input type="hidden" name="provider" value="LOCAL" /><input type="hidden" name="callback" value="'.$SITE_ROOT . "/" . $callbackUrl.'"</p></form>';
			}
		}
		
		public function callback()
		{
			if(!$this->db)
			{
				global $db;
				$this->db = $db;
                        }
                        
			global $cfg;
			//don't use MD5!
			$salt = $cfg->settings["security"]["salt"];		
			
			$data =  $this->db->escapeArg($_POST["pwd"]);
			$username =  $this->db->escapeArg($_POST["uname"]);
			$enc_data = crypt($data, "$2a$08$".$salt."$");
			$this->data = "{\"username\" : \"$username\", \"auth\" : \"$enc_data\" }";
			
			$res = $this->db->do_query("SELECT idUsers FROM user WHERE details = '$this->data';");
			if($res !== true) die("!!!$res");
			
			if($arr = $this->db->get_row_array())
			{
				return true;
			}
			return false;			
		}
		
		public function createUser($username, $pass, $email, $firstName, $lastName, $language, $serverManager = false)
		{
			global $cfg;
			//don't use MD5!
			
			if(!$this->db)
			{
				global $db;
				$this->db = $db;
 				include_once("db/dbConnection.php");
				if(!$this->db) $this->db = new dbConnection();
			}
			
			$username = $this->db->escapeArg($username);
			$pass = $this->db->escapeArg($pass);
			$email = $this->db->escapeArg($email);
			$firstName = $this->db->escapeArg($firstName);
			$lastName = $this->db->escapeArg($lastName);
			$language = $this->db->escapeArg($language);
			
			$salt = $cfg->settings["security"]["salt"];
			$enc_data = crypt($pass, "$2a$08$".$salt ."$");
			$this->data = "{\"username\" : \"{$username}\", \"auth\" : \"$enc_data\" }";
			
			$sman = $serverManager ? "1" : "0";
			
			$res = $this->db->do_query("INSERT INTO user (FirstName, LastName, Email, details, language, serverManager) VALUES ('$firstName', '$lastName', '$email', '{$this->data}', '$language', $sman)");
		
			return $res;
		}
		
		public function logout(){
		}
		public function setCredentialString($str){
		}
		public function getDetails(){
		}
	}
?>