<?php

function listMyProjects() {

    // echo 'list my projects';


    header("Cache-Control: no-cache, must-revalidate");
    global $SITE_ROOT, $db, $log, $auth;

    $vals = array();
    $server = trim($_SERVER["HTTP_HOST"], "/");
    $root = trim($SITE_ROOT, "/");

    //if($_SERVER["HTTPS"] == 'on'){ header(sprintf('location: http://%s%s ', $server, $root));}

    if (!$db->connected) {
        $rurl = "http://$server/$root/test?redir=true";
        header("location: $rurl");
        return;
    }

    $vals["featured"] = '<div class="featured-projects" data-example-id="thumbnails-with-custom-content">
        <div class="row">
            <div class="col-sm-12 col-md-4">
                <div class="thumbnail">
                    <img class="img-rounded" src="http://lorempixel.com/output/nature-q-c-200-150-7.jpg" alt="Generic placeholder thumbnail">
                    <div class="caption">
                        <h3>Thumbnail label</h3>
                        <p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
                        <p><a href="#" class="btn btn-primary pull-right" role="button">View data</a></p>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="thumbnail">
                    <img class="img-rounded" src="http://lorempixel.com/output/transport-q-c-200-150-1.jpg" alt="Generic placeholder thumbnail">
                    <div class="caption">
                        <h3>Thumbnail label</h3>
                        <p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
                        <p><a href="#" class="btn btn-primary pull-right" role="button">View data</a></p>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="thumbnail">
                    <img class="img-rounded" src="http://lorempixel.com/output/animals-q-c-200-150-7.jpg" alt="Generic placeholder thumbnail">
                    <div class="caption">
                        <h3>Thumbnail label</h3>
                        <p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
                        <p><a href="#" class="btn btn-primary pull-right" role="button">View data</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.bs-example -->';

    if ($auth->isLoggedIn()) {
        $vals['userprojects'] = '<div class="ecplus-userprojects"><h3>My projects</h3>';

        $prjs = EcProject::getUserProjects($auth->getEcUserId());

        $count = count($prjs);

        //create dom for list of user projects
        $html = '<div class="list-group">';
        for ($i = 0; $i < $count; $i++) {

            //project metadata
            $href = $SITE_ROOT.'/'.$prjs[$i]["name"];
            $project_name = $prjs[$i]["name"];
            $total_entries = $prjs[$i]["ttl"];
            $total_entries_24 = $prjs[$i]["ttl24"];

            $html .= '<a href="'.$href.'" class="list-group-item"><i class="fa fa-file-text-o fa-2x project-icon"></i>'.$project_name;
            $html.= '<span class="badge">'.$total_entries.' total entries</span>';
            $html.= '<span class="badge">'.$total_entries_24.' entries in the last 24 hours </span>';
            $html.= '</a>';


            //$vals['userprojects'] .= '<div class="project"><i class="fa fa-file-text-o fa-2x project-icon"></i><a href="{#SITE_ROOT#}/{$prjs[$i]["name"]}">{$prjs[$i]["name"]}</a><div class="total">{$prjs[$i]["ttl"]} entries with <b>" . ($prjs[$i]["ttl24"] ? $prjs[$i]["ttl24"] : "0") . "</b> in the last 24 hours </div></div>';
        }
        $html .= '</div>';

        $vals['userprojects'] = $html;
    }

    echo applyTemplate("my-projects.html", "index.html", $vals);
}