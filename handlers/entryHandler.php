<?php

function entryHandler() {
    global $auth, $url, $log, $SITE_ROOT;

    header("Cache-Control: no-cache, must-revalidate");

    $prjEnd = strpos($url, "/");
    $frmEnd = strpos($url, "/", $prjEnd + 1);
    $prjName = substr($url, 0, $prjEnd);
    $frmName = substr($url, $prjEnd + 1, $frmEnd - $prjEnd - 1);
    $entId = urldecode(substr($url, $frmEnd + 1));

    $prj = new EcProject();
    $prj->name = $prjName;
    $prj->fetch();

    $permissionLevel = 0;
    $loggedIn = $auth->isLoggedIn();

    if ($loggedIn)
        $permissionLevel = $prj->checkPermission($auth->getEcUserId());

    $ent = new EcEntry($prj->tables[$frmName]);
    $ent->key = $entId;
    $r = $ent->fetch();


    if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
        if ($permissionLevel < 2) {
            flash('You do not have permission to delete entries on this project');
            header('HTTP/1.1 403 Forbidden', 403);
            return;
        }

        if ($r === true) {
            try {
                $ent->delete();
            } catch (Exception $e) {
                if (preg_match("/^Message\s?:/", $e->getMessage())) {
                    header("HTTP/1.1 409 Conflict", 409);
                } else {
                    header("HTTP/1.1 500 Internal Server Error", 500);
                }
                echo $e->getMessage();
            }
        } else {
            echo $r;
        }
    } else if ($_SERVER["REQUEST_METHOD"] == "PUT") {
        if ($permissionLevel < 2) {
            flash('You do not have permission to edit entries on this project');
            header('HTTP/1.1 403 Forbidden', 403);
            return;
        }

        if ($r === true) {
            $request_vars = array();
            parse_str(file_get_contents("php://input"), $request_vars);

            foreach ($request_vars as $key => $value) {
                if (array_key_exists($key, $prj->tables[$frmName]->fields)) {
                    $ent->values[$key] = $value;
                }
            }

            $r = $ent->put();
            if ($r !== true) {
                echo "{ \"false\" : true, \"msg\" : \"$r\"}";
            } else {
                echo "{ \"success\" : true, \"msg\" : \"\"}";
            }
        } else {
            echo "{ \"success\" : false, \"msg\" : \"$r\"";
        }
    } else if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $val = getValIfExists($_GET, 'term');
        $do = getValIfExists($_GET, 'validate');
        $key_from = getValIfExists($_GET, 'key_from');
        $secondary_field = getValIfExists($_GET, 'secondary_field');
        $secondary_value = getValIfExists($_GET, 'secondary_value');
        ini_set('max_execution_time', 60);
        if ($do) {
            echo json_encode($prj->tables[$frmName]->validateTitle($entId, $val));
        } else {
            echo $prj->tables[$frmName]->autoComplete($entId, $val);
        }
    }
}