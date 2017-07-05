# Notice
## This repo is deprecated and not maintained anymore. 
We are working on a new version called Epicollect5 which is currently available as a public beta at https://five.epicollect.net.

### Any issue or bug on this repository will not be fixed. Install at your own risk. The most stable branch is `development`. 
### You have been warned

#License

EpiCollect+ is licenced under a [AGPLv3 Licence](http://opensource.org/licenses/AGPL-3.0). If you want to use a custom version of the server code, please fork this repository and upload your changes to GitHub to help the EpiCollect Community.

# Mobile Apps

<a href="https://play.google.com/store/apps/details?id=uk.ac.imperial.epicollectplus.html5&hl=en_GB">Android</a>
<a href="https://itunes.apple.com/us/app/epicollect+/id999309173?ls=1&mt=8">iOS</a>

# Acknowledgements
EpiCollect+ uses [Glyphicons](http://glyphicons.com/), [jQuery](http://jquery.com), [jQuery UI](http://jqueryui.com) and [Raphael](http://raphaeljs.com/)

#Server Installation

The following instructions will assume that EpiCollect is being installed in the root directory of a website (e.g. http://plus.epicollect.net). Where the instructions will differ if 

To get the server running on your own server you need the following pre-requisites.

- Apahce 2 with mod_rewrite or IIS with [URLRewrite](http://www.iis.net/downloads/microsoft/url-rewrite)
- HTTPS support is strongly recommended
- PHP 5.3+ with mysqli extension enabled
- MySQL 5.1+

[XAMPP](http://www.apachefriends.org/en/xampp.html) provides a usefull method of getting all of these in one easy-to-install package.

##Step 1 : Download or clone the repo

A zip of the current code is available to download, but we recommend using git to clone the repository; It's easier to keep up to date with the latest updates.

    git clone http://github.com/ImperialCollegeLondon/EpiCollectplus.git

##Step 2 : URL Redirection

EpiCollect makes nice, friendly URLS for all your projects by redirecting requests through a routing PHP script. This does mean we need to configure your webserver to send these requests to the correct place. If you're using Apache as your webserver then add a .htaccess file to your EpiCollect directory.

Let's say you install epicollect on http://localhost/dev/epicollectplus

    AddDefaultCharset utf-8
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/dev/epicollectplus-dev/(ec\/uploads|images|js|css|png)
    RewriteCond %{REQUEST_FILENAME} !\.(css|js|png|jpg)$
    RewriteRule .* main.php
    
Or if you install in the server root:   

     AddDefaultCharset utf-8
     RewriteEngine on
     RewriteCond %{REQUEST_URI} !^/(ec\/uploads|images|js|css|png)
     RewriteRule .* main.php

If you're using IIS (tip: just don't) then you'll need to configure URLRewrite.

1. in _Features View_ open URLRewrite
1. _Add Rule(s)..._ (top of the right hand panel)
1. Choose _Blank Rule_ and _OK_
1. Give it a sensible name like EpiCollect... this is so you know what it's for
1. Set the pattern to _.*_ if using a whole site or the path to the EpiCollect folder followed by _.*_ (e.g. for the url http://www.example.com/epicollect the rule woule be _epicollect/.*_
1. _Apply_ (top of the right panel again)

##Step 3 : check file permissions

There is a folder called _ec_ within the the EpiCollect directory that the Web user will require read/write access to. For linux you could run
    
    chmod -R 777 ec
    
On windows you'll need to right-click on the folder in Explorer then open _Properties > Security > Edit. Locate the IIS user (usually _machinename\IUSR) and grant write and modify access).

##Step 4 : Set up MySQL

You'll need 2 accounts for the setup an admin account with full access and another user account. To assigne the correct permissions epicollect user account you will need to use the following. If you have only just set up MySQL you may well need to follow [these instructions](http://dev.mysql.com/doc/refman/5.0/en/default-privileges.html). 
    
    #assuming you've used
    CREATE DATABASE epicollectplus
    
    GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE, CREATE TEMPORARY TABLES ON epicollectplus.* TO '<user_goes_here>'


##Step 5 : Open your EpiCollect site

You will be asked for the details of your MySQL installation.

If it is a fresh new install, usually you can use `root` as both your user name and password. You could use `root`for the installation, but it is advised for security purposes to create an admin user yourself and delete the root user. The server is where you installed MySQL (if it is locally on your machine, just enter localhost) and set the port accordingly 

Once these are entered EpiCollect will then ask you for an admin username and password to set up the database. Again, you could use `root`for the installation, but it is advised for security to create an user yourself. When all the tests are passing open the site again and create the first user account, login and you're ready to [create your first project](http://www.epicollect.net/plus_Instructions/creating/default.html)

More about MySQL securityy can be found at http://goo.gl/ExTaym


##Step 6: Add your Google API keys to use Google+ Sign-In

To use the Google Login, you need to open the file ec/epicollect.ini and add the following properties under `[security]`


- `google_client_id = <your google client id>`
- `google_client_secret = <your google client secret>`
- `google_redirect_url = <your.domain.com>/loginCallback/`


replacing the placeholders with your Google API details https://developers.google.com/+/web/api/rest/oauth

If you do not add these parameters, the option to login using Google Plus Sign will not be shown

#### Notes for developers only

`development` branch is the branch we currently push to production

`relayout` branch is an experimental branch featuring a bit of re-design and it is the most up to date, so the best candidate for a fork, as it will be merged to `development` soon

All the other branches are just legacy branches we keep for reference and we might delete in the future

Google API PHP library: we are using an old repo, you can find the code into `Auth/GooglePHPLibrary/` folder
