<?php
/**
 * Craft bootstrap file.
 *
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

use craft\app\dates\DateTime;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\Io;

// Setup
// -----------------------------------------------------------------------------

// Determine what type of application we're loading
if (!isset($appType) || ($appType !== 'web' && $appType !== 'console')) {
    $appType = 'web';
}

$getArg = function ($param, $unset = true) {
    if (isset($_SERVER['argv'])) {
        foreach ($_SERVER['argv'] as $key => $arg) {
            if (strpos($arg, "--{$param}=") !== false) {
                $parts = explode('=', $arg);
                $value = $parts[1];

                if ($unset) {
                    unset($_SERVER['argv'][$key]);
                }

                return $value;
            }
        }
    }

    return null;
};

$createFolder = function ($path) {
    // Code borrowed from Io...
    if (!is_dir($path)) {
        $oldumask = umask(0);

        if (!mkdir($path, 0755, true)) {
            // Set a 503 response header so things like Varnish won't cache a bad page.
            http_response_code(503);

            exit('Tried to create a folder at '.$path.', but could not.');
        }

        // Because setting permission with mkdir is a crapshoot.
        chmod($path, 0755);
        umask($oldumask);
    }
};

$ensureFolderIsReadable = function ($path, $writableToo = false) {
    $realPath = realpath($path);

    // !@file_exists('/.') is a workaround for the terrible is_executable()
    if ($realPath === false || !is_dir($realPath) || !@file_exists($realPath.'/.')) {
        // Set a 503 response header so things like Varnish won't cache a bad page.
        http_response_code(503);

        exit(($realPath !== false ? $realPath : $path).' doesn\'t exist or isn\'t writable by PHP. Please fix that.');
    }

    if ($writableToo) {
        if (!is_writable($realPath)) {
            // Set a 503 response header so things like Varnish won't cache a bad page.
            http_response_code(503);

            exit($realPath.' isn\'t writable by PHP. Please fix that.');
        }
    }
};

// Determine the paths
// -----------------------------------------------------------------------------

// App folder, we are already in you.
$appPath = __DIR__;

// By default the craft/ folder will be one level up
$craftPath = realpath(defined('CRAFT_BASE_PATH') ? CRAFT_BASE_PATH : $getArg('basePath') ?: dirname($appPath));

// By default the remaining folders will be in craft/
$configPath = realpath(defined('CRAFT_CONFIG_PATH') ? CRAFT_CONFIG_PATH : $getArg('configPath') ?: $craftPath.'/config');
$contentMigrationsPath = realpath(defined('CRAFT_CONTENT_MIGRATIONS_PATH') ? CRAFT_CONTENT_MIGRATIONS_PATH : $getArg('contentMigrationsPath') ?: $craftPath.'/migrations');
$pluginsPath = realpath(defined('CRAFT_PLUGINS_PATH') ? CRAFT_PLUGINS_PATH : $getArg('pluginsPath') ?: $craftPath.'/plugins');
$storagePath = realpath(defined('CRAFT_STORAGE_PATH') ? CRAFT_STORAGE_PATH : $getArg('storagePath') ?: $craftPath.'/storage');
$templatesPath = realpath(defined('CRAFT_TEMPLATES_PATH') ? CRAFT_TEMPLATES_PATH : $getArg('templatesPath') ?: $craftPath.'/templates');
$translationsPath = realpath(defined('CRAFT_TRANSLATIONS_PATH') ? CRAFT_TRANSLATIONS_PATH : $getArg('translationsPath') ?: $craftPath.'/translations');

// Validate the paths
// -----------------------------------------------------------------------------

// Validate permissions on craft/config/ and craft/storage/
$ensureFolderIsReadable($configPath);

if ($appType === 'web') {
    $licensePath = $configPath.'/license.key';

    // If license.key doesn't exist yet, make sure the config folder is readable and we can write a temp one.
    if (!file_exists($licensePath)) {
        // Make sure config is at least readable.
        $ensureFolderIsReadable($configPath);

        // Try and write out a temp license.key file.
        @file_put_contents($licensePath, 'temp');

        // See if it worked.
        if (!file_exists($licensePath) || (file_exists($licensePath) && file_get_contents($licensePath) !== 'temp')) {
            exit($licensePath.' isn\'t writable by PHP. Please fix that.');
        }
    }
}

$ensureFolderIsReadable($storagePath, true);

// Create the craft/storage/runtime/ folder if it doesn't already exist
$createFolder($storagePath.'/runtime');
$ensureFolderIsReadable($storagePath.'/runtime', true);

// Create the craft/storage/logs/ folder if it doesn't already exist
$createFolder($storagePath.'/logs');
$ensureFolderIsReadable($storagePath.'/logs', true);

// Log errors to craft/storage/logs/phperrors.log
ini_set('log_errors', 1);
ini_set('error_log', $storagePath.'/logs/phperrors.log');

// Determine if Craft is running in Dev Mode
// -----------------------------------------------------------------------------


// We need to special case devMode in the config because YII_DEBUG has to be set as early as possible.
if ($appType === 'console') {
    // Set the environment
    defined('CRAFT_ENVIRONMENT') || define('CRAFT_ENVIRONMENT', '');

    $devMode = true;
} else {
    // Set the environment
    defined('CRAFT_ENVIRONMENT') || define('CRAFT_ENVIRONMENT', $_SERVER['SERVER_NAME']);

    $devMode = false;
    $generalConfigPath = $configPath.'/general.php';

    if (file_exists($generalConfigPath)) {
        $generalConfig = require $generalConfigPath;

        if (is_array($generalConfig)) {
            // Normalize it to a multi-environment config
            if (!array_key_exists('*', $generalConfig)) {
                $generalConfig = ['*' => $generalConfig];
            }

            // Loop through all of the environment configs, figuring out what the final word is on Dev Mode
            foreach ($generalConfig as $env => $envConfig) {
                if ($env == '*' || strpos(CRAFT_ENVIRONMENT, $env) !== false) {
                    if (isset($envConfig['devMode'])) {
                        $devMode = $envConfig['devMode'];
                    }
                }
            }
        }
    }
}

if ($devMode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    defined('YII_DEBUG') || define('YII_DEBUG', true);
    defined('YII_ENV') || define('YII_ENV', 'dev');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    defined('YII_DEBUG') || define('YII_DEBUG', false);
    defined('YII_ENV') || define('YII_ENV', 'prod');
}

// Load the Composer dependencies and the app
// -----------------------------------------------------------------------------

// Guzzle makes use of these PHP constants, but they aren't actually defined in some compilations of PHP
// See: http://it.blog.adclick.pt/php/fixing-php-notice-use-of-undefined-constant-curlopt_timeout_ms-assumed-curlopt_timeout_ms/
defined('CURLOPT_TIMEOUT_MS') || define('CURLOPT_TIMEOUT_MS', 155);
defined('CURLOPT_CONNECTTIMEOUT_MS') || define('CURLOPT_CONNECTTIMEOUT_MS', 156);

// Load the files
require $appPath.'/vendor/autoload.php';
require $appPath.'/vendor/yiisoft/yii2/Yii.php';
require $appPath.'/Craft.php';

// Set aliases
Craft::setAlias('@craft/app', $appPath);
Craft::setAlias('@config', $configPath);
Craft::setAlias('@contentMigrations', $contentMigrationsPath);
Craft::setAlias('@plugins', $pluginsPath);
Craft::setAlias('@storage', $storagePath);
Craft::setAlias('@templates', $templatesPath);
Craft::setAlias('@translations', $translationsPath);

// Load the config
$config = ArrayHelper::merge(
    require $appPath.'/config/main.php',
    require $appPath.'/config/common.php',
    require $appPath.'/config/'.$appType.'.php'
);

// Set the current site
if (defined('CRAFT_SITE') || defined('CRAFT_LOCALE')) {
    $config = ArrayHelper::merge($config, [
        'components' => [
            'sites' => [
                'currentSite' => defined('CRAFT_SITE') ? CRAFT_SITE : CRAFT_LOCALE,
            ],
        ],
    ]);
}

// Allow sites to make custom changes to this
if (file_exists($configPath.'/app.php')) {
    $config = ArrayHelper::merge($config, require $configPath.'/app.php');
}

$config['releaseDate'] = new DateTime('@'.$config['releaseDate']);

// Initialize the application
$class = 'craft\\app\\'.$appType.'\\Application';
/** @var $app craft\app\web\Application|craft\app\console\Application */
$app = new $class($config);

