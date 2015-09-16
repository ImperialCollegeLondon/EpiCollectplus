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
            <a href="http://geokey.org.uk/" class="col-xs-6 col-sm-6 col-md-6 col-lg-3">
                <div class="thumbnail">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-geokey.png" alt="GeoKey logo">
                    <h3>GeoKey</h3>
                    <p>Collect, share and discuss local knowledge. <br/><br/>GeoKey is an infrastructure for participatory mapping.</p>
                </div>
            </a>
             <a href="{#SITE_ROOT#}/lichens" class="col-xs-6 col-sm-6 col-md-6 col-lg-3">
                <div class="thumbnail">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-lichens.png" alt="Crowd Crafting Lichens Project">
                    <h3>Lichens as Biomarkers</h3>
                    <p>The goal of this application is to help to analyze, classify and measure the size of the lichens in order to study the quality of air in different areas of the cities.
                    </p>
                </div>
            </a>
             <a href="http://www.geotagx.org/" class="col-xs-6 col-sm-6 col-md-6 col-lg-3">
                <div class="thumbnail">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-geotagx.png" alt="GGeoTag-X">
                    <h3>GeoTag-X</h3>
                    <p>Help disaster relief efforts on the ground to plan a response by asking volunteers to analyse photos taken in disaster-affected areas</p>
                </div>
            </a>
              <a href="{#SITE_ROOT#}/Schools" class="col-xs-6 col-sm-6 col-md-6 col-lg-3">
                <div class="thumbnail">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-schools.png" alt="Schools project">
                    <h3>Schools</h3>
                    <p>Help participate in a survey of schools collecting information on size of school, type of school, location and facilities available for pupils.</p>
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