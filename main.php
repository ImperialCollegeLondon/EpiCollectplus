<?php

//phpinfo();
//exit();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', -1);

if (isset($_REQUEST['_SESSION']))
    throw new Exception('Bad client request');

date_default_timezone_set('UTC');
$dat = new DateTime('now');
//$dfmat = '%s.u';

$SITE_ROOT = '';
$PUBLIC = false;
$XML_VERSION = 1.0;
$CODE_VERSION = "1.6g";
$BUILD = "26";

if (!isset($PHP_UNIT)) {
    $PHP_UNIT = false;
}
if (!$PHP_UNIT) {
    @session_start();
}
$DIR = str_replace('main.php', '', $_SERVER['SCRIPT_FILENAME']);

if ($PHP_UNIT) {
    $DIR = getcwd();
}

if (strpos($_SERVER['SCRIPT_NAME'], 'main.php')) {
    //IIS
    $SITE_ROOT = str_replace('/main.php', '', $_SERVER['PHP_SELF']);
} else {
    //Apache
    $SITE_ROOT = str_replace(array($_SERVER['DOCUMENT_ROOT'], '/main.php'), '', $_SERVER['SCRIPT_FILENAME']);
}

include(sprintf('%s/handlers/helpers.php', $DIR));
include(sprintf('%s/handlers/validate.php', $DIR));
include(sprintf('%s/handlers/entryHandler.php', $DIR));
include(sprintf('%s/handlers/formHandler.php', $DIR));
include(sprintf('%s/handlers/dataHandler.php', $DIR));
include(sprintf('%s/handlers/media.php', $DIR));
include(sprintf('%s/handlers/ui/applyTemplate.php', $DIR));
include(sprintf('%s/handlers/ui/siteHome.php', $DIR));
include(sprintf('%s/handlers/ui/listMyProjects.php', $DIR));
include(sprintf('%s/handlers/login/login.php', $DIR));
include(sprintf('%s/handlers/project/project.php', $DIR));
include(sprintf('%s/handlers/project/uploadProjectXML.php', $DIR));
include(sprintf('%s/handlers/project/handleXML.php', $DIR));
include(sprintf('%s/handlers/config/sitetest.php', $DIR));
include(sprintf('%s/handlers/map/markers.php', $DIR));
include(sprintf('%s/handlers/user/updateUser.php', $DIR));
include(sprintf('%s/handlers/user/createAccount.php', $DIR));
include(sprintf('%s/handlers/user/createUser.php', $DIR));
include(sprintf('%s/handlers/user/saveUser.php', $DIR));
include(sprintf('%s/handlers/user/userHandler.php', $DIR));
include(sprintf('%s/handlers/user/admin.php', $DIR));
include(sprintf('%s/handlers/user/listUsers.php', $DIR));
include(sprintf('%s/handlers/user/toggleUser.php', $DIR));
include(sprintf('%s/handlers/user/managerHandler.php', $DIR));
include(sprintf('%s/handlers/user/resetPassword.php', $DIR));
include(sprintf('%s/handlers/HttpUtils.php', $DIR));
include(sprintf('%s/Auth/AuthManager.php', $DIR));
include(sprintf('%s/db/dbConnection.php', $DIR));
include(sprintf('%s/handlers/Encoding.php', $DIR));
include(sprintf('%s/Classes/PageRule.php', $DIR));
include(sprintf('%s/Classes/configManager.php', $DIR));
include(sprintf('%s/Classes/Logger.php', $DIR));
include(sprintf('%s/Classes/EcProject.php', $DIR));
include(sprintf('%s/Classes/EcTable.php', $DIR));
include(sprintf('%s/Classes/EcField.php', $DIR));
include(sprintf('%s/Classes/EcOption.php', $DIR));
include(sprintf('%s/Classes/EcEntry.php', $DIR));


$url = (array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : $_SERVER["HTTP_X_ORIGINAL_URL"]); //strip off site root and GET query
if ($SITE_ROOT != '')
    $url = str_replace($SITE_ROOT, '', $url);