if ($appType === 'web') {
    // See if the resource base path exists and is writable
    $resourceBasePath = Craft::getAlias($app->config->get('resourceBasePath'));
    Io::ensureFolderExists($resourceBasePath, true);

    if (!Io::folderExists($resourceBasePath) || !Io::isWritable($resourceBasePath)) {
        exit($resourceBasePath.' doesn\'t exist or isn\'t writable by PHP. Please fix that.');
    }

    // See if we should enable the Debug module
    $session = $app->getSession();

    if ($session->getHasSessionId() || $session->getIsActive()) {
        $isCpRequest = $app->getRequest()->getIsCpRequest();

        if (($isCpRequest && $session->get('enableDebugToolbarForCp')) || (!$isCpRequest && $session->get('enableDebugToolbarForSite'))) {
            /** @var yii\debug\Module $module */
            $module = $app->getModule('debug');
            $module->bootstrap($app);
            \yii\debug\Module::setYiiLogo("data:image/svg+xml;utf8,<svg width='30px' height='30px' viewBox='0 0 30 30' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><g fill='#DA5B47'><path d='M21.5549104,8.56198524 C21.6709104,8.6498314 21.7812181,8.74275447 21.8889104,8.83706217 L23.6315258,7.47013909 L23.6858335,7.39952371 C23.4189104,7.12998524 23.132295,6.87506217 22.8224489,6.64075447 C18.8236796,3.62275447 12.7813719,4.88598524 9.32737193,9.46275447 C5.87321809,14.0393699 6.31475655,20.195216 10.3135258,23.2138314 C13.578295,25.6779852 18.2047565,25.287216 21.6732181,22.5699852 L21.6693719,22.5630622 L20.0107565,21.2621391 C17.4407565,22.9144468 14.252295,23.0333699 11.9458335,21.2927545 C8.87414116,18.9746006 8.53506424,14.245216 11.188295,10.7293699 C13.8419873,7.21398524 18.4832181,6.24367755 21.5549104,8.56198524'></path></g></svg>");
        }
    }
}

return $app;
