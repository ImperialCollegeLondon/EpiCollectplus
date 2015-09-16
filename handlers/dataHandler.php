<?php

/* Function to handle data upload
 * */
function uploadData() {
    global $url, $log;


    $flog = fopen('ec/uploads/fileUploadLog.log', 'a');
    $prj = new EcProject();
    $prj->name = preg_replace('/\/upload\.?(xml|json)?$/', '', $url);

    $prj->fetch();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (count($_POST) == 0) {
            parse_str(file_get_contents("php://input"), $_POST);
        }
        
        file_put_contents('/log.txt', print_r($_POST, true));
        if (count($_FILES) > 0) {
            foreach ($_FILES as $file) {

                if (preg_match("/.+\.xml$/", $file["name"])) {
                    $ts = new DateTime("now", new DateTimeZone("UTC"));
                    $ts = $ts->getTimestamp();


                    $fn = "$ts-{$file["name"]}";

                    for ($i = 1; file_exists("../ec/rescue/{$fn}"); $i++) {
                        $fn = "$ts-$i-{$file['name']}";
                    }
                    move_uploaded_file($file['tmp_name'], "./ec/rescue/{$fn}");

                    $res = $prj->parseEntries(file_get_contents("./ec/rescue/{$fn}"));

                    if (preg_match("/(CHROME|FIREFOX)/i", $_SERVER["HTTP_USER_AGENT"])) {
                        echo $res;
                    } else {

                        //fwrite($flog, "$res\r\m");
                        $log->write("debug", "$res");
                        echo($res === true ? "1" : "0");
                    }
                } else if (preg_match("/\.(png|gif|rtf|docx?|pdf|jpg|jpeg|txt|avi|mpe?g|mov|mpe?g?3|wav|mpe?g?4|3gp)$/", $file['name'])) {

                    try {
                        //if(!fileExists("./uploads/{$prj->name}")) mkdir("./uploads/{$prj->name}");

                        move_uploaded_file($file['tmp_name'], "./ec/uploads/{$prj->name}~" . ($_REQUEST["type"] == "thumbnail" ? "tn~" : "") . "{$file['name']}");
                        $log->write('debug', $file['name'] . " copied to uploads directory\n");
                        echo 1;
                    } catch (Exception $e) {
                        $log->write("error", $e . "\r\n");
                        echo "0";
                    }
                } else {
                    $log->write("error", $file['name'] . " error : file type not allowed\r\n");
                    echo "0";
                }
            }

        } else {
            $log->write("POST", "data : " . serialize($_POST) . "\r\n");
            $tn = $_POST["table"];
            unset($_POST["table"]);

            try {

                $ent = new EcEntry($prj->tables[$tn]);
                if (array_key_exists("ecPhoneID", $_POST)) {
                    $ent->deviceId = $_POST["ecPhoneID"];
                } else {
                    $ent->deviceId = "web";
                }
                if (array_key_exists("ecTimeCreated", $_POST)) {
                    $ent->created = $_POST["ecTimeCreated"];
                } else {
                    $d = new DateTime('now', new DateTimeZone('UTC'));
                    $ent->created = $d->getTimestamp();
                }
                $ent->project = $prj;

                foreach ($prj->tables[$tn]->fields as $key => $fld) {
                    if ($fld->type == 'gps' || $fld->type == 'location') {
                        $lat = "{$key}_lat";
                        $lon = "{$key}_lon";
                        $alt = "{$key}_alt";
                        $acc = "{$key}_acc";
                        $src = "{$key}_provider";
                        $bearing = "{$key}_bearing";

                        $ent->values[$key] = array(
                            'latitude' => (string)getValIfExists($_POST, $lat),
                            'longitude' => (string)getValIfExists($_POST, $lon),
                            'altitude' => (string)getValIfExists($_POST, $alt),
                            'accuracy' => (string)getValIfExists($_POST, $acc),
                            'provider' => (string)getValIfExists($_POST, $src),
                            'bearing' => (string)getValIfExists($_POST, $bearing),
                        );
                    } else if (!array_key_exists($key, $_POST)) {
                        $ent->values[$key] = "";
                        continue;
                    } else if ($fld->type != "branch") {
                        $ent->values[$key] = (string)$_POST[$key];
                    }
                }

                $log->write("debug", "posting ... \r\n");
                $res = $ent->post();
                $log->write("debug", "response : $res \r\n");

                if ($res === true) {
                    header("HTTP/1.1 200 OK");
                    echo 1;
                } else {
                    header("HTTP/1.1 405 Bad Request");
                    $log->write("error", "error : $res\r\n");
                    echo $res;
                }
            } catch (Exception $e) {
                $log->write("error", "error : " . $e->getMessage() . "\r\n");
                $msg = $e->getMessage();
                if (preg_match("/^Message/", $msg)) {
                    header("HTTP/1.1 405 $msg");
                } else {
                    header("HTTP/1.1 405 Bad Request");
                }
                echo $msg;
            }
        }
    }
    fclose($flog);
}

