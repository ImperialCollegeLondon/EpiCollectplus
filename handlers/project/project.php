<?php

function projectCreator() {
    if (!file_exists("ec/xml"))
        mkdir("ec/xml");

    if (array_key_exists("xml", $_FILES)) {
        move_uploaded_file($_FILES["xml"]["tmp_name"], "ec/xml/{$_FILES["xml"]["name"]}");
    }
    if (getValIfExists($_REQUEST, "json")) {
        $n = '';
        echo validate("{$_FILES["xml"]["name"]}", NULL, $n, getValIfExists($_POST, 'update'));
    } else {
        $vals = array();
        $vals["xmlFolder"] = getcwd() . "/xml";
        $vals["projects"] = listXML();
        echo applyTemplate("base.html", "create.html", $vals);
    }
}

function projectList() {
    /**
     * Produce a list of all the projects on this server that are
     *    - publically listed
     *  - if a user is logged in, owned, curated or managed by the user
     */
    global $auth;

    $qry = getValIfExists($_GET, "q");
    $lmt = getValIfExists($_GET, "limit", false);

    $prjs = EcProject::getPublicProjects($qry, $lmt);
    $usr_prjs = array();
    if ($auth->isLoggedIn()) {
        $usr_prjs = EcProject::getUserProjects($auth->getEcUserId(), true);
        $up_l = count($usr_prjs);
        for ($p = 0; $p < $up_l; $p++) {
            if ($usr_prjs[$p]["listed"] === 0) {
                array_push($prjs, $usr_prjs[$p]);
            }
        }
    }

    echo json_encode($prjs);
}


function projectHome() {
    global $url, $SITE_ROOT, $auth;

    $eou = strlen($url) - 1;
    if ($url{$eou} == '/') {
        $url{$eou} = '';
    }
    $url = ltrim($url, '/');

    $prj = new EcProject();
    if (array_key_exists('name', $_GET)) {
        $prj->name = $_GET['name'];
    } else {
        $prj->name = preg_replace('/\.(xml|json)$/', '', $url);
    }

    $prj->fetch();

    if (!$prj->id) {
        $vals = array('error' => 'Project could not be found');
        echo applyTemplate('base.html', './404.html', $vals);
        die;
    }


    $loggedIn = $auth->isLoggedIn();
    $role = $prj->checkPermission($auth->getEcUserId());

    if (!$prj->isPublic && !$loggedIn && !preg_match('/\.xml$/', $url)) {
        flash('This is a private project, please log in to view the project.');
        loginHandler($url);
        return;
    } else if (!$prj->isPublic && $role < 2 && !preg_match('/\.xml$/', $url)) {
        flash(sprintf('You do not have permission to view %s.', $prj->name));
        header(sprintf('location: http://%s/%s', $_SERVER['HTTP_HOST'], $SITE_ROOT));
        return;
    }


    //echo strtoupper($_SERVER["REQUEST_METHOD"]);
    $reqType = strtoupper($_SERVER['REQUEST_METHOD']);
    if ($reqType == 'POST') //
    {
        //echo 'POST';
        // update project
        $prj->description = $_POST['description'];
        $prj->image = $_POST['image'];
        $prj->isPublic = array_key_exists('isPublic', $_POST) && $_POST['isPublic'] == 'on' ? 1 : 0;
        $prj->isListed = array_key_exists('isListed', $_POST) && $_POST['isListed'] == 'on' ? 1 : 0;
        $prj->publicSubmission = array_key_exists('publicSubmission', $_POST) && $_POST['publicSubmission'] == 'on' ? 1 : 0;

        $res = $prj->id ? $prj->push() : $prj->post();
        if ($res !== true) {
            echo $res;
        }

        if ($_POST['admins'] && $res === true) {
            $res = $prj->setAdmins($_POST["admins"]);
        }

        if ($_POST['users'] && $res === true) {
            $res = $prj->setUsers($_POST["users"]);
        }

        if ($_POST['submitters'] && $res === true) {
            $res = $prj->setSubmitters($_POST['submitters']);
        }
        echo $res;
    } elseif ($reqType == 'DELETE') {
        if ($role == 3) {
            $res = $prj->deleteProject();
            if ($res === true) {
                header('HTTP/1.1 200 OK', true, 200);
                echo '{ "success": true }';
                return;
            } else {
                header('HTTP/1.1 500 Error', true, 500);
                echo ' {"success" : false, "message" : "Could not delete project" }';
            }
        } else {
            header('HTTP/1.1 403 Forbidden', true, 403);
            echo ' {"success" : false, "message" : "You do not have permission to delete this project" }';
        }

    } elseif ($reqType == 'GET') {

        if (array_key_exists('HTTP_ACCEPT', $_SERVER))
            $format = substr($_SERVER["HTTP_ACCEPT"], strpos($_SERVER["HTTP_ACCEPT"], "$SITE_ROOT/") + 1);
        $ext = substr($url, strrpos($url, '.') + 1);
        $format = $ext != '' ? $ext : $format;
        if ($format == 'xml') {
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: text/xml; charset=utf-8;');
            echo $prj->toXML();
        } elseif ($format == 'json') {
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: application/json; charset=utf-8;');
            echo $prj->toJSON();
        } else {


            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: text/html;');

            try {

                $tblList = '';
                foreach ($prj->tables as $tbl) {
                    $tblList .= "<div class=\"tblDiv\"><a class=\"tblName\" href=\"{$prj->name}/{$tbl->name}\">{$tbl->name}</a><a href=\"{$prj->name}/{$tbl->name}\">View All Data</a> | <form name=\"{$tbl->name}SearchForm\" action=\"./{$prj->name}/{$tbl->name}\" method=\"GET\"> Search for {$tbl->key} <input type=\"text\" name=\"{$tbl->key}\" /> <a href=\"javascript:document.{$tbl->name}SearchForm.submit();\">Search</a></form></div>";
                }

                $imgName = $prj->image;

                $image = '';

                if (file_exists($imgName)) {
                    $image = "<div class='project-thumbnail-big' style='background-image: url(" . $imgName . "');'></div>";
                } else {
                    //show placeholder

                    $imgName = $SITE_ROOT . '/images/project-image-placeholder-100x100.png';

                    $image = "<div class='project-thumbnail-big' style='background-image: url(" . $imgName . "');'></div>";
                }

                $adminMenu = '';
                $curpage = trim($url, '/');
                $curpage = sprintf('http://%s%s/%s', $_SERVER['HTTP_HOST'], $SITE_ROOT, $curpage);

                if ($role == 3) {
                    $adminMenu = "<span class=\"button-set\"><a href=\"{$curpage}/manage\" class=\"button\">Manage Project</a> <a href=\"{$curpage}/formBuilder\" class=\"button\">Create or Edit Forms</a></span>";
                }

                $vals = array(
                    'projectName' => $prj->name,
                    'projectDescription' => preg_replace('/\<\/?(p|h[\dr]|div|section|img)\s?[a-z0-9\=\"\/\~\.\s]*\>/', '', $prj->description),
                    'projectImage' => $image,
                    'tables' => $tblList,
                    'adminMenu' => $adminMenu,
                    'userMenu' => ''
                );



                if($vals['projectDescription'] == '') {
                    $vals['projectDescription'] = 'No project description set yet';
                }

                echo applyTemplate('base.html', 'projectHome.html', $vals);
                return;
            } catch (Exception $e) {

                $vals = array('error' => $e->getMessage());
                echo applyTemplate('base.html', 'error.html', $vals);
            }
        }
    }
}

