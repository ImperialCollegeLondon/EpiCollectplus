<?php

if (isset($_REQUEST['_SESSION'])) throw new Exception('Bad client request');

date_default_timezone_set('UTC');
$dat = new DateTime('now');
//$dfmat = '%s.u';

$SITE_ROOT = '';
$XML_VERSION = 1.0;
$CODE_VERSION = "1.4c_++";

if( !isset($PHP_UNIT) ) { $PHP_UNIT = false; }
if( !$PHP_UNIT ){ @session_start(); }

function getValIfExists($array, $key)
{
	if(array_key_exists($key, $array))
	{
		return $array[$key];
	}
	else
	{
		return null;
	}
}

$DIR = str_replace('main.php', '', $_SERVER['SCRIPT_FILENAME']);

if($PHP_UNIT)
{
	$DIR = getcwd();
}

if(strpos($_SERVER['SCRIPT_NAME'], 'main.php'))
{
	//IIS
	$SITE_ROOT = str_replace('/main.php', '', $_SERVER['PHP_SELF']);
}
else
{
	//Apache
	$SITE_ROOT = str_replace(array($_SERVER['DOCUMENT_ROOT'], '/main.php') , '', $_SERVER['SCRIPT_FILENAME']);
}

include (sprintf('%s/utils/HttpUtils.php', $DIR));
include (sprintf('%s/Auth/AuthManager.php', $DIR));
include (sprintf('%s/db/dbConnection.php', $DIR));

$url = (array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : $_SERVER["HTTP_X_ORIGINAL_URL"]); //strip off site root and GET query
if($SITE_ROOT != '') $url = str_replace($SITE_ROOT, '', $url);
if(strpos($url, '?')) $url = substr($url, 0, strpos($url, '?'));
$url = trim($url, '/');
$url = urldecode($url);

include (sprintf('%s/Classes/PageSettings.php', $DIR));
include (sprintf('%s/Classes/configManager.php', $DIR));
include (sprintf('%s/Classes/Logger.php', $DIR));


/*
 * Ec Class declatratioions
 */

include(sprintf('%s/Classes/EcProject.php', $DIR));
include(sprintf('%s/Classes/EcTable.php', $DIR));
include(sprintf('%s/Classes/EcField.php', $DIR));
include (sprintf('%s/Classes/EcOption.php', $DIR));
include (sprintf('%s/Classes/EcEntry.php', $DIR));
/*
 * End of Ec Class definitions
 */
global $cfg;
$cfg = new ConfigManager(sprintf('%s/ec/epicollect.ini', rtrim($DIR, '/')));

function genStr()
{
	$source_str = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	$rand_str = str_shuffle($source_str);
	$str = substr($rand_str, -22);

        unset($source_str, $rand_str);
        
        return $str;
}

if($cfg->settings['security']['use_ldap'] && !function_exists('ldap_connect'))
{
	$cfg->settings['security']['use_ldap'] = false;
	$cfg->writeConfig();
}


if(!array_key_exists('salt',$cfg->settings['security']) || trim($cfg->settings['security']['salt']) == '')
{
	$str = genStr();
	$cfg->settings['security']['salt'] = $str;
	$cfg->writeConfig();
}

function makeUrl($fn)
{
	global $SITE_ROOT;
        $root =  trim($SITE_ROOT, '/');
        if($root !== '')
        {
            return sprintf('http://%s/%s/ec/uploads/%s', $_SERVER['HTTP_HOST'], $root , $fn);
        }
        else
        {
            return sprintf('http://%s/ec/uploads/%s', $_SERVER['HTTP_HOST'], $fn);
        }
}

function handleError($errno, $errstr, $errfile, $errline, array $errcontext)
{
	// error was suppressed with the @-operator
	if (0 === error_reporting()) {
		return false;
	}
	
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}


set_error_handler('handleError', E_ALL);

$DEFAULT_OUT = $cfg->settings['misc']['default_out'];
$log = new Logger('Ec2');
global $db, $auth;
$db = false;
$auth = new AuthManager();


//try{
	$db = new dbConnection();
//}catch(Exception $err){
	
//}
/* class and function definitions */

function escapeTSV($string)
{
	$string = str_replace("\n", "\\n", $string);
	$string = str_replace("\r", "\\r", $string);
	$string = str_replace("\t", "\\t", $string);
	return $string;
}

function flash($msg, $type="msg")
{
	if(!array_key_exists("flashes", $_SESSION) || !is_array($_SESSION["flashes"]))
	{
		$_SESSION["flashes"] = array();
	}
	$nflash = array("msg" => $msg, "type" => $type);

	foreach($_SESSION["flashes"] as $flash)
	{
		if($flash == $nflash )return;
	}
	array_push($_SESSION["flashes"], $nflash);

}

function redirectTo($url)
{
    global $SITE_ROOT;
    $server = $_SERVER['HTTP_HOST'];
    $root = trim($SITE_ROOT, '/');
    header(sprintf('location: http://%s%s/', $server, $root != '' ? ('/' .$root) : ''));
}

function accessDenied($location)
{
    flash(sprintf('You do not have access to %s', $location));
    echo redirectTo("");
}

function setupDB()
{
	global $cfg, $auth, $SITE_ROOT;

	try{
		$db = new dbConnection($_POST["un"], $_POST["pwd"]);
			
	}catch(Exception $e)
	{
		$_GET["redir"] = "pwd";
		siteTest();
		return ;
	}
	if(!$db)
	{
		echo "DB not connected";
		return;
	}

	$sql = file_get_contents("./db/epicollect2.sql");

	$qrys = explode("~", $sql);

	for($i = 0 ; $i < count($qrys); $i++)
	{
		if($qrys[$i] != "")
		{

			$res = $db->do_multi_query($qrys[$i]);
			if($res !== true && !preg_match("/already exists|Duplicate entry .* for key/", $res))
			{
				siteHome();
				return;
			}
		}
	}
	
	flash('Please sign in to register as the first administartor of this server.');
	header(sprintf('location: http://%s%s/login.php' , $_SERVER['HTTP_HOST'], $SITE_ROOT));
	return;
}

function assocToDelimStr($arr, $delim)
{
	$str = implode($delim, array_keys($arr[0])) . "\r\n";
	for($i = 0; $i < count($arr); $i++)
	{
		$str .= implode($delim, array_values($arr[$i])) . "\r\n";
	}
	return $str;
}

function getTimestamp($fmt = false)
{
	$date = new DateTime("now", new DateTimeZone("UTC"));
	if( !$fmt ) return $date->getTimestamp() * 1000;
	else return $date->format($fmt);
}

function regexEscape($s)
{
	$s = str_replace("/" , "\/" , $s);
	return $s;
}

