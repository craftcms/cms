<?php
/**
 * Craft bootstrap file.
 *
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\helpers\App;
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

$findConfigPath = function($cliName, $envName) use ($createFolder) {
    $path = App::cliOption($cliName, true) ?? App::env($envName);
    if (!$path) {
        return null;
    }
    $createFolder($path);
    return realpath($path);
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
$vendorPath = $findConfigPath('--vendorPath', 'CRAFT_VENDOR_PATH') ?? dirname(__DIR__, 3);

// Set the "project root" path that contains config/, storage/, etc. By default assume that it's up a level from vendor/.
$rootPath = $findConfigPath('--basePath', 'CRAFT_BASE_PATH') ?? dirname($vendorPath);

// By default the remaining directories will be in the base directory
$dotenvPath = $findConfigPath('--dotenvPath', 'CRAFT_DOTENV_PATH') ?? "$rootPath/.env";
$configPath = $findConfigPath('--configPath', 'CRAFT_CONFIG_PATH') ?? "$rootPath/config";
$contentMigrationsPath = $findConfigPath('--contentMigrationsPath', 'CRAFT_CONTENT_MIGRATIONS_PATH') ?? "$rootPath/migrations";
$storagePath = $findConfigPath('--storagePath', 'CRAFT_STORAGE_PATH') ?? "$rootPath/storage";
$templatesPath = $findConfigPath('--templatesPath', 'CRAFT_TEMPLATES_PATH') ?? "$rootPath/templates";
$translationsPath = $findConfigPath('--translationsPath', 'CRAFT_TRANSLATIONS_PATH') ?? "$rootPath/translations";
$testsPath = $findConfigPath('--testsPath', 'CRAFT_TESTS_PATH') ?? "$rootPath/tests";

// Set the environment
$environment = App::cliOption('--env', true)
    ?? App::env('CRAFT_ENVIRONMENT')
    ?? App::env('ENVIRONMENT')
    ?? $_SERVER['SERVER_NAME']
    ?? null;

// Validate the paths
// -----------------------------------------------------------------------------

if (!App::env('CRAFT_LICENSE_KEY') && !App::isEphemeral()) {
    $licenseKeyPath = App::env('CRAFT_LICENSE_KEY_PATH');

    // Validate permissions on the license key file path (default config/) and storage/
    if ($licenseKeyPath) {
        $licensePath = dirname($licenseKeyPath);
        $licenseKeyName = basename($licenseKeyPath);
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
            if (!file_exists($licenseFullPath) || file_get_contents($licenseFullPath) !== 'temp') {
                // Set a 503 response header so things like Varnish won't cache a bad page.
                http_response_code(503);
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
if (!App::isStreamLog()) {
    $createFolder($storagePath . DIRECTORY_SEPARATOR . 'logs');
    $ensureFolderIsReadable($storagePath . DIRECTORY_SEPARATOR . 'logs', true);
}

// Log errors to storage/logs/phperrors.log or php://stderr
if (App::parseBooleanEnv('$CRAFT_LOG_PHP_ERRORS') !== false) {
    ini_set('log_errors', '1');

    if (App::isStreamLog()) {
        ini_set('error_log', 'php://stderr');
    } else {
        ini_set('error_log', $storagePath . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'phperrors.log');
    }
}

$errorLevel = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED;
error_reporting($errorLevel);

// Load the general config
// -----------------------------------------------------------------------------

$configService = new Config();
$configService->env = $environment;
$configService->configDir = $configPath;
$configService->appDefaultsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'defaults';
$generalConfig = $configService->getConfigFromFile('general');

// Determine if Craft is running in Dev Mode
// -----------------------------------------------------------------------------

$devMode = App::env('CRAFT_DEV_MODE') ?? $generalConfig['devMode'] ?? false;

if ($devMode) {
    ini_set('display_errors', '1');
    defined('YII_DEBUG') || define('YII_DEBUG', true);
    defined('YII_ENV') || define('YII_ENV', 'dev');
} else {
    ini_set('display_errors', '0');
    defined('YII_DEBUG') || define('YII_DEBUG', false);
    defined('YII_ENV') || define('YII_ENV', 'prod');

    // don't let PHP warnings & notices halt execution
    error_reporting($errorLevel & ~E_WARNING & ~E_NOTICE);
}

// Load the Composer dependencies and the app
// -----------------------------------------------------------------------------

// Guzzle makes use of these PHP constants, but they aren't actually defined in some compilations of PHP
// See: http://it.blog.adclick.pt/php/fixing-php-notice-use-of-undefined-constant-curlopt_timeout_ms-assumed-curlopt_timeout_ms/
defined('CURLOPT_TIMEOUT_MS') || define('CURLOPT_TIMEOUT_MS', 155);
defined('CURLOPT_CONNECTTIMEOUT_MS') || define('CURLOPT_CONNECTTIMEOUT_MS', 156);

// Load the files
$cmsPath = dirname(__DIR__);
$libPath = $cmsPath . DIRECTORY_SEPARATOR . 'lib';
$srcPath = $cmsPath . DIRECTORY_SEPARATOR . 'src';
require $libPath . DIRECTORY_SEPARATOR . 'yii2' . DIRECTORY_SEPARATOR . 'Yii.php';
require $srcPath . DIRECTORY_SEPARATOR . 'Craft.php';

// Set aliases
Craft::setAlias('@craftcms', $cmsPath);
Craft::setAlias('@root', $rootPath);
Craft::setAlias('@lib', $libPath);
Craft::setAlias('@craft', $srcPath);
Craft::setAlias('@appicons', $srcPath . DIRECTORY_SEPARATOR . 'icons');
Craft::setAlias('@dotenv', $dotenvPath);
Craft::setAlias('@config', $configPath);
Craft::setAlias('@contentMigrations', $contentMigrationsPath);
Craft::setAlias('@storage', $storagePath);
Craft::setAlias('@templates', $templatesPath);
Craft::setAlias('@translations', $translationsPath);
Craft::setAlias('@tests', $testsPath);

$webUrl = App::env('CRAFT_WEB_URL');
if ($webUrl) {
    Craft::setAlias('@web', $webUrl);
}

$webRoot = App::env('CRAFT_WEB_ROOT');
if ($webRoot) {
    Craft::setAlias('@webroot', $webRoot);
}

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

// Initialize the application
/** @var \craft\web\Application|craft\console\Application $app */
$app = Craft::createObject($config);

// If there was a max_input_vars error, kill the request before we start processing it with incomplete data
if ($lastError && strpos($lastError['message'], 'max_input_vars') !== false) {
    throw new ErrorException($lastError['message']);
}

return $app;
