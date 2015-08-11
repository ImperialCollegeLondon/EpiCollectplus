<?php

function resetPassword() {
    global $auth;

    $user = getValIfExists($_POST, "user");

    if ($auth->isLoggedIn() && $auth->isServerManager() && $_SERVER["REQUEST_METHOD"] == "POST" && $user && preg_match("/[0-9]+/", $user)) {
        $res = $auth->resetPassword($user);

        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Type: application/json");
        echo "{\"result\" : \"$res\"}";

    } else {
        header("HTTP/1.1 403 Access Denied", null, 403);
    }
}