function applyTemplate($baseUri, $targetUri = false, $templateVars = array())
{
	global $db, $SITE_ROOT, $DIR, $auth, $CODE_VERSION, $cfg;

	$template = file_get_contents(sprintf('%shtml/%s', $DIR, trim( $baseUri,'.')));
	$templateVars['SITE_ROOT'] = ltrim($SITE_ROOT, '\\');
	$templateVars['uid'] = md5($_SERVER['HTTP_HOST']);
	$templateVars['codeVersion'] = $CODE_VERSION;
	$templateVars['protocol'] = (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http');
	$templateVars['GA_ACCOUNT'] = $cfg->settings['misc']['ga_account'];
	// Is there a user logged in?

	$flashes = '';


	if(array_key_exists('flashes', $_SESSION) && is_array($_SESSION['flashes']))
	{
		while($flash = array_pop($_SESSION['flashes']))
		{
			$flashes .= sprintf('<p class="flash %s">%s</p>', $flash["type"], $flash["msg"]);
		}
	}


	try{
		if(isset($db) && $db->connected && $auth && $auth->isLoggedIn())
		{
	
			//if so put the user's name and a logout option in the login section
			if($auth->isServerManager())
			{
				$template = str_replace('{#loggedIn#}', 'Logged in as ' . $auth->getUserNickname() . ' (' . $auth->getUserEmail() .  ')  <a href="{#SITE_ROOT#}/logout">Sign out</a>  <a href="{#SITE_ROOT#}/updateUser.html">Update User</a>  <a href="{#SITE_ROOT#}/admin">Manage Server</a>', $template);
			}
			else
			{
				$template = str_replace('{#loggedIn#}', sprintf('Logged in as %s (%s) <a class="btn btn-mini" href="{#SITE_ROOT#}/logout">Sign out</a>  <a href="{#SITE_ROOT#}/updateUser.html">Update User</a>', $auth->getUserNickname(), $auth->getUserEmail()), $template);
			}
			$templateVars['userEmail'] = $auth->getUserEmail();
		}
		// else show the login link
		else
		{
			$template = str_replace('{#loggedIn#}', '<a href="{#SITE_ROOT#}/login.php">Sign in</a>', $template);
		}
		// work out breadcrumbs
		//$template = str_replace("{#breadcrumbs#}", '', $template);
	}catch(Exception $err){
		unset($db);
		siteTest();
	}	
	
	$script = "";
	$sections = array();
	if($targetUri)
	{

		$fname = sprintf('%shtml/%s', $DIR, trim( $targetUri,'./'));
		if(file_exists($fname))
		{
			$data = file_get_contents($fname);
			
			$fPos = 0;
			$iStart = 0;
			$iEnd = 0;
			$sEnd = 0;
			$id = '';

			while($fPos <= strlen($data) && $fPos >= 0)
			{
				//echo "--";
				// find {{
				$iStart = strpos($data, '{{', $fPos);
					
				if($iStart===false || $iStart < $fPos) break;
				//echo $iStart;
				//get identifier (to }})
				$iEnd = strpos($data, '}}', $iStart);
					
				//echo $iEnd;
				$id = substr($data, $iStart + 2, ($iEnd-2) - ($iStart));
				//find matching end {{/}}
				$sEnd = strpos($data, sprintf('{{/%s}}', $id), $iEnd);
				$sections[$id] = substr($data, $iEnd + 2, $sEnd - ($iEnd + 2));
					
				$fPos = $sEnd + strlen($id) + 3;
				//echo ("$fPos --- " . strlen($data) . " $id :: ");
			}
		}
		else
		{
			$sections['script'] = '';
			$sections['main'] = '<h1>404 - page not found</h1>
				<p>Sorry, the page you were looking for could not be found.</p>';
			header('HTTP/1.1 404 Page not found');
		}
		foreach(array_keys($sections) as $sec)
		{
			// do processing
			$template = str_replace(sprintf('{#%s#}',$sec) , $sections[$sec], $template);
		}
		$template = str_replace('{#flashes#}', $flashes, $template);
	}
	if($templateVars)
	{
		foreach($templateVars as $sec => $cts)
		{
			// do processing
			$template = str_replace(sprintf('{#%s#}', $sec), $cts, $template);
		}
	}

	$template = preg_replace('/\{#[a-z0-9_]+#\}/i', '', $template);
	return $template;
}

function formDataLastUpdated()
{
    global $url,  $log, $auth;

	$http_accept = getValIfExists($_SERVER, 'HTTP_ACCEPT');
	$format = ($http_accept ? substr($http_accept, strpos($http_accept, '/') + 1) : '');
	$ext = substr($url, strrpos($url, ".") + 1);
	$format = $ext != "" ? $ext : $format;

	$prj = new EcProject();
	$pNameEnd = strpos($url, "/");

	$prj->name = substr($url, 0, $pNameEnd);
	$prj->fetch();
	
	if(!$prj->id)
	{
		echo applyTemplate("./base.html", "./error.html", array("errorType" => "404 ", "error" => "The project {$prj->name} does not exist on this server"));
		return;
	}
	
	$permissionLevel = 0;
	$loggedIn = $auth->isLoggedIn();
	
	if($loggedIn) $permissionLevel = $prj->checkPermission($auth->getEcUserId());

	if(!$prj->isPublic && !$loggedIn)
	{
		loginHandler($url);
		return;
	}
	else if(!$prj->isPublic &&  $permissionLevel < 2)
	{
		echo applyTemplate("./base.html", "./error.html", array("errorType" => "403 ", "error" => "You do not have permission to view this project"));
		return;
	}

	$extStart = strpos($url, ".");
	$frmName = substr($url, $pNameEnd + 1, strrpos($url, '/', 1) - strlen($url));

        
        
	if(!array_key_exists($frmName, $prj->tables))
	{
		echo applyTemplate("./base.html", "./error.html", array("errorType" => "404 ", "error" => "The project {$prj->name} does not contain the form $frmName"));
		return;
	}
        
        echo json_encode($prj->tables[$frmName]->getLastActivity());
        return;
}

function mimeType($f)
{
	$mimeTypes = array(
			'ico' => 'image/x-icon',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'jpg' => 'image/jpeg',
			'css' => 'text/css',
			'html' => 'text/html',
			'js' => 'text/javascript',
			'json' => 'text/javascript',
			'xml' => 'text/xml',
			'php' => 'text/html',
			'mp4' => 'video/mp4',
			'csv' => 'text/csv'
	);

	$f = preg_replace('/\?.*$/', '', $f);
	$ext = substr($f, strrpos($f, '.') +1);
	if(array_key_exists($ext, $mimeTypes))
	{
		return $mimeTypes[$ext];
	}
	else
	{
		return 'text/plain';
	}
}

/* end of class and function definitions */

/* handlers	*/

function defaultHandler()
{
	global $url;
	header(sprintf('Content-type: $s', mimeType($url)));
	echo applyTemplate('base.html', "./" . $url);
}

function createAccount()
{
    if($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        global $cfg;
        if($cfg->settings['misc']['public_server'] === "true")
        {
            createUser();
            flash("Account created, please log in.");
            header(sprintf('location: http://%s/%s/login.php', $server, $root));
        }
        else
        {
            flash("This server is not public", "err");
            header(sprintf('location: http://%s/%s/', $server, $root));
        }   
    } else {
        global $auth;
        echo applyTemplate('./base.html', './loginbase.html', array( 'form' => $auth->requestSignup()));
    }
    
}

/**
 * Called when the page requires a log in
 */
function loginHandler()
{
	$cb_url='';
	header('Cache-Control: no-cache, must-revalidate');

	global $auth, $url, $db;
	
	if( !preg_match('/login.php/', $url) )
	{
		$cb_url = $url; 
	}
	
	if( !$auth ) $auth = new AuthManager();
	
	if(array_key_exists('provider', $_GET))
	{
		$_SESSION['provider'] = $_GET['provider'];
		$frm = $auth->requestlogin($cb_url, $_SESSION['provider']);
	}
	elseif (array_key_exists('provider', $_SESSION))
	{
		$frm = $auth->requestlogin($cb_url, $_SESSION['provider']);
	}
	else
	{
		$frm = $auth->requestlogin($cb_url);
	}

	
	echo applyTemplate('./base.html', './loginbase.html', array( 'form' => $frm));
}

function loginCallback()
{
	header('Cache-Control: no-cache, must-revalidate');
     
	global $auth, $cfg, $db;
        $provider = getValIfExists($_POST, 'provider');
        if(!$provider)
            $provider = getValIfExists($_SESSION, 'provider');
        else {
            $_SESSION['provider'] = $provider;
        }

	$db = new dbConnection();
	if(!$auth) $auth = new AuthManager();
	$auth->callback($provider);
}

function logoutHandler()
{
	header('Cache-Control: no-cache, must-revalidate');

	global $auth, $SITE_ROOT;
	$server = trim($_SERVER['HTTP_HOST'], '/');
	$root = trim($SITE_ROOT, '/');
	if($auth)
	{
		$auth->logout();
		header(sprintf('location: http://%s/%s/', $server, $root));
		return;
	}
	else
	{
		echo 'No Auth';
	}
}

function uploadHandlerFromExt()
{
	global $log;
	//$flog = fopen('fileUploadLog.log', 'w');
	if($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		if(count($_FILES) > 0)
		{
			$keys = array_keys($_FILES);
			foreach($keys as $key)
			{
				if($_FILES[$key]['error'] > 0)
				{
					//fwrite($flog, $key . " error : " .$_FILES[$key]['error']);
					$log->write("error", $key . " error : " .$_FILES[$key]['error'] );
				}
				else
				{
					if(preg_match( "/.(png|gif|rtf|docx?|pdf|jpg|jpeg|txt|avi|mpeg|mpg|mov|mp3|wav)$/i", $_FILES[$key]['name']))
					{
                                                if(!file_exists("ec/uploads/")) mkdir ("ec/uploads/");
						move_uploaded_file($_FILES[$key]['tmp_name'], "ec/uploads/{$_FILES[$key]['name']}");
						echo  "{\"success\" : true , \"msg\":\"ec/uploads/{$_FILES[$key]['name']}\"}";
					}
					else
					{
						echo  " error : file type not allowed";
							
					}
				}
			}
		}
		else
		{
			echo "No file submitted";
		}
	}
	else
	{
		echo "Incorrect method";
	}
	fclose($flog);
}

function projectList()
{
	/**
	 * Produce a list of all the projects on this server that are
	 * 	- publically listed
	 *  - if a user is logged in, owned, curated or managed by the user
	 */
	global $auth;

	$prjs = EcProject::getPublicProjects();
	$usr_prjs = array();
	if($auth->isLoggedIn())
	{  
            $usr_prjs = EcProject::getUserProjects($auth->getEcUserId());
            $up_l = count($usr_prjs);
            for($p = 0; $p < $up_l; $p++ )
            {
                if($usr_prjs[$p]["listed"] === 0)
                {
                    array_push($prjs, $usr_prjs[$p]);
                }
            }
	}

	echo json_encode($prjs);
}


function projectHome()
{
	global $url, $SITE_ROOT, $auth;

	$eou = strlen($url) - 1;
	if($url{$eou} == '/')
	{
		$url{$eou} = '';
	}
	$url = ltrim($url, '/');

	$prj = new EcProject();
	if(array_key_exists('name', $_GET))
	{
		$prj->name = $_GET['name'];
	}
	else
	{
		$prj->name = preg_replace('/\.(xml|json)$/', '', $url);
	}
	
	$prj->fetch();

	if(!$prj->id)
	{
		$vals = array('error' => 'Project could not be found');
		echo applyTemplate('base.html','./404.html', $vals);
		die;
	}
	
	
	$loggedIn = $auth->isLoggedIn();
	$role = $prj->checkPermission($auth->getEcUserId());

	if( !$prj->isPublic && !$loggedIn && !preg_match('/\.xml$/',$url) )
	{
		flash('This is a private project, please log in to view the project.');
		loginHandler($url);
		return;
	}
	else if( !$prj->isPublic && $role < 2 && !preg_match('/\.xml$/',$url) )
	{
		flash(sprintf('You do not have permission to view %s.', $prj->name));
		header(sprintf('location: http://%s/%s', $_SERVER['HTTP_HOST'], $SITE_ROOT));
		return;
	}
	
	

	//echo strtoupper($_SERVER["REQUEST_METHOD"]);
	$reqType = strtoupper($_SERVER['REQUEST_METHOD']);
	if( $reqType == 'POST' ) //
	{
		//echo 'POST';
		// update project
		$prj->description = $_POST['description'];
		$prj->image = $_POST['image'];
		$prj->isPublic = array_key_exists('isPublic', $_POST) && $_POST['isPublic'] == 'on' ?  1 : 0;
		$prj->isListed =  array_key_exists('isListed', $_POST) && $_POST['isListed'] == 'on' ?  1 : 0;
		$prj->publicSubmission =  array_key_exists('publicSubmission', $_POST) && $_POST['publicSubmission'] == 'on' ?  1 : 0;
			
		$res = $prj->id ? $prj->push() : $prj->post();
		if( $res !== true )
		{
			echo $res;
		}
			
		if( $_POST['admins'] && $res === true )
		{
			$res = $prj->setAdmins($_POST["admins"]);
		}
			
		if( $_POST['users'] && $res === true )
		{
			$res = $prj->setUsers($_POST["users"]);
		}
			
		if( $_POST['submitters'] && $res === true )
		{
			$res = $prj->setSubmitters($_POST['submitters']);
		}
		echo $res;
	}
	elseif( $reqType == 'DELETE' )
	{
		if( $role  == 3 )
		{
			$res = $prj->deleteProject();
			if( $res === true )
			{
				header('HTTP/1.1 200 OK', true, 200);
				echo '{ "success": true }';
				return;
			}
			else
			{
				header('HTTP/1.1 500 Error', true, 500);
				echo ' {"success" : false, "message" : "Could not delete project" }';
			}
		}
		else
		{
			header('HTTP/1.1 403 Forbidden', true, 403);
			echo ' {"success" : false, "message" : "You do not have permission to delete this project" }';
		}
		
	}
	elseif( $reqType == 'GET' )
	{
	
            if( array_key_exists('HTTP_ACCEPT', $_SERVER)) $format = substr($_SERVER["HTTP_ACCEPT"], strpos($_SERVER["HTTP_ACCEPT"], "$SITE_ROOT/") + 1 );
            $ext = substr($url, strrpos($url, '.') + 1);
            $format = $ext != '' ? $ext : $format;
            if( $format == 'xml' )
            {
                header('Cache-Control: no-cache, must-revalidate');
                header('Content-type: text/xml; charset=utf-8;');
                echo $prj->toXML();
            }else {
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: text/html;');

            try{
                    //$userMenu = '<h2>View Data</h2><span class="menuItem"><img src="images/map.png" alt="Map" /><br />View Map</span><span class="menuItem"><img src="images/form_view.png" alt="List" /><br />List Data</span>';
                    //$adminMenu = '<h2>Project Administration</h2><span class="menuItem"><a href="./' . $prj->name . '/formBuilder.html"><img src="'.$SITE_ROOT.'/images/form_small.png" alt="Form" /><br />Create or Edit Form(s)</a></span><span class="menuItem"><a href="editProject.html?name='.$prj->name.'"><img src="'.$SITE_ROOT.'/images/homepage_update.png" alt="Home" /><br />Update Project</a></span>';
                    $tblList = '';
                    foreach( $prj->tables as $tbl )
                    {
                            $tblList .= "<div class=\"tblDiv\"><a class=\"tblName\" href=\"{$prj->name}/{$tbl->name}\">{$tbl->name}</a><a href=\"{$prj->name}/{$tbl->name}\">View All Data</a> | <form name=\"{$tbl->name}SearchForm\" action=\"./{$prj->name}/{$tbl->name}\" method=\"GET\"> Search for {$tbl->key} <input type=\"text\" name=\"{$tbl->key}\" /> <a href=\"javascript:document.{$tbl->name}SearchForm.submit();\">Search</a></form></div>";
                    }

                    $imgName = $prj->image ? $prj->image : "images/projectPlaceholder.png";

                    if( file_exists($imgName) )
                    {
                            $imgSize = getimagesize($imgName);
                    }
                    else
                    {
                            $imgSize = array(0,0);
                    }

                    $adminMenu = '';
                    $curpage = trim($url ,'/');
                    $curpage = sprintf('http://%s%s/%s', $_SERVER['HTTP_HOST'], $SITE_ROOT, $curpage);

                    if( $role == 3 )
                    {
                            $adminMenu = "<span class=\"button-set\"><a href=\"{$curpage}/manage\" class=\"button\">Manage Project</a> <a href=\"{$curpage}/formBuilder\" class=\"button\">Edit Forms</a></span>";
                    }

                    $vals =  array(
                            'projectName' => $prj->name,
                            'projectDescription' => $prj->description && $prj->description != "" ? $prj->description : "Project homepage for {$prj->name}",
                            'projectImage' => str_replace($prj->name, $imgName, $curpage),
                            'imageWidth' => $imgSize[0],
                            'imageHeight' =>$imgSize[1],
                            'tables' => $tblList,
                            'adminMenu' => $adminMenu,
                            'userMenu' => ''
                    );


                    echo applyTemplate('base.html','projectHome.html',$vals);
                    return;
                }
                catch( Exception $e )
                {

                    $vals = array('error' => $e->getMessage());
                    echo applyTemplate('base.html','error.html',$vals);
                }
            }
	}
}

function siteTest()
{
	$res = array();
	global $cfg, $db;

	$template = 'testResults.html';
	
	$doit = true;
	if(!array_key_exists("database", $cfg->settings) || !array_key_exists("server", $cfg->settings["database"]) ||trim($cfg->settings["database"]["server"]) == "")
	{
		$res["dbStatus"] = "fail";
		$res["dbResult"] = "No database server specified, please amend the file ec/settings.php and so that \$DBSERVER equals the name of the MySQL server";
		$doit = false;
	}
	else if(!array_key_exists("user", $cfg->settings["database"]) || trim($cfg->settings["database"]["user"]) == "")
	{
		$res["dbStatus"] = "fail";
		$res["dbResult"] = "No database user specified, please amend the file ec/settings.php so that \$DBUSER and \$DBPASS equal the credentials for MySQL server";
		$doit = false;
	}
	else if(!array_key_exists("database", $cfg->settings["database"]) ||trim($cfg->settings["database"]["database"]) == "")
	{
		$res["dbStatus"] = "fail";
		$res["dbResult"] = "No database name specified, please amend the file ec/settings.php so that \$DBNAME equals the name of the MySQL database";
		$doit = false;
	}

	if($doit && !(array_key_exists("edit", $_GET) && $_GET["edit"] === "true"))
	{
		if(array_key_exists("redir", $_GET) && $_GET["redir"] === "true") $res["redirMsg"] = "	<p class=\"message\">You have been brought to this page because of a fatal error opening the home page</p>";
		if(array_key_exists("redir", $_GET) && $_GET["redir"] === "pwd") $res["redirMsg"] = "	<p class=\"message\">The username and password you entered were incorrect, please try again.</p>";
		
		if(!$db) $db = new dbConnection();
		
		
		if($db->connected)
		{
			$res["dbStatus"] = "succeed";
			$res["dbResult"] = "Connected";
		}else{
			$ex = $db->errorCode;
			if($ex == 1045)
			{
				$res["dbStatus"] = "fail";
				$res["dbResult"] = "DB Server found, but the combination of the username and password invalid. <a href=\"./test?edit=true\">Edit Settings</a>";
			}
			elseif($ex == 1044)
			{
				$res["dbStatus"] = "fail";
				$res["dbResult"] = "DB Server found, but the database specified does not exist or the user specified does not have access to the database. <a href=\"./test?edit=true\">Edit Settings</a>";
			}
			else
			{
				$res["dbStatus"] = "fail";
				$res["dbResult"] =  "Could not find the DB Server ";
			}
		}
		
		if($db->connected)
		{
			$dbNameRes = $db->do_query("SHOW DATABASES");
			if($dbNameRes !== true)
			{
				echo $dbNameRes;
				return;
			}
			while($arr = $db->get_row_array())
			{
					
				if( $arr['Database'] == $cfg->settings["database"]["database"])
				{
					$res["dbStatus"] = "succeed";
					$res["dbResult"] = "";
					break;
				}
				else
				{
					$res["dbStatus"] = "fail";
					$res["dbResult"] = "DB Server found, but the database '{$cfg->settings["database"]["database"]}' does not exist.<br />";
				}
			}

			$res["dbPermStatus"] = "fail";
			$res["dbPermResults"] = "";
			$res["dbTableStatus"] = "fail";

			if($res["dbStatus"] == "succeed")
			{
				$dbres = $db->do_query("SHOW GRANTS FOR {$cfg->settings["database"]["user"]};");
				if($dbres !== true)
				{
					$res["dbPermResults"] = $res;
				}
				else
				{
					$perms = array("SELECT", "INSERT", "UPDATE", "DELETE", "EXECUTE");
					$res ["dbPermResults"] = "Permssions not set, the user {$cfg->settings["database"]["user"]} requires SELECT, UPDATE, INSERT, DELETE and EXECUTE permissions on the database {$cfg->settings["database"]["database"]}";
					while($arr = $db->get_row_array())
					{
						$_g = implode(" -- ", $arr) . "<br />";
						if(preg_match("/ON (`?{$cfg->settings["database"]["database"]}`?|\*\.\*)/", $_g))
						{
							if(preg_match("/ALL PERMISSIONS/i", $_g))
							{
								$res["dbPermStatus"] = "fail";
								$res["dbPermResults"] = "The user account {$cfg->settings["database"]["user"]} by the website should only have SELECT, INSERT, UPDATE, DELETE and EXECUTE priviliges on {$cfg->settings["database"]["database"]}";
								break;
							}
							for($_p = 0; $_p < count($perms); $_p++)
							{
								if(preg_match("/{$perms[$_p]}/i", $_g)) // &&  preg_match("/INSERT/", $_g) &&  preg_match("/UPDATE/", $_g) &&  preg_match("/DELETE/", $_g) &&  preg_match("/EXECUTE/", $_g))
								{
									unset($perms[$_p]);
									$perms = array_values($perms);
									$_p--;
								}
							}
						}
					}
					if(count($perms) == 0)
					{
						$res["dbPermStatus"] = "succeed";
						$res["dbPermResults"] = "Permssions Correct";
					}
					else
					{
						$res ["dbPermResults"] = "Permssions not set, the user {$cfg->settings["database"]["user"]} is missing " . implode(", ", $perms) .  " permissions on the database {$cfg->settings["database"]["database"]}";
					}
				}
			}
		}

		if($db->connected && $res["dbPermStatus"] == "succeed")
		{

			$tblTemplate = array(
					"device" => false,
					"deviceuser" => false,
					"enterprise" => false,
					"entry" => false,
					"entryvalue" => false,
					"entryvaluehistory" => false,
					"field" => false,
					"fieldtype" => false,
					"form" => false,
					"option" => false,
					"project" => false,
					"role" => false,
					"user" => false,
					"userprojectpermission" => false	
			);

			$dres = $db->do_query("SHOW TABLES");
			if($dres !== true)
			{
				$res["dbTableStatus"] = "fail";
				$res["dbTableResult"] = "EpiCollect Database is not set up correctly";
			}
			else
			{
				$i = 0;
				while($arr = $db->get_row_array())
				{
					$tblTemplate[$arr["Tables_in_{$cfg->settings["database"]["database"]}"]] = true;
					$i++;
				}
				if($i == 0)
				{
					$template = 'dbSetup.html';
					$res["dbTableStatus"] = "fail";
					$res["dbTableResult"] = "<p>Database is blank,  enter an <b>administrator</b> username and password for the database to create the database tables.</p>
				<form method=\"post\" action=\"createDB\">
					<b>Username : </b><input name=\"un\" type=\"text\" /> <b>Password : </b><input name=\"pwd\" type=\"password\" /> <input type=\"hidden\" name=\"create\" value=\"true\" /><input type=\"submit\" value=\"Create Database\" name=\"Submit\" />
				</form>";
				}
				else
				{
					$done = true;
					foreach($tblTemplate as $key => $val)
					{
						$done &= $val;
					}

					if($done)
					{
						$res["dbTableStatus"] = "succeed";
						$res["dbTableResult"] = "EpiCollect Database ready";
					}
					else
					{
						$res["dbTableStatus"] = "fail";
						$res["dbTableResult"] = "EpiCollect Database is not set up correctly";
					}
				}
			}

		}
			
		$res["endStatus"] = array_key_exists("dbTableStatus", $res) ? ($res["dbTableStatus"] == "fail" ? "fail" : "") : "fail";
		$res["endMsg"] = ($res["endStatus"] == "fail" ? "The MySQL database is not ready, please correct the errors in red above and refresh this page. <a href = \"./test?edit=true\">Configuration tool</a>" : "You are now ready to create EpiCollect projects, place xml project definitions in {$_SERVER["PHP_SELF"]}/xml and visit the <a href=\"createProject.html\">create project</a> page");
		echo applyTemplate("base.html", $template, $res);
	}
	else
	{
		$arr = "{";
		foreach($cfg->settings as $k => $v)
		{
			foreach($v as $sk => $sv)
			{
				$arr .= "\"{$k}\\\\{$sk}\" : \"$sv\",";
			}
		}
		$arr = trim($arr, ",") . "}";
			
		echo applyTemplate("base.html", "setup.html", array("vals" => $arr));
	}
	
}


function getClusterMarker()
{
	include '/utils/markers.php';
	$colours = getValIfExists($_GET, "colours");
	$counts = getValIfExists($_GET, "counts");
	
	$colours = trim($colours, '|');
	$counts = trim($counts, '|');
	
	
	if(!$colours)
	{
		$colours = array("#FF0000");
	}
	else
	{
		$colours = explode("|", $colours);
	}

	if(!$counts)
	{
		$counts = array(111);
	}
	else
	{
		$counts = explode("|", $counts);
	}
		
	header("Content-type: image/svg+xml");
	echo getGroupMarker($colours, $counts);
}

function getPointMarker()
{
	include "./utils/markers.php";
	
	$colour = getValIfExists($_GET, "colour");
	$shape = getValIfExists($_GET, "shape");
	if(!$colour) $colour = "FF0000";
	$colour = trim($colour, "#");
	header("Content-type: image/svg+xml");
	echo getMapMaker($colour, $shape);
}

function siteHome()
{
	header("Cache-Control: no-cache, must-revalidate");
	global $SITE_ROOT, $db, $log,$auth;
	
	$vals = array();
	$server = trim($_SERVER["HTTP_HOST"], "/");
	$root = trim($SITE_ROOT, "/");
	
	//if($_SERVER["HTTPS"] == 'on'){ header(sprintf('location: http://%s%s ', $server, $root));}
	
	if(!$db->connected)
	{
		$rurl = "http://$server/$root/test?redir=true";
		header("location: $rurl");
		return;
	}

	$res = $db->do_query("SELECT name, ttl, ttl24 FROM (SELECT name, count(entry.idEntry) as ttl, x.ttl as ttl24 FROM project left join entry on project.name = entry.projectName left join (select count(idEntry) as ttl, projectName from entry where created > ((UNIX_TIMESTAMP() - 86400)*1000) group by projectName) x on project.name = x.projectName Where project.isListed = 1 group by project.name) a order by ttl desc LIMIT 10");
	if($res !== true)
	{
			
		//$vals["projects"] = "<p class=\"error\">Database is not set up correctly, go to the <a href=\"test\">test page</a> to establish the problem.</p>";
		//echo applyTemplate("base.html","./index.html",$vals);
		$rurl = "http://$server/$root/test?redir=true";
		header("location: $rurl");
		return;
	}
	$vals["projects"] = "<div class=\"ecplus-projectlist\"><h1>Most popular projects on this server</h1>" ;

	$i = 0;

	while($row = $db->get_row_array())
	{
		$vals["projects"] .= "<div class=\"project\"><a href=\"{#SITE_ROOT#}/{$row["name"]}\">{$row["name"]}</a><div class=\"total\">{$row["ttl"]} entries with <b>" . ($row["ttl24"] ? $row["ttl24"] : "0") ."</b> in the last 24 hours </div></div>";
		$i++;
	}
	
	if($i == 0)
	{
		$vals["projects"] = "<p>No projects exist on this server, <a href=\"createProject.html\">create a new project</a></p>";
	}
	else
	{
		$vals["projects"] .= "</div>";
	}
	
	if($auth->isLoggedIn())
	{
		$vals['userprojects'] = '<div class="ecplus-userprojects"><h1>My Projects</h1>';
		
		$prjs = EcProject::getUserProjects($auth->getEcUserId());
		$count = count($prjs);
               
		for($i = 0; $i < $count; $i++)
		{
			$vals['userprojects'] .= "<div class=\"project\"><a href=\"{#SITE_ROOT#}/{$prjs[$i]["name"]}\">{$prjs[$i]["name"]}</a><div class=\"total\">{$prjs[$i]["ttl"]} entries with <b>" . ($prjs[$i]["ttl24"] ? $prjs[$i]["ttl24"] : "0") ."</b> in the last 24 hours </div></div>";
		}
		
		$vals['userprojects'] .= '</div>';
	}

	echo applyTemplate("base.html","index.html",$vals);
}



function uploadData()
{
	global  $url, $log;
	$flog = fopen('ec/uploads/fileUploadLog.log', 'a');
	$prj = new EcProject();
	$prj->name = preg_replace('/\/upload\.?(xml|json)?$/', '', $url);

	$prj->fetch();
	
	if($_SERVER["REQUEST_METHOD"] == "POST"){
		if(count($_POST) == 0)
		{
			parse_str(file_get_contents("php://input"), $_POST);
		}
		
		if(count($_FILES) > 0)
		{
			foreach($_FILES as $file){
					
				if(preg_match("/.+\.xml$/", $file["name"])){
					$ts = new DateTime("now", new DateTimeZone("UTC"));
					$ts = $ts->getTimestamp();
						

					$fn = "$ts-{$file["name"]}";

					for($i = 1; file_exists("../ec/rescue/{$fn}"); $i++)

					{
						$fn = "$ts-$i-{$file['name']}";
					}
					move_uploaded_file($file['tmp_name'], "./ec/rescue/{$fn}");

					$res = $prj->parseEntries(file_get_contents("./ec/rescue/{$fn}"));

					if(preg_match("/(CHROME|FIREFOX)/i", $_SERVER["HTTP_USER_AGENT"]))
					{
						echo $res;
					}
					else
					{

						//fwrite($flog, "$res\r\m");
						$log->write("debug", "$res");
						echo ($res === true ? "1" : "0");
					}
				}
				else if(preg_match("/\.(png|gif|rtf|docx?|pdf|jpg|jpeg|txt|avi|mpe?g|mov|mpe?g?3|wav|mpe?g?4)$/", $file['name']))
				{

					try{
						//if(!fileExists("./uploads/{$prj->name}")) mkdir("./uploads/{$prj->name}");
							
						move_uploaded_file($file['tmp_name'], "./ec/uploads/{$prj->name}~" . ($_REQUEST["type"] == "thumbnail" ? "tn~" : "" ) ."{$file['name']}");
						$log->write('debug', $file['name'] . " copied to uploads directory\n");
						echo 1;
					}
					catch(Exception $e)
					{
						$log->write("error", $e . "\r\n");
						echo "0";
					}
				}
				else
				{
					$log->write("error", $file['name'] . " error : file type not allowed\r\n");
					echo "0";
				}
			}

		}
		else
		{
			$log->write("POST", "data : " . serialize($_POST) . "\r\n");
			$tn = $_POST["table"];
			unset($_POST["table"]);

			try
			{
				 
				$ent = new EcEntry($prj->tables[$tn]);
				if(array_key_exists("ecPhoneID", $_POST))
				{
					$ent->deviceId = $_POST["ecPhoneID"];
				}
				else
				{
					$ent->deviceId = "web";
				}
				if(array_key_exists("ecTimeCreated", $_POST))
				{
					$ent->created = $_POST["ecTimeCreated"];
				}
				else
				{
					$d = new DateTime('now', new DateTimeZone('UTC'));
					$ent->created = $d->getTimestamp();
				}
				$ent->project = $prj;
					
				foreach($prj->tables[$tn]->fields as $key => $fld){
					if($fld->type == 'gps' || $fld->type == 'location')
					{
						$lat = "{$key}_lat";
						$lon = "{$key}_lon";
						$alt = "{$key}_alt";
						$acc = "{$key}_acc";
						$src = "{$key}_provider";
						$bearing = "{$key}_bearing";
						
						$ent->values[$key] = array(
							'latitude' => (string) getValIfExists($_POST, $lat),
							'longitude' => (string)getValIfExists($_POST,$lon),
							'altitude' => (string)getValIfExists($_POST,$alt),
							'accuracy' => (string) getValIfExists($_POST,$acc), 
							'provider' => (string)getValIfExists($_POST,$src),
							'bearing' =>  (string)getValIfExists($_POST,$bearing),
						);
					}
					else if(!array_key_exists($key, $_POST))
					{
						$ent->values[$key] = "";
						continue;
					}
					else if($fld->type != "branch")
					{
						$ent->values[$key] = (string)$_POST[$key];
					}
				}
				
				$log->write("debug", "posting ... \r\n");
				$res = $ent->post();
				$log->write("debug",  "response : $res \r\n");
					
				if($res === true)
				{
					header("HTTP/1.1 200 OK");
					echo 1;
				}
				else
				{
					header("HTTP/1.1 405 Bad Request");
					$log->write("error",  "error : $res\r\n");
					echo $res;
				}
			}
			catch(Exception $e)
			{
				$log->write("error",  "error : " . $e->getMessage() . "\r\n");
				$msg = $e->getMessage();
				if(preg_match("/^Message/", $msg))
				{
					header("HTTP/1.1 405 $msg");
				}
				else
				{
					header("HTTP/1.1 405 Bad Request");
				}
				echo $msg;
			}
		}
	}
	fclose($flog);
}

function getChildEntries($survey, $tbl, $entry, &$res, $stopTbl = false)
{
	//	global $survey;

	foreach($survey->tables as $subTbl)
	{
			
		if(($subTbl->number <= $tbl->number && $subTbl->branchOf != $tbl->name)||($stopTbl !== false && $subTbl->number > $stopTbl && $subTbl->branchOf != $tbl->name)){
			continue;
		}
			
		foreach($subTbl->fields as $fld)
		{
			if($fld->name == $tbl->key && !array_key_exists($subTbl->name, $res))
			{
					
				$res[$subTbl->name] = $subTbl->get(Array($tbl->key => $entry));
				//print_r($res[$subTbl->name]);
				foreach($res[$subTbl->name][$subTbl->name] as $sEntry)
				{

					getChildEntries($survey, $subTbl, $sEntry[$subTbl->key][$subTbl->key], $res, $stopTbl);

				}
					
			}

		}
	}

}

function downloadData()
{
	global  $url, $SITE_ROOT;
	header("Cache-Control: no-cache,  must-revalidate");

	//$flog = fopen('ec/uploads/fileUploadLog.log', 'a+');
	$survey = new EcProject();
	$survey->name = preg_replace("/\/download\.?(xml|json)?$/", "", $url);

	$survey->fetch();

	$lastUpdated = $survey->getLastUpdated();
	$qString = $_SERVER["QUERY_STRING"];

	$baseFn = md5($lastUpdated . $qString);
	//the root of the working directory is the Script filename minus everthing after the last \
	//NOTE: This will be the same for EC+ as the upload directory is project-independant
	$pos = max(strrpos($_SERVER["SCRIPT_FILENAME"], "\\") ,strrpos($_SERVER["SCRIPT_FILENAME"], "/"));
	$root =substr($_SERVER["SCRIPT_FILENAME"], 0, $pos);
	
	$wwwroot = "http://{$_SERVER["HTTP_HOST"]}$SITE_ROOT";
	$startTbl = (array_key_exists('select_table', $_GET) ? getValIfExists($_GET, "table") : false);
	$endTbl = (array_key_exists('select_table', $_GET) ? getValIfExists($_GET, "select_table") :  getValIfExists($_GET, "table"));
	$entry = getValIfExists($_GET, "entry");
	$dataType = (array_key_exists('type', $_GET) ? $_GET["type"] : "data");
	$xml = !(array_key_exists('xml', $_GET) && $_GET['xml'] === "false");

	$files_added = 0;

	$delim = "\t";
	$rowDelim = "\n";

	$tbls = array();
	$branches = array();

	$n = $startTbl ? $survey->tables[$startTbl]->number : 1;
	$end = $endTbl ? $survey->tables[$endTbl]->number : count($survey->tables);

	// if we're doing a select_table query we don't want the data from the first table, as we already have that entry.
	if(array_key_exists('select_table', $_GET) && $entry) $n++;

	//for each table between startTbl and end Tbl (or that is a branch of a table we want)
	//we'll loop through the table array to establish which tables we need
	foreach($survey->tables as $name => $tbl)
	{
		//first off is $tbl is already in $tbls we can skip it
		if(array_key_exists($name, $tbls))
		{
			continue;
		}
		
		// are we doing name-based or type-based checking?
		elseif( $dataType  == 'group' )
		{
			if( $tbl->group )
			{
				array_push($tbls, $name);
			}
		}
		else
		{
			// first check if the table has a number between $n and $end
			if( ($tbl->number >= $n && $tbl->number <= $end) )
			{
				array_push($tbls, $name);
			}
			
			if( count($tbl->branches) > 0 )
			{
				$tbls = array_merge($tbls, $tbl->branches);
			}	
		}
	}

	if( $dataType  == 'group' ) $dataType = 'data';
	
	//criteria
	$cField = false;
	$cVals = array();
	if( $entry )
	{
		$cField = $survey->tables[$startTbl]->key;
		$cVals[0] = $entry;
	}

	$nxtCVals = array();
		
	//for each main table we're intersted in (i.e. main tables between stat and end table)
	//$ts = new DateTime("now", new DateTimeZone("UTC"));
	//$ts = $ts->getTimestamp();
	if( $dataType == 'data' && $xml )
	{
		header('Content-type: text/xml');
		$fxn = "$root\\ec\\uploads\\{$baseFn}.xml";
		$fx_url = "$wwwroot/ec/uploads/{$baseFn}.xml";
		if(file_exists($fxn))
		{
			header("location: $fx_url");
			return;
		}
		$fxml = fopen("$fxn", "w+");
		fwrite($fxml,"<?xml version=\"1.0\"?><entries>");
			
	}
	else if($dataType == "data")
	{
		header("Content-type: text/plain");
		$txn = "$root\\ec\\uploads\\{$baseFn}.tsv";
		$ts_url = "$wwwroot/ec/uploads/{$baseFn}.tsv";
		if(file_exists($txn))
		{
			header("Location: $ts_url");
			return;
		}
			
		$tsv = fopen($txn, "w+");
	}
	else
	{

		$zfn = "$root\\ec\\uploads\\arc{$baseFn}.zip";
		$zrl = "$wwwroot/ec/uploads/arc{$baseFn}.zip";
			
		if(file_exists($zfn))
		{
			header("Location: $zrl");
			return;
		}
			
		$arc = new ZipArchive;
		$x = $arc->open($zfn, ZipArchive::CREATE);
		if(!$x) die("Could not create the zip file.");
	}
	

	for($t = 0; $t <= $end && array_key_exists($t, $tbls); $t++)
	{
		//echo '...' . $cField . "\r\n";
		//print_r($cVals);
		
		if($dataType == "data" && $xml)
		{
			fwrite($fxml, "<table><table_name>{$tbls[$t]}</table_name>");
		}

		for($c = 0; $c < count($cVals) || $c < 1; $c++)
		{

			$res = false;
			
			if($entry && count($cVals) == 0) break;
			$args = array();
			
			if($entry) $args[$cField] = $cVals[$c];
				
			$res = $survey->tables[$tbls[$t]]->ask($args,0,0,'created','asc',true,'object',false);

			if($res !== true) echo $res;
	
			while ($ent = $survey->tables[$tbls[$t]]->recieve(1))
			{
				$ent = $ent[0];
				
				if($dataType == "data")
				{
					
					if($xml)
					{
						fwrite($fxml,"\t\t<entry>\n");
						foreach(array_keys($ent) as $fld)
						{
							if($fld == "childEntries") continue;
							if(array_key_exists($fld, $survey->tables[$tbls[$t]]->fields) && preg_match("/^(gps|location)$/i", $survey->tables[$tbls[$t]]->fields[$fld]->type))
							{
								$gpsObj = $ent[$fld];
								try{
									fwrite($fxml,"\t\t\t<{$fld}_lat>{$gpsObj['latitude']}</{$fld}_lat>\n");
									fwrite($fxml,"\t\t\t<{$fld}_lon>{$gpsObj['longitude']}</{$fld}_lon>\n");
									fwrite($fxml,"\t\t\t<{$fld}_acc>{$gpsObj['accuracy']}</{$fld}_acc>\n");
									if (array_key_exists('provider', $gpsObj)) fwrite($fxml,"\t\t\t<{$fld}_provider>{$gpsObj['provider']}</{$fld}_provider>\n");
									if (array_key_exists('altitude', $gpsObj)) fwrite($fxml,"\t\t\t<{$fld}_alt>{$gpsObj['altitude']}</{$fld}_alt>\n");
									if (array_key_exists('bearing', $gpsObj)) fwrite($fxml,"\t\t\t<{$fld}_bearing>{$gpsObj['bearing']}</{$fld}_bearing>\n");
								}
								catch(ErrorException $e)
								{
									fwrite($fxml,"\t\t\t<{$fld}_lat>0</{$fld}_lat>\n");
									fwrite($fxml,"\t\t\t<{$fld}_lon>0</{$fld}_lon>\n");
									fwrite($fxml,"\t\t\t<{$fld}_acc>-1</{$fld}_acc>\n");
									fwrite($fxml,"\t\t\t<{$fld}_provider>None</{$fld}_provider>\n");
									fwrite($fxml,"\t\t\t<{$fld}_alt>0</{$fld}_alt>\n");
									fwrite($fxml,"\t\t\t<{$fld}_bearing>0</{$fld}_bearing>\n");
									$e = null;
								}
								$gpsObj = null;
							}
							else
							{
								fwrite($fxml,"\t\t\t<$fld>" . str_replace(">", "&gt;", str_replace("<", "&lt;", str_replace("&", "&amp;", $ent[$fld]))) . "</$fld>\n");
							}
						}
						fwrite($fxml, "\t\t</entry>\n");
					}
					else
					{
						fwrite($tsv, "{$tbls[$t]}$delim");
						foreach(array_keys($ent) as $fld)
						{
							if(array_key_exists($fld, $survey->tables[$tbls[$t]]->fields) && preg_match("/^(gps|location)$/i", $survey->tables[$tbls[$t]]->fields[$fld]->type) && $ent[$fld] != "")
							{
								$gpsObj = $ent[$fld];
								fwrite($tsv,"{$fld}_lat{$delim}{$gpsObj['latitude']}{$delim}");
								fwrite($tsv,"{$fld}_lon{$delim}{$gpsObj['longitude']}{$delim}");
								fwrite($tsv,"{$fld}_acc{$delim}{$gpsObj['accuracy']}{$delim}");
								fwrite($tsv,"{$fld}_provider{$delim}{$gpsObj['provider']}{$delim}");
								fwrite($tsv,"{$fld}_alt{$delim}{$gpsObj['altitude']}{$delim}");
								if(array_key_exists('bearing', $gpsObj)) fwrite($tsv,"{$fld}_bearing{$delim}{$gpsObj['bearing']}{$delim}");
								
							}
							else
							{
								fwrite($tsv,  "$fld$delim" . escapeTSV($ent[$fld]). $delim);
							}
						}
						//fwrite($tsv, $ent);
						fwrite($tsv,  $rowDelim);
						
					}
					
				}
				elseif(strtolower($_GET["type"]) == "thumbnail")
				{
					foreach(array_keys($ent) as $fld)
					{
						if($fld == "childEntries" || !array_key_exists($fld, $survey->tables[$tbls[$t]]->fields)) continue;
						if($survey->tables[$tbls[$t]]->fields[$fld]->type == "photo" && $ent[$fld] != "")// && file_exists("$root\\ec\\uploads\\tn_".$ent[$fld]))
						{
							$fn = "$root\\ec\\uploads\\";
							$bfn = "$root\\ec\\uploads\\" . $ent[$fld];
							if(strstr($ent[$fld], '~tn~'))
							{
								//for images where the value was stored as a thumbnail
								$fn .= $ent[$fld];
							}
							elseif(strstr($ent[$fld], '~'))
							{
								//for images stored as a value with the project name
								$fn .= str_replace('~', '~tn~', $ent[$fld]);
							}
							else
							{
								//otherwise
								$fn .= $survey->name . '~tn~' . $ent[$fld];
							}
							
							if(file_exists($fn))
							{
								if(!$arc->addFile( $fn, $ent[$fld])) die("fail -- " . $fn);
								$files_added++;
							}
							elseif (file_exists($bfn))
							{
								if(!$arc->addFile( $bfn, $ent[$fld])) die("fail -- " . $bfn);
								$files_added++;
							}
						}
					}
				}
				elseif(strtolower($_GET["type"]) == "full_image")
				{
					foreach(array_keys($ent) as $fld)
					{
					if($fld == "childEntries" || !array_key_exists($fld, $survey->tables[$tbls[$t]]->fields)) continue;
						if($survey->tables[$tbls[$t]]->fields[$fld]->type == "photo" && $ent[$fld] != "")// && file_exists("$root\\ec\\uploads\\".$ent[$fld]))
						{
							$fn = "$root\\ec\\uploads\\";
							$bfn = "$root\\ec\\uploads\\" . $ent[$fld];
							if(strstr($ent[$fld], '~tn~'))
							{
								//for images where the value was stored as a thumbnail
								$fn .= str_replace('~tn~', '~', $ent[$fld]);
							}
							elseif(strstr($ent[$fld], '~'))
							{
								//for images stored as a value with the project name
								$fn .=  $ent[$fld];
							}
							else
							{
								//otherwise
								$fn .= $survey->name . '~' . $ent[$fld];
							}
							
							if(file_exists($fn))
							{
								if(!$arc->addFile( $fn, $ent[$fld])) die("fail -- " . $fn);
								$files_added++;
							}
							elseif (file_exists($bfn))
							{
								if(!$arc->addFile( $bfn, $ent[$fld])) die("fail -- " . $bfn);
								$files_added++;
							}
						}
					}
				}
				else
				{
					foreach(array_keys($ent) as $fld)
					{
						if($fld == "childEntries" || !array_key_exists($fld, $survey->tables[$tbls[$t]]->fields)) continue;
						if($survey->tables[$tbls[$t]]->fields[$fld]->type == $_GET["type"] && $ent[$fld] != "" && file_exists("$root\\ec\\uploads\\".$ent[$fld]))
						{
							if(!$arc->addFile( "$root\\ec\\uploads\\" . $ent[$fld], $ent[$fld])) die("fail -- \\ec\\uploads\\" . $ent[$fld]);
							$files_added++;
						}
					}
				}

				if($ent && !array_key_exists($ent[$survey->tables[$tbls[$t]]->key], $nxtCVals))
				{	
					$nxtCVals[$ent[$survey->tables[$tbls[$t]]->key]] = true;
				}
			}
		}
		if($dataType == "data" && $xml)
		{
			fwrite($fxml,  "</table>");
		}

		if($entry)
		{
			$cField = $survey->tables[$tbls[$t]]->key;
			$cVals = array_keys($nxtCVals);
			$nxtCVals = array();
		}
	}

	if($dataType == "data" && $xml)
	{
		fwrite($fxml,  "</entries>");
		fclose($fxml);
		header("location: $fx_url");
		return;
		//echo file_get_contents($fxn);
	}
	elseif ($dataType == "data")
	{
		fclose($tsv);
		header("location: $ts_url");
		return;
		//echo file_get_contents($txn);
	}
	else
	{
		//close zip files
		$err = $arc->close();
		if($files_added === 0)
		{
			echo "no files";
			return;
		}
			
		if(!$err == true) {
			echo "fail expecting $files_added files";
			return;
		}

		header("Location: $zrl");
		return;
	}
}


function formHandler()
{
	global $url,  $log, $auth;

	$http_accept = getValIfExists($_SERVER, 'HTTP_ACCEPT');
	$format = ($http_accept ? substr($http_accept, strpos($http_accept, '/') + 1) : '');
	$ext = substr($url, strrpos($url, ".") + 1);
	$format = $ext != "" ? $ext : $format;

	$prj = new EcProject();
	$pNameEnd = strpos($url, "/");

	$prj->name = substr($url, 0, $pNameEnd);
	$prj->fetch();
	
	if(!$prj->id)
	{
		echo applyTemplate("./base.html", "./error.html", array("errorType" => "404 ", "error" => "The project {$prj->name} does not exist on this server"));
		return;
	}
	
	$permissionLevel = 0;
	$loggedIn = $auth->isLoggedIn();
	
	if($loggedIn) $permissionLevel = $prj->checkPermission($auth->getEcUserId());

	if(!$prj->isPublic && !$loggedIn)
	{
		loginHandler($url);
		return;
	}
	else if(!$prj->isPublic &&  $permissionLevel < 2)
	{
		echo applyTemplate("./base.html", "./error.html", array("errorType" => "403 ", "error" => "You do not have permission to view this project"));
		return;
	}

	$extStart = strpos($url, ".");
	$frmName = rtrim(substr($url, $pNameEnd + 1, ($extStart > 0 ?  $extStart : strlen($url)) - $pNameEnd - 1), "/");

	if(!array_key_exists($frmName, $prj->tables))
	{
		echo applyTemplate("./base.html", "./error.html", array("errorType" => "404 ", "error" => "The project {$prj->name} does not contain the form $frmName"));
		return;
	}
	
	if($_SERVER["REQUEST_METHOD"] == 'POST')
	{
		
		$log->write("debug", json_encode($_POST));
		header("Cache-Control: no-cache, must-revalidate");
		
		
		$_f = getValIfExists($_FILES, "upload");
		
		if( $_f )
		{
			if($_f['tmp_name'] == '')
			{
				flash('The file is too big to upload', 'err');
			}
			else
			{
				try{
					ini_set('max_execution_time', 200);
					if( preg_match("/\.csv$/", $_f["name"]) )
					{
						$fh = fopen($_f["tmp_name"], 'r');
						
						$res = $prj->tables[$frmName]->parseEntriesCSV($fh);
						
						fclose($fh);
						unset ($fh);
					}
					elseif( preg_match("/\.xml$/", $_f["name"]) )
					{
						$res = $prj->tables[$frmName]->parseEntries(simplexml_load_string(file_get_contents($_f["tmp_name"])));
					}
					//echo "{\"success\":" . ($res === true ? "true": "false") .  ", \"msg\":\"" . ($res==="true" ? "success" : $res) . "\"}";
					flash ("Upload Complete");
				}catch(Exception $ex)
				{
					flash($ex->getMessage(), 'err');
				}
			}
		}
		else
		{
			$ent = $prj->tables[$frmName]->createEntry();
				
			$ent->created = $_POST["created"];
			$ent->deviceId = $_POST["DeviceID"];
			$ent->uploaded = getTimestamp('Y-m-d H:i:s');
			$ent->user = 0;
			
			foreach( array_keys($ent->values) as $key )
			{
				if(!$prj->tables[$frmName]->fields[$key]->active) continue;
				if(array_key_exists($key, $_POST))
				{
					$ent->values[$key] = $_POST[$key];
				}
				elseif (!$prj->tables[$frmName]->fields[$key]->required && !$prj->tables[$frmName]->fields[$key]->key)
				{
					$ent->values[$key] = "";
				}
				else
				{
					header("HTTP/1.1 405 Bad Request");
					echo "{\"success\":false, \"msg\":\"$key is a required field\"}";
					return;
				}
			}
			
			try
			{
				$res = $ent->post();
				echo "{\"success\":" . ($res === true ? "true": "false") .  ", \"msg\":\"" . ($res==="true" ? "success" : $res) . "\"}";
				return;
			}
			catch(Exception $e)
			{
				header("HTTP/1.1 500 Conflict");
				echo $e->getMessage();
			}
		}
	}
	elseif($_SERVER["REQUEST_METHOD"] == "DELETE")
	{
		echo "delete form";
		return;
	}
	else
	{
		ini_set('max_execution_time', 200);
		header("Cache-Control: no-cache, must-revalidate");
		$offset = array_key_exists('start', $_GET) ? $_GET['start'] : 0;
		$limit = array_key_exists('limit', $_GET) ? $_GET['limit'] : 0;;
		
		
		switch($format){
			case 'json':
				
				header('Content-Type: application/json');
				
				$res = $prj->tables[$frmName]->ask($_GET, $offset, $limit, getValIfExists($_GET,"sort"), getValIfExists($_GET,"dir"), false, "object");
				if($res !== true) die($res);
						
				$i = 0;			
				
				$recordSet = array();
				
				while($rec = $prj->tables[$frmName]->recieve(1, true))
				{
					$recordSet = array_merge($recordSet, $rec); 
				}
				
				echo json_encode($recordSet);
				
				return;
				
			case "xml":
				
				header("Content-Type: text/xml");
				if(array_key_exists("mode", $_GET) && $_GET["mode"] == "list")
				{
					echo "<entries>";
					$res = $prj->tables[$frmName]->ask($_GET, $offset, $limit, getValIfExists($_GET,"sort"), getValIfExists($_GET,"dir"), false, "xml");
					if($res !== true) die($res);
					while($ent = $prj->tables[$frmName]->recieve(1, true))
					{
						echo $ent;
					}
					echo "</entries>";
					return;
				}
				else
				{
					echo $prj->tables[$frmName]->toXml();
					return;
				}
			case "kml":
				
				header("Content-Type: application/vnd.google-earth.kml+xml");
				echo '<?xml version="1.0" encoding="UTF-8"?><kml xmlns="http://earth.google.com/kml/2.0"><Document><name>EpiCollect</name><Folder><name>';
				echo "{$prj->name} - {$frmName}";
				echo '</name><visibility>1</visibility>';
					
				$arr = $prj->tables[$frmName]->ask(false, $offset, $limit);
					
				while($ent = $prj->tables[$frmName]->recieve(1, true))
				{
					echo "<Placemark>";
					$desc = "";
					$title = "";
					foreach($prj->tables[$frmName]->fields as $name => $fld)
					{
						if(!$fld->active)continue;
						if($fld->type == "location" || $fld->type == "gps")
						{
							$loc = json_decode($ent[0][$name]);
							echo "<Point><coordinates>{$loc->longitude},{$loc->latitude}</coordinates></Point>";
						}
						elseif($fld->title)
						{
							$title = ($title == "" ? $ent[0][$name] : "$title\t{$ent[0][$name]}");
						}
						else
						{
							$desc = "$name : {$ent[0][$name]}";
						}
					}
					if($title == "") $title = $arr[$prj->tables[$frmName]->key];

					echo "<name>$title</name>";
					echo "<description><![CDATA[$desc]]></description>";
					echo "</Placemark>";
				}
				echo '</Folder></Document></kml>';
					
					
				return;

			case "csv":
				
				//
				if( !file_exists('ec/uploads')) mkdir('ec/uploads');
				$filename = sprintf('ec/uploads/%s_%s_%s%s.csv', $prj->name, $frmName, $prj->getLastUpdated(), md5(http_build_query($_GET)));
				if(!file_exists($filename))
				{
					//ob_implicit_flush(false);
					$fp = fopen($filename, 'w+');
					//$arr = $prj->tables[$frmName]->get(false, $offset, $limit);
					//$arr = $arr[$frmName];
					//echo assocToDelimStr($arr, ",");
					$headers = array_merge(array('DeviceID','created','lastEdited','uploaded'), array_keys($prj->tables[$frmName]->fields));
					$_off = 4;
										
					$num_h = count($headers) - $_off;
					
					$nxt = $prj->getNextTable($frmName, true);
					if($nxt) array_push($headers, sprintf('%s_entries', $nxt->name));
					
					$real_flds = $headers;
					for( $i = 0; $i < $num_h; $i++ )
					{
						$fld = $prj->tables[$frmName]->fields[$headers[$i + $_off]];
						if(!$fld->active)
						{
							array_splice($headers, $i + $_off, 1);
                                                        $num_h--;
						}
						elseif($fld->type == "gps" || $fld->type == "location")
						{
							$name = $fld->name;
							
							//take the GPS fields table, apply each one as a suffix to the field name and then splice 
							
							$gps_flds = array_values(EcTable::$GPS_FIELDS);
							foreach($gps_flds as &$val)
							{
								$val = sprintf('%s%s', $name, $val);
							}
							array_splice($headers, $i + $_off, 1, $gps_flds);
							$i = $i + 5;
						}
					}
					
					fwrite($fp, sprintf("\"%s\"\n", implode('","', $headers)));
					$res = $prj->tables[$frmName]->ask($_GET, $offset, $limit, getValIfExists($_GET,"sort"), getValIfExists($_GET,"dir"), false, "object", true);
					if($res !== true) die($res);
					
					$count_h = count($real_flds);
					
					while($xml = $prj->tables[$frmName]->recieve(1, true))
					{
						$xml = $xml[0];
//						fwrite($fp, sprintf('"%s"
//', $xml));	
						///print_r($xml); 
						for( $i = 0; $i < $count_h; $i++ )
						{
							
							if( $i > 0 ) fwrite($fp, ',');
							fwrite($fp, '"');
							
							if (array_key_exists($real_flds[$i], $xml))
							{
								if($i > $_off && ($i != $count_h - 1) && ($prj->tables[$frmName]->fields[$real_flds[$i]]->type == "gps" || $prj->tables[$frmName]->fields[$real_flds[$i]]->type == "location"))
								{
									try{
										
										$arr = $xml[$real_flds[$i]];
										if(is_string($arr) && trim($xml[$real_flds[$i]]) != '' ){
											$escval = str_replace(': N/A' ,': "N/A"', $xml[$real_flds[$i]]);
											$arr = json_decode($escval, true);	
										}
										
										if(is_array($arr))
										{
											$x = 0;
											foreach(array_keys(EcTable::$GPS_FIELDS) as $k)
											{
												if($x > 0) fwrite($fp, '","');
												
												if(array_key_exists($k, $arr))
												{
													fwrite($fp, $arr[$k]);
												}
												
												$x++;
											}
										}
										else
										{
											for($fieldsIn = 0; $fieldsIn < 6; $fieldsIn++)
											{
												fwrite($fp, '","');
											}
										}
									}catch(Exception $e)
									{
										throw $e;
									}	
								
								}
								else
								{
									fwrite($fp, $xml[$real_flds[$i]]);
								}
							}
							fwrite($fp, '"');
						}
						
						fwrite($fp, "\r\n");
					}
				}
			
				global $SITE_ROOT;
				header("Content-Type: text/csv");
				header(sprintf('location: http://%s%s/%s', $_SERVER['HTTP_HOST'], $SITE_ROOT, $filename));
				
				return;
			
			case "tsv":
		
				//
				if( !file_exists('ec/uploads')) mkdir('ec/uploads');
				$filename = sprintf('ec/uploads/%s_%s_%s%s.tsv', $prj->name, $frmName, $prj->getLastUpdated(), md5(http_build_query($_GET)));
				
				if(!file_exists($filename))
				{
					//ob_implicit_flush(false);
					$fp = fopen($filename, 'w+');
					//$arr = $prj->tables[$frmName]->get(false, $offset, $limit);
					//$arr = $arr[$frmName];
					//echo assocToDelimStr($arr, ",");
					$headers = array_merge(array('DeviceID','created','lastEdited','uploaded'), array_keys($prj->tables[$frmName]->fields));
					$_off = 4;
										
					$num_h = count($headers) - $_off;
					
					$nxt = $prj->getNextTable($frmName, true);
					if($nxt) array_push($headers, sprintf('%s_entries', $nxt->name));
					
					$real_flds = $headers;
					for( $i = 0; $i < $num_h; $i++ )
					{
						$fld = $prj->tables[$frmName]->fields[$headers[$i + $_off]];
						if(!$fld->active)
						{
							array_splice($headers, $i + $_off, 1);
						}
						elseif($fld->type == "gps" || $fld->type == "location")
						{
							$name = $fld->name;
							
							//take the GPS fields table, apply each one as a suffix to the field name and then splice 
							
							$gps_flds = array_values(EcTable::$GPS_FIELDS);
							foreach($gps_flds	 as &$val)
							{
								$val = sprintf('%s_%s', $name, $val);
							}
							array_splice($headers, $i + $_off, 1, $gps_flds);
							$i = $i + 5;
						}
					}
					
					fwrite($fp, sprintf("\"%s\"\n", implode("\"\t\"", $headers)));
					$res = $prj->tables[$frmName]->ask($_GET, $offset, $limit, getValIfExists($_GET,"sort"), getValIfExists($_GET,"dir"), false, "object", true);
					if($res !== true) die($res);
					
					$count_h = count($real_flds);
					
					while($xml = $prj->tables[$frmName]->recieve(1, true))
					{
						$xml = $xml[0];
//						fwrite($fp, sprintf('"%s"
//', $xml));	
						///print_r($xml); 
						for( $i = 0; $i < $count_h; $i++ )
						{
							
							if( $i > 0 ) fwrite($fp, ',');
							fwrite($fp, '"');
							
							if (array_key_exists($real_flds[$i], $xml))
							{
								if($i > $_off && ($i != $count_h - 1) && ($prj->tables[$frmName]->fields[$real_flds[$i]]->type == "gps" || $prj->tables[$frmName]->fields[$real_flds[$i]]->type == "location"))
								{
									try{
										
										$arr = $xml[$real_flds[$i]];
										if(is_string($arr) && trim($xml[$real_flds[$i]]) != '' ){
											$escval = str_replace(': N/A' ,': "N/A"', $xml[$real_flds[$i]]);
											$arr = json_decode($escval, true);	
										}
										
										if(is_array($arr))
										{
											$x = 0;
											foreach(array_keys(EcTable::$GPS_FIELDS) as $k)
											{
												if($x > 0) fwrite($fp, "\"\t\"");
												
												if(array_key_exists($k, $arr))
												{
													fwrite($fp, $arr[$k]);
												}
												
												$x++;
											}
										}
										else
										{
											for($fieldsIn = 0; $fieldsIn < 6; $fieldsIn++)
											{
												fwrite($fp, "\"t\"");
											}
										}
									}catch(Exception $e)
									{
										throw $e;
									}	
								
								}
								else
								{
									fwrite($fp, $xml[$real_flds[$i]]);
								}
							}
							fwrite($fp, '"');
						}
						
						fwrite($fp, "\r\n");
					}
				}
			
				global $SITE_ROOT;
				header("Content-Type: text/tsv");
				header(sprintf('location: http://%s%s/%s', $_SERVER['HTTP_HOST'], $SITE_ROOT, $filename));
			case "js" :
				global $SITE_ROOT;
					
				$files = array("./Ext/ext-base.js", "./Ext/ext-all.js", "./js/EpiCollect2.js");
				header("Content-type: text/javascript");

				echo packFiles($files);
				echo "var survey;
		var table;
		
		var uid = 'web_" .  md5($_SERVER["HTTP_HOST"]) .  "';
		
		function init()
		{
			survey = new EcSurvey;
			//table = new EcTable();
			Ext.Ajax.request({
				url: location.pathname.substring(0, location.pathname.lastIndexOf('/')) + \".xml\",
				success: function (res)
				{
					survey.parse(res.responseXML);
					table = survey.tables[location.pathname.substring(location.pathname.lastIndexOf('/') + 1)]
					drawPage();
				}
			})
		}
		
		function drawPage(){
			var tbl = table.getTable(true, true, true);
			tbl.render('tabPanel');
		}
		
		Ext.onReady(init);";
				return;
			case "css":
				global $SITE_ROOT;
				header("Cache-Control: public; max-age=100000;");
				header("Content-type: text/css");
					
				$files = array("./Ext/ext-all.css", "./css/EpiCollect2.css");
				echo packFiles($files);
				echo ".cp-item {
			vertical-align: top;
			display: inline-block;
			margin-left : 10px;
		}
		
		.cp-item img {
			margin: 0;
		}
		
		.entry
		{
			border-bottom : 1px solid #CCCCCC;
			background-color : #EEEEEE;
			margin : 0;
			padding : 5px 5px 5px 5px;
			
		}
		
		.nolocation
		{
			font-style : italic; 
		}
		
		#timeText
		{
			width : 30em;
		}

		.button
		{
			padding : 0.25em 0.5em 0.25em 0.5em;
			margin : 0em 0.25em 0em 0.25em;
			background-color:#C7DFFC;
			border-radius: 0.25em;
			cursor: pointer;
			font-weight : bold;	
			width : 30%;
		}

		
		.button:active
		{
			background-color: #CCCCCC;
		}
					";
				return;
			default:
				break;
		}
	}
	
	
	
	global $SITE_ROOT;
	$referer = array_key_exists("HTTP_REFERER", $_SERVER) ? $_SERVER["HTTP_REFERER"] : "";
	if(!array_key_exists("formCrumbs", $_SESSION) || !$prj->getPreviousTable($frmName) || !preg_match("/{$prj->name}\//", $referer))
	{
		$_SESSION["formCrumbs"] = array();
	}
	$p = "";
	if(array_key_exists("prevForm", $_GET))
	{
	
		$pKey = $prj->tables[$_GET["prevForm"]]->key;
		$_SESSION["formCrumbs"][$_GET["prevForm"]] =  $_GET[$pKey];
		//if we've come back up a step we need to remove the entry. We assume that the crumbs are in the correct order to
		//draw them in the correct order
	}

	$pk = null;
	$pv = null;
	foreach($_SESSION["formCrumbs"] as $k => $v)
	{
		if($prj->tables[$k]->number >= $prj->tables[$frmName]->number)
		{
			unset($_SESSION["formCrumbs"][$k]);
		}
		else
		{
			if($pk)
			{
				$p .= "&gt; <a href=\"{$k}?{$prj->tables[$pk]->key}=$pv\">{$k} : $v </a>";
			}
			else
			{
				$p .= "&gt; <a href=\"{$k}\">{$k} : $v </a>";
			}
			
			$pk = $k;
			$pv = $v;
		}
	}
	
	
		
	$mapScript = $prj->tables[$frmName]->hasGps() ? "<script type=\"text/javascript\" src=\"" . (getValIfExists($_SERVER, 'HTTPS') ? 'https' : 'http') . "://maps.google.com/maps/api/js?sensor=false\"></script>
	<script type=\"text/javascript\" src=\"{$SITE_ROOT}/js/markerclusterer.js\"></script>" : "";
	$vars = array(
			"prevForm" => $p,
			"projectName" => $prj->name,
			"formName" => $frmName, 
			"curate" =>  $permissionLevel > 1 ? "true" : "false", 
			"mapScript" => $mapScript,
			"curationbuttons" => $permissionLevel > 1 ? sprintf('<span class="button-set"><a href="javascript:project.forms[formName].displayForm({ vertical : false });"><img src="%s/images/glyphicons/glyphicons_248_asterisk.png" title="New Entry" alt="New Entry"></a>
				<a href="javascript:editSelected();"><img src="%s/images/glyphicons/glyphicons_030_pencil.png" title="Edit Entry" alt="Edit Entry"></a>
				<a href="javascript:project.forms[formName].deleteEntry(window.ecplus_entries[$(\'.ecplus-data tbody tr.selected\').index()][project.forms[formName].key]);"><img src="%s/images/glyphicons/glyphicons_016_bin.png" title="Delete Entry" alt="Delete Entry"></a></span>',
				$SITE_ROOT, $SITE_ROOT, $SITE_ROOT): '',
			"csvform" => $permissionLevel > 1 ?$csvform = '<div id="csvform">
				<h3><a href="#">Upload data from a CSV file</a></h3>
				<div>
					<form method="POST" enctype="multipart/form-data" >
						<label for="upload">File to upload : </label><input type="file" name="upload" /><br />
						<input type="submit" name="submit" value="Upload File" />
					</form>
				</div>
			</div>' : '' );
	echo applyTemplate('base.html', './FormHome.html', $vars);
}

function entryHandler()
{
	global $auth, $url, $log, $SITE_ROOT;

	header("Cache-Control: no-cache, must-revalidate");

	$prjEnd = strpos($url, "/");
	$frmEnd =  strpos($url, "/", $prjEnd+1);
	$prjName = substr($url,0,$prjEnd);
	$frmName = substr($url,$prjEnd + 1,$frmEnd - $prjEnd - 1);
	$entId = urldecode(substr($url, $frmEnd + 1));

	$prj = new EcProject();
	$prj->name = $prjName;
	$prj->fetch();

	$permissionLevel = 0;
	$loggedIn = $auth->isLoggedIn();
	
	if($loggedIn) $permissionLevel = $prj->checkPermission($auth->getEcUserId());
	
	$ent = new EcEntry($prj->tables[$frmName]);
	$ent->key = $entId;
	$r = $ent->fetch();


	if($_SERVER["REQUEST_METHOD"] == "DELETE")
	{
		if($permissionLevel < 2)
		{
			flash('You do not have permission to delete entries on this project');
			header('HTTP/1.1 403 Forbidden', 403);	
			return;
		}
		
		if($r === true)
		{
			try
			{
				$ent->delete();
			}
			catch(Exception $e)
			{
				if(preg_match("/^Message\s?:/", $e->getMessage()))
				{
					header("HTTP/1.1 409 Conflict", 409);
				}
				else
				{
					header("HTTP/1.1 500 Internal Server Error", 500);
				}
				echo $e->getMessage();
			}
		}
		else
		{
			echo $r;
		}
	}
	else if($_SERVER["REQUEST_METHOD"] == "PUT")
	{
		if($permissionLevel < 2)
		{
			flash('You do not have permission to edit entries on this project');
			header('HTTP/1.1 403 Forbidden', 403);
			return;
		}
		
		if($r === true)
		{
			$request_vars = array();
			parse_str(file_get_contents("php://input"), $request_vars);

			foreach($request_vars as $key => $value)
			{
				if(array_key_exists($key, $prj->tables[$frmName]->fields))
				{
					$ent->values[$key] = $value;
				}
			}

			$r = $ent->put();
			if($r !== true)
			{ 
				echo "{ \"false\" : true, \"msg\" : \"$r\"}";
			}
			else 
			{
				echo "{ \"success\" : true, \"msg\" : \"\"}";
			}
		}
		else{
			echo "{ \"success\" : false, \"msg\" : \"$r\"";
		}
	}
	else if($_SERVER["REQUEST_METHOD"] == "GET")
	{
		$val = getValIfExists($_GET, 'term');
		$do  = getValIfExists($_GET, 'validate');
		$key_from = getValIfExists($_GET, 'key_from');
		$secondary_field = getValIfExists($_GET, 'secondary_field');
		$secondary_value = getValIfExists($_GET, 'secondary_value');
		ini_set('max_execution_time', 60);
		if($entId == 'title')
		{
			if($do)
			{
				echo $prj->tables[$frmName]->validateTitle($val, $secondary_field, $secondary_value);				
			}
			elseif($key_from)
			{

				echo $prj->tables[$frmName]->getTitleFromKey($val);
			}
			else
			{
				
				echo $prj->tables[$frmName]->autoCompleteTitle($val, $secondary_field, $secondary_value);
			}
		}
		elseif($do)
		{
			echo $prj->tables[$frmName]->validate($entId, $val);
		}
		else
		{
			echo $prj->tables[$frmName]->autoComplete($entId, $val);
		}
	}
}


function updateUser()
{
	global $auth;
	
	if($_SERVER["REQUEST_METHOD"] == "POST")
	{
		$pwd = getValIfExists($_POST, "password");
		$con = getValIfExists($_POST, "confirmpassword");
		
		$change = true;
		
		if(!$pwd || !$con)
		{
			$change = false;
			flash("Password not changed, password was blank.", "err");
		}
		
		if($pwd != $con)
		{
			$change = false;
			flash("Password not changed, passwords did not match.", "err");
		}
		
		
		if(strlen($pwd) < 8) {
			$change = false;
			flash("Password not changed, password was shorter than 8 characters.", "err");
		}
		
		if(!preg_match("/^.*(?=.{8,})(?=.*\d)(?=.*[a-zA-Z]).*$/", $pwd))
		{
			$change = false;
			flash("Password not changed, password must be longer than 8 characters and contain at least one letter and at least one number.", "err");
		}
		
		if($auth->setPassword($auth->getEcUserId(), $_POST["password"]))
		{
			flash("Password changed");
		}else {
			flash("Password not changed.", "err");
		}
	}
	
	$name = explode(" ", $auth->getUserNickname());
	
	$username = $auth->getUserName();
	$is_not_local = $_SESSION['provider'] != 'LOCAL';
	
	if($is_not_local) flash('You cannot update user information for Open ID or LDAP users unless you do it throught your Open ID or LDAP provider','err');
		
	echo applyTemplate("base.html", "./updateUser.html", array(
			"firstName" => $name[0], 
			"lastName" => $name[1],
			"email" => $auth->getUserEmail(),
			"userName" => $username,
			"disabled" => $is_not_local ? 'disabled="disabled"' : ''
	));
}

function saveUser()
{
	global $auth, $db;
	$qry = "CALL updateUser(" . $auth->getEcUserId() . ",'{$_POST["name"]}','{$_POST["email"]}')";
	$res = $db->do_query($qry);

	if($res === true)
	{
		echo '{"success" : true, "msg" : "User updated successfully"}';
	}
	else
	{
		echo '{"success" : false, "msg" : "'.$res.'"}';
	}
}

function uploadProjectXML()
{
	global $SITE_ROOT;

	$prj = new EcProject();

	if(!file_exists("ec/xml")) mkdir("ec/xml");

	$newfn = "ec/xml/" . $_FILES["projectXML"]["name"];
	move_uploaded_file($_FILES["projectXML"]["tmp_name"], $newfn);
	$prj->parse(file_get_contents($newfn));

	$res = $prj->post();
	if($res === true)
	{
		$server = trim($_SERVER["HTTP_HOST"], "/");
		$root = trim($SITE_ROOT, "/");
		header("location: http://$server/$root/editProject.html?name={$prj->name}");
		return;
	}
	else
	{
		$vals = array("error" => $res);
		echo applyTemplate("base.html","./error.html",$vals);
	}
}

function createFromXml()
{
	global $url, $SITE_ROOT, $server, $root;

	$prj = new EcProject();
	
	if(array_key_exists("xml", $_REQUEST) && $_REQUEST["xml"] != "")
	{
		$xmlFn = "ec/xml/{$_REQUEST["xml"]}";
	
		$prj->parse(file_get_contents($xmlFn));
	}
	elseif(array_key_exists("name", $_POST))
	{
		$prj->name = $_POST["name"];
		$prj->submission_id = strtolower($prj->name);
	}
	elseif(array_key_exists("raw_xml", $_POST))
	{
		$prj->parse($_POST["raw_xml"]);
	}
	
	if(!$prj->name || $prj->name == "")
	{
		flash("No project name provided");
		header("location: http://$server/$root/createProject.html");
	}
	
	$prj->isListed = $_REQUEST["listed"] == "true";
	$prj->isPublic = $_REQUEST["public"] == "true";
	$prj->publicSubmission = true;
	$res = $prj->post();
	if($res !== true)die($res);
	
	$res = $prj->setManagers($_POST["managers"]);
	if($res !== true)die($res);
	$res = $prj->setCurators($_POST["curators"]);
	if($res !== true)die($res);
	// TODO : add submitter $prj->setProjectPermissions($submitters,1);
	
	if($res === true)
	{
		$server = trim($_SERVER["HTTP_HOST"], "/");
		$root = trim($SITE_ROOT, "/");
		header ("location: http://$server/$root/" . preg_replace("/create.*$/", $prj->name, $url));
	}
	else
	{
		$vals = array("error" => $res);
		echo applyTemplate("base.html","error.html",$vals);
	}
}

function updateXML()
{
	global $url, $SITE_ROOT;

	$xml = '';
	if(array_key_exists("xml", $_REQUEST) && trim($_REQUEST['xml']) != '')
	{
		$xml = file_get_contents("ec/xml/{$_REQUEST["xml"]}");
	}
	elseif(array_key_exists("data", $_POST) && $_POST["data"] != '')
	{
		$xml = $_POST["data"];	
	}
	else 
	{
		$xml = false;
	}
		
	$prj = new EcProject();
	$prj->name = substr($url, 0, strpos($url, "/"));
	$prj->fetch();
	
	//echo '--', $xml , '--';
	if($xml)
	{
		$n = '';
		$validation = validate(NULL,$xml, $n, true, true);
		if($validation !== true)
		{
			echo "{ \"result\": false , \"message\" : \"" . $validation . "\" }";
			return;
		}
		unset($validation);
		
		foreach($prj->tables as $name => $tbl)
		{
			foreach($prj->tables[$name]->fields as $fldname => $fld)
			{
				$prj->tables[$name]->fields[$fldname]->active = false;
			}
		}
		try 
		{
			$prj->parse($xml);
			
		}catch(Exception $err)
		{
			echo "{ \"result\": false , \"message\" : \"" . $err->getMessage() . "\" }";
			return;
		}
		
		$prj->publicSubmission = true;
	}

	if(!getValIfExists($_POST, "skipdesc"))
	{	
		$prj->description = getValIfExists($_POST, "description");
		$prj->image = getValIfExists($_POST, "projectImage");
	}
	
	if(array_key_exists("listed", $_REQUEST)) $prj->isListed = $_REQUEST["listed"] == "true";
	if(array_key_exists("public", $_REQUEST)) $prj->isPublic = $_REQUEST["public"] == "true";
	$res = $prj->put($prj->name);
	if($res !== true) die($res);
	if(array_key_exists("managers", $_POST)) $prj->setManagers($_POST["managers"]);
	if(array_key_exists("curators", $_POST)) $prj->setCurators($_POST["curators"]);
	// TODO : add submitter $prj->setProjectPermissions($submitters,1);

	if($res === true)
	{
		$server = trim($_SERVER["HTTP_HOST"], "/");
		$root = trim($SITE_ROOT, "/");
		//header ("location: http://$server/$root/" . preg_replace("/updateStructure.*$/", $prj->name, $url));
		echo "{ \"result\": true }";
	}
	else
	{
		echo "{ \"result\": false , \"message\" : \"$res\" }";
	}
}

function tableStats()
{
	global  $url, $log;
	ini_set('max_execution_time', 60);
	header("Cache-Control: no-cache, must-revalidate");

	$prjEnd = strpos($url, "/");
	$frmEnd =  strpos($url, "/", $prjEnd+1);
	$prjName = substr($url,0,$prjEnd);
	$frmName = substr($url,$prjEnd + 1,$frmEnd - $prjEnd - 1);

	$prj = new EcProject();
	$prj->name = $prjName;
	$prj->fetch();
	echo json_encode($prj->tables[$frmName]->getSummary($_GET));
}

function listXml()
{
	//List XML files
	if(!file_exists("ec/xml")) mkdir("ec/xml");
	$h = opendir("ec/xml");
	$tbl =  "<table id=\"projectTable\"><tr><th>File</th><th>Validation Result</th><th>Create</th><td>&nbsp;</td></tr>";
	$n = "";
	while($fn = readdir($h))
	{
		if(!preg_match("/^\.|.*\.xsd$/", $fn))
		{
			$e = false;
			$v = validate($fn, NULL, $n);
			if($v === true)
			{
				$p = new EcProject;
				$p->name = $n;
				$res = $p->fetch();
				if($res !== true) echo $res;
				$e = count($p->tables) > 0;
			}

			$tbl .= "<tr id=\"{$n}row\"><td>$fn</td><td>" . ($v === true ? "$n - <span class=\"success\" >Valid</span>" : "$n - <span class=\"failure\" >Invalid</span> <a href=\"javascript:expand('{$n}res', '{$n}row')\">Show errors</a><div id=\"{$n}res\" class=\"verrors\">$v</div>") . ($e === true ?  "</td><td>Project already exists : <a class=\"button\" href=\"$n\">homepage</a></td><td>&nbsp;</td></tr>" : ($v ===true ? "</td><td><a class=\"button\" href=\"create?xml=$fn\">Create Project</a></td><td>&nbsp;</td></tr>" : "</td><td></td><td>&nbsp;</td></tr>"));
		}
	}
	$tbl.= "</table>";
	return $tbl;
	//DONE!: for each get the project name and work out if the project exists.
}

function projectCreator()
{
	if(!file_exists("ec/xml")) mkdir("ec/xml");
	
	if(array_key_exists("xml", $_FILES))
	{
		move_uploaded_file($_FILES["xml"]["tmp_name"], "ec/xml/{$_FILES["xml"]["name"]}");
	}
	if(getValIfExists($_REQUEST, "json"))
	{
		$n = '';
		echo validate("{$_FILES["xml"]["name"]}", NULL, $n, getValIfExists($_POST, 'update'));
	}
	else
	{
		$vals = array();
		$vals["xmlFolder"] = getcwd() . "/xml";
		$vals["projects"] = listXML();
		echo applyTemplate("base.html","create.html", $vals);
	}
}

function validate($fn = NULL, $xml = NULL, &$name = NULL, $update = false, $returnJson = false)
{
	global $SITE_ROOT;

	$isValid = true;
	$msgs = array();

	if(!$fn) $fn = getValIfExists($_GET, "filename");
	
	if($fn && !$xml)
	{		

		$xml = file_get_contents("./ec/xml/$fn");
	}

	$prj = new EcProject;
	try{
		$prj->parse($xml);
	}
	catch(Exception $err)
	{
		array_push($msgs, "The XML for this project is invalid : " . $err->getMessage());
	}
	
	if(count($msgs) == 0)
	{	
		$prj->name = trim($prj->name);
		
		if(!$update && EcProject::projectExists($prj->name))
		{
			array_push($msgs, sprintf('A project called %s already exists.', $prj->name));
		}
		
		if(!$prj->name || $prj->name == "")
		{
			array_push($msgs, "This project does not have a name, please include a projectName attribute in the model tag.");
		}
	
		if(!$prj->ecVersionNumber || $prj->ecVersionNumber == "")
		{
			array_push($msgs, "Projects must specify a version");
		}
	
		if(count($prj->tables) == 0) array_push($msgs, "A project must contain at least one table.");
	
		foreach($prj->tables as $tbl)
		{
			if($tbl->number <= 0) continue;
			if(!$tbl->name || $tbl->name == "") array_push($msgs, "Each form must have a name.");
			if(!$tbl->key || $tbl->key == "")
			{
				array_push($msgs, "Each form must have a unique key field.");
			}
			elseif(!$tbl->fields[$tbl->key])
			{
				array_push($msgs, "The form {$tbl->name} does not have a field called {$tbl->key}, please specify another key field.");
			}
			elseif(!preg_match("/input|barcode/", $tbl->fields[$tbl->key]->type))
			{
				array_push($msgs, "The field {$tbl->key} in the form {$tbl->name} is a {$tbl->fields[$tbl->key]->type} field. All key fields must be either text inputs or barcodes.");
			}
				
			//array_push($msgs, "<b>$tbl->name</b>");
			foreach($tbl->fields as $fld)
			{
				if(preg_match("/^[0-9]/", $fld->name) || $fld->name == '')
				{
					$isValid = false;
					array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid name, field names cannot start with a number");
				}
				if(!$fld->label || $fld->label == '')
				{
					$isValid = false;
					array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has no label. All fields must have a label and the label must not be null. If you have added a label to the field please make sure the tags are all in lower case i.e. <label>...</label> not <Label>...</Label>");
				}
	
				if($fld->jump)
				{
					//break the jump up into it's parts
					$jBits = explode(",", $fld->jump);
					if(count($jBits) % 2 != 0)
					{
						$isValid = false;
						array_push($msgs, "The field called {$fld->name} in the form {$tbl->name} has an invalid jump attribute. All jumps should be in the format value,target");
					}
						
					for($i = 0; $i + 1 < count($jBits); $i += 2)
					{
						$jBits[$i] = trim($jBits[$i]);
						$jBits[$i + 1] = trim($jBits[$i + 1]);
						//check that the jump destination exists in the current form
						if(!preg_match( '/END/i', $jBits[$i]) && !array_key_exists($jBits[$i], $tbl->fields))
						{
							$isValid = false;
							array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid jump statement the field {$jBits[$i]} that is the target when the value is {$jBits[$i+1]} does not exist in this form");
						}
						//check that the jump value exists in the form
						if( $fld->type == "select1" || $fld->type == "radio")
						{
							$tval = preg_replace('/^!/', '',$jBits[$i + 1]);
							if(!($jBits[$i + 1] == "all" ||  (preg_match('/^[0-9]+$/',$tval) && (intval($tval) <= count($fld->options)) && intval($tval) > 0)))
							{
								$isValid = false;
								array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid jump statement the jump to {$jBits[$i]} is set to happen when {$jBits[$i+1]}. If the field type is {$fld->type} the target must be between 1 and " . (count($fld->options)) . " for this field options the criteria must be a valid index of an element or 'all'");
							}
						}
						elseif($fld->type == "select")
						{
							$found = false;
							for($o = 0; $o < count($fld->options); $o++)
							{
								if(preg_match("/^!?" . $fld->options[$o]->value."$/", $jBits[$i +1 ]))
								{
									$found = true;
									break;
								}
							}
							if(!$found)
							{
								$isValid = false;
								array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid jump statement the jump to {$jBits[$i]} is set to happen when this field is {$jBits[$i+1]}. This value does not exist as an option.");
							}
						}
						elseif($fld->type == 'numeric')
						{
							if(!preg_match('/NULL|all/i', $jBits[$i+1]))
							{
								$v = intval($jBits[$i+1], 10);
								if($fld->max && $v > $fld->max)
								{
									$isValid = false;
									array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid jump statement, the jump value exceeds the fields maximum;");
								}
								if($fld->min && $v < $fld->min)
								{
									$isValid = false;
									array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid jump statement, the jump value is less than the fields maximum;");
								}
							}
						}
					
					}
				}
				if($fld->type == "group")
				{
					//make sure the group form exists
					if(!$fld->group_form)
					{
						$isValid = false;
						array_push($msgs, "The field {$fld->name} is a group form but has no group attribute.");
					}
					/*elseif(!array_key_exists($fld->group_form, $prj->tables))
					{
						$isValid = false;
						array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has the form {$fld->group_form} set as it's group form, but the form {$fld->group_form} doesn not exist.");
					}*/
				}
				if($fld->type == "branch")
				{
					//make sure the branch form exists
					if(!array_key_exists($fld->branch_form, $prj->tables))
					{
						$isValid = false;
						array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has the form {$fld->branch_form} set as it's branch form, but the form {$fld->branch_form} doesn not exist.");
					}
				}
				if($fld->regex)
				{
					//make sure the REGEX is a valid Regex
					try
					{
						preg_match("/" . $fld->regex . "/", "12345");
					}
					catch(Exception $err)
					{
						array_push($msgs, "The field {$fld->name} in the form {$tbl->name} has an invalid regular expression in it's regex attribute \"($fld->regex)\".");
					}		
				}
			}
		}
		$name = $prj->name;
	}
	
	if( $returnJson )
	{
		return count($msgs) == 0 ? true : str_replace('"', '\"', implode("\",\"", $msgs));
	}
	elseif( getValIfExists($_REQUEST, "json") )
	{
		echo "{\"valid\" : " . (count($msgs) == 0 ? "true" : "false") . ", \"msgs\" : [ \"" . str_replace('"', '\"', implode("\",\"", $msgs))  . "\" ], \"name\" : \"$name\", \"file\" :\"$fn\" }";
	}
	else
	{
		return count($msgs) == 0 ? true : "<ol><li>" . str_replace('"', '\"', implode("</li><li>", $msgs)) . "</li></ol>";
	}
}

function admin()
{

	global $auth, $SITE_ROOT, $cfg;

	if(count($auth->getServerManagers()) > 0 && $auth->isLoggedIn() && !$auth->isServerManager())
	{
		flash("Configuration only available to server managers", "err");
			
		header("location: $SITE_ROOT/");
		return;
	}

	if($_SERVER["REQUEST_METHOD"] == "GET")
	{
		$mans = $auth->getServerManagers();
		$men = "";
		foreach($mans as $man)
		{
			$men .= "<form method=\"POST\" action=\"user/manager\"><p>{$man["firstName"]} {$man["lastName"]} ({$man["Email"]})<input type=\"hidden\" name=\"email\" value=\"{$man["Email"]}\" />" .($auth->getUserEmail() == $man["Email"] ? "" : "<input type=\"submit\" name=\"remove\" value=\"Remove\" />" ) ."</form></p>";
		}
			
		$arr = "{";
		foreach($cfg->settings as $k => $v)
		{
			foreach($v as $sk => $sv)
			{
				$arr .= "\"{$k}\\\\{$sk}\" : \"$sv\",";
			}
		}
		$arr = trim($arr, ",") . "}";
			
		echo applyTemplate("./base.html", "./admin.html", array("serverManagers" => $men, "vals" => $arr));

	}
	else if($_SERVER["REQUEST_METHOD"] == "POST")
	{
		createUser();
	}
}

function createUser()
{
	global $auth, $SITE_ROOT, $cfg;

	header("Cache-Control: no-cache; must-revalidate;");

	if($cfg->settings["security"]["use_local"] != "true")
	{
		flash("This server is not configured to user Local Accounts", "err");
	}
	elseif($auth->createUser($_POST["username"], $_POST["password"], $_POST["email"], $_POST["fname"], $_POST["lname"],"en"))
	{
		flash("User Added");
	}
	else
	{
		flash("Could not create the user", "err");
	}

	header("location: http://{$_SERVER["HTTP_HOST"]}$SITE_ROOT/admin");
	return;
}

function managerHandler()
{
	global $auth, $SITE_ROOT;

	if($_SERVER["REQUEST_METHOD"] == "POST")
	{
		if(array_key_exists("remove", $_POST) && $_POST["remove"] == "Remove")
		{
			$auth->removeServerManager($_POST["email"]);
			flash("{$_POST["email"]} is no longer a server manager.");
		}
		else
		{
			$x = $auth->makeServerManager($_POST["email"]);
			if($x === 1)
			{
				flash("{$_POST["email"]} is now a server manager.");
			}
			elseif ($x === -1)
			{
				flash("{$_POST["email"]} is already a server manager.");
			}
			else
			{
				flash("Could not find user {$_POST["email"]}. ($x)", "err");
			}
		}
	}


	header("location:  http://{$_SERVER["HTTP_HOST"]}{$SITE_ROOT}/admin#manage");
	return;
}

function createProject()
{
	global $url;

	header("Cache-Control: no-cache, must-revalidate");

	$vals =  array(
		
	);

	echo applyTemplate("./base.html","./createProject.html",$vals);

}

function updateProject()
{
	global  $url, $auth, $db;

	$pNameEnd = strrpos($url, "/");
	$oldName = substr($url, 0, $pNameEnd);
	$prj = new EcProject();
	$prj->name = $oldName;
	$prj->fetch();
	
	$role = intVal($prj->checkPermission($auth->getEcUserId()));
	
	if($role != 3)
	{
		
		header("Cache-Control: no-cache; must-revalidate;");
		flash ("You do not have permission to manage this project", "err");
		$url = str_replace("update", "", $url);
		header("location: {$SITE_ROOT}/$url");
	}
	else
	{
		header("Cache-Control: no-cache; must-revalidate;");
		if($_SERVER["REQUEST_METHOD"] == "POST")
		{
			$xml = getValIfExists($_POST, "xml");
			$managers = getValIfExists($_POST, "managers");
			$curators = getValIfExists($_POST, "curators");
			$public = getValIfExists($_POST, "public");
			$listed = getValIfExists($_POST, "listed");

			
			
			$drty = false;
			if($xml && $xml != "")
			{
				$prj->parse($xml);
				if($prj->name != oldName) 
				{
					header("HTTP/1.1 400 CANNOT CHANGE NAME", 400);
					return false;
				}
				$drty = true;
			}
			
			echo 'description ' . $prj->description . ' ' .getValIfExists($_POST, "description") ;
			if($prj->description != getValIfExists($_POST, "description"))
			{
				
				$prj->description = getValIfExists($_POST, "description");
				$drty = true;
			}
			if($prj->image != getValIfExists($_POST, "projectImage"))
			{
				$prj->image = getValIfExists($_POST, "projectImage");
				$drty = true;
			}
			
			if($public !== false)
			{
				$prj->isPublic = $public === "true";
				$drty = true;
			}
			if($listed !== false)
			{
				$prj->isListed = $listed === "true";
				$drty = true;
			}
			if($drty)
			{
				$prj->publicSubmission = true;
				$prj->put($oldName);
			}
			if($curators) $prj->setCurators($curators);
			if($managers) $prj->setManagers($managers);
				
		}
		else
		{
			$managers = $prj->getManagers();
			if(is_array($managers))
			{
				$managers = '"' . implode(",", $managers) . '"';
			}else{
				$curators = '""';
			}

			$curators = $prj->getCurators();
			if(is_array($curators))
			{
				$curators = '"' . implode(",", $curators) . '"';
			}else{
				$curators = '""';
			}

			$img = $prj->image;
			$img = substr($img, strpos($img, '~') + 1);
			
			echo applyTemplate("./base.html", "./updateProject.html", array("projectName" => $prj->name, "description" => $prj->description, "image" => $img, "managers" => $managers, "curators" => $curators, "public" => $prj->isPublic, "listed" => $prj->isListed ));
			return;
		}
	}
}


function formBuilder()
{
	global $url, $auth;
	$prj_name = str_replace('/formBuilder', '', $url);
	
        $prj = new EcProject();
        $prj->name = $prj_name;
        $prj->fetch();
        
        $uid = $auth->getEcUserId();
        
        if($prj->checkPermission($uid))
        {
            echo applyTemplate('./base.html' , './createOrEditForm.html', array('projectName' => $prj_name));
        }
        else
        {
            accessDenied(sprintf(' Project %s' , $prj_name ));
        }
}

function getControlTypes()
{
	global $db;
	//$db = new dbConnection();
	$res = $db->do_query('SELECT * FROM FieldType');

	if($res === true)
	{
		$arr = array();
		while ($a = $db->get_row_array())
		{
			array_push($arr, $a);
		}
			
		header ("Content-type: application/json");
		echo json_encode(array("controlTypes" => $arr));
	}
}

function uploadMedia()
{
	global $url, $SITE_ROOT;
	$pNameEnd = strpos($url, "/");
	$pname = substr($url, 0, $pNameEnd);
	$extStart = strpos($url, ".");
	$fNameEnd = strpos($url, "/", $pNameEnd + 1);
	$frmName = rtrim(substr($url, $pNameEnd + 1, $fNameEnd - $pNameEnd), "/");

	if($frmName == 'uploadMedia') $frmName = false;
	
	$tvals = array("project" => $pname,"form" => $frmName);

	if(!file_exists('ec/uploads')) mkdir('ec/uploads');
	
	if(array_key_exists("newfile", $_FILES) && $_FILES["newfile"]["error"] == 0)
	{
		if(preg_match("/\.(png|gif|jpe?g|bmp|tiff?)$/", $_FILES["newfile"]["name"]))
		{
			$fn = "ec/uploads/{$pname}~".$_FILES["newfile"]["name"];
			move_uploaded_file($_FILES["newfile"]["tmp_name"], $fn);

			$tnfn = str_replace("~", "~tn~", $fn);

			$imgSize = getimagesize($fn);

			$scl = min(384/$imgSize[0], 512/$imgSize[1]);
			$nw = $imgSize[0] * $scl;$nh =  $imgSize[1] * $scl;

			if(preg_match("/\.jpe?g$/", $fn))
			{
				$img = imagecreatefromjpeg($fn);
			}
			elseif(preg_match("/\.gif$/", $fn))
			{
				$img = imagecreatefromgif($fn);
			}
			elseif(preg_match("/\.png$/", $fn))
			{
				$img = imagecreatefrompng($fn);
				imagealphablending($img, true); // setting alpha blending on
				imagesavealpha($img, true); // save alphablending setting (important)
			}
			else
			{
				echo "not supported";
				return;
			}

			$thn = imagecreatetruecolor($nw,$nh);
			imagecopyresampled($thn, $img, 0,0, 0,0, $nw, $nh, $imgSize[0], $imgSize[1]);


			if(preg_match("/\.jpe?g$/", $fn))
			{
				imagejpeg($thn, $tnfn, 95);
			}
			elseif(preg_match("/\.gif$/", $fn))
			{
				imagegif($thn, $tnfn);
			}
			elseif(preg_match("/\.png$/", $fn))
			{
				imagepng($thn, $tnfn);
			}

			$tvals["mediaTag"] = "<img src=\"$SITE_ROOT/{$tnfn}\" />";
		}
		elseif(preg_match("/\.(mov|wav|mpe?g?[34]|ogg|ogv)$/", $_FILES["newfile"]["name"]))
		{
			//audio/video handler
			$fn = "ec/uploads/{$pname}~".$_FILES["newfile"]["name"];
			move_uploaded_file($_FILES["newfile"]["tmp_name"], $fn);

			$tvals["mediaTag"] = "<a href=\"$SITE_ROOT/{$pname}~{$fn}\" >View File</a>";
		}
		else
		{
			echo "not supported";
			return;
		}
		
	}

	if(array_key_exists("fn", $_GET))
	{
		$fn = "ec/uploads/{$pname}~".$_GET["fn"];
		$tvals["mediaTag"] = "<img src=\"$SITE_ROOT/{$fn}\" height=\"150\" />";
		$tvals["fn"] = str_replace("ec/uploads/", "", $fn);
	}

	echo applyTemplate("./uploadIFrame.html", "./base.html", $tvals);
}

function getMedia()
{
	global $url;
	
	if(preg_match('~tn~', $url) )
	{
		//if the image is a thumbnail just try and open it
		header("Content-type: " . mimeType($url));
		echo file_get_contents("./" . $url);
	}
	else
	{
		if(file_exists("./$url"))
		{
			header("Content-type: " . mimeType($url));
			echo file_get_contents("./" . $url);
		}
		elseif(file_exists('./'. str_replace("~", "~tn~", $url)))
		{
			$u = str_replace("~", "~tn~", $url);
			header("Content-type: " . mimeType($u));
			echo file_get_contents("./" . $u);
		}
		elseif(file_exists('./'. substr($url, strpos($url, '~'))))
		{
			$u = substr($url, strpos($url, '~'));
			header("Content-type: " . mimeType($u));
			echo file_get_contents("./" . $u);
		}
		else
		{
			header('HTTP/1.1 404 NOT FOUND', 404);
			return;
		}
	}
}

function getImage()
{
	global $url, $auth;
	
	$prj = new EcProject();
	$pNameEnd = strpos($url, "/");
	
	$prj->name = substr($url, 0, $pNameEnd);
	$prj->fetch();
	
	if(!$prj->id)
	{
		echo applyTemplate("./base.html", "./error.html", array("errorType" => "404 ", "error" => "The project {$prj->name} does not exist on this server"));
		return;
	}
	
	
	$permissionLevel = 0;
	$loggedIn = $auth->isLoggedIn();
	
	if($loggedIn) $permissionLevel = $prj->checkPermission($auth->getEcUserId());
	
	if(!$prj->isPublic && !$loggedIn)
	{
		loginHandler($url);
		return;
	}
	else if(!$prj->isPublic &&  $permissionLevel < 2)
	{
		echo applyTemplate("./base.html", "./error.html", array("errorType" => "403 ", "error" => "You do not have permission to view this project"));
		return;
	}
	
	$extStart = strrpos($url, '/');
	$frmName = rtrim(substr($url, $pNameEnd + 1, ($extStart > 0 ?  $extStart : strlen($url)) - $pNameEnd - 1), "/");
	
	$picName = getValIfExists($_GET, 'img');
	
	header('Content-type: image/jpeg');
	
	if($picName)
	{
		$tn = sprintf('./ec/uploads/%s~tn~%s', $prj->name, $picName);
		$full = sprintf('./ec/uploads/%s~%s', $prj->name, $picName);
		
		$thumbnail = getValIfExists($_GET, 'thumbnail') === 'true';
		
		$raw_not_tn = str_replace('~tn~', '~', $picName);
		
		if(!$thumbnail && file_exists($full))
		{
			//try with project prefix
			echo file_get_contents($full);
		}
		elseif(file_exists($tn))
		{
			//try with project and thumbnail prefix
			echo file_get_contents($tn);
		}
		elseif(!$thumbnail && file_exists(sprintf('./ec/uploads/%s', $raw_not_tn)))
		{
			//try with raw non thumbnail filename
			echo file_get_contents(sprintf('./ec/uploads/%s', $raw_not_tn));
		}
		elseif(file_exists(sprintf('./ec/uploads/%s', $picName)))
		{
			//try with raw filename
			echo file_get_contents(sprintf('./ec/uploads/%s', $picName));
		}
		else
		{
			echo file_get_contents('./images/no_image.png');
		}
	}
	else
	{
		echo file_get_contents('./images/no_image.png');
	}
}

function getXML()
{
	if(array_key_exists('name', $_GET))
	{
		$prj = new EcProject();
		$prj->name = $_GET["name"];
		$prj->fetch();
		//print_r($prj);
		echo $prj->toXML();
			
	}
}

function projectSummary()
{
	global $url;

	$prj = new EcProject();
	$prj->name = substr($url, 0, strpos($url, "/"));
	$prj->fetch();
	$sum = $prj->getSummary();

	echo "{\"forms\" : ". json_encode($sum) . "}";
}

function projectUsage()
{
	global $url, $auth;

	$prj = new EcProject();
	$prj->name = substr($url, 0, strpos($url, "/"));
	$prj->fetch();

	if(!$prj->isPublic && $prj->checkPermission($auth->getEcUserId()) < 2) return "access denied";

	$sum = $prj->getUsage();
	header("Content-type: text/plain");
	echo $sum; //"{\"forms\" : ". json_encode($sum) . "}";
}

function writeSettings()
{
	global $cfg, $SITE_ROOT;
	foreach ($_POST as $k => $v)
	{
		$kp = explode("\\", $k);
		if(count($kp) > 1)
		$cfg->settings[$kp[0]][$kp[1]] = $v;
	}

	if(!array_key_exists("salt",$cfg->settings["security"]) || $cfg->settings["security"]["salt"] == "")
	{
		$str = "./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
		$str = str_shuffle($str);
		$str = substr($str, -22);
		$cfg->settings["security"]["salt"] = $str;
	}
		
	$cfg->writeConfig();
	header("Cache-Control: no-cache, must-revalidate");
	if(getValIfExists($_POST, "edit"))
	{
		header("location: $SITE_ROOT/admin");
	}
	else
	{
		header("location: $SITE_ROOT/test");
	}
}

function packFiles($files)
{
	if(!is_array($files)) throw new Exception("files to be packed must be an array");

	$str = "";

	foreach($files as $k => $f)
	{
		$str .= file_get_contents($f);
		$str .= "\r\n";
	}

	return $str;
}

function listUsers()
{
	global $auth, $url;
	
	if($auth->isLoggedIn())
	{
		if($auth->isServerManager())
		{
			header("Cache-Control: no-cache, must-revalidate");
			header("Content-Type: application/json");
			
			echo "{\"users\":[";
			$usrs = $auth->getUsers();
			for($i = 0; $i < count($usrs); $i++)
			{
				if($i > 0) echo ",";
				echo "{
					\"userId\" : \"{$usrs[$i]["userId"]}\",
					\"firstName\" : \"{$usrs[$i]["FirstName"]}\",
					\"lastName\" : \"{$usrs[$i]["LastName"]}\",
					\"email\" : \"{$usrs[$i]["Email"]}\",
					\"active\" : {$usrs[$i]["active"]}
				}";
			}
			echo "]}";
		}
		else
		{
			echo applyTemplate("./base.html", "./error.html", array("errorType" => 403, "error" => "Permission denied"));
		}
	}
	else
	{
		loginHandler($url);
	}
}