function downloadData() {
    global $url, $SITE_ROOT;
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
    $pos = max(strrpos($_SERVER["SCRIPT_FILENAME"], "\\"), strrpos($_SERVER["SCRIPT_FILENAME"], "/"));
    $root = substr($_SERVER["SCRIPT_FILENAME"], 0, $pos);

    $wwwroot = "http://{$_SERVER["HTTP_HOST"]}$SITE_ROOT";
    $startTbl = (array_key_exists('select_table', $_GET) ? getValIfExists($_GET, "table") : false);
    $endTbl = (array_key_exists('select_table', $_GET) ? getValIfExists($_GET, "select_table") : getValIfExists($_GET, "table"));
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
    if (array_key_exists('select_table', $_GET) && $entry)
        $n++;

    //for each table between startTbl and end Tbl (or that is a branch of a table we want)
    //we'll loop through the table array to establish which tables we need
    foreach ($survey->tables as $name => $tbl) {
        //first off is $tbl is already in $tbls we can skip it
        if (array_key_exists($name, $tbls)) {
            continue;
        } // are we doing name-based or type-based checking?
        elseif ($dataType == 'group') {
            if ($tbl->group) {
                array_push($tbls, $name);
            }
        } else {
            // first check if the table has a number between $n and $end
            if (($tbl->number >= $n && $tbl->number <= $end)) {
                array_push($tbls, $name);
            }

            if (count($tbl->branches) > 0) {
                $tbls = array_merge($tbls, $tbl->branches);
            }
        }
    }

    if ($dataType == 'group')
        $dataType = 'data';

    //criteria
    $cField = false;
    $cVals = array();
    if ($entry) {
        $cField = $survey->tables[$startTbl]->key;
        $cVals[0] = $entry;
    }

    $nxtCVals = array();

    //for each main table we're intersted in (i.e. main tables between stat and end table)
    //$ts = new DateTime("now", new DateTimeZone("UTC"));
    //$ts = $ts->getTimestamp();
    if ($dataType == 'data' && $xml) {
        header('Content-type: text/xml');
        $fxn = "$root\\ec\\uploads\\{$baseFn}.xml";
        $fx_url = "$wwwroot/ec/uploads/{$baseFn}.xml";
        if (file_exists($fxn)) {
            header("location: $fx_url");
            return;
        }
        $fxml = fopen("$fxn", "w+");
        fwrite($fxml, "<?xml version=\"1.0\"?><entries>");

    } else if ($dataType == "data") {
        header("Content-type: text/plain");

        $txn = "$root\\ec\\uploads\\{$baseFn}.tsv";
        $ts_url = "$wwwroot/ec/uploads/{$baseFn}.tsv";
        if (file_exists($txn)) {
            header("Location: $ts_url");
            return;
        }

        $tsv = fopen($txn, "w+");
    } else {

        $zfn = "$root\\ec\\uploads\\arc{$baseFn}.zip";
        $zrl = "$wwwroot/ec/uploads/arc{$baseFn}.zip";

        if (file_exists($zfn)) {
            header("Location: $zrl");
            return;
        }

        $arc = new ZipArchive;
        $x = $arc->open($zfn, ZipArchive::CREATE);
        if (!$x)
            die("Could not create the zip file.");
    }


    for ($t = 0; $t <= $end && array_key_exists($t, $tbls); $t++) {
        //echo '...' . $cField . "\r\n";
        //print_r($cVals);

        if ($dataType == "data" && $xml) {
            fwrite($fxml, "<table><table_name>{$tbls[$t]}</table_name>");
        }

        for ($c = 0; $c < count($cVals) || $c < 1; $c++) {

            $res = false;

            if ($entry && count($cVals) == 0)
                break;
            $args = array();

            if ($entry)
                $args[$cField] = $cVals[$c];

            $res = $survey->tables[$tbls[$t]]->ask($args, 0, 0, 'created', 'asc', true, 'object', false);

            if ($res !== true)
                echo $res;

            while ($ent = $survey->tables[$tbls[$t]]->recieve(1)) {
                $ent = $ent[0];

                if ($dataType == "data") {

                    if ($xml) {
                        fwrite($fxml, "\t\t<entry>\n");
                        foreach (array_keys($ent) as $fld) {
                            if ($fld == "childEntries")
                                continue;
                            if (array_key_exists($fld, $survey->tables[$tbls[$t]]->fields) && preg_match("/^(gps|location)$/i", $survey->tables[$tbls[$t]]->fields[$fld]->type)) {
                                $gpsObj = $ent[$fld];
                                try {
                                    fwrite($fxml, "\t\t\t<{$fld}_lat>{$gpsObj['latitude']}</{$fld}_lat>\n");
                                    fwrite($fxml, "\t\t\t<{$fld}_lon>{$gpsObj['longitude']}</{$fld}_lon>\n");
                                    fwrite($fxml, "\t\t\t<{$fld}_acc>{$gpsObj['accuracy']}</{$fld}_acc>\n");
                                    if (array_key_exists('provider', $gpsObj))
                                        fwrite($fxml, "\t\t\t<{$fld}_provider>{$gpsObj['provider']}</{$fld}_provider>\n");
                                    if (array_key_exists('altitude', $gpsObj))
                                        fwrite($fxml, "\t\t\t<{$fld}_alt>{$gpsObj['altitude']}</{$fld}_alt>\n");
                                    if (array_key_exists('bearing', $gpsObj))
                                        fwrite($fxml, "\t\t\t<{$fld}_bearing>{$gpsObj['bearing']}</{$fld}_bearing>\n");
                                } catch (ErrorException $e) {
                                    fwrite($fxml, "\t\t\t<{$fld}_lat>0</{$fld}_lat>\n");
                                    fwrite($fxml, "\t\t\t<{$fld}_lon>0</{$fld}_lon>\n");
                                    fwrite($fxml, "\t\t\t<{$fld}_acc>-1</{$fld}_acc>\n");
                                    fwrite($fxml, "\t\t\t<{$fld}_provider>None</{$fld}_provider>\n");
                                    fwrite($fxml, "\t\t\t<{$fld}_alt>0</{$fld}_alt>\n");
                                    fwrite($fxml, "\t\t\t<{$fld}_bearing>0</{$fld}_bearing>\n");
                                    $e = null;
                                }
                                $gpsObj = null;
                            } else {
                                fwrite($fxml, "\t\t\t<$fld>" . str_replace(">", "&gt;", str_replace("<", "&lt;", str_replace("&", "&amp;", $ent[$fld]))) . "</$fld>\n");
                            }
                        }
                        fwrite($fxml, "\t\t</entry>\n");
                    } else {
                        fwrite($tsv, "{$tbls[$t]}$delim");
                        foreach (array_keys($ent) as $fld) {
                            if (array_key_exists($fld, $survey->tables[$tbls[$t]]->fields) && preg_match("/^(gps|location)$/i", $survey->tables[$tbls[$t]]->fields[$fld]->type) && $ent[$fld] != "") {
                                $gpsObj = $ent[$fld];
                                fwrite($tsv, "{$fld}_lat{$delim}{$gpsObj['latitude']}{$delim}");
                                fwrite($tsv, "{$fld}_lon{$delim}{$gpsObj['longitude']}{$delim}");
                                fwrite($tsv, "{$fld}_acc{$delim}{$gpsObj['accuracy']}{$delim}");
                                fwrite($tsv, "{$fld}_provider{$delim}{$gpsObj['provider']}{$delim}");
                                fwrite($tsv, "{$fld}_alt{$delim}{$gpsObj['altitude']}{$delim}");
                                if (array_key_exists('bearing', $gpsObj))
                                    fwrite($tsv, "{$fld}_bearing{$delim}{$gpsObj['bearing']}{$delim}");

                            } else {
                                fwrite($tsv, "$fld$delim" . escapeTSV($ent[$fld]) . $delim);
                            }
                        }
                        //fwrite($tsv, $ent);
                        fwrite($tsv, $rowDelim);

                    }

                } elseif (strtolower($_GET["type"]) == "thumbnail") {
                    foreach (array_keys($ent) as $fld) {
                        if ($fld == "childEntries" || !array_key_exists($fld, $survey->tables[$tbls[$t]]->fields))
                            continue;
                        if ($survey->tables[$tbls[$t]]->fields[$fld]->type == "photo" && $ent[$fld] != "")// && file_exists("$root\\ec\\uploads\\tn_".$ent[$fld]))
                        {
                            $fn = "$root\\ec\\uploads\\";
                            $bfn = "$root\\ec\\uploads\\" . $ent[$fld];
                            if (strstr($ent[$fld], '~tn~')) {
                                //for images where the value was stored as a thumbnail
                                $fn .= $ent[$fld];
                            } elseif (strstr($ent[$fld], '~')) {
                                //for images stored as a value with the project name
                                $fn .= str_replace('~', '~tn~', $ent[$fld]);
                            } else {
                                //otherwise
                                $fn .= $survey->name . '~tn~' . $ent[$fld];
                            }

                            if (file_exists($fn)) {
                                if (!$arc->addFile($fn, $ent[$fld]))
                                    die("fail -- " . $fn);
                                $files_added++;
                            } elseif (file_exists($bfn)) {
                                if (!$arc->addFile($bfn, $ent[$fld]))
                                    die("fail -- " . $bfn);
                                $files_added++;
                            }
                        }
                    }
                } elseif (strtolower($_GET["type"]) == "full_image") {
                    foreach (array_keys($ent) as $fld) {
                        if ($fld == "childEntries" || !array_key_exists($fld, $survey->tables[$tbls[$t]]->fields))
                            continue;
                        if ($survey->tables[$tbls[$t]]->fields[$fld]->type == "photo" && $ent[$fld] != "")// && file_exists("$root\\ec\\uploads\\".$ent[$fld]))
                        {
                            $fn = "$root\\ec\\uploads\\";
                            $bfn = "$root\\ec\\uploads\\" . $ent[$fld];
                            if (strstr($ent[$fld], '~tn~')) {
                                //for images where the value was stored as a thumbnail
                                $fn .= str_replace('~tn~', '~', $ent[$fld]);
                            } elseif (strstr($ent[$fld], '~')) {
                                //for images stored as a value with the project name
                                $fn .= $ent[$fld];
                            } else {
                                //otherwise
                                $fn .= $survey->name . '~' . $ent[$fld];
                            }

                            if (file_exists($fn)) {
                                if (!$arc->addFile($fn, $ent[$fld]))
                                    die("fail -- " . $fn);
                                $files_added++;
                            } elseif (file_exists($bfn)) {
                                if (!$arc->addFile($bfn, $ent[$fld]))
                                    die("fail -- " . $bfn);
                                $files_added++;
                            }
                        }
                    }
                } else {
                    foreach (array_keys($ent) as $fld) {
                        if ($fld == "childEntries" || !array_key_exists($fld, $survey->tables[$tbls[$t]]->fields))
                            continue;
                        if ($survey->tables[$tbls[$t]]->fields[$fld]->type == $_GET["type"] && $ent[$fld] != "" && file_exists("$root\\ec\\uploads\\" . $ent[$fld])) {
                            if (!$arc->addFile("$root\\ec\\uploads\\" . $ent[$fld], $ent[$fld]))
                                die("fail -- \\ec\\uploads\\" . $ent[$fld]);
                            $files_added++;
                        }
                    }
                }

                if ($ent && !array_key_exists($ent[$survey->tables[$tbls[$t]]->key], $nxtCVals)) {
                    $nxtCVals[$ent[$survey->tables[$tbls[$t]]->key]] = true;
                }
            }
        }
        if ($dataType == "data" && $xml) {
            fwrite($fxml, "</table>");
        }

        if ($entry) {
            $cField = $survey->tables[$tbls[$t]]->key;
            $cVals = array_keys($nxtCVals);
            $nxtCVals = array();
        }
    }

    if ($dataType == "data" && $xml) {
        fwrite($fxml, "</entries>");
        fclose($fxml);
        header("location: $fx_url");
        return;
        //echo file_get_contents($fxn);
    } elseif ($dataType == "data") {
        fclose($tsv);
        header("location: $ts_url");
        return;
        //echo file_get_contents($txn);
    } else {
        //close zip files
        $err = $arc->close();
        if ($files_added === 0) {
            echo "no files";
            return;
        }

        if (!$err == true) {
            echo "fail expecting $files_added files";
            return;
        }

        header("Location: $zrl");
        return;
    }
}

