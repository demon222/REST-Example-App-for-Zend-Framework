This is a modified version of the Zend Framework Quickstart application.

It has been modified to be RESTful.

Along the way a couple other things have been modified for improvement.


Credits:
- jara of github for his rest routing example at 
git://github.com/jara/zf-restful-app-demo.git and walk-through at 
http://avnetlabs.com/zend-framework/restful-controllers-with-zend-framework
- Rob Taylor (roboncode.com) for his ZendCon 2009 Rest Presentation and slides

Below is a somewhat improved content of the original README.


This is the Quick Start application for Zend Framework. 

Full instructions and explanations may be found at:

    http://framework.zend.com/docs/quickstart

In order to use the application, you will first need to perform a few
steps.

First, you will need a copy of Zend Framework. If you do not have one
already, download it from here:

    http://framework.zend.com/download/latest

The easiest way to make it work with your application is to symlink the
library/Zend/ subdirectory into the library/ subdirectory of this
application.

If you have Zend Framework already, please ensure it's on the
include_path.

Next, setup the database and permissions to the data/ subdirectory. You
may do this as follows, from a command line:

    % php scripts/load.sqlite.php --withdata
    % chmod -R a+rwX data

Once you have, you will also need to point your web server to the
application. Using apache, you could add a vhost as follows:

    <VirtualHost *:80>
        ServerAdmin matthew@zend.com
        DocumentRoot <PATH_TO_QUICKSTART>/public
        
        SetEnv development

        <Directory <PATH_TO_QUICKSTART>/public>
            DirectoryIndex index.php
            AllowOverride All
            Order allow,deny
            Allow from all
        </Directory>
    </VirtualHost>

You _must_ substitute the correct path to this directory for
<PATH_TO_QUICKSTART>.

Finally, point your browser to http://localhost/ to see the
application in action.
