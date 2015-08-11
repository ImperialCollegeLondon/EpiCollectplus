<?php

function siteHome() {
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

    $res = $db->do_query("SELECT name, ttl, ttl24 FROM (SELECT name, count(entry.idEntry) as ttl, x.ttl as ttl24 FROM project left join entry on project.name = entry.projectName left join (select count(idEntry) as ttl, projectName from entry where created > ((UNIX_TIMESTAMP() - 86400)*1000) group by projectName) x on project.name = x.projectName Where project.isListed = 1 group by project.name) a order by ttl desc LIMIT 10");
    if ($res !== true) {

        $rurl = "http://$server/$root/test?redir=true";
        header("location: $rurl");
        return;
    }
    $vals["projects"] = "<div class=\"ecplus-projectlist\"><h3>Popular projects</h3>";

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

    $i = 0;

    while ($row = $db->get_row_array()) {
        $vals["projects"] .= "<div class=\"project\"><i class=\"fa fa-file-text-o fa-2x project-icon\"></i><a href=\"{#SITE_ROOT#}/{$row["name"]}\">{$row["name"]}</a><div class=\"total\">{$row["ttl"]} entries with <b>" . ($row["ttl24"] ? $row["ttl24"] : "0") . "</b> in the last 24 hours </div></div>";
        $i++;
    }

    if ($i == 0) {
        $vals["projects"] = "<p>No projects exist on this server, <a href=\"createProject.html\">create a new project</a></p>";
    } else {
        $vals["projects"] .= "</div>";
    }

    //let's move user projects to its own dedivated page

    //if ($auth->isLoggedIn()) {
    //    //$vals['userprojects'] = '<div class="ecplus-userprojects"><h3>My projects</h3>';
    //    //
    //    //$prjs = EcProject::getUserProjects($auth->getEcUserId());
    //    //$count = count($prjs);
    //    //
    //    //for ($i = 0; $i < $count; $i++) {
    //    //    $vals['userprojects'] .= "<div class=\"project\"><i class=\"fa fa-file-text-o fa-2x project-icon\"></i><a href=\"{#SITE_ROOT#}/{$prjs[$i]["name"]}\">{$prjs[$i]["name"]}</a><div class=\"total\">{$prjs[$i]["ttl"]} entries with <b>" . ($prjs[$i]["ttl24"] ? $prjs[$i]["ttl24"] : "0") . "</b> in the last 24 hours </div></div>";
    //    //}
    //    //
    //    //$vals['userprojects'] .= '</div>';
    //} else {
    //
    //    //        $vals['userprojects'] = '<a class="twitter-timeline" href="https://twitter.com/EpiCollect" data-widget-id="630833052789395456">Tweets by @EpiCollect</a>
    //    //<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?"http":"https";if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>';
    //
    //}

    echo applyTemplate("base.html", "index.html", $vals);
}