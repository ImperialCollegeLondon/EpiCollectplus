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

    //get popular projects with a limit of 12: run a faster query, not getting the total entries in the last 24hrs but way faster
    $query = 'SELECT entry.projectname as name, project.description as description, project.image as image,  count(*) AS ttl FROM entry JOIN project ON project.name=entry.projectName AND project.isListed=1 group by projectName order by ttl desc LIMIT 12';

    $res = $db->do_query($query);

    //get popular projects with a limit of 12
//    $res = $db->do_query("SELECT name, ttl, ttl24, description, image FROM (SELECT name, project.description as description , project.image as image, count(entry.idEntry) as ttl, x.ttl as ttl24 FROM project left join entry on project.name = entry.projectName left join (select count(idEntry) as ttl, projectName from entry where created > ((UNIX_TIMESTAMP() - 86400)*1000) group by projectName) x on project.name = x.projectName Where project.isListed = 1 group by project.name) a order by ttl desc LIMIT 12");

    if ($res !== true) {

        $rurl = "http://$server/$root/test?redir=true";
        header("location: $rurl");
        return;
    }
    $vals["projects"] = "<div class=\"ecplus-projectlist\"><h3>Popular projects</h3>";

    $vals["featured"] = '<div class="featured-projects" data-example-id="thumbnails-with-custom-content">
        <h3>Featured Projects</h3>
        <div class="row">
        <a href="{#SITE_ROOT#}/Schools" class="col-xs-6 col-sm-6 col-md-6 col-lg-3 ">
                <div class="thumbnail match-height">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-schools.png" alt="Schools project">
                    <h3>Schools</h3>
                    <p>Help participate in a survey of schools collecting information on size of school, type of school, location and facilities available for pupils.</p>
                </div>
            </a>

            <a href="{#SITE_ROOT#}/lichens" class="col-xs-6 col-sm-6 col-md-6 col-lg-3 ">
                <div class="thumbnail match-height">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-lichens.png" alt="Crowd Crafting Lichens Project">
                    <h3>Lichens</h3>
                    <p>The goal of this application is to help to analyze, classify and measure the size of the lichens in order to study the quality of air in different areas of the cities.
                    </p>
                </div>
            </a>
            <a href="{#SITE_ROOT#}/NCNsignplus" class="col-xs-6 col-sm-6 col-md-6 col-lg-3 ">
                <div class="thumbnail match-height">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-sustrans.png" alt="Sustrans">
                    <h3>Sustrans</h3>
                    <p>Help map the state of signage the national Cycle network<br/> <br/>Sustrans Data will be used to inform repair strategies</p>
                </div>
            </a>
             <a href="{#SITE_ROOT#}/RDCEP_Urban_Research_2015" class="col-xs-6 col-sm-6 col-md-6 col-lg-3 ">
                <div class="thumbnail match-height">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-chicago.png" alt="GeoKey logo">
                    <h3>RDCEP Urban Research</h3>
                    <p>The Centre for Robust Decision making on Climate and Energy Policy - Summer projects for students mapping out potholes across Chicago, USA</p>
                </div>
            </a>
        </div>
    </div><!-- /.bs-example -->';

    $vals["integrations"] = '<div class="featured-projects" data-example-id="thumbnails-with-custom-content">
        <h3>Integrations</h3>
        <div class="row">
            <a href="http://geokey.org.uk/" class="col-xs-6 col-sm-6 col-md-6 col-lg-3 ">
                <div class="thumbnail match-height">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-geokey.png" alt="GeoKey">
                    <h3>GeoKey</h3>
                    <p>Collect, share and discuss local knowledge. <br/><br/>GeoKey is an infrastructure for participatory mapping.</p>
                </div>
            </a>
             <a href="https://crowdcrafting.org/" class="col-xs-6 col-sm-6 col-md-6 col-lg-3 ">
                <div class="thumbnail match-height">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-crowdcrafting.png" alt="Crowd Crafting">
                    <h3>Crowdcrafting</h3>
                    <p>Crowdcrafting was born at a hackathon in Cape Town, South Africa in 2011. It is a free and open source alternative to existing citizen science platforms.
                    </p>
                </div>
            </a>
            <a href="http://www.geotagx.org/" class="col-xs-6 col-sm-6 col-md-6 col-lg-3 ">
                <div class="thumbnail match-height">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-geotagx.png" alt="GeoTag-X">
                    <h3>GeoTag-X</h3>
                    <p>Help disaster relief efforts on the ground to plan a response by asking volunteers to analyse photos taken in disaster-affected areas</p>
                </div>
            </a>
            <a href="https://cartodb.com" class="col-xs-6 col-sm-6 col-md-6 col-lg-3">
                <div class="thumbnail match-height">
                    <img class="img-responsive " src="{#SITE_ROOT#}/images/cc-cartodb.png" alt="carto DB">
                    <h3>CartoDB</h3>
                    <p>MAP YOUR WORLD\'S DATA: CartoDB is the easiest way to map and analyze your location data </p>
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
        //$total_entries_24 = ($row["ttl24"] == null ? 0 : $row["ttl24"]);
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
      //  $html .= '<span class="badge">' . $total_entries_24 . ' entries in the last 24 hours </span>';
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