function enableUser()
{
	global $auth;
	
	$user = getValIfExists($_POST, "user");
	
	if($auth->isLoggedIn() && $auth->isServerManager() && $_SERVER["REQUEST_METHOD"] == "POST" && $user)
	{
		header("Cache-Control: no-cache, must-revalidate");
		header("Content-Type: application/json");
		$res = $auth->setEnabled($user, true);
		if($res === true)
		{
			
			echo "{\"result\" : true}";
		}
		else
		{
			echo $res;
			echo "{\"result\" : false}";
		}
	}
	else
	{
		header("HTTP/1.1 403 Access Denied",null,403);	
	}
}

function disableUser()
{
	global $auth;
	
	$user = getValIfExists($_POST, "user");
	
	if($auth->isLoggedIn() && $auth->isServerManager() && $_SERVER["REQUEST_METHOD"] == "POST" && $user)
	{
		header("Cache-Control: no-cache, must-revalidate");
		header("Content-Type: application/json");
		if($auth->setEnabled($user, false))
		{
			
			echo "{\"result\" : true}";
		}
		else
		{
			echo "{\"result\" : false}";
		}
	}
	else
	{
		header("HTTP/1.1 403 Access Denied",null,403);
	}
}

function resetPassword()
{
	global $auth;
	
	$user = getValIfExists($_POST, "user");
	
	if($auth->isLoggedIn() && $auth->isServerManager() && $_SERVER["REQUEST_METHOD"] == "POST" && $user && preg_match("/[0-9]+/", $user))
	{
		$res = $auth->resetPassword($user);
		
		header("Cache-Control: no-cache, must-revalidate");
		header("Content-Type: application/json");
		echo "{\"result\" : \"$res\"}";
		
	}
	else
	{
		header("HTTP/1.1 403 Access Denied",null,403);
	}
}

