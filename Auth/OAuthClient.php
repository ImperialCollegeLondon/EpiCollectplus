<?php
    //http://code.google.com/apis/accounts/docs/OAuth_ref.html#RequestToken

    class OAuth {
        public $requestTokenURL;
        public $requestTokenParams; // Additional parameters to include with the request Token request
        public $authoriseTokenURL;
        public $getAccessTokenURL;
        
        public $appName;
        public $callbackUrl;
        
        public $signatureMethod = "HMAC-SHA1"; //RSA-SHA1 or HMAC-SHA1
        
        private $consumerKey = "anonymous";
        private $secret;
        
        
        
        function __contstruct($config)
        {
        
        }
        
        function getNonce()
        {
            $m = round((mt_rand() / mt_getrandmax()) * 0xffffffff, 0);
            $n = round((mt_rand() / mt_getrandmax()) * 0xffffffff, 0);
            return sprintf("%x", $m) . sprintf("%x", $n);
        }
        
        function getTimestamp()
        {
            $dat = new DateTime();
            return $dat->getTimestamp();
        }
        
        function signRequest ($method, $url, $params)
        {
            if(ksort($params))
            {
                $baseString = "$method&" . rawurlencode($url);
                $qString = "";
                foreach(array_keys($params) as $key)
                {
                    
                    $qString = "$qString&{$key}%3D{$params[$key]}%26";
                }
                $qString=rtrim($qString, "%26");
                
                $baseString = "$baseString&$qString";
                
                $sig = "";
                
                switch($signatureMethod)
                {
                    case "HMAC-SHA1":
                        $sig = hash_hmac('SHA1', $baseString, $this->secret);
                        break;
                    case "RSA-SHA1":
                        
                        break;
                    default:
                        break;
                }
                
                return $sig;
            }
        }
        
        function getRequestToken()
        {
            $params = $this->requestTokenParams;//get extra params
            $params["oauth_callback"] = $this->callbackUrl;
            $params["oauth_consumer_key"] = $this->consumerKey;
            $params["oauth_nonce"] = $this->getNonce();
            $params["oauth_signature_method"] = $this->signatureMethod;
            $params["oauth_timestamp"] = $this->getTimestamp();
            $params["oauth_version"] = "1.0";
            $params["xoauth_displayname"] = $this->appName;
            
            
        }
    }

?>