<?php

function saveUser() {
    global $auth, $db;
    $qry = "CALL updateUser(" . $auth->getEcUserId() . ",'{$_POST["name"]}','{$_POST["email"]}')";
    $res = $db->do_query($qry);

    if ($res === true) {
        echo '{"success" : true, "msg" : "User updated successfully"}';
    } else {
        echo '{"success" : false, "msg" : "' . $res . '"}';
    }
}