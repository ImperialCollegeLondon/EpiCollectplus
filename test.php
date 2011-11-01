<?php
	include "./Auth/AuthManager.php";
	include './db/dbConnection.php';
	include "./Auth/OpenID.php";
	include "./Auth/OAuth.php";
	include "./settings.php";

	session_start();
	if(!isset($_SESSION["EPICOLLECT_ACCESS_TOKEN"])) OAuthTest();
	//else echo $_SESSION["EPICOLLECT_ACCESS_TOKEN"]
?>
<!DOCTYPE html?>
<html lang="en">
	<head>
		<title>EpiCollect 2 Unit Tests</title>
		<link rel="stylesheet" type="text/css" href="EpiCollect2.css" />
	</head>
	<body>
		<h1>EpiCollect 2 Unit Tests</h1>
		
		<table>
			<tr>
				<th scope="col">Item Tested</th>
				<th scope="col">Result</th>
			</tr>
<?php
	

	//SQL battery
	echo"<tr><th colspan=\"2\">SQL Tests</th></tr>
	<tr><td>Connect to db</td><td>";
	try{
		$db = new dbConnection();
		echo "<span class=\"success\">OK</span>";
	}catch(Exception $e)
	{
		echo "<span class=\"failure\">{$e->message}</span>";
	}
	echo "</td></tr>";
	
	$spPass = true;
	
	$res = $db->do_query("CALL getOAuthProvider('twitter');");
	while($arr = $db->get_row_array()) {
	
	}
	if($res === true)
	{
	
	}
	else
	{
		$spPass = false;
		echo"<tr><td>Get OAuth Provider (twitter)</td><td>";
		echo "<span class=\"failure\">$res</span>";
	}

	$db = new dbConnection();
	
	$res = $db->do_query("CALL setOAuthLoginDetails('twitter', '12345', 'test', 'testToken', 'testToken', 'abcdefghij');");
	if($res === true)
	{
		
	}
	else
	{
		$spPass = false;
		echo"<tr><td>Set OAuth Login details</td><td>";
		echo "<span class=\"failure\">$res</span>";
	}
	
	$db = new dbConnection();
	
	$res = $db->do_query("CALL updateUser(2, 'name', 'email@domain.tld');");
	if($res === true)
	{
		
	}
	else
	{
		$spPass = false;
		echo"<tr><td>Update User</td><td>";
		echo "<span class=\"failure\">$res</span>";
	}
	
	$db = new dbConnection();
	
	$res = $db->do_query("CALL getUserOAuthDetails(2, 'twitter');");
	if($res === true)
	{
		
	}
	else
	{
		$spPass = false;
		echo"<tr><td>Get User OAuth</td><td>";
		echo "<span class=\"failure\">$res</span>";
	}
	
	$db = new dbConnection();
	
	$res = $db->do_query("CALL endOAuthSession(2, 'twitter');");
	if($res === true)
	{
		
	}
	else
	{
		$spPass = false;
		echo"<tr><td>End OAuth Session</td><td>";
		echo "<span class=\"failure\">$res</span>";
	}
	
	if($spPass)
	{
		echo"<tr><td>Stored Procedures</td><td>";
		echo "<span class=\"success\">OK</span></tr>";
	}

	echo "<tr><th colspan=\"2\">Authorisation Interface</th></tr>";
	//Authorisation battery

	//Oauth Test
	
	//OpenId Test
	function OpenIDTest()
	{
		$authManager = new AuthManager();
		echo $authManager->login("");
	}
	
	function OAuthTest()
	{
		$authManager = new AuthManager();
		$authManager->login(NULL, "twitter");
	}
?>
	<tr>
		<td>OAuth Interface</td>		
		<td>
<?php	
	try{
		if(isset($_SESSION["EPICOLLECT_ACCESS_TOKEN"]))
		{
			echo "<span class=\"success\">OK</span>";
		}
		
	}catch(Exception $e)
	{
		echo "<span class=\"failure\">{$e}</span>";
	}
?>
		</td>
	</tr>
		</table>
	</body>	
</html>