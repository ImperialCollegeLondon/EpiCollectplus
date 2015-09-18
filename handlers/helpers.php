<?php
//probably the most useless function ever
function getValIfExists($array, $key, $default = null) {
    if (array_key_exists($key, $array)) {
        return $array[$key];
    } else {
        return $default;
    }
}

function escape_xml($str) {
    return str_replace('>', '&gt;', str_replace('<', '&lt;', str_replace('&', '&amp;', $str)));
}

function openCfg() {
    global $cfg, $DIR, $PUBLIC;

    //OPEN CONFIGURATION OPTIONS
    $cfg_fn = sprintf('%s/ec/epicollect.ini', rtrim($DIR, '/'));

    if (!file_exists($cfg_fn)) {
        makeCfg();
    }
    makedirs();
    try {
        $cfg = new ConfigManager($cfg_fn);

        if ($cfg->settings['security']['use_ldap'] && !function_exists('ldap_connect')) {
            $cfg->settings['security']['use_ldap'] = false;
            $cfg->writeConfig();
        }

        if (!array_key_exists('salt', $cfg->settings['security']) || trim($cfg->settings['security']['salt']) == '') {
            $str = genStr();
            $cfg->settings['security']['salt'] = $str;
            $cfg->writeConfig();
        }


        $PUBLIC = $cfg->settings['misc']['public_server'];

    } catch (Exception $err) {
        die ('could not load configuration');
    }
}

function makeCfg() {
    global $DIR;

    $cfg_tpl_fn = sprintf('%s/ec/epicollect-blank.ini', rtrim($DIR, '/'));
    $cfg_fn = sprintf('%s/ec/epicollect.ini', rtrim($DIR, '/'));

    copy($cfg_tpl_fn, $cfg_fn);
    makedirs();

}

function makedirs() {
    global $DIR;

    $updir = sprintf('%s/ec/uploads', rtrim($DIR, '/'));
    if (!file_exists($updir))
        mkdir($updir);
}

function genStr() {
    $source_str = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $rand_str = str_shuffle($source_str);
    $str = substr($rand_str, -22);

    unset($source_str, $rand_str);

    return $str;
}

function makeUrl($fn) {
    global $SITE_ROOT;
    $root = trim($SITE_ROOT, '/');
    if ($root !== '') {
        return sprintf('http://%s/%s/ec/uploads/%s', $_SERVER['HTTP_HOST'], $root, $fn);
    } else {
        return sprintf('http://%s/ec/uploads/%s', $_SERVER['HTTP_HOST'], $fn);
    }
}

