<?php

function admin() {

    global $auth, $SITE_ROOT, $cfg;

    if (count($auth->getServerManagers()) > 0 && $auth->isLoggedIn() && !$auth->isServerManager()) {
        flash("Configuration only available to server managers", "err");

        header("location: $SITE_ROOT/");
        return;
    }

    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $mans = $auth->getServerManagers();
        $men = "";
        foreach ($mans as $man) {
            $men .= "<form method=\"POST\" action=\"user/manager\"><p>{$man["firstName"]} {$man["lastName"]} ({$man["Email"]})<input type=\"hidden\" name=\"email\" value=\"{$man["Email"]}\" />" . ($auth->getUserEmail() == $man["Email"] ? "" : "<input type=\"submit\" name=\"remove\" value=\"Remove\" />") . "</form></p>";
        }

        $arr = "{";
        foreach ($cfg->settings as $k => $v) {
            foreach ($v as $sk => $sv) {
                $arr .= "\"{$k}\\\\{$sk}\" : \"$sv\",";
            }
        }
        $arr = trim($arr, ",") . "}";

        echo applyTemplate("./base.html", "./admin.html", array("serverManagers" => $men, "vals" => $arr));

    } else if ($_SERVER["REQUEST_METHOD"] == "POST") {
        createUser();
    }
}