function createProject() {
    global $url;

    header("Cache-Control: no-cache, must-revalidate");

    $vals = array();

    echo applyTemplate("./base.html", "./createProject.html", $vals);

}

function updateProject() {
    global $url, $auth, $db;

    $pNameEnd = strrpos($url, "/");
    $oldName = substr($url, 0, $pNameEnd);
    $prj = new EcProject();
    $prj->name = $oldName;
    $prj->fetch();

    $role = intVal($prj->checkPermission($auth->getEcUserId()));

    if ($role != 3) {

        header("Cache-Control: no-cache; must-revalidate;");
        flash("You do not have permission to manage this project", "err");
        $url = str_replace("update", "", $url);
        header("location: {$SITE_ROOT}/$url");
    } else {
        header("Cache-Control: no-cache; must-revalidate;");
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $xml = getValIfExists($_POST, "xml");
            $managers = getValIfExists($_POST, "managers");
            $curators = getValIfExists($_POST, "curators");
            $public = getValIfExists($_POST, "public");
            $listed = getValIfExists($_POST, "listed");


            $drty = false;
            if ($xml && $xml != "") {
                $prj->parse($xml);
                if ($prj->name != $oldName) {
                    header("HTTP/1.1 400 CANNOT CHANGE NAME", 400);
                    return false;
                }
                $drty = true;
            }

            echo 'description ' . $prj->description . ' ' . getValIfExists($_POST, "description");
            if ($prj->description != getValIfExists($_POST, "description")) {

                $prj->description = getValIfExists($_POST, "description");
                $drty = true;
            }
            if ($prj->image != getValIfExists($_POST, "projectImage")) {
                $prj->image = getValIfExists($_POST, "projectImage");
                $drty = true;
            }

            if ($public !== false) {
                $prj->isPublic = $public === "true";
                $drty = true;
            }
            if ($listed !== false) {
                $prj->isListed = $listed === "true";
                $drty = true;
            }
            if ($drty) {
                $prj->publicSubmission = true;
                $prj->put($oldName);
            }
            if ($curators)
                $prj->setCurators($curators);
            if ($managers)
                $prj->setManagers($managers);

        } else {
            $managers = $prj->getManagers();
            if (is_array($managers)) {
                $managers = '"' . implode(",", $managers) . '"';
            } else {
                $curators = '""';
            }

            $curators = $prj->getCurators();
            if (is_array($curators)) {
                $curators = '"' . implode(",", $curators) . '"';
            } else {
                $curators = '""';
            }

            $img = $prj->image;
            $img = substr($img, strpos($img, '~') + 1);

            echo applyTemplate("./base.html", "./updateProject.html", array("projectName" => $prj->name, "description" => $prj->description, "image" => $img, "managers" => $managers, "curators" => $curators, "public" => $prj->isPublic, "listed" => $prj->isListed));
            return;
        }
    }
}

function projectSummary() {
    global $url;

    $prj = new EcProject();
    $prj->name = substr($url, 0, strpos($url, "/"));
    $prj->fetch();
    $sum = $prj->getSummary();

    echo "{\"forms\" : " . json_encode($sum) . "}";
}

function projectUsage() {
    global $url, $auth;

    $prj = new EcProject();
    $prj->name = substr($url, 0, strpos($url, "/"));
    $prj->fetch();

    if (!$prj->isPublic && $prj->checkPermission($auth->getEcUserId()) < 2)
        return "access denied";

    $sum = $prj->getUsage();
    header("Content-type: text/plain");
    echo $sum; //"{\"forms\" : ". json_encode($sum) . "}";
}