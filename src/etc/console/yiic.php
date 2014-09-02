<?php

$configPath = dirname(__FILE__).'/../config/console.php';

$frontConfigPath = false;

// See if --configPath is specified from the command line.  If so, use that.
if (isset($_SERVER['argv']))
{
	foreach ($argv as $key => $arg)
	{
		if (strpos($arg, '--configPath=') !== false)
		{
			$parts = explode('=', $arg);
			$frontConfigPath = rtrim($parts[1], '/').'/';
			unset($_SERVER['argv'][$key]);
			break;
		}
	}
}

defined('CRAFT_BASE_PATH')         || define('CRAFT_BASE_PATH', str_replace('\\', '/', realpath(dirname(__FILE__).'/../../../')).'/');
defined('CRAFT_APP_PATH')          || define('CRAFT_APP_PATH',          CRAFT_BASE_PATH.'app/');
if ($frontConfigPath)
{
	defined('CRAFT_CONFIG_PATH')   || define('CRAFT_CONFIG_PATH',       $frontConfigPath);
}
else
{
	defined('CRAFT_CONFIG_PATH')   || define('CRAFT_CONFIG_PATH',       CRAFT_BASE_PATH.'config/');
}

defined('CRAFT_PLUGINS_PATH')      || define('CRAFT_PLUGINS_PATH',      CRAFT_BASE_PATH.'plugins/');
defined('CRAFT_STORAGE_PATH')      || define('CRAFT_STORAGE_PATH',      CRAFT_BASE_PATH.'storage/');
defined('CRAFT_TEMPLATES_PATH')    || define('CRAFT_TEMPLATES_PATH',    CRAFT_BASE_PATH.'templates/');
defined('CRAFT_TRANSLATIONS_PATH') || define('CRAFT_TRANSLATIONS_PATH', CRAFT_BASE_PATH.'translations/');
defined('CRAFT_ENVIRONMENT')       || define('CRAFT_ENVIRONMENT',       'console');

/**
 * Yii command line script file configured for Craft.
 */

// fix for fcgi
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

require_once dirname(__FILE__).'/../../framework/yii.php';
require_once CRAFT_APP_PATH.'Craft.php';
require_once CRAFT_APP_PATH.'Info.php';

// Guzzle makes use of these PHP constants, but they aren't actually defined in some compilations of PHP.
// See http://it.blog.adclick.pt/php/fixing-php-notice-use-of-undefined-constant-curlopt_timeout_ms-assumed-curlopt_timeout_ms/
defined('CURLOPT_TIMEOUT_MS')        || define('CURLOPT_TIMEOUT_MS',        155);
defined('CURLOPT_CONNECTTIMEOUT_MS') || define('CURLOPT_CONNECTTIMEOUT_MS', 156);

// Load up Composer's files
require CRAFT_APP_PATH.'vendor/autoload.php';

// Disable the PHP include path
Yii::$enableIncludePath = false;

require_once(dirname(__FILE__).'/ConsoleApp.php');

// Because CHttpRequest is one of those stupid Yii files that has multiple classes defined in it.
require_once(CRAFT_APP_PATH.'framework/web/CHttpRequest.php');

Yii::setPathOfAlias('app', CRAFT_APP_PATH);
Yii::setPathOfAlias('plugins', CRAFT_PLUGINS_PATH);

$app = Yii::createApplication('Craft\ConsoleApp', $configPath);
$app->commandRunner->addCommands(Craft\Craft::getPathOfAlias('application.consolecommands.*'));

$app->run();
