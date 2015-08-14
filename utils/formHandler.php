<?php

function formHandler() {
    global $url, $log, $auth;

    $http_accept = getValIfExists($_SERVER, 'HTTP_ACCEPT');
    $format = ($http_accept ? substr($http_accept, strpos($http_accept, '/') + 1) : '');
    $ext = substr($url, strrpos($url, ".") + 1);
    $format = $ext != "" ? $ext : $format;

    $prj = new EcProject();
    $pNameEnd = strpos($url, "/");

    $prj->name = substr($url, 0, $pNameEnd);
    $prj->fetch();

    if (!$prj->id) {
        echo applyTemplate("./base.html", "./error.html", array("errorType" => "404 ", "error" => "The project {$prj->name} does not exist on this server"));
        return;
    }

    $permissionLevel = 0;
    $loggedIn = $auth->isLoggedIn();

    if ($loggedIn)
        $permissionLevel = $prj->checkPermission($auth->getEcUserId());

    if (!$prj->isPublic && !$loggedIn) {
        loginHandler($url);
        return;
    } else if (!$prj->isPublic && $permissionLevel < 2) {
        echo applyTemplate("./base.html", "./error.html", array("errorType" => "403 ", "error" => "You do not have permission to view this project"));
        return;
    }

    $extStart = strpos($url, ".");
    $frmName = rtrim(substr($url, $pNameEnd + 1, ($extStart > 0 ? $extStart : strlen($url)) - $pNameEnd - 1), "/");

    if (!array_key_exists($frmName, $prj->tables)) {
        echo applyTemplate("./base.html", "./error.html", array("errorType" => "404 ", "error" => "The project {$prj->name} does not contain the form $frmName"));
        return;
    }

    if ($_SERVER["REQUEST_METHOD"] == 'POST') {

        $log->write("debug", json_encode($_POST));
        header("Cache-Control: no-cache, must-revalidate");


        $_f = getValIfExists($_FILES, "upload");

        if ($_f) {
            if ($_f['tmp_name'] == '') {
                flash('The file is too big to upload', 'err');
            } else {
                try {
                    ini_set('max_execution_time', 200);
                    ini_set("auto_detect_line_endings", true);
                    if (preg_match("/\.csv$/", $_f["name"])) {
                        $fh = fopen($_f["tmp_name"], 'r');

                        $res = $prj->tables[$frmName]->parseEntriesCSV($fh);

                        fclose($fh);
                        unset ($fh);
                    } elseif (preg_match("/\.xml$/", $_f["name"])) {
                        $res = $prj->tables[$frmName]->parseEntries(simplexml_load_string(file_get_contents($_f["tmp_name"])));
                    }
                    //echo "{\"success\":" . ($res === true ? "true": "false") .  ", \"msg\":\"" . ($res==="true" ? "success" : $res) . "\"}";
                    flash("Upload Complete");
                } catch (Exception $ex) {
                    flash($ex->getMessage(), 'err');
                }
            }
        } else {
            $ent = $prj->tables[$frmName]->createEntry();

            $ent->created = $_POST["created"];
            $ent->deviceId = $_POST["DeviceID"];
            $ent->uploaded = getTimestamp('Y-m-d H:i:s');
            $ent->user = 0;

            foreach (array_keys($ent->values) as $key) {
                if (!$prj->tables[$frmName]->fields[$key]->active)
                    continue;
                if (array_key_exists($key, $_POST)) {
                    $ent->values[$key] = $_POST[$key];
                } elseif (!$prj->tables[$frmName]->fields[$key]->required && !$prj->tables[$frmName]->fields[$key]->key) {
                    $ent->values[$key] = "";
                } else {
                    header("HTTP/1.1 405 Bad Request");
                    echo "{\"success\":false, \"msg\":\"$key is a required field\"}";
                    return;
                }
            }

            try {
                $res = $ent->post();
                echo "{\"success\":" . ($res === true ? "true" : "false") . ", \"msg\":\"" . ($res === "true" ? "success" : $res) . "\"}";
                return;
            } catch (Exception $e) {
                header("HTTP/1.1 500 Conflict");
                echo $e->getMessage();
            }
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "DELETE") {
        echo "delete form";
        return;
    } else {
        ini_set('max_execution_time', 200);
        header("Cache-Control: no-cache, must-revalidate");
        $offset = array_key_exists('start', $_GET) ? $_GET['start'] : 0;
        $limit = array_key_exists('limit', $_GET) ? $_GET['limit'] : 0;;
        $full_urls = getValIfExists($_GET, 'full_paths', true);

        if ($full_urls === 'false') {
            $full_urls = false;
        } elseif ($full_urls === 'true') {
            $full_urls = true;
        }

        switch ($format) {
            case 'json':

                header('Content-Type: application/json');

                $res = $prj->tables[$frmName]->ask($_GET, $offset, $limit, getValIfExists($_GET, "sort"), getValIfExists($_GET, "dir"), false, "object");
                if ($res !== true)
                    die($res);

                $i = 0;

                $recordSet = array();

                while ($rec = $prj->tables[$frmName]->recieve(1, $full_urls)) {
                    $recordSet = array_merge($recordSet, $rec);
                }


                echo json_encode($recordSet);

                return;

            case "xml":

                header("Content-Type: text/xml");
                if (array_key_exists("mode", $_GET) && $_GET["mode"] == "list") {
                    echo "<entries>";
                    $res = $prj->tables[$frmName]->ask($_GET, $offset, $limit, getValIfExists($_GET, "sort"), getValIfExists($_GET, "dir"), false, "object");
                    if ($res !== true)
                        die($res);
                    while ($ent = $prj->tables[$frmName]->recieve(1, true)) {
                        echo "<entry>";
                        foreach ($ent[0] as $key => $val) {
                            if (array_key_exists($key, $prj->tables[$frmName]->fields) && ($prj->tables[$frmName]->fields[$key]->type === 'location' || $prj->tables[$frmName]->fields[$key]->type === 'gps')) {
                                foreach ($val as $x => $y) {
                                    printf('<%s_%s>%s</%s_%s>', $key, $x, $y, $key, $x);
                                }
                            } else {
                                printf('<%s>%s</%s>', $key, $val, $key);
                            }
                        }
                        echo "</entry>";
                    }
                    echo "</entries>";
                    return;
                } else {
                    echo $prj->tables[$frmName]->toXml();
                    return;
                }
            case "kml":

                header("Content-Type: application/vnd.google-earth.kml+xml");
                echo '<?xml version="1.0" encoding="UTF-8"?><kml xmlns="http://earth.google.com/kml/2.0"><Document><name>EpiCollect</name><Folder><name>';
                echo "{$prj->name} - {$frmName}";
                echo '</name><visibility>1</visibility>';

                $arr = $prj->tables[$frmName]->ask(false, $offset, $limit);

                while ($ent = $prj->tables[$frmName]->recieve(1, true)) {
                    echo "<Placemark>";
                    $desc = "";
                    $title = "";
                    foreach ($prj->tables[$frmName]->fields as $name => $fld) {
                        if (!$fld->active)
                            continue;
                        if ($fld->type == "location" || $fld->type == "gps") {
                            $loc = json_decode($ent[0][$name]);
                            echo "<Point><coordinates>{$loc->longitude},{$loc->latitude}</coordinates></Point>";
                        } elseif ($fld->title) {
                            $title = ($title == "" ? $ent[0][$name] : "$title\t{$ent[0][$name]}");
                        } else {
                            $desc = "$name : {$ent[0][$name]}";
                        }
                    }
                    if ($title == "")
                        $title = $arr[$prj->tables[$frmName]->key];

                    echo "<name>$title</name>";
                    echo "<description><![CDATA[$desc]]></description>";
                    echo "</Placemark>";
                }
                echo '</Folder></Document></kml>';


                return;

            case "csv":

                //
                if (!file_exists('ec/uploads'))
                    mkdir('ec/uploads');
                $filename = sprintf('ec/uploads/%s_%s_%s%s.csv', $prj->name, $frmName, $prj->getLastUpdated(), md5(http_build_query($_GET)));

                if (!file_exists($filename) || getValIfExists($_GET, 'bypass_cache') === 'true') {
                    //ob_implicit_flush(false);
                    $fp = fopen($filename, 'w+');
                    //$arr = $prj->tables[$frmName]->get(false, $offset, $limit);
                    //$arr = $arr[$frmName];
                    //echo assocToDelimStr($arr, ",");
                    $headers = array_merge(array('DeviceID', 'created', 'lastEdited', 'uploaded'), array_keys($prj->tables[$frmName]->fields));
                    $_off = 4;

                    $num_h = count($headers) - $_off;

                    $nxt = $prj->getNextTable($frmName, true);
                    if ($nxt)
                        array_push($headers, sprintf('%s_entries', $nxt->name));

                    $real_flds = $headers;

                    for ($i = 0; $i < $num_h; $i++) {
                        $fld = $prj->tables[$frmName]->fields[$headers[$i + $_off]];
                        if (!$fld->active) {
                            array_splice($headers, $i + $_off, 1);
                            $num_h--;
                        } elseif ($fld->type == "gps" || $fld->type == "location") {
                            $name = $fld->name;

                            //take the GPS fields table, apply each one as a suffix to the field name and then splice

                            $gps_flds = array_values(EcTable::$GPS_FIELDS);
                            foreach ($gps_flds as &$val) {
                                $val = sprintf('%s%s', $name, $val);
                            }


                            array_splice($headers, $i + $_off, 1, $gps_flds);
                            $i = $i + 5;
                        }
                    }

                    fwrite($fp, sprintf("\"%s\"\n", implode('","', $headers)));
                    $res = $prj->tables[$frmName]->ask($_GET, $offset, $limit, getValIfExists($_GET, "sort"), getValIfExists($_GET, "dir"), false, "object", true);
                    if ($res !== true)
                        die($res);

                    $count_h = count($real_flds);

                    while ($xml = $prj->tables[$frmName]->recieve(1, true)) {
                        $xml = $xml[0];
                        //						fwrite($fp, sprintf('"%s"
                        //', $xml));
                        ///print_r($xml);
                        for ($i = 0; $i < $count_h; $i++) {

                            if ($i > 0)
                                fwrite($fp, ',');
                            fwrite($fp, '"');

                            if (array_key_exists($real_flds[$i], $xml)) {
                                if ($i >= $_off && array_key_exists($real_flds[$i], $prj->tables[$frmName]->fields) && ($prj->tables[$frmName]->fields[$real_flds[$i]]->type == "gps" || $prj->tables[$frmName]->fields[$real_flds[$i]]->type == "location")) {
                                    try {

                                        $arr = $xml[$real_flds[$i]];
                                        if (is_string($arr) && trim($xml[$real_flds[$i]]) != '') {
                                            $escval = str_replace(': N/A', ': "N/A"', $xml[$real_flds[$i]]);
                                            $arr = json_decode($escval, true);
                                        }

                                        if (is_array($arr)) {
                                            $x = 0;
                                            foreach (array_keys(EcTable::$GPS_FIELDS) as $k) {
                                                if ($x > 0)
                                                    fwrite($fp, '","');

                                                if (array_key_exists($k, $arr)) {
                                                    fwrite($fp, $arr[$k]);
                                                }

                                                $x++;
                                            }
                                        } else {
                                            for ($fieldsIn = 0; $fieldsIn < 6; $fieldsIn++) {
                                                fwrite($fp, '","');
                                            }
                                        }
                                    } catch (Exception $e) {
                                        throw $e;
                                    }

                                } else {
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


                if (!file_exists('ec/uploads'))
                    mkdir('ec/uploads');
                $filename = sprintf('ec/uploads/%s_%s_%s%s.tsv', $prj->name, $frmName, $prj->getLastUpdated(), md5(http_build_query($_GET)));

                if (!file_exists($filename)) {
                    //ob_implicit_flush(false);
                    $fp = fopen($filename, 'w+');
                    //$arr = $prj->tables[$frmName]->get(false, $offset, $limit);
                    //$arr = $arr[$frmName];
                    //echo assocToDelimStr($arr, ",");
                    $headers = array_merge(array('DeviceID', 'created', 'lastEdited', 'uploaded'), array_keys($prj->tables[$frmName]->fields));
                    $_off = 4;

                    $num_h = count($headers) - $_off;

                    $nxt = $prj->getNextTable($frmName, true);
                    if ($nxt)
                        array_push($headers, sprintf('%s_entries', $nxt->name));

                    $real_flds = $headers;
                    for ($i = 0; $i < $num_h; $i++) {
                        $fld = $prj->tables[$frmName]->fields[$headers[$i + $_off]];
                        if (!$fld->active) {
                            array_splice($headers, $i + $_off, 1);
                        } elseif ($fld->type == "gps" || $fld->type == "location") {
                            $name = $fld->name;

                            //take the GPS fields table, apply each one as a suffix to the field name and then splice

                            $gps_flds = array_values(EcTable::$GPS_FIELDS);
                            foreach ($gps_flds as &$val) {
                                $val = sprintf('%s_%s', $name, $val);
                            }
                            array_splice($headers, $i + $_off, 1, $gps_flds);
                            $i = $i + 5;
                        }
                    }

                    fwrite($fp, sprintf("\"%s\"\n", implode("\"\t\"", $headers)));
                    $res = $prj->tables[$frmName]->ask($_GET, $offset, $limit, getValIfExists($_GET, "sort"), getValIfExists($_GET, "dir"), false, "object", true);
                    if ($res !== true)
                        die($res);

                    $count_h = count($real_flds);

                    while ($xml = $prj->tables[$frmName]->recieve(1, true)) {
                        $xml = $xml[0];
                        //						fwrite($fp, sprintf('"%s"
                        //', $xml));
                        ///print_r($xml);
                        for ($i = 0; $i < $count_h; $i++) {

                            if ($i > 0)
                                fwrite($fp, ',');
                            fwrite($fp, '"');

                            if (array_key_exists($real_flds[$i], $xml)) {
                                if ($i > $_off && ($i != $count_h - 1) && ($prj->tables[$frmName]->fields[$real_flds[$i]]->type == "gps" || $prj->tables[$frmName]->fields[$real_flds[$i]]->type == "location")) {
                                    try {

                                        $arr = $xml[$real_flds[$i]];
                                        if (is_string($arr) && trim($xml[$real_flds[$i]]) != '') {
                                            $escval = str_replace(': N/A', ': "N/A"', $xml[$real_flds[$i]]);
                                            $arr = json_decode($escval, true);
                                        }

                                        if (is_array($arr)) {
                                            $x = 0;
                                            foreach (array_keys(EcTable::$GPS_FIELDS) as $k) {
                                                if ($x > 0)
                                                    fwrite($fp, "\"\t\"");

                                                if (array_key_exists($k, $arr)) {
                                                    fwrite($fp, $arr[$k]);
                                                }

                                                $x++;
                                            }
                                        } else {
                                            for ($fieldsIn = 0; $fieldsIn < 6; $fieldsIn++) {
                                                fwrite($fp, "\"t\"");
                                            }
                                        }
                                    } catch (Exception $e) {
                                        throw $e;
                                    }

                                } else {
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

		var uid = 'web_" . md5($_SERVER["HTTP_HOST"]) . "';

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
    if (!array_key_exists("formCrumbs", $_SESSION) || !$prj->getPreviousTable($frmName) || !preg_match("/{$prj->name}\//", $referer)) {
        $_SESSION["formCrumbs"] = array();
    }
    $p = "";
    if (array_key_exists("prevForm", $_GET)) {

        $pKey = $prj->tables[$_GET["prevForm"]]->key;
        $_SESSION["formCrumbs"][$_GET["prevForm"]] = $_GET[$pKey];
        //if we've come back up a step we need to remove the entry. We assume that the crumbs are in the correct order to
        //draw them in the correct order
    }

    $pk = null;
    $pv = null;
    foreach ($_SESSION["formCrumbs"] as $k => $v) {
        if ($prj->tables[$k]->number >= $prj->tables[$frmName]->number) {
            unset($_SESSION["formCrumbs"][$k]);
        } else {
            if ($pk) {
                $p .= "&gt; <a href=\"{$k}?{$prj->tables[$pk]->key}=$pv\">{$k} : $v </a>";
            } else {
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
        "curate" => $permissionLevel > 1 ? "true" : "false",
        "mapScript" => $mapScript,
        "curationbuttons" => $permissionLevel > 1 ? sprintf('<span class="button-set"><a class="btn btn-default" href="javascript:project.forms[formName].displayForm({ vertical : false });"><i class="fa fa-plus fa-2x"></i></a>
				<a class="btn btn-default" href="javascript:editSelected();"><i class="fa fa-pencil fa-2x"></i></a>
				<a class="btn btn-default" href="javascript:project.forms[formName].deleteEntry(window.ecplus_entries[$(\'.ecplus-data tbody tr.selected\').index()][project.forms[formName].key]);"><i class="fa fa-trash-o fa-2x"></i></a></span>',
            $SITE_ROOT, $SITE_ROOT, $SITE_ROOT) : '',
        "csvform" => $permissionLevel > 1 ? $csvform = '<div id="csvform">
				<h3><a href="#">Upload data from a CSV file</a></h3>
				<div>
					<form method="POST" enctype="multipart/form-data" class="form-inline">
						<label for="upload">File to upload</label>
						<input class="upload-csv" type="file" name="upload" />
						<input class="form-control" type="submit" name="submit" value="Upload File" />
					</form>
				</div>
			</div>' : '');
    echo applyTemplate('base.html', './FormHome.html', $vars);
}