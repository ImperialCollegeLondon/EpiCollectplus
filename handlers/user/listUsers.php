<?php
function listUsers() {
    global $auth, $url;

    if ($auth->isLoggedIn()) {
        if ($auth->isServerManager()) {
            header("Cache-Control: no-cache, must-revalidate");
            header("Content-Type: application/json");

            echo "{\"users\":[";
            $usrs = $auth->getUsers();
            for ($i = 0; $i < count($usrs); $i++) {
                if ($i > 0)
                    echo ",";
                echo "{
\"userId\" : \"{$usrs[$i]["userId"]}\",
\"firstName\" : \"{$usrs[$i]["FirstName"]}\",
\"lastName\" : \"{$usrs[$i]["LastName"]}\",
\"email\" : \"{$usrs[$i]["Email"]}\",
\"active\" : {$usrs[$i]["active"]}
}";
            }
            echo "]}";
        } else {
            echo applyTemplate("./base.html", "./error.html", array("errorType" => 403, "error" => "Permission denied"));
        }
    } else {
        loginHandler($url);
    }
}