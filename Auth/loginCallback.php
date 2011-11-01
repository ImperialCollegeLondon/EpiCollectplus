<?php
	include './AuthManager.php';
	include '../db/dbConnection.php';
	include "./OpenID.php";
	include "./OAuth.php";
	include "../settings.php";
	session_start();

	if(!isset($_SESSION["EPICOLLECT_ACCESS_TOKEN"]))
	{
		$manager = new AuthManager();
		if(isset($_SESSION["provider"]))
		{
			$manager->authCallback();
		}		
	}
	
	header("Location: http://epicollect2.local.tld/test.php");
	

?>
