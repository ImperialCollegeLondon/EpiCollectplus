<?php
 /*
  * EpiCollect 2 OAuth interface
  * v1.0 	Created : 28/9/2010 	Last Edited  : 01/10/2010
  * Created By : Chris Powell 	Last Editor : Chris Powell
  * v1.0 using the Zend Framework OAuth Library
  */
 define('URL_ROOT', 'http://epicollect2.local.tld');
 include 'Zend/Oauth/Consumer.php';
 
 class EcOAuthComsumer{
  public $configuration = array(
   'version' => '1.0', // there is no other version...
   'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER,
   'signatureMethod' => 'HMAC-SHA1',
   'callbackUrl' => '',
   'requestTokenUrl' => '',
   'authorizeUrl' => '',
   'accessTokenUrl' => '',
   'consumerKey' => '',
   'consumerSecret' => ''
  );
  public $token;
  public $consumer;
  private $provider;
  public $providerFriendlyName;
  public $providerIcon;
  
  function __construct(){
   
  }
  
  function getConfiguration($provider)
  {
   $db = new dbConnection();
   $res = $db->do_query("CALL getOAuthProvider('$provider')");
   if($res)
   {
	$this->provider = $provider;
	$p = $db->get_row_object();
	$this->configuration['callbackUrl'] = "http://" . $_SERVER["HTTP_HOST"] . $p->callbackUrl;
	$this->configuration['requestTokenUrl'] = $p->requestTokenUrl;
	$this->configuration['authorizeUrl'] = $p->authorizeUrl;
	$this->configuration['accessTokenUrl'] = $p->accessTokenUrl;
	$this->configuration['consumerKey'] = $p->consumerKey;
	$this->configuration['consumerSecret'] = $p->consumerSecret;
	$this->providerFriendlyName = $p->friendlyNameVar;
	$this->providerIcon = $p->providerIcon;
   }
   else
   {
	//log/throw error
	$err = new OAuthException();
	$err->error = 404;
	$err->message = "Provider \"$provider\" is not available for use with EpiCollect";
	throw $err;
   }
  }
  
  function login()
  {
   //session_start(); -- handled by AuthManager
   
   $this->consumer = new Zend_OAuth_Consumer($this->configuration);
   
   $_SESSION["EPICOLLECT_TOKEN"] = NULL;
   
   if(!isset($_SESSION["EPICOLLECT_TOKEN"]))
   {
	$this->token = $this->consumer->getRequestToken();
	$_SESSION['EPICOLLECT_TOKEN'] = serialize($this->token);
	
   }
   $this->consumer->redirect();
  }
  
  function logout()
  {
   $db = new dbConnection();
   $res = $db->do_query("CALL setOAuthLoginDetails ('{$this->provider}', '{$token->user_id}', '{$token->screen_name}', NULL, NULL,NULL)");
   if($res !== true)
   {
	throw new OAuthException(500, $res);
   }
  }
  
  function processCallback()
  {
   $this->consumer = new Zend_OAuth_Consumer($this->configuration);
   if (!empty($_GET) && isset($_SESSION['EPICOLLECT_TOKEN'])) {
	$token = $this->consumer->getAccessToken($_GET, unserialize($_SESSION['EPICOLLECT_TOKEN']));
	$_SESSION['EPICOLLECT_ACCESS_TOKEN'] = serialize($token);
	$_SESSION['EPICOLLECT_TOKEN'] = null;
	
	$db = new dbConnection();
	
	$res = $db->do_query("CALL setOAuthLoginDetails('{$this->provider}', '{$token->user_id}', '{$token->screen_name}', '{$_SESSION['EPICOLLECT_TOKEN']}', '{$_SESSION['EPICOLLECT_ACCESS_TOKEN']}', '".session_id()."')");
	if($res === true)
	{
	 if($arr =  $db->get_row_array()){   
	  $_SESSION["EcUserId"] = $arr["EcUserId"];
	  $_SESSION["newUser"] = $arr["newUser"];
	 }
	}
	else
	{
	 throw new OAuthException(500, $res);
	}
   } else {
	exit('Invalid callback request. Oops. Sorry.');
   }
  }
 }
 
 class OAuthException extends Exception
 {
  public $error;
  public $message;
  
  function __construct($err = 500, $msg = "Unknown Error")
  {
   $this->error = $err;
   $this->message = $msg;
  }
 }
?>