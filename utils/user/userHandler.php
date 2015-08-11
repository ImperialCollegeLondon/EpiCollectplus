<?php

function userHandler() {

    global $url;

    $qry = str_replace("user/", "", $url);

    //$db = new dbConnection();
    global $db;

    //details column was storing open ids, now we do not have that anymore...
    $sql = "Select details from user where Email = '$qry'";

    $res = $db->do_query($sql);
    if ($res === true) {
        $arr = $db->get_row_array();
        if ($arr) {
            if (array_key_exists("details", $arr)) {
                echo "true";
                return;
            } else {
                print_r($arr);
            }
        } else {
            echo "false";
        }
    } else {
        die($res + " " + $sql);
    }
}