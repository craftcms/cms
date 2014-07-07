<?php

// Make sure this is PHP 5.3 or later
// ----------------------------------------------------------------------

if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
{
	exit('@@@appName@@@ requires PHP 5.3.0 or later, but you&rsquo;re running '.PHP_VERSION.'. Please talk to your host/IT department about upgrading PHP or your server.');
}


// omitScriptNameInUrls and usePathInfo tests
// ----------------------------------------------------------------------

if ((isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/testScriptNameRedirect')
	|| (isset($_SERVER['QUERY_STRING']) && strpos($_SERVER['QUERY_STRING'], 'testScriptNameRedirect') !== false))
{
	exit('success');
}

if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/testPathInfo')
{
	exit('success');
}


// Load and run Craft
// ----------------------------------------------------------------------

$app = require 'bootstrap.php';
$app->run();
