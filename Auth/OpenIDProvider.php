<?php
require_once "openid.php";
require_once "ProviderTemplate.php";
//include google api files
require_once 'GooglePHPLibrary/Google_Client.php';
require_once 'GooglePHPLibrary/contrib/Google_Oauth2Service.php';

//log tool, Chrome Logger for PHP
require_once __DIR__ . "/../handlers/ChromePhp.php";

class OpenIDProvider extends AuthProvider {

    public $data = array();

    //google objects
    public $gClient;
    public $google_oauthV2;
    public $authUrl;

    public function __construct($url) {

        global $SITE_ROOT, $cfg;

        //if (isset($_SESSION["token"])) {
        //    ChromePhp::log($_SESSION["token"]);
        //}

        $this->openid = new LightOpenID("$SITE_ROOT/loginCallback");
        $this->openid->identity = array_key_exists("openid", $_SESSION) ? $_SESSION["openid"] : "";
        $this->openid->required = array('namePerson/first', 'namePerson/last', 'contact/email', 'contact/country/home', 'pref/language');

        $this->gClient = new Google_Client();
        $this->gClient->setApplicationName('Login to Epicollect+');
        $this->gClient->setClientId($cfg->settings['security']['google_client_id']);
        $this->gClient->setClientSecret($cfg->settings['security']['google_client_secret']);
        $this->gClient->setRedirectUri($cfg->settings['security']['google_redirect_url']);

        //auto or force: use force during development to always show the prompt
        //@see https://developers.google.com/accounts/docs/OAuth2WebServer
      //  $this->gClient->setApprovalPrompt("force");

        //Create a state token to prevent request forgery.
        // Store it in the session for later validation.
        if (!isset($_SESSION["state"])) {
            $state = md5(rand());
            $_SESSION["state"] = $state;
        }

        $this->gClient->setState($_SESSION["state"]);

        //$this->gClient->setDeveloperKey($this->google_developer_key);

        $this->google_oauthV2 = new Google_Oauth2Service($this->gClient);

        //For not logged in user, get google login url
        $this->authUrl = $this->gClient->createAuthUrl();

        if (!isset($_SESSION["Google_OAuth2_URL"])) {
            $_SESSION["Google_OAuth2_URL"] = $this->authUrl;
        }
    }

    function getType() {
        return "OPEN_ID";
    }

    //redirect user to Google for authentication
    public function requestLogin($callbackUrl, $firstLogin = false) {

        //use OpenID Connect (Google OAuth 2)
        header('Location: ' . $_SESSION["Google_OAuth2_URL"]);
        return false;
    }

    public function callback() {

        //With new OAuth2 we have a code sent from Google to access Google APIs
        if (isset($_GET['code']) && isset($_GET['state'])) {

            //check if "state" value is the same the server started the request with.
            //If it is, the request is legit, otherwise exit as someone is try to hack the authentication
            if ($_GET['state'] == $_SESSION['state']) {

                //exchange the code with a token and authenticate user with Google
                $this->gClient->authenticate($_GET['code']);
                $_SESSION['token'] = $this->gClient->getAccessToken();
                $this->gClient->setAccessToken($_SESSION['token']);

                //we have to populate the data array (Open ID) for backward compatibility
                //Get details from Google
                $this->user = $this->google_oauthV2->userinfo->get();
                $this->data = array();

                // we should be able to get the old openid_id from Google, but it is not working on the dev server?
                // @see https://developers.google.com/accounts/docs/OpenID#openid-connect
                $this->data["openid"] = $this->user['id'];
                $this->firstName = filter_var($this->user['given_name'], FILTER_SANITIZE_SPECIAL_CHARS);
                $this->lastName = filter_var($this->user['family_name'], FILTER_SANITIZE_SPECIAL_CHARS);
                $this->email = filter_var($this->user['email'], FILTER_SANITIZE_EMAIL);
                $this->language = filter_var($this->user['locale'], FILTER_SANITIZE_SPECIAL_CHARS);

                unset($_SESSION['state']);

                return true;
            } else {

                //state does not match, request was not from this server
                return false;
            }
        } else {
            return false;
        }
    }

    public function logout() {
    }

    public function setCredentialString($str) {
    }

    public function getDetails() {
    }

    public function getUserName() {
        return '';
    }

}

?>