function getChildEntries($survey, $tbl, $entry, &$res, $stopTbl = false) {
    //	global $survey;

    foreach ($survey->tables as $subTbl) {

        if (($subTbl->number <= $tbl->number && $subTbl->branchOf != $tbl->name) || ($stopTbl !== false && $subTbl->number > $stopTbl && $subTbl->branchOf != $tbl->name)) {
            continue;
        }

        foreach ($subTbl->fields as $fld) {
            if ($fld->name == $tbl->key && !array_key_exists($subTbl->name, $res)) {

                $res[$subTbl->name] = $subTbl->get(Array($tbl->key => $entry));
                //print_r($res[$subTbl->name]);
                foreach ($res[$subTbl->name][$subTbl->name] as $sEntry) {

                    getChildEntries($survey, $subTbl, $sEntry[$subTbl->key][$subTbl->key], $res, $stopTbl);

                }

            }

        }
    }

}

function tableStats() {
    global $url, $log;
    ini_set('max_execution_time', 60);
    header("Cache-Control: no-cache, must-revalidate");

    $prjEnd = strpos($url, "/");
    $frmEnd = strpos($url, "/", $prjEnd + 1);
    $prjName = substr($url, 0, $prjEnd);
    $frmName = substr($url, $prjEnd + 1, $frmEnd - $prjEnd - 1);

    $prj = new EcProject();
    $prj->name = $prjName;
    $prj->fetch();
    echo json_encode($prj->tables[$frmName]->getSummary($_GET));
}

function formDataLastUpdated() {
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
    $frmName = substr($url, $pNameEnd + 1, strrpos($url, '/', 1) - strlen($url));


    if (!array_key_exists($frmName, $prj->tables)) {
        echo applyTemplate("./base.html", "./error.html", array("errorType" => "404 ", "error" => "The project {$prj->name} does not contain the form $frmName"));
        return;
    }

    echo json_encode($prj->tables[$frmName]->getLastActivity());
    return;
}