<?php
# Logging in with Google accounts requires setting special identity, so this example shows how to do it.
require 'openid.php';
try {
    $openid = new LightOpenID;
    $openid->required = array('namePerson/first', 'contact/email', 'contact/country/home', 'pref/language');
    $openid->optional = array('namePerson/first');
    if(!$openid->mode) {
        $openid->identity = "https://www.google.com/accounts/o8/id?id=AItOawmtcBQpBaFyFEhKbDhjR0c_h8Fmxw3jQoM";
        if(!$openid->validate()) {
            $openid->identity = 'https://www.google.com/accounts/o8/id';
            header('Location: ' . $openid->authUrl());
        }
?>
<form action="?login" method="post">
    <button>Login with Google</button>
</form>
<?php
    } elseif($openid->mode == 'cancel') {
        echo 'User has canceled authentication!';
    } else {
        
        echo $openid->mode . ' User ' . ($openid->validate() ? $openid->identity . ' has ' : 'has not ') . 'logged in.';
        print_r($openid);
    }
    
} catch(ErrorException $e) {
    echo $e->getMessage();
}
