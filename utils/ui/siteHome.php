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




    $res = $db->do_query("SELECT name, ttl, ttl24, description, image FROM (SELECT name, project.description as description , project.image as image, count(entry.idEntry) as ttl, x.ttl as ttl24 FROM project left join entry on project.name = entry.projectName left join (select count(idEntry) as ttl, projectName from entry where created > ((UNIX_TIMESTAMP() - 86400)*1000) group by projectName) x on project.name = x.projectName Where project.isListed = 1 group by project.name) a order by ttl desc LIMIT 10");
    if ($res !== true) {

        $rurl = "http://$server/$root/test?redir=true";
        header("location: $rurl");
        return;
    }
    $vals["projects"] = "<div class=\"ecplus-projectlist\"><h3>Popular projects</h3>";

    $vals["featured"] = '<div class="featured-projects" data-example-id="thumbnails-with-custom-content">
        <h3>Featured Projects</h3>
        <div class="row">
            <div class="col-sm-12 col-md-4">
                <div class="thumbnail">
                    <img class="img-rounded" src="http://lorempixel.com/output/nature-q-c-200-150-7.jpg" alt="Generic placeholder thumbnail">
                    <div class="caption">
                        <h3>Schools</h3>
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
    $html = '<h3>Popular projects</h3>';
    while ($row = $db->get_row_array()) {

        //project metadata
        $href = $SITE_ROOT . '/' . $row["name"];
        $project_name = $row["name"];
        $total_entries = $row["ttl"];
        $total_entries_24 = ($row["ttl24"] == null ? 0 : $row["ttl24"]);
        $project_image = $row["image"];
        $project_desc = $row["description"];

        if ($project_image == null) {
            $project_image = $SITE_ROOT . '/images/project-image-placeholder-100x100.png';
        }
        if ($project_desc == null) {
            $project_desc = 'No description available yet';
        } else {
            //truncate description to 300 chars for display purposes on long text
            if (strlen($project_desc) >= 300) {
                $project_desc = substr($project_desc, 0, 300) . '...';
            }
        }

        $html .= '<a href="' . $href . '" class="project-list-item list-group-item">';
        $html .= "<div class='project-thumbnail' style='background-image: url(" . $project_image . "');'>";
        //$html .= '<img class="project-image img-rounded ' . $orientation . ' " src="' . $project_image . '" alt="Project image"/>';
        $html .= '</div>';
        // $html .= '<i class="fa fa-file-text-o fa-2x project-icon"></i>';
        $html .= '<span class="project-name">' . $project_name . '</span>';
        $html .= '<em><span class="project-description">' . $project_desc . '</span></em>';
        $html .= '<span class="badge">' . $total_entries . ' total entries</span>';
        $html .= '<span class="badge">' . $total_entries_24 . ' entries in the last 24 hours </span>';
        $html .= '</a>';


        //$vals["projects"] .= "<div class=\"project\"><i class=\"fa fa-file-text-o fa-2x project-icon\"></i><a href=\"{#SITE_ROOT#}/{$row["name"]}\">{$row["name"]}</a><div class=\"total\">{$row["ttl"]} entries with <b>" . ($row["ttl24"] ? $row["ttl24"] : "0") . "</b> in the last 24 hours </div></div>";
        $i++;
    }
    $html .= '</div>';


    if ($i == 0) {
        $html = '<p>No projects exist on this server, <a href="createProject.html">create a new project</a></p>';
    }

    $vals['projects'] = $html;

    echo applyTemplate("base.html", "index.html", $vals);
}