if (strpos($url, '?'))
    $url = substr($url, 0, strpos($url, '?'));
$url = trim($url, '/');
$url = urldecode($url);


set_error_handler('handleError', E_ALL);
openCfg();
$DEFAULT_OUT = $cfg->settings['misc']['default_out'];
$log = new Logger('Ec2');
global $db, $auth;
$db = false;
$auth = new AuthManager();
$db = new dbConnection();


/*
 * The page rules array defines how to handle certain urls, if a page rule
* hasn't been defined then then the script should return a 404 error (this
* is in order to protect files that should not be open to public view such
* as log files which may contain restricted data)
*/

try {
    $hasManagers = $db->connected && count($auth->getServerManagers()) > 0;
} catch (Exception $err) {
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
    'api' => new PageRule('apidocs.html', 'defaultHandler'),

    //project handlers
    'pc' => new PageRule(null, 'projectCreator', true),
    'create' => new PageRule(null, 'createFromXml', true),
    'createProject.html' => new PageRule(null, 'createProject', true),
    'projectHome.html' => new PageRule(null, 'projectHome'),
    'createOrEditForm.html' => new PageRule(null, 'formBuilder', true),
    'uploadProject' => new PageRule(null, 'uploadProjectXML', true),
    'getForm' => new PageRule(null, 'getXML', true),
    'validate' => new PageRule(null, 'validate', false),
    //'listXML' => new PageRule(null, 'listXML',false),
    //login handlers
    //'Auth/loginCallback.php' => new PageRule(null,'loginCallbackHandler'),
    'login.php' => new PageRule(null, 'loginHandler', false, true),
    'loginCallback' => new PageRule(null, 'loginCallback', false, true),
    'logout' => new PageRule(null, 'logoutHandler'),
    'chooseProvider.html' => new PageRule(null, 'chooseProvider'),

    //user projects
    'my-projects.html' => new PageRule(null, 'listMyProjects', true),

    //user handlers
    'updateUser.html' => new PageRule(null, 'updateUser', true),
    'saveUser' => new PageRule(null, 'saveUser', true),
    'user/manager/?' => new PageRule(null, 'managerHandler', true),
    'user/.*@.*?' => new PageRule(null, 'userHandler', true),
    'admin' => new PageRule(null, 'admin', $hasManagers),
    'listUsers' => new PageRule(null, 'listUsers', $hasManagers),
    'disableUser' => new PageRule(null, 'disableUser', true),
    'enableUser' => new PageRule(null, 'enableUser', true),
    'resetPassword' => new PageRule(null, 'resetPassword', true),
    'register' => new PageRule(null, 'createAccount', false),

    //generic, dynamic handlers
    'getControls' => new PageRule(null, 'getControlTypes'),
    'uploadFile.php' => new PageRule(null, 'uploadHandlerFromExt'),
    'ec/uploads/.+\.(jpe?g|mp4)$' => new PageRule(null, 'getMedia'),
    'ec/uploads/.+' => new PageRule(null, 'getUpload'),

    'uploadTest.html' => new PageRule(null, 'defaultHandler', true),
    'test' => new PageRule(null, 'siteTest', false),
    'tests.*' => new PageRule(),
    'createDB' => new PageRule(null, 'setupDB', $hasManagers),
    'writeSettings' => new PageRule(null, 'writeSettings', $hasManagers),

    //to API
    'projects' => new PageRule(null, 'projectList'),
    '[a-zA-Z0-9_-]+(\.xml|\.json|\.tsv|\.csv|/)?' => new PageRule(null, 'projectHome'),
    '[a-zA-Z0-9_-]+/upload' => new PageRule(null, 'uploadData'),
    '[a-zA-Z0-9_-]+/download' => new PageRule(null, 'downloadData'),
    '[a-zA-Z0-9_-]+/summary' => new PageRule(null, 'projectSummary'),
    '[a-zA-Z0-9_-]+/usage' => new PageRule(null, 'projectUsage'),
    '[a-zA-Z0-9_-]+/formBuilder(\.html)?' => new PageRule(null, 'formBuilder', true),
    '[a-zA-Z0-9_-]+/editProject.html' => new PageRule(null, 'editProject', true),
    '[a-zA-Z0-9_-]+/update' => new PageRule(null, 'updateProject', true),
    '[a-zA-Z0-9_-]+/manage' => new PageRule(null, 'updateProject', true),
    '[a-zA-Z0-9_-]+/updateStructure' => new PageRule(null, 'updateXML', true),
    '[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+/__stats' => new PageRule(null, 'tableStats'),
    '[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+/__activity' => new PageRule(null, 'formDataLastUpdated'),
    '[a-zA-Z0-9_-]+/uploadMedia' => new PageRule(null, 'uploadMedia'),
    '[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+/uploadMedia' => new PageRule(null, 'uploadMedia'),
    '[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+/__getImage' => new PageRule(null, 'getImage'),

    '[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+(\.xml|\.json|\.tsv|\.csv|\.kml|\.js|\.css|/)?' => new PageRule(null, 'formHandler'),

    //'[a-zA-Z0-9_-]*/[a-zA-Z0-9_-]*/usage' => new  => new PageRule(null, formUsage),
    '[^/\.]*/[^/\.]+/[^/\.]*(\.xml|\.json|/)?' => new PageRule(null, 'entryHandler')

);

