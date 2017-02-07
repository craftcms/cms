<?php
/**
 * Craft bootstrap file.
 *
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\services\Config;

// Setup
// -----------------------------------------------------------------------------

// Validate the app type
if (!isset($appType) || ($appType !== 'web' && $appType !== 'console')) {
    throw new Exception('$appType must be set to "web" or "console".');
}

$findConfig = function($constName, $argName) {
    if (defined($constName)) {
        return realpath(constant($constName));
    }

    if (!empty($_SERVER['argv'])) {
        foreach ($_SERVER['argv'] as $key => $arg) {
            if (strpos($arg, "--{$argName}=") !== false) {
                $parts = explode('=', $arg);
                $value = $parts[1];
                unset($_SERVER['argv'][$key]);

                return $value;
            }
        }
    }

    return null;
};

$createFolder = function($path) {
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

$ensureFolderIsReadable = function($path, $writableToo = false) {
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

// Set the vendor path. By default assume that it's 4 levels up from here
$vendorPath = $findConfig('CRAFT_VENDOR_PATH', 'vendorPath') ?: dirname(__DIR__, 3);

// Set the base directory path that contains config/, storage/, etc. By default assume that it's up a level from vendor/.
$basePath = $findConfig('CRAFT_BASE_PATH', 'basePath') ?: dirname($vendorPath);

// By default the remaining directories will be in the base directory
$configPath = $findConfig('CRAFT_CONFIG_PATH', 'configPath') ?: $basePath.'/config';
$contentMigrationsPath = $findConfig('CRAFT_CONTENT_MIGRATIONS_PATH', 'contentMigrationsPath') ?: $basePath.'/migrations';
$pluginsPath = $findConfig('CRAFT_PLUGINS_PATH', 'pluginsPath') ?: $basePath.'/plugins';
$storagePath = $findConfig('CRAFT_STORAGE_PATH', 'storagePath') ?: $basePath.'/storage';
$templatesPath = $findConfig('CRAFT_TEMPLATES_PATH', 'templatesPath') ?: $basePath.'/templates';
$translationsPath = $findConfig('CRAFT_TRANSLATIONS_PATH', 'translationsPath') ?: $basePath.'/translations';

// Set the environment
$environment = $findConfig('CRAFT_ENVIRONMENT', 'env') ?: ($_SERVER['SERVER_NAME'] ?? null);

// Validate the paths
// -----------------------------------------------------------------------------

// Validate permissions on config/ and storage/
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

// Create the storage/runtime/ folder if it doesn't already exist
$createFolder($storagePath.'/runtime');
$ensureFolderIsReadable($storagePath.'/runtime', true);

// Create the storage/logs/ folder if it doesn't already exist
$createFolder($storagePath.'/logs');
$ensureFolderIsReadable($storagePath.'/logs', true);

// Log errors to storage/logs/phperrors.log
ini_set('log_errors', 1);
ini_set('error_log', $storagePath.'/logs/phperrors.log');
error_reporting(E_ALL);

// Determine if Craft is running in Dev Mode
// -----------------------------------------------------------------------------

// Initialize the Config service
$configService = new Config();
$configService->env = $environment;
$configService->configDir = $configPath;
$configService->appDefaultsDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'defaults';

// We need to special case devMode in the config because YII_DEBUG has to be set as early as possible.
if ($appType === 'console') {
    $devMode = true;
} else {
    $devMode = $configService->get('devMode');
}

if ($devMode) {
    ini_set('display_errors', 1);
    defined('YII_DEBUG') || define('YII_DEBUG', true);
    defined('YII_ENV') || define('YII_ENV', 'dev');
} else {
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
$cmsPath = $vendorPath.'/craftcms/cms';
$libPath = $cmsPath.'/lib';
$srcPath = $cmsPath.'/src';
require $vendorPath.'/yiisoft/yii2/Yii.php';
require $srcPath.'/Craft.php';

// Set aliases
Craft::setAlias('@lib', $libPath);
Craft::setAlias('@craft', $srcPath);
Craft::setAlias('@config', $configPath);
Craft::setAlias('@contentMigrations', $contentMigrationsPath);
Craft::setAlias('@plugins', $pluginsPath);
Craft::setAlias('@storage', $storagePath);
Craft::setAlias('@templates', $templatesPath);
Craft::setAlias('@translations', $translationsPath);

// Load the config
$components = [
    'config' => $configService,
];

if (defined('CRAFT_SITE') || defined('CRAFT_LOCALE')) {
    $components['sites'] = [
        'currentSite' => defined('CRAFT_SITE') ? CRAFT_SITE : CRAFT_LOCALE,
    ];
}

$config = ArrayHelper::merge(
    [
        'vendorPath' => $vendorPath,
        'env' => $environment,
        'components' => $components,
    ],
    require $srcPath.'/config/main.php',
    require $srcPath.'/config/common.php',
    require $srcPath.'/config/'.$appType.'.php',
    $configService->getConfigSettings(Config::CATEGORY_APP)
);

// Initialize the application
$class = 'craft\\'.$appType.'\\Application';
/** @var $app craft\web\Application|craft\console\Application */
$app = new $class($config);

if ($appType === 'web') {
    // See if the resource base path exists and is writable
    $resourceBasePath = Craft::getAlias($app->config->get('resourceBasePath'));
    @FileHelper::createDirectory($resourceBasePath);

    if (!is_dir($resourceBasePath) || !FileHelper::isWritable($resourceBasePath)) {
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
