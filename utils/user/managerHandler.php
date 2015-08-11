<?php

function managerHandler() {
global $auth, $SITE_ROOT;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
if (array_key_exists("remove", $_POST) && $_POST["remove"] == "Remove") {
$auth->removeServerManager($_POST["email"]);
flash("{$_POST["email"]} is no longer a server manager.");
} else {
$x = $auth->makeServerManager($_POST["email"]);
if ($x === 1) {
flash("{$_POST["email"]} is now a server manager.");
} elseif ($x === -1) {
flash("{$_POST["email"]} is already a server manager.");
} else {
flash("Could not find user {$_POST["email"]}. ($x)", "err");
}
}
}


header("location:  http://{$_SERVER["HTTP_HOST"]}{$SITE_ROOT}/admin#manage");
return;
}