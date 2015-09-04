<?php
function uploadProjectXML() {
    global $SITE_ROOT;

    $prj = new EcProject();

    if (!file_exists("ec/xml"))
        mkdir("ec/xml");

    $newfn = "ec/xml/" . $_FILES["projectXML"]["name"];
    move_uploaded_file($_FILES["projectXML"]["tmp_name"], $newfn);
    $prj->parse(file_get_contents($newfn));

    $res = $prj->post();
    if ($res === true) {
        $server = trim($_SERVER["HTTP_HOST"], "/");
        $root = trim($SITE_ROOT, "/");
        header("location: http://$server/$root/editProject.html?name={$prj->name}");
        return;
    } else {
        $vals = array("error" => $res);
        echo applyTemplate("base.html", "./error.html", $vals);
    }
}