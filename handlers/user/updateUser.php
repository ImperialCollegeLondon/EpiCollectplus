<?php
function updateUser() {
    global $auth;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $pwd = getValIfExists($_POST, "password");
        $con = getValIfExists($_POST, "confirmpassword");

        $change = true;

        if (!$pwd || !$con) {
            $change = false;
            flash("Password not changed, password was blank.", "err");
        }

        if ($pwd != $con) {
            $change = false;
            flash("Password not changed, passwords did not match.", "err");
        }


        if (strlen($pwd) < 8) {
            $change = false;
            flash("Password not changed, password was shorter than 8 characters.", "err");
        }

        if (!preg_match("/^.*(?=.{8,})(?=.*\d)(?=.*[a-zA-Z]).*$/", $pwd)) {
            $change = false;
            flash("Password not changed, password must be longer than 8 characters and contain at least one letter and at least one number.", "err");
        }

        if ($auth->setPassword($auth->getEcUserId(), $_POST["password"])) {
            flash("Password changed");
        } else {
            flash("Password not changed.", "err");
        }
    }

    $name = explode(" ", $auth->getUserNickname());

    $username = $auth->getUserName();
    $is_not_local = $_SESSION['provider'] != 'LOCAL';

    if ($is_not_local)
        flash('You cannot update user information for Open ID or LDAP users unless you do it throught your Open ID or LDAP provider', 'err');

    echo applyTemplate("base.html", "./updateUser.html", array(
        "firstName" => $name[0],
        "lastName" => $name[1],
        "email" => $auth->getUserEmail(),
        "userName" => $username,
        "disabled" => $is_not_local ? 'disabled="disabled"' : ''
    ));
}