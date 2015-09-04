<?php

function enableUser() {
    global $auth;

    $user = getValIfExists($_POST, "user");

    if ($auth->isLoggedIn() && $auth->isServerManager() && $_SERVER["REQUEST_METHOD"] == "POST" && $user) {
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Type: application/json");
        $res = $auth->setEnabled($user, true);
        if ($res === true) {

            echo "{\"result\" : true}";
        } else {
            echo $res;
            echo "{\"result\" : false}";
        }
    } else {
        header("HTTP/1.1 403 Access Denied", null, 403);
    }
}

function disableUser() {
    global $auth;

    $user = getValIfExists($_POST, "user");

    if ($auth->isLoggedIn() && $auth->isServerManager() && $_SERVER["REQUEST_METHOD"] == "POST" && $user) {
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Type: application/json");
        if ($auth->setEnabled($user, false)) {

            echo "{\"result\" : true}";
        } else {
            echo "{\"result\" : false}";
        }
    } else {
        header("HTTP/1.1 403 Access Denied", null, 403);
    }
}