<?php

function uploadMedia() {
    global $url, $SITE_ROOT;
    $pNameEnd = strpos($url, "/");
    $pname = substr($url, 0, $pNameEnd);
    $extStart = strpos($url, ".");
    $fNameEnd = strpos($url, "/", $pNameEnd + 1);
    $frmName = rtrim(substr($url, $pNameEnd + 1, $fNameEnd - $pNameEnd), "/");

    if ($frmName == 'uploadMedia')
        $frmName = false;

    $tvals = array("project" => $pname, "form" => $frmName);

    if (!file_exists('ec/uploads'))
        mkdir('ec/uploads');

    if (array_key_exists("newfile", $_FILES) && $_FILES["newfile"]["error"] == 0) {
        if (preg_match("/\.(png|gif|jpe?g|bmp|tiff?)$/", $_FILES["newfile"]["name"])) {
            $fn = "ec/uploads/{$pname}~" . $_FILES["newfile"]["name"];
            move_uploaded_file($_FILES["newfile"]["tmp_name"], $fn);

            $tnfn = str_replace("~", "~tn~", $fn);

            $imgSize = getimagesize($fn);

            $scl = min(384 / $imgSize[0], 512 / $imgSize[1]);
            $nw = $imgSize[0] * $scl;
            $nh = $imgSize[1] * $scl;

            if (preg_match("/\.jpe?g$/", $fn)) {
                $img = imagecreatefromjpeg($fn);
            } elseif (preg_match("/\.gif$/", $fn)) {
                $img = imagecreatefromgif($fn);
            } elseif (preg_match("/\.png$/", $fn)) {
                $img = imagecreatefrompng($fn);
                imagealphablending($img, true); // setting alpha blending on
                imagesavealpha($img, true); // save alphablending setting (important)
            } else {
                echo "not supported";
                return;
            }

            $thn = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($thn, $img, 0, 0, 0, 0, $nw, $nh, $imgSize[0], $imgSize[1]);


            if (preg_match("/\.jpe?g$/", $fn)) {
                imagejpeg($thn, $tnfn, 95);
            } elseif (preg_match("/\.gif$/", $fn)) {
                imagegif($thn, $tnfn);
            } elseif (preg_match("/\.png$/", $fn)) {
                imagepng($thn, $tnfn);
            }

            $tvals["mediaTag"] = "<img src=\"$SITE_ROOT/{$tnfn}\" />";
        } elseif (preg_match("/\.(mov|wav|mpe?g?[34]|ogg|ogv)$/", $_FILES["newfile"]["name"])) {
            //audio/video handler
            $fn = "ec/uploads/{$pname}~" . $_FILES["newfile"]["name"];
            move_uploaded_file($_FILES["newfile"]["tmp_name"], $fn);

            $tvals["mediaTag"] = "<a href=\"$SITE_ROOT/{$pname}~{$fn}\" >View File</a>";
        } else {
            echo "not supported";
            return;
        }

    }

    if (getValIfExists($_GET, 'fn')) {
        $fn = "ec/uploads/{$pname}~" . $_GET["fn"];
        $tvals["mediaTag"] = "<img src=\"$SITE_ROOT/{$fn}\" height=\"150\" />";
        $tvals["fn"] = str_replace("ec/uploads/", "", $fn);
    }

    echo applyTemplate("./uploadIFrame.html", "./base.html", $tvals);
}

function getMedia() {
    global $url;

    header("Content-Disposition: attachment");

    if (preg_match('~tn~', $url)) {
        //if the image is a thumbnail just try and open it
        header("Content-type: " . mimeType($url));
        echo file_get_contents("./" . $url);
    } else {
        if (file_exists("./$url")) {
            header("Content-type: " . mimeType($url));
            echo file_get_contents("./" . $url);
        } elseif (file_exists('./' . str_replace("~", "~tn~", $url))) {
            $u = str_replace("~", "~tn~", $url);
            header("Content-type: " . mimeType($u));
            echo file_get_contents("./" . $u);
        } elseif (file_exists('./' . substr($url, strpos($url, '~')))) {
            $u = substr($url, strpos($url, '~'));
            header("Content-type: " . mimeType($u));
            echo file_get_contents("./" . $u);
        } else {
            header('HTTP/1.1 404 NOT FOUND', 404);
            return;
        }
    }
}

function getImage() {
    global $url, $auth;

    $prj = new EcProject();
    $pNameEnd = strpos($url, "/");

    $prj->name = substr($url, 0, $pNameEnd);
    $prj->fetch();

    if (!$prj->id) {
        echo applyTemplate("./base.html", "./error.html", array("errorType" => "404 ", "error" => "The project {$prj->name} does not exist on this server"));
        return;
    }


    $permissionLevel = 0;
    $loggedIn = $auth->isLoggedIn();

    if ($loggedIn)
        $permissionLevel = $prj->checkPermission($auth->getEcUserId());

    if (!$prj->isPublic && !$loggedIn) {
        loginHandler($url);
        return;
    } else if (!$prj->isPublic && $permissionLevel < 2) {
        echo applyTemplate("./base.html", "./error.html", array("errorType" => "403 ", "error" => "You do not have permission to view this project"));
        return;
    }

    $extStart = strrpos($url, '/');
    $frmName = rtrim(substr($url, $pNameEnd + 1, ($extStart > 0 ? $extStart : strlen($url)) - $pNameEnd - 1), "/");

    $picName = getValIfExists($_GET, 'img');

    header('Content-type: image/jpeg');

    if ($picName) {
        $tn = sprintf('./ec/uploads/%s~tn~%s', $prj->name, $picName);
        $full = sprintf('./ec/uploads/%s~%s', $prj->name, $picName);

        $thumbnail = getValIfExists($_GET, 'thumbnail') === 'true';

        $raw_not_tn = str_replace('~tn~', '~', $picName);

        if (!$thumbnail && file_exists($full)) {
            //try with project prefix
            echo file_get_contents($full);
        } elseif (file_exists($tn)) {
            //try with project and thumbnail prefix
            echo file_get_contents($tn);
        } elseif (!$thumbnail && file_exists(sprintf('./ec/uploads/%s', $raw_not_tn))) {
            //try with raw non thumbnail filename
            echo file_get_contents(sprintf('./ec/uploads/%s', $raw_not_tn));
        } elseif (file_exists(sprintf('./ec/uploads/%s', $picName))) {
            //try with raw filename
            echo file_get_contents(sprintf('./ec/uploads/%s', $picName));
        } else {
            echo file_get_contents('./images/no_image.png');
        }
    } else {
        echo file_get_contents('./images/no_image.png');
    }
}

function getUpload() {
    global $url;
    header("Content-Disposition: attachment");
    echo file_get_contents("./" . $url);
}