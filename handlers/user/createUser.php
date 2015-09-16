<?php
function createUser() {
    global $auth, $SITE_ROOT, $cfg;

    header("Cache-Control: no-cache; must-revalidate;");

    if ($cfg->settings["security"]["use_local"] != "true") {
        flash("This server is not configured to user Local Accounts", "err");
    } elseif ($auth->createUser('', $_POST["password"], $_POST["email"], $_POST["fname"], $_POST["lname"], "en")) {
        flash("User Added");
    } else {
        flash("Could not create the user", "err");
    }

    header("location: http://{$_SERVER["HTTP_HOST"]}$SITE_ROOT/admin");
    return;
}