function handleError($errno, $errstr, $errfile, $errline, array $errcontext) {

    global $SITE_ROOT;
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {

        //experimental!
        //clear cache
        header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        //reload home page
        header("location: http://{$_SERVER["HTTP_HOST"]}{$SITE_ROOT}/login.php");

        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function escapeTSV($string) {
    $string = str_replace("\n", "\\n", $string);
    $string = str_replace("\r", "\\r", $string);
    $string = str_replace("\t", "\\t", $string);
    return $string;
}

function flash($msg, $type = "msg") {
    if (!array_key_exists("flashes", $_SESSION) || !is_array($_SESSION["flashes"])) {
        $_SESSION["flashes"] = array();
    }
    $nflash = array("msg" => $msg, "type" => $type);

    foreach ($_SESSION["flashes"] as $flash) {
        if ($flash == $nflash)
            return;
    }
    array_push($_SESSION["flashes"], $nflash);

}
function redirectTo($url) {
    global $SITE_ROOT;
    $server = $_SERVER['HTTP_HOST'];
    $root = trim($SITE_ROOT, '/');
    header(sprintf('location: http://%s%s/', $server, $root != '' ? ('/' . $root) : ''));
}

function accessDenied($location) {
    flash(sprintf('You do not have access to %s', $location));
    echo redirectTo("");
}

function setupDB() {
    global $cfg, $auth, $SITE_ROOT;

    try {
        $db = new dbConnection($_POST["un"], $_POST["pwd"]);

    } catch (Exception $e) {
        $_GET["redir"] = "pwd";
        siteTest();
        return;
    }
    if (!$db) {
        echo "DB not connected";
        return;
    }

    $sql = file_get_contents("./db/epicollect2.sql");

    $qrys = explode("~", $sql);

    for ($i = 0; $i < count($qrys); $i++) {
        if ($qrys[$i] != "") {

            $res = $db->do_multi_query($qrys[$i]);
            if ($res !== true && !preg_match("/already exists|Duplicate entry .* for key/", $res)) {
                siteHome();
                return;
            }
        }
    }

    flash('Please sign in to register as the first administartor of this server.');
    header(sprintf('location: http://%s%s/login.php', $_SERVER['HTTP_HOST'], $SITE_ROOT));
    return;
}

function assocToDelimStr($arr, $delim) {
    $str = implode($delim, array_keys($arr[0])) . "\r\n";
    for ($i = 0; $i < count($arr); $i++) {
        $str .= implode($delim, array_values($arr[$i])) . "\r\n";
    }
    return $str;
}

function getTimestamp($fmt = false) {
    $date = new DateTime("now", new DateTimeZone("UTC"));
    if (!$fmt)
        return $date->getTimestamp() * 1000;
    else return $date->format($fmt);
}

function regexEscape($s) {
    $s = str_replace("/", "\/", $s);
    return $s;
}
function mimeType($f) {
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
    $ext = substr($f, strrpos($f, '.') + 1);
    if (array_key_exists($ext, $mimeTypes)) {
        return $mimeTypes[$ext];
    } else {
        return 'text/plain';
    }
}
function defaultHandler() {
    global $url;
    header(sprintf('Content-type: $s', mimeType($url)));
    echo applyTemplate('base.html', "./" . $url);
}
function uploadHandlerFromExt() {
    global $log;
    //$flog = fopen('fileUploadLog.log', 'w');
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (count($_FILES) > 0) {
            $keys = array_keys($_FILES);
            foreach ($keys as $key) {
                if ($_FILES[$key]['error'] > 0) {
                    //fwrite($flog, $key . " error : " .$_FILES[$key]['error']);
                    $log->write("error", $key . " error : " . $_FILES[$key]['error']);
                } else {
                    if (preg_match("/.(png|gif|rtf|docx?|pdf|jpg|jpeg|txt|avi|mpeg|mpg|mov|mp3|wav)$/i", $_FILES[$key]['name'])) {
                        if (!file_exists("ec/uploads/"))
                            mkdir("ec/uploads/");
                        move_uploaded_file($_FILES[$key]['tmp_name'], "ec/uploads/{$_FILES[$key]['name']}");
                        echo "{\"success\" : true , \"msg\":\"ec/uploads/{$_FILES[$key]['name']}\"}";
                    } else {
                        echo " error : file type not allowed";

                    }
                }
            }
        } else {
            echo "No file submitted";
        }
    } else {
        echo "Incorrect method";
    }
    fclose($flog);
}

function formBuilder() {
    global $url, $auth;
    $prj_name = str_replace('/formBuilder', '', $url);

    $prj = new EcProject();
    $prj->name = $prj_name;
    $prj->fetch();

    $uid = $auth->getEcUserId();

    if ($prj->checkPermission($uid)) {
        echo applyTemplate('./base.html', './createOrEditForm.html', array('projectName' => $prj_name));
    } else {
        accessDenied(sprintf(' Project %s', $prj_name));
    }
}

function getControlTypes() {
    global $db;
    //$db = new dbConnection();
    $res = $db->do_query('SELECT * FROM FieldType');

    if ($res === true) {
        $arr = array();
        while ($a = $db->get_row_array()) {
            array_push($arr, $a);
        }

        header("Content-type: application/json");
        echo json_encode(array("controlTypes" => $arr));
    }
}



function writeSettings() {
    global $cfg, $SITE_ROOT;
    foreach ($_POST as $k => $v) {
        $kp = explode("\\", $k);
        if (count($kp) > 1)
            $cfg->settings[$kp[0]][$kp[1]] = $v;
    }

    if (!array_key_exists("salt", $cfg->settings["security"]) || $cfg->settings["security"]["salt"] == "") {
        $str = "./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        $str = str_shuffle($str);
        $str = substr($str, -22);
        $cfg->settings["security"]["salt"] = $str;
    }

    $cfg->writeConfig();
    header("Cache-Control: no-cache, must-revalidate");
    if (getValIfExists($_POST, "edit")) {
        header("location: $SITE_ROOT/admin");
    } else {
        header("location: $SITE_ROOT/test");
    }
}

function packFiles($files) {
    if (!is_array($files))
        throw new Exception("files to be packed must be an array");

    $str = "";

    foreach ($files as $k => $f) {
        $str .= file_get_contents($f);
        $str .= "\r\n";
    }

    return $str;
}