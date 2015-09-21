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

    if ($auth->isLoggedIn()) {
        $vals['userprojects'] = '<div class="ecplus-userprojects"><h3>My projects</h3>';

        $prjs = EcProject::getUserProjects($auth->getEcUserId());
        $count = count($prjs);

        //create dom for list of user projects
        $html = '<div class="row">';
        $html .= '<div class="col-md-6 col-md-offset-3">';
        $html .= '<div class="panel panel-default">';
        $html .= '<div class="panel-heading">';
        $html .= '<h3 class="panel-title">My Projects</h3>';
        $html .= '</div>';
        $html .= '<div class="panel-body">';
        $html .= '<div class="my-projects-list list-group ">';

        $hasProjects = false;
        for ($i = 0; $i < $count; $i++) {

            if (!$hasProjects) {
                $hasProjects = true;
            }

            //project metadata
            $href = $SITE_ROOT . '/' . $prjs[$i]["name"];
            $project_name = $prjs[$i]["name"];
            $total_entries = $prjs[$i]["ttl"];
            $total_entries_24 = $prjs[$i]["ttl24"];
            $project_image = $prjs[$i]["image"];
            $project_desc = $prjs[$i]["description"];

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
            $html .= '<div class="clearfix"></div>';
            $html .= '<div class="project-badge-counters">';
            $html .= '<span class="badge">' . $total_entries . ' total entries</span>';
            $html .= '<span class="badge">' . $total_entries_24 . ' entries in the last 24 hours </span>';
            $html .= '</div>';
            $html .= '</a>';
        }

        // if the user has no projects, inform them
        if (!$hasProjects) {
            $html .= '<p>You currently have no Projects. <a href="createProject.html">Create one now</a>.</p>';
        }

        $html .= '</div></div></div></div></div>';

        $vals['userprojects'] = $html;

    }

    echo applyTemplate("my-projects.html", "index.html", $vals);
}