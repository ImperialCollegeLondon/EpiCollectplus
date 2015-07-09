#License

EpiCollect+ is licenced under a [AGPLv3 Licence](http://opensource.org/licenses/AGPL-3.0). If you want to use a custom version of the server code, please fork this repository and upload your changes to GitHub to help the EpiCollect Community.

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
    RewriteCond %{REQUEST_URI} !^/dev/epicollectplus/(ec\/uploads|images|js|css)
    RewriteRule .* main.php
    
Or if you install in the server root:   

     AddDefaultCharset utf-8
     RewriteEngine on
     RewriteCond %{REQUEST_URI} !^/(ec\/uploads|images|js|css)
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
    CREATE DATABASE epicollect
    
    GRANT SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES ON epicollect.* TO <epicollect_user>


##Step 5 : Open your EpiCollect site

You will be asked for the details of your MySQL installation. Once these are entered EpiCollect will then ask you for an admin username and password to set up the database. When all the tests are passing open the site again and create the first user account, login and you're ready to [create your first project](http://www.epicollect.net/plus_Instructions/creating/default.html)


##Step 6: Add your Google API keys to use Google+ Sign-In

To use the Google Login, you need to open the file ec/epicollect.ini and add the following properties under `[security]`


- `google_client_id = <your google client id>`
- `google_client_secret = <your google client secret>`
- `google_redirect_url = <your.domain.com>/loginCallback/`


replacing the placeholders with your Google API details https://developers.google.com/+/web/api/rest/oauth

