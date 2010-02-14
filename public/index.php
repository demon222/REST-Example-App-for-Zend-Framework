<?php

/*
// for some quick profiling, see bottom of page
$t1 = microtime(true);
$m1 = memory_get_usage();
*/

// Set the initial include_path. You may need to change this to ensure that 
// Zend Framework is in the include_path; additionally, for performance 
// reasons, it's best to move this to your web server configuration or php.ini 
// for production.
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(dirname(__FILE__) . '/../library'),
    get_include_path(),
)));

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV, 
    APPLICATION_PATH . '/configs/application.ini'
);
$application->bootstrap();
$application->run();

/*
// continuation of quick profiling
$t2 = microtime(true);
$m2 = memory_get_usage();
var_dump(array('time (s)' => $t2 - $t1, 'memory total (MB)' => (int) ($m2 / 1000), 'memory diff (MB)' => (int) (($m2 - $m1) / 1000)));
*/