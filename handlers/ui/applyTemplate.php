<?php

function applyTemplate($baseUri, $targetUri = false, $templateVars = array()) {
    global $db, $SITE_ROOT, $DIR, $auth, $CODE_VERSION, $BUILD, $cfg;

    $template = file_get_contents(sprintf('%shtml/%s', $DIR, trim($baseUri, '.')));

    $templateVars['SITE_ROOT'] = ltrim($SITE_ROOT, '\\');
    $templateVars['uid'] = md5($_SERVER['HTTP_HOST']);
    $templateVars['codeVersion'] = $CODE_VERSION;
    $templateVars['build'] = $BUILD;
    $templateVars['protocol'] = (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http');
    $templateVars['GA_ACCOUNT'] = $cfg->settings['misc']['ga_account'];
    // Is there a user logged in?

    $flashes = '';

    if (array_key_exists('flashes', $_SESSION) && is_array($_SESSION['flashes'])) {
        while ($flash = array_pop($_SESSION['flashes'])) {
            $flashes .= sprintf('<p class="flash %s">%s</p>', $flash["type"], $flash["msg"]);
        }
    }

    try {
        if (isset($db) && $db->connected && $auth && $auth->isLoggedIn()) {

            //TODO : remove update user unless user is local
            //if so put the user's name and a logout option in the login section
            $type = $auth->getProviderType();


            if ($auth->isServerManager()) {
                $logged_in_tmpl = '<li class="ecplus-username">Hi, ' . $auth->getUserNickname().':</li>';
                $logged_in_tmpl .= '<li><a href="{#SITE_ROOT#}/createProject.html">Create Project</a></li>';
                $logged_in_tmpl .= '<li><a href="{#SITE_ROOT#}/my-projects.html">My Projects</a></li>';
                $logged_in_tmpl .= '<li><a href="{#SITE_ROOT#}/logout">Sign out</a></li>';

                if($type == 'LOCAL') {
                    $logged_in_tmpl .= '<li><a href="{#SITE_ROOT#}/updateUser.html">Update User</a></li>';
                }
                else {
                    $logged_in_tmpl .= '<li><a href="{#SITE_ROOT#}/admin">Manage Server</a></li>';
                }

                $template = str_replace('{#loggedIn#}', $logged_in_tmpl , $template);

            } else {
                $template = str_replace('{#loggedIn#}', sprintf('Hi, %s (%s) | <a href="{#SITE_ROOT#}/createProject.html">Create Project</a> | <a href="{#SITE_ROOT#}/my-projects.html">My Projects</a> | <a href="{#SITE_ROOT#}/logout">Sign out</a>   ' . ($type == 'LOCAL' ? '| <a href="{#SITE_ROOT#}/updateUser.html">Update User</a>' : ''), $auth->getUserNickname(), $auth->getUserEmail()), $template);
            }
            $templateVars['userEmail'] = $auth->getUserEmail();
        } // else show the login link
        else {
            global $PUBLIC;
            $template = str_replace('{#loggedIn#}', '<li><a href="{#SITE_ROOT#}/login.php">Log in</a></li>'/* . ($PUBLIC ? ' or <a href="{#SITE_ROOT#}/register">register</a>' : '')*/, $template);
        }
        // work out breadcrumbs
        //$template = str_replace("{#breadcrumbs#}", '', $template);
    } catch (Exception $err) {
        unset($db);
        siteTest();
    }

    $script = "";
    $sections = array();
    if ($targetUri) {

        $fname = sprintf('%shtml/%s', $DIR, trim($targetUri, './'));
        if (file_exists($fname)) {
            $data = file_get_contents($fname);

            $fPos = 0;
            $iStart = 0;
            $iEnd = 0;
            $sEnd = 0;
            $id = '';

            while ($fPos <= strlen($data) && $fPos >= 0) {
                //echo "--";
                // find {{
                $iStart = strpos($data, '{{', $fPos);

                if ($iStart === false || $iStart < $fPos)
                    break;
                //echo $iStart;
                //get identifier (to }})
                $iEnd = strpos($data, '}}', $iStart);

                //echo $iEnd;
                $id = substr($data, $iStart + 2, ($iEnd - 2) - ($iStart));
                //find matching end {{/}}
                $sEnd = strpos($data, sprintf('{{/%s}}', $id), $iEnd);
                $sections[$id] = substr($data, $iEnd + 2, $sEnd - ($iEnd + 2));

                $fPos = $sEnd + strlen($id) + 3;

            }
        } else {
            $sections['script'] = '';
            $sections['main'] = '<h1>404 - page not found</h1>
<p>Sorry, the page you were looking for could not be found.</p>';
            header('HTTP/1.1 404 Page not found');
        }
        foreach (array_keys($sections) as $sec) {
            // do processing
            $template = str_replace(sprintf('{#%s#}', $sec), $sections[$sec], $template);
        }
        $template = str_replace('{#flashes#}', $flashes, $template);
    }
    if ($templateVars) {
        foreach ($templateVars as $sec => $cts) {
            // do processing
            try {
                $template = str_replace(sprintf('{#%s#}', $sec), $cts, $template);
            } catch (Exception $e) {
                echo 'Please grant privileges to admin user and refresh once done (see <a href="https://github.com/ImperialCollegeLondon/EpiCollectplus#step-4--set-up-mysql">installation instruction</a>) like so:';
                echo '<br/><br/>';
                echo "<i>GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE, CREATE TEMPORARY TABLES ON your_db_name_here.* TO 'user_goes_here';</i>";
                exit();
            }
        }
    }

    $template = preg_replace('/\{#[a-z0-9_]+#\}/i', '', $template);
    return $template;
}