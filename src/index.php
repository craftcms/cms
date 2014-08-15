<?php

// Make sure this is PHP 5.3 or later
// -----------------------------------------------------------------------------

if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
{
	exit('@@@appName@@@ requires PHP 5.3.0 or later, but you&rsquo;re running '.PHP_VERSION.'. Please talk to your host/IT department about upgrading PHP or your server.');
}

// Check for this early because Craft uses it before the requirements checker gets a chance to run.
if (!extension_loaded('mbstring') || (extension_loaded('mbstring') && ini_get('mbstring.func_overload') == 1))
{
	exit('@@@appName@@@ requires the <a href="http://php.net/manual/en/book.mbstring.php" target="_blank">PHP multibyte string</a> extension in order to run. Please talk to your host/IT department about enabling it on your server.');
}

// omitScriptNameInUrls and usePathInfo tests
// -----------------------------------------------------------------------------

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
// -----------------------------------------------------------------------------

$app = require 'bootstrap.php';
$app->run();
