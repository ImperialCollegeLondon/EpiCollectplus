<?php

function createAccount() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        global $cfg;
        if ($cfg->settings['misc']['public_server'] === "true") {
            createUser();
            flash("Account created, please log in.");
            header(sprintf('location: http://%s/%s/login.php', $server, $root));
        } else {
            flash("This server is not public", "err");
            header(sprintf('location: http://%s/%s/', $server, $root));
        }
    } else {
        global $auth;
        echo applyTemplate('./base.html', './loginbase.html', array('form' => $auth->requestSignup()));
    }
}