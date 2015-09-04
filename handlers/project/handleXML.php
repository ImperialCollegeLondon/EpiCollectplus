<?php

function getXML() {
    if (array_key_exists('name', $_GET)) {
        $prj = new EcProject();
        $prj->name = $_GET["name"];
        $prj->fetch();
        //print_r($prj);
        echo $prj->toXML();

    }
}

function createFromXml() {
    global $url, $SITE_ROOT, $server, $root;

    $prj = new EcProject();

    if (array_key_exists("xml", $_REQUEST) && $_REQUEST["xml"] != "") {
        $xmlFn = "ec/xml/{$_REQUEST["xml"]}";

        $prj->parse(file_get_contents($xmlFn));
    } elseif (array_key_exists("name", $_POST)) {
        $prj->name = $_POST["name"];
        $prj->submission_id = strtolower($prj->name);
    } elseif (array_key_exists("raw_xml", $_POST)) {
        $prj->parse($_POST["raw_xml"]);
    }

    if (!$prj->name || $prj->name == "") {
        flash("No project name provided");
        header("location: http://$server/$root/createProject.html");
    }

    $prj->isListed = $_REQUEST["listed"] == "true";
    $prj->isPublic = $_REQUEST["public"] == "true";
    $prj->publicSubmission = true;
    $res = $prj->post();
    if ($res !== true)
        die($res);

    $res = $prj->setManagers($_POST["managers"]);
    if ($res !== true)
        die($res);
    $res = $prj->setCurators($_POST["curators"]);
    if ($res !== true)
        die($res);
    // TODO : add submitter $prj->setProjectPermissions($submitters,1);

    if ($res === true) {
        $server = trim($_SERVER["HTTP_HOST"], "/");
        $root = trim($SITE_ROOT, "/");
        header("location: http://$server/$root/" . preg_replace("/create.*$/", $prj->name, $url));
    } else {
        $vals = array("error" => $res);
        echo applyTemplate("base.html", "error.html", $vals);
    }
}

function updateXML() {
    global $url, $SITE_ROOT;

    $xml = '';
    if (array_key_exists("xml", $_REQUEST) && trim($_REQUEST['xml']) != '') {
        $xml = file_get_contents("ec/xml/{$_REQUEST["xml"]}");
    } elseif (array_key_exists("data", $_POST) && $_POST["data"] != '') {
        $xml = $_POST["data"];
    } else {
        $xml = false;
    }

    $prj = new EcProject();
    $prj->name = substr($url, 0, strpos($url, "/"));
    $prj->fetch();

    //echo '--', $xml , '--';
    if ($xml) {
        $n = '';
        $validation = validate(NULL, $xml, $n, true, true);
        if ($validation !== true) {
            echo "{ \"result\": false , \"message\" : \"" . $validation . "\" }";
            return;
        }
        unset($validation);

        foreach ($prj->tables as $name => $tbl) {
            foreach ($prj->tables[$name]->fields as $fldname => $fld) {
                $prj->tables[$name]->fields[$fldname]->active = false;
            }
        }
        try {
            $prj->parse($xml);

        } catch (Exception $err) {
            echo "{ \"result\": false , \"message\" : \"" . $err->getMessage() . "\" }";
            return;
        }

        $prj->publicSubmission = true;
    }

    if (!getValIfExists($_POST, "skipdesc")) {
        $prj->description = getValIfExists($_POST, "description");
        $prj->image = getValIfExists($_POST, "projectImage");
    }

    if (array_key_exists("listed", $_REQUEST))
        $prj->isListed = $_REQUEST["listed"] == "true";
    if (array_key_exists("public", $_REQUEST))
        $prj->isPublic = $_REQUEST["public"] == "true";
    $res = $prj->put($prj->name);
    if ($res !== true)
        die($res);
    if (array_key_exists("managers", $_POST))
        $prj->setManagers($_POST["managers"]);
    if (array_key_exists("curators", $_POST))
        $prj->setCurators($_POST["curators"]);
    // TODO : add submitter $prj->setProjectPermissions($submitters,1);

    if ($res === true) {
        $server = trim($_SERVER["HTTP_HOST"], "/");
        $root = trim($SITE_ROOT, "/");
        //header ("location: http://$server/$root/" . preg_replace("/updateStructure.*$/", $prj->name, $url));
        echo "{ \"result\": true }";
    } else {
        echo "{ \"result\": false , \"message\" : \"$res\" }";
    }
}

function listXml() {
    //List XML files
    if (!file_exists("ec/xml"))
        mkdir("ec/xml");
    $h = opendir("ec/xml");
    $tbl = "<table id=\"projectTable\"><tr><th>File</th><th>Validation Result</th><th>Create</th><td>&nbsp;</td></tr>";
    $n = "";
    while ($fn = readdir($h)) {
        if (!preg_match("/^\.|.*\.xsd$/", $fn)) {
            $e = false;
            $v = validate($fn, NULL, $n);
            if ($v === true) {
                $p = new EcProject;
                $p->name = $n;
                $res = $p->fetch();
                if ($res !== true)
                    echo $res;
                $e = count($p->tables) > 0;
            }

            $tbl .= "<tr id=\"{$n}row\"><td>$fn</td><td>" . ($v === true ? "$n - <span class=\"success\" >Valid</span>" : "$n - <span class=\"failure\" >Invalid</span> <a href=\"javascript:expand('{$n}res', '{$n}row')\">Show errors</a><div id=\"{$n}res\" class=\"verrors\">$v</div>") . ($e === true ? "</td><td>Project already exists : <a class=\"button\" href=\"$n\">homepage</a></td><td>&nbsp;</td></tr>" : ($v === true ? "</td><td><a class=\"button\" href=\"create?xml=$fn\">Create Project</a></td><td>&nbsp;</td></tr>" : "</td><td></td><td>&nbsp;</td></tr>"));
        }
    }
    $tbl .= "</table>";
    return $tbl;
    //DONE!: for each get the project name and work out if the project exists.
}