$d = new DateTime();
$i = $dat->format("su") - $d->format("su");

$rule = false;

/*Cookie policy handler*/

if (!getValIfExists($_SESSION, 'SEEN_COOKIE_MSG')) {
    flash(sprintf('EpiCollectPlus only uses first party cookies to make the site work. We do not add or read third-party cookies. If you are concerned about our use of cookies please read our <a href="%s/privacy.html">Privacy Statement</a>', $SITE_ROOT));
    $_SESSION['SEEN_COOKIE_MSG'] = true;
}


if (array_key_exists($url, $pageRules)) {
    $rule = $pageRules[$url];
} else {

    foreach (array_keys($pageRules) as $key) {
        if (preg_match("/^" . regexEscape($key) . "$/", $url)) {
            //echo $key;
            $rule = $pageRules[$key];
            break;
        }
    }
}

if ($rule) {
    if ($rule->secure && !getValIfExists($_SERVER, "HTTPS")) {
        $https_enabled = false;
        try {
            $https_enabled = file_exists("https://{$_SERVER["HTTP_HOST"]}/{$SITE_ROOT}/{$url}");
        } catch (Exception $e) {
            $https_enabled = false;
        }
        if ($https_enabled) {
            header("location: https://{$_SERVER["HTTP_HOST"]}/{$SITE_ROOT}/{$url}");
            die();
        }
    } elseif ($rule->secure) {
        //flash("Warning: this page is not secure as HTTPS is not avaiable", "err");
    }


    if ($rule->login && !$auth->isLoggedIn()) {
        header("Cache-Control: no-cache, must-revalidate");

        if (array_key_exists("provider", $_GET)) {
            $_SESSION["provider"] = $_GET["provider"];
            $auth = new AuthManager();
            $frm = $auth->requestlogin($url, $_GET["provider"]);
        } else {
            $auth = new AuthManager();
            $frm = $auth->requestlogin($url);
        }
        echo applyTemplate("./base.html", "./loginbase.html", array("form" => $frm));
        return;
    }
    if ($rule->redirect) {
        $url = $rule->redirect;
    }
    if ($rule->handler) {
        $h = $rule->handler;
        //if($h != 'defaultHandler') @session_start();

        //execute handler for page requested
        $h();
    } else {

        //static files
        header("Content-type: " . mimeType($url));
        header("Cache-Control: public; max-age=100000;");
        echo file_get_contents("./" . $url);
    }
} else {

    $parts = explode("/", $url);
    echo applyTemplate("./base.html", "./error.html");
}

$d = new DateTime();
$i = $dat->format("su") - $d->format("su");


?>
