<?php

function getClusterMarker() {
    //include './utils/markers.php';
    $colours = getValIfExists($_GET, "colours");
    $counts = getValIfExists($_GET, "counts");

    $colours = trim($colours, '|');
    $counts = trim($counts, '|');


    if (!$colours) {
        $colours = array("#FF0000");
    } else {
        $colours = explode("|", $colours);
    }

    if (!$counts) {
        $counts = array(111);
    } else {
        $counts = explode("|", $counts);
    }

    header("Content-type: image/svg+xml");
    echo getGroupMarker($colours, $counts);
}

function getPointMarker() {
   // include "./utils/markers.php";

    $colour = getValIfExists($_GET, "colour");
    $shape = getValIfExists($_GET, "shape");

    if (!$colour)
        $colour = "FF0000";
    header("Content-type: image/svg+xml");
    echo getMapMaker($colour, $shape);
}