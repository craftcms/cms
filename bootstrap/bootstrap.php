<?php
/**
 * Craft bootstrap file.
 *
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\helpers\ArrayHelper;
use craft\services\Config;
use yii\base\ErrorException;

// Get the last error at the earliest opportunity, so we can catch max_input_vars errors
// see https://stackoverflow.com/a/21601349/1688568
$lastError = error_get_last();

// Setup
// -----------------------------------------------------------------------------

// Validate the app type
if (!isset($appType) || ($appType !== 'web' && $appType !== 'console')) {
    throw new Exception('$appType must be set to "web" or "console".');
}

$findConfig = function($constName, $argName) {
    if (defined($constName)) {
        return constant($constName);
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

$findConfigPath = function($constName, $argName) use ($findConfig) {
    $path = $findConfig($constName, $argName);
    return $path ? realpath($path) : null;
};

$createFolder = function($path) {
    // Code borrowed from Io...
    if (!is_dir($path)) {
        $oldumask = umask(0);

        if (!mkdir($path, 0755, true)) {
            // Set a 503 response header so things like Varnish won't cache a bad page.
            http_response_code(503);
            exit('Tried to create a folder at ' . $path . ', but could not.' . PHP_EOL);
        }

        // Because setting permission with mkdir is a crapshoot.
        chmod($path, 0755);
        umask($oldumask);
    }
};

$ensureFolderIsReadable = function($path, $writableToo = false) {
    $realPath = realpath($path);

    // !@file_exists('/.') is a workaround for the terrible is_executable()
    if ($realPath === false || !is_dir($realPath) || !@file_exists($realPath . DIRECTORY_SEPARATOR . '.')) {
        // Set a 503 response header so things like Varnish won't cache a bad page.
        http_response_code(503);
        exit(($realPath !== false ? $realPath : $path) . ' doesn\'t exist or isn\'t writable by PHP. Please fix that.' . PHP_EOL);
    }

    if ($writableToo && !is_writable($realPath)) {
        // Set a 503 response header so things like Varnish won't cache a bad page.
        http_response_code(503);
        exit($realPath . ' isn\'t writable by PHP. Please fix that.' . PHP_EOL);
    }
};

// Determine the paths
// -----------------------------------------------------------------------------

// Set the vendor path. By default assume that it's 4 levels up from here
$vendorPath = $findConfigPath('CRAFT_VENDOR_PATH', 'vendorPath') ?: dirname(__DIR__, 3);

// Set the "project root" path that contains config/, storage/, etc. By default assume that it's up a level from vendor/.
$rootPath = $findConfigPath('CRAFT_BASE_PATH', 'basePath') ?: dirname($vendorPath);

// By default the remaining directories will be in the base directory
$configPath = $findConfigPath('CRAFT_CONFIG_PATH', 'configPath') ?: $rootPath . DIRECTORY_SEPARATOR . 'config';
$contentMigrationsPath = $findConfigPath('CRAFT_CONTENT_MIGRATIONS_PATH', 'contentMigrationsPath') ?: $rootPath . DIRECTORY_SEPARATOR . 'migrations';
$storagePath = $findConfigPath('CRAFT_STORAGE_PATH', 'storagePath') ?: $rootPath . DIRECTORY_SEPARATOR . 'storage';
$templatesPath = $findConfigPath('CRAFT_TEMPLATES_PATH', 'templatesPath') ?: $rootPath . DIRECTORY_SEPARATOR . 'templates';
$translationsPath = $findConfigPath('CRAFT_TRANSLATIONS_PATH', 'translationsPath') ?: $rootPath . DIRECTORY_SEPARATOR . 'translations';

// Set the environment
$environment = $findConfig('CRAFT_ENVIRONMENT', 'env') ?: ($_SERVER['SERVER_NAME'] ?? null);

// Validate the paths
// -----------------------------------------------------------------------------

if (!defined('CRAFT_LICENSE_KEY')) {
    // Validate permissions on the license key file path (default config/) and storage/
    if (defined('CRAFT_LICENSE_KEY_PATH')) {
        $licensePath = dirname(CRAFT_LICENSE_KEY_PATH);
        $licenseKeyName = basename(CRAFT_LICENSE_KEY_PATH);
    } else {
        $licensePath = $configPath;
        $licenseKeyName = 'license.key';
    }

    // Make sure the license folder exists.
    if (!is_dir($licensePath) && !file_exists($licensePath)) {
        $createFolder($licensePath);
    }

    $ensureFolderIsReadable($licensePath);

    if ($appType === 'web') {
        $licenseFullPath = $licensePath . DIRECTORY_SEPARATOR . $licenseKeyName;

        // If the license key doesn't exist yet, make sure the folder is readable and we can write a temp one.
        if (!file_exists($licenseFullPath)) {
            // Try and write out a temp license key file.
            @file_put_contents($licenseFullPath, 'temp');

            // See if it worked.
            if (!file_exists($licenseFullPath) || (file_exists($licenseFullPath) && file_get_contents($licenseFullPath) !== 'temp')) {
                exit($licensePath . ' isn\'t writable by PHP. Please fix that.' . PHP_EOL);
            }
        }
    }
}


$ensureFolderIsReadable($storagePath, true);

// Create the storage/runtime/ folder if it doesn't already exist
$createFolder($storagePath . DIRECTORY_SEPARATOR . 'runtime');
$ensureFolderIsReadable($storagePath . DIRECTORY_SEPARATOR . 'runtime', true);

// Create the storage/logs/ folder if it doesn't already exist
$createFolder($storagePath . DIRECTORY_SEPARATOR . 'logs');
$ensureFolderIsReadable($storagePath . DIRECTORY_SEPARATOR . 'logs', true);

// Log errors to storage/logs/phperrors.log
if (!defined('CRAFT_LOG_PHP_ERRORS') || CRAFT_LOG_PHP_ERRORS) {
    ini_set('log_errors', 1);
    ini_set('error_log', $storagePath . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'phperrors.log');
}

error_reporting(E_ALL);

// Load the general config
// -----------------------------------------------------------------------------

$configService = new Config();
$configService->env = $environment;
$configService->configDir = $configPath;
$configService->appDefaultsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'defaults';
$generalConfig = $configService->getConfigFromFile('general');

// Determine if Craft is running in Dev Mode
// -----------------------------------------------------------------------------

if ($appType === 'console') {
    $devMode = true;
} else {
    $devMode = ArrayHelper::getValue($generalConfig, 'devMode', false);
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
$cmsPath = $vendorPath . DIRECTORY_SEPARATOR . 'craftcms' . DIRECTORY_SEPARATOR . 'cms';
$libPath = $cmsPath . DIRECTORY_SEPARATOR . 'lib';
$srcPath = $cmsPath . DIRECTORY_SEPARATOR . 'src';
require $libPath . DIRECTORY_SEPARATOR . 'yii2' . DIRECTORY_SEPARATOR . 'Yii.php';
require $srcPath . DIRECTORY_SEPARATOR . 'Craft.php';

// Move Yii's autoloader to the end (Composer's is faster when optimized)
spl_autoload_unregister(['Yii', 'autoload']);
spl_autoload_register(['Yii', 'autoload'], true, false);

// Set aliases
Craft::setAlias('@root', $rootPath);
Craft::setAlias('@lib', $libPath);
Craft::setAlias('@craft', $srcPath);
Craft::setAlias('@config', $configPath);
Craft::setAlias('@contentMigrations', $contentMigrationsPath);
Craft::setAlias('@storage', $storagePath);
Craft::setAlias('@templates', $templatesPath);
Craft::setAlias('@translations', $translationsPath);

// Set any custom aliases
$customAliases = $generalConfig['aliases'] ?? $generalConfig['environmentVariables'] ?? null;
if (is_array($customAliases)) {
    foreach ($customAliases as $name => $value) {
        if (is_string($value)) {
            Craft::setAlias($name, $value);
        }
    }
}

// Load the config
$components = [
    'config' => $configService,
];

$config = ArrayHelper::merge(
    [
        'vendorPath' => $vendorPath,
        'env' => $environment,
        'components' => $components,
    ],
    require $srcPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php',
    require $srcPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . "app.{$appType}.php",
    $configService->getConfigFromFile('app'),
    $configService->getConfigFromFile("app.{$appType}")
);

if (defined('CRAFT_SITE') || defined('CRAFT_LOCALE')) {
    $config['components']['sites']['currentSite'] = defined('CRAFT_SITE') ? CRAFT_SITE : CRAFT_LOCALE;
}

// Initialize the application
/** @var \craft\web\Application|craft\console\Application $app */
$app = Craft::createObject($config);

// If there was a max_input_vars error, kill the request before we start processing it with incomplete data
if ($lastError && strpos($lastError['message'], 'max_input_vars') !== false) {
    throw new ErrorException($lastError['message']);
}

return $app;
