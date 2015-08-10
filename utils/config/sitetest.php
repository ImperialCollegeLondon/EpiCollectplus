<?php

function siteTest() {
    $res = array();
    global $cfg, $db;

    $template = 'testResults.html';

    $doit = true;
    if (!array_key_exists("database", $cfg->settings) || !array_key_exists("server", $cfg->settings["database"]) || trim($cfg->settings["database"]["server"]) == "") {
        $res["dbStatus"] = "fail";
        $res["dbResult"] = "No database server specified, please amend the file ec/settings.php and so that \$DBSERVER equals the name of the MySQL server";
        $doit = false;
    } else if (!array_key_exists("user", $cfg->settings["database"]) || trim($cfg->settings["database"]["user"]) == "") {
        $res["dbStatus"] = "fail";
        $res["dbResult"] = "No database user specified, please amend the file ec/settings.php so that \$DBUSER and \$DBPASS equal the credentials for MySQL server";
        $doit = false;
    } else if (!array_key_exists("database", $cfg->settings["database"]) || trim($cfg->settings["database"]["database"]) == "") {
        $res["dbStatus"] = "fail";
        $res["dbResult"] = "No database name specified, please amend the file ec/settings.php so that \$DBNAME equals the name of the MySQL database";
        $doit = false;
    }

    if ($doit && !(array_key_exists("edit", $_GET) && $_GET["edit"] === "true")) {
        if (array_key_exists("redir", $_GET) && $_GET["redir"] === "true")
            $res["redirMsg"] = "	<p class=\"message\">You have been brought to this page because of a fatal error opening the home page</p>";
        if (array_key_exists("redir", $_GET) && $_GET["redir"] === "pwd")
            $res["redirMsg"] = "	<p class=\"message\">The username and password you entered were incorrect, please try again.</p>";

        if (!$db)
            $db = new dbConnection();


        if ($db->connected) {
            $res["dbStatus"] = "succeed";
            $res["dbResult"] = "Connected";
        } else {
            $ex = $db->errorCode;
            if ($ex == 1045) {
                $res["dbStatus"] = "fail";
                $res["dbResult"] = "DB Server found, but the combination of the username and password invalid. <a href=\"./test?edit=true\">Edit Settings</a>";
            } elseif ($ex == 1044) {
                $res["dbStatus"] = "fail";
                $res["dbResult"] = "DB Server found, but the database specified does not exist or the user specified does not have access to the database. <a href=\"./test?edit=true\">Edit Settings</a>";
            } else {
                $res["dbStatus"] = "fail";
                $res["dbResult"] = "Could not find the DB Server ";
            }
        }

        if ($db->connected) {
            $dbNameRes = $db->do_query("SHOW DATABASES");
            if ($dbNameRes !== true) {
                echo $dbNameRes;
                return;
            }
            while ($arr = $db->get_row_array()) {

                if ($arr['Database'] == $cfg->settings["database"]["database"]) {
                    $res["dbStatus"] = "succeed";
                    $res["dbResult"] = "";
                    break;
                } else {
                    $res["dbStatus"] = "fail";
                    $res["dbResult"] = "DB Server found, but the database '{$cfg->settings["database"]["database"]}' does not exist.<br />";
                }
            }

            $res["dbPermStatus"] = "fail";
            $res["dbPermResults"] = "";
            $res["dbTableStatus"] = "fail";

            if ($res["dbStatus"] == "succeed") {
                $dbres = $db->do_query("SHOW GRANTS FOR {$cfg->settings["database"]["user"]};");
                if ($dbres !== true) {
                    $res["dbPermResults"] = $res;
                } else {
                    $perms = array("SELECT", "INSERT", "UPDATE", "DELETE", "EXECUTE");
                    $res ["dbPermResults"] = "Permssions not set, the user {$cfg->settings["database"]["user"]} requires SELECT, UPDATE, INSERT, DELETE and EXECUTE permissions on the database {$cfg->settings["database"]["database"]}";
                    while ($arr = $db->get_row_array()) {
                        $_g = implode(" -- ", $arr) . "<br />";
                        if (preg_match("/ON (`?{$cfg->settings["database"]["database"]}`?|\*\.\*)/", $_g)) {
                            if (preg_match("/ALL PERMISSIONS/i", $_g)) {
                                $res["dbPermStatus"] = "fail";
                                $res["dbPermResults"] = "The user account {$cfg->settings["database"]["user"]} by the website should only have SELECT, INSERT, UPDATE, DELETE and EXECUTE priviliges on {$cfg->settings["database"]["database"]}";
                                break;
                            }
                            for ($_p = 0; $_p < count($perms); $_p++) {
                                if (preg_match("/{$perms[$_p]}/i", $_g)) // &&  preg_match("/INSERT/", $_g) &&  preg_match("/UPDATE/", $_g) &&  preg_match("/DELETE/", $_g) &&  preg_match("/EXECUTE/", $_g))
                                {
                                    unset($perms[$_p]);
                                    $perms = array_values($perms);
                                    $_p--;
                                }
                            }
                        }
                    }
                    if (count($perms) == 0) {
                        $res["dbPermStatus"] = "succeed";
                        $res["dbPermResults"] = "Permssions Correct";
                    } else {
                        $res ["dbPermResults"] = "Permssions not set, the user {$cfg->settings["database"]["user"]} is missing " . implode(", ", $perms) . " permissions on the database {$cfg->settings["database"]["database"]}";
                    }
                }
            }
        }

        if ($db->connected && $res["dbPermStatus"] == "succeed") {

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
            if ($dres !== true) {
                $res["dbTableStatus"] = "fail";
                $res["dbTableResult"] = "EpiCollect Database is not set up correctly";
            } else {
                $i = 0;
                while ($arr = $db->get_row_array()) {
                    $tblTemplate[$arr["Tables_in_{$cfg->settings["database"]["database"]}"]] = true;
                    $i++;
                }
                if ($i == 0) {
                    $template = 'dbSetup.html';
                    $res["dbTableStatus"] = "fail";
                    $res["dbTableResult"] = "<p>Database is blank,  enter an <b>administrator</b> username and password for the database to create the database tables.</p>
<form method=\"post\" action=\"createDB\">
    <b>Username : </b><input name=\"un\" type=\"text\" /> <b>Password : </b><input name=\"pwd\" type=\"password\" /> <input type=\"hidden\" name=\"create\" value=\"true\" /><input type=\"submit\" value=\"Create Database\" name=\"Submit\" />
</form>";
                } else {
                    $done = true;
                    foreach ($tblTemplate as $key => $val) {
                        $done &= $val;
                    }

                    if ($done) {
                        $res["dbTableStatus"] = "succeed";
                        $res["dbTableResult"] = "EpiCollect Database ready";
                    } else {
                        $res["dbTableStatus"] = "fail";
                        $res["dbTableResult"] = "EpiCollect Database is not set up correctly";
                    }
                }
            }

        }

        $res["endStatus"] = array_key_exists("dbTableStatus", $res) ? ($res["dbTableStatus"] == "fail" ? "fail" : "") : "fail";
        $res["endMsg"] = ($res["endStatus"] == "fail" ? "The MySQL database is not ready, please correct the errors in red above and refresh this page. <a href = \"./test?edit=true\">Configuration tool</a>" : "You are now ready to create EpiCollect projects, place xml project definitions in {$_SERVER["PHP_SELF"]}/xml and visit the <a href=\"createProject.html\">create project</a> page");
        echo applyTemplate("base.html", $template, $res);
    } else {
        $arr = "{";
        foreach ($cfg->settings as $k => $v) {
            foreach ($v as $sk => $sv) {
                $arr .= "\"{$k}\\\\{$sk}\" : \"$sv\",";
            }
        }
        $arr = trim($arr, ",") . "}";

        echo applyTemplate("base.html", "setup.html", array("vals" => $arr));
    }

}