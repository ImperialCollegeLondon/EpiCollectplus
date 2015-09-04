<?php
function loginHandler() {
    $cb_url = '';
    header('Cache-Control: no-cache, must-revalidate');

    global $auth, $url, $db;

    if (!preg_match('/login.php/', $url)) {
        $cb_url = $url;
    }

    if (!$auth)
        $auth = new AuthManager();

    if (array_key_exists('provider', $_GET)) {
        $_SESSION['provider'] = $_GET['provider'];
        $frm = $auth->requestlogin($cb_url, $_SESSION['provider']);
    } elseif (array_key_exists('provider', $_SESSION)) {
        $frm = $auth->requestlogin($cb_url, $_SESSION['provider']);
    } else {
        $frm = $auth->requestlogin($cb_url);
    }


    echo applyTemplate('./base.html', './loginbase.html', array('form' => $frm, 'breadcrumbs' => ' > Login '));
}

function loginCallback() {
    header('Cache-Control: no-cache, must-revalidate');

    global $auth, $cfg, $db;
    $provider = getValIfExists($_POST, 'provider');
    if (!$provider)
        $provider = getValIfExists($_SESSION, 'provider');
    else {
        $_SESSION['provider'] = $provider;
    }

    $db = new dbConnection();
    if (!$auth)
        $auth = new AuthManager();
    $auth->callback($provider);
}

function logoutHandler() {
    header('Cache-Control: no-cache, must-revalidate');

    global $auth, $SITE_ROOT;
    $server = trim($_SERVER['HTTP_HOST'], '/');
    $root = trim($SITE_ROOT, '/');
    if ($auth) {
        $auth->logout();
        header(sprintf('location: http://%s/%s/', $server, $root));
        return;
    } else {
        echo 'No Auth';
    }
}