function userHandler()
{
	global $url;

	//if(!(strstr($_SERVER["HTTP_REFERER"], "/createProject.html"))) return;

	$qry = str_replace("user/", "", $url);

	//$db = new dbConnection();
	global $db;
	$sql = "Select details from user where Email = '$qry'";

	$res = $db->do_query($sql);
	if($res === true)
	{
                $arr = $db->get_row_array();
		if($arr)
		{
			if(array_key_exists("details", $arr))
			{
				echo "true";
				return;
			}
			else
			{
				print_r($arr);
			}
		}
		else
		{
			echo "false";
		}
	}
	else
	{
		die($res + " " + $sql);
	}
}
/* end handlers */

/*
 * The page rules array defines how to handle certain urls, if a page rule
* hasn't been defined then then the script should return a 404 error (this
* is in order to protect files that should not be open to public view such
* as log files which may contain restricted data)
*/

try{
	$hasManagers = $db->connected && count($auth->getServerManagers()) > 0;
}catch (Exception $err)
{
	$hasManagers = false;
}

$pageRules = array(
		
		'markers/point' => new PageRule(null, 'getPointMarker'),
		'markers/cluster' => new PageRule(null, 'getClusterMarker'),
//static file handlers
		'' => new PageRule('index.html', 'siteHome'),
		'index.html?' => new PageRule('index.html', 'siteHome'),
		'privacy.html' => new PageRule('privacy.html', 'defaultHandler'),
		'[a-zA-Z0-1]+\.html' => new PageRule(null, 'defaultHandler'),
		'images/.+' => new PageRule(),
		'favicon\..+' => new PageRule(),
		'js/.+' => new PageRule(),
		'css/.+' => new PageRule(),
                'EpiCollectplus\.apk' => new PageRule(),
		'html/projectIFrame.html' => new PageRule(),

//project handlers
		'pc' => new PageRule(null, 'projectCreator', true),
		'create' => new PageRule(null, 'createFromXml', true),
		'createProject.html' => new PageRule(null, 'createProject', true),
		'projectHome.html' => new PageRule(null, 'projectHome'),
		'createOrEditForm.html' => new PageRule(null ,'formBuilder', true),
		'uploadProject' =>new PageRule(null, 'uploadProjectXML', true),
		'getForm' => new PageRule(null, 'getXML',	 true),
		'validate' => new PageRule(null, 'validate',false),
//'listXML' => new PageRule(null, 'listXML',false),
//login handlers
//'Auth/loginCallback.php' => new PageRule(null,'loginCallbackHandler'),
		'login.php' => new PageRule(null,'loginHandler', false, true),
		'loginCallback' => new PageRule(null,'loginCallback', false, true),
		'logout' => new PageRule(null, 'logoutHandler'),
		'chooseProvider.html' => new PageRule(null, 'chooseProvider'),

//user handlers
		'updateUser.html' => new PageRule(null, 'updateUser', true),
		'saveUser' =>new PageRule(null, 'saveUser', true),
		'user/manager/?' => new PageRule(null, 'managerHandler', true),
		'user/.*@.*?' => new PageRule(null, 'userHandler', true),
		'admin' => new PageRule(null, 'admin', $hasManagers),
		'listUsers' => new PageRule(null, 'listUsers', $hasManagers),
		'disableUser' => new PageRule(null, 'disableUser',true),
		'enableUser' => new PageRule(null, 'enableUser',true),
		'resetPassword' => new PageRule(null, 'resetPassword',true),
                'register' => new PageRule(null, 'createAccount', false),
		
//generic, dynamic handlers
		'getControls' =>  new PageRule(null, 'getControlTypes'),
		'uploadFile.php' => new PageRule(null, 'uploadHandlerFromExt'),
		'ec/uploads/.+\.(jpe?g|mp4)$' => new PageRule(null, 'getMedia'),
		'ec/uploads/.+' => new PageRule(null, null),
	
		'uploadTest.html' => new PageRule(null, 'defaultHandler', true),
		'test' => new PageRule(null, 'siteTest', false),
		'tests.*' => new PageRule(),
		'createDB' => new PageRule(null, 'setupDB',$hasManagers),
		'writeSettings' => new PageRule(null, 'writeSettings', $hasManagers),
		
//to API
		'projects' => new PageRule(null, 'projectList'),
		'[a-zA-Z0-9_-]+(\.xml|\.json|\.tsv|\.csv|/)?' =>new PageRule(null, 'projectHome'),
		'[a-zA-Z0-9_-]+/upload' =>new PageRule(null, 'uploadData'),
		'[a-zA-Z0-9_-]+/download' =>new PageRule(null, 'downloadData'),
		'[a-zA-Z0-9_-]+/summary' =>new PageRule(null, 'projectSummary'),
		'[a-zA-Z0-9_-]+/usage' =>  new PageRule(null, 'projectUsage'),
		'[a-zA-Z0-9_-]+/formBuilder(\.html)?' =>  new PageRule(null, 'formBuilder', true),
		'[a-zA-Z0-9_-]+/editProject.html' =>new PageRule(null, 'editProject', true),
		'[a-zA-Z0-9_-]+/update' =>new PageRule(null, 'updateProject', true),
		'[a-zA-Z0-9_-]+/manage' =>new PageRule(null, 'updateProject', true),
		'[a-zA-Z0-9_-]+/updateStructure' =>new PageRule(null, 'updateXML', true),
		'[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+/__stats' =>new PageRule(null, 'tableStats'),
                '[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+/__activity' =>new PageRule(null, 'formDataLastUpdated'),
		'[a-zA-Z0-9_-]+/uploadMedia' =>new PageRule(null, 'uploadMedia'),
		'[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+/uploadMedia' =>new PageRule(null, 'uploadMedia'),
		'[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+/__getImage' =>new PageRule(null, 'getImage'),
		
		'[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+(\.xml|\.json|\.tsv|\.csv|\.kml|\.js|\.css|/)?' => new PageRule(null, 'formHandler'),

//'[a-zA-Z0-9_-]*/[a-zA-Z0-9_-]*/usage' => new  => new PageRule(null, formUsage),
		'[^/\.]*/[^/\.]+/[^/\.]*(\.xml|\.json|/)?' => new PageRule(null, 'entryHandler')

//forTesting

);

$d = new DateTime();
$i = $dat->format("su") - $d->format("su");

$rule = false;

/*Cookie policy handler*/

if(!getValIfExists($_SESSION, 'SEEN_COOKIE_MSG')) {
	flash(sprintf('EpiCollectPlus only uses first party cookies to make the site work. We do not add or read third-party cookies. If you are concerned about our use of cookies please read our <a href="%s/privacy.html">Privacy Statement</a>', $SITE_ROOT));
	$_SESSION['SEEN_COOKIE_MSG'] = true;
}


if(array_key_exists($url, $pageRules))
{
	$rule = $pageRules[$url];
}
else
{

	foreach(array_keys($pageRules) as $key)
	{
		if(preg_match("/^".regexEscape($key)."$/", $url))
		{
			//echo $key;
			$rule = $pageRules[$key];
			break;
		}
	}
}

if($rule)
{
	if($rule->secure && !getValIfExists($_SERVER, "HTTPS"))
	{
		$https_enabled = false;
		try{
			$https_enabled = file_exists("https://{$_SERVER["HTTP_HOST"]}/{$SITE_ROOT}/{$url}");
		}
		catch(Exception $e)
		{
			$https_enabled = false;
		}
		if($https_enabled)
		{
			header("location: https://{$_SERVER["HTTP_HOST"]}/{$SITE_ROOT}/{$url}");
			die();
		}
	}
	elseif($rule->secure)
	{
		//flash("Warning: this page is not secure as HTTPS is not avaiable", "err");
	}


	if($rule->login && !$auth->isLoggedIn())
	{
		header("Cache-Control: no-cache, must-revalidate");
			
		if(array_key_exists("provider", $_GET))
		{
			$_SESSION["provider"] = $_GET["provider"];
			$auth = new AuthManager();
			$frm = $auth->requestlogin($url, $_GET["provider"]);
		}
		else
		{
			$auth = new AuthManager();
			$frm = $auth->requestlogin($url);
		}
		echo applyTemplate("./base.html", "./loginbase.html", array( "form" => $frm));
		return;
	}
	if($rule->redirect)
	{
		$url = $rule->redirect;
	}
	if($rule->handler)
	{
		$h = $rule->handler;
		//if($h != 'defaultHandler') @session_start();
		$h();
	}
	else
	{
			
		//static files
		header("Content-type: " . mimeType($url));
		header("Cache-Control: public; max-age=100000;");
		echo file_get_contents("./" . $url);
	}
}
else
{

	$parts = explode("/", $url);
	echo applyTemplate("./base.html", "./error.html");
}

$d = new DateTime();
$i = $dat->format("su") - $d->format("su");


?>
