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


    //get popular projects with a limit of 12
    $res = $db->do_query("SELECT name, ttl, ttl24, description, image FROM (SELECT name, project.description as description , project.image as image, count(entry.idEntry) as ttl, x.ttl as ttl24 FROM project left join entry on project.name = entry.projectName left join (select count(idEntry) as ttl, projectName from entry where created > ((UNIX_TIMESTAMP() - 86400)*1000) group by projectName) x on project.name = x.projectName Where project.isListed = 1 group by project.name) a order by ttl desc LIMIT 12");
    if ($res !== true) {

        $rurl = "http://$server/$root/test?redir=true";
        header("location: $rurl");
        return;
    }
    $vals["projects"] = "<div class=\"ecplus-projectlist\"><h3>Popular projects</h3>";

    $vals["featured"] = '<div class="featured-projects" data-example-id="thumbnails-with-custom-content">
        <h3>Featured Projects</h3>
        <div class="row">
            <a href="#" class="col-sm-6 col-md-3">
                <div class="thumbnail">
                    <img class="img-responsive img-rounded" src="http://lorempixel.com/output/nature-q-c-400-225-7.jpg" alt="Generic placeholder thumbnail">
                    <h3>Thumbnail label</h3>
                    <p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
                </div>
            </a>
             <a href="#" class="col-sm-6 col-md-3">
                <div class="thumbnail">
                    <img class="img-responsive img-rounded" src="http://lorempixel.com/output/nature-q-c-400-225-3.jpg" alt="Generic placeholder thumbnail">
                    <h3>Thumbnail label</h3>
                    <p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
                </div>
            </a>
             <a href="#" class="col-sm-6 col-md-3">
                <div class="thumbnail">
                    <img class="img-responsive img-rounded" src="http://lorempixel.com/output/technics-q-c-400-225-4.jpg" alt="Generic placeholder thumbnail">
                    <h3>Thumbnail label</h3>
                    <p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
                </div>
            </a>
              <a href="#" class="col-sm-6 col-md-3">
                <div class="thumbnail">
                    <img class="img-responsive img-rounded" src="http://lorempixel.com/output/animals-q-c-400-225-4.jpg" alt="Generic placeholder thumbnail">
                    <h3>Thumbnail label</h3>
                    <p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
                </div>
            </a>
        </div>
    </div><!-- /.bs-example -->';

    $i = 0;
    $html = '<h3>Popular projects</h3>';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-6">';
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
            $project_desc = 'Description not available yet';
        } else {
            //truncate description to 300 chars for display purposes on long text
            if (strlen($project_desc) >= 200) {
                $project_desc = substr($project_desc, 0, 200) . '...';
            }
        }

        $html .= '<a href="' . $href . '" class="project-list-item list-group-item">';
        //$html .= "<div class='project-thumbnail' style='background-image: url(" . $project_image . "');'>";
        //$html .= '</div>';
        $html .= '<div class="project-metadata">';
        $html .= '<span class="project-name">' . $project_name . '</span>';
        $html .= '<em><span class="project-description">' . $project_desc . '</span></em>';
        $html .= '</div>';
        //$html .= '<div class="clearfix"></div>';
        $html .= '<div class="project-badge-counters">';
        $html .= '<span class="badge">' . $total_entries . ' total entries</span>';
        $html .= '<span class="badge">' . $total_entries_24 . ' entries in the last 24 hours </span>';
        $html .= '</div>';
        $html .= '</a>';

        //$html .= '<a href="#" class="media"><div class="media-left">';
        //$html .= "<div class='project-thumbnail' style='background-image: url(" . $project_image . "');">
        //$html .='</div>
        //            <div class="media-body">
        //<h4 class="media-heading">Media heading</h4>
        //         '. $project_desc.'
        //         </div>';
        //$html .= '<div class="project-badge-counters">';
        //$html .= '<span class="badge">' . $total_entries . ' total entries</span>';
        //$html .= '<span class="badge">' . $total_entries_24 . ' entries in the last 24 hours </span>';
        //$html .= '</div>';
        //$html .=  '</a>';

        $i++;

        //add new row every 2 projects, at project 8 just wrap it up
        if ($i % 2 == 0) {
            $html .= '</div>';
            if ($i != 12) {
                $html .= '<div class="col-md-6">';
            }
        }

    }//while
    $html .= '</div>';


    if ($i == 0) {
        $html = '<p>No projects exist on this server, <a href="createProject.html">create a new project</a></p>';
    }

    $vals['projects'] = $html;

    echo applyTemplate("base.html", "index.html", $vals);
}