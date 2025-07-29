<?php
/**
 * Craft bootstrap file.
 *
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\services\Config;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Env;
use yii\base\ErrorException;

// Get the last error at the earliest opportunity, so we can catch max_input_vars errors
// see https://stackoverflow.com/a/21601349/1688568
$lastError = error_get_last();

// Validate the app type
// -----------------------------------------------------------------------------

if (!isset($appType) || ($appType !== 'web' && $appType !== 'console')) {
    throw new Exception('$appType must be set to "web" or "console".');
}

// Determine the paths
// -----------------------------------------------------------------------------

$findConfig = function(string $cliName, string $envName) use ($appType) {
    if ($appType === 'console') {
        $value = App::cliOption($cliName, true);
        if ($value !== null) {
            return $value;
        }
    }

    return Env::get($envName);
};

// Set the vendor path. By default assume that it's 4 levels up from here
$vendorPath = FileHelper::normalizePath($findConfig('--vendorPath', 'CRAFT_VENDOR_PATH') ?? dirname(__DIR__, 3));

// Set the "project root" path that contains config/, storage/, etc. By default assume that it's up a level from vendor/.
$rootPath = FileHelper::normalizePath($findConfig('--basePath', 'CRAFT_BASE_PATH') ?? dirname($vendorPath));

// By default the remaining files/directories will be in the base directory
$dotenvPath = FileHelper::normalizePath($findConfig('--dotenvPath', 'CRAFT_DOTENV_PATH') ?? "$rootPath/.env");
$configPath = FileHelper::normalizePath($findConfig('--configPath', 'CRAFT_CONFIG_PATH') ?? "$rootPath/config");
$contentMigrationsPath = FileHelper::normalizePath($findConfig('--contentMigrationsPath', 'CRAFT_CONTENT_MIGRATIONS_PATH') ?? "$rootPath/migrations");
$storagePath = FileHelper::normalizePath($findConfig('--storagePath', 'CRAFT_STORAGE_PATH') ?? "$rootPath/storage");
$templatesPath = FileHelper::normalizePath($findConfig('--templatesPath', 'CRAFT_TEMPLATES_PATH') ?? "$rootPath/templates");
$translationsPath = FileHelper::normalizePath($findConfig('--translationsPath', 'CRAFT_TRANSLATIONS_PATH') ?? "$rootPath/translations");
$testsPath = FileHelper::normalizePath($findConfig('--testsPath', 'CRAFT_TESTS_PATH') ?? "$rootPath/tests");

// Set the environment
// -----------------------------------------------------------------------------

$environment = $findConfig('--env', 'CRAFT_ENVIRONMENT') ?? Env::get('ENVIRONMENT') ?? $_SERVER['SERVER_NAME'] ?? null;

// Load the general config
// -----------------------------------------------------------------------------

$configService = new Config();
$configService->appType = $appType;
$configService->env = $environment;
$configService->configDir = $configPath;
$configService->appDefaultsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'defaults';
$generalConfig = app(GeneralConfig::class);

// Validation
// -----------------------------------------------------------------------------

$createFolder = function($path) use ($generalConfig) {
    FileHelper::createDirectory($path, $generalConfig->defaultDirMode ?? 0775);
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

// Validate the paths
// -----------------------------------------------------------------------------

if (!Env::get('CRAFT_LICENSE_KEY') && !App::isEphemeral()) {
    $licenseKeyPath = Env::get('CRAFT_LICENSE_KEY_PATH');

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

$createFolder($storagePath);
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

// Determine if Craft is running in Dev Mode
// -----------------------------------------------------------------------------

$devMode = Env::get('CRAFT_DEV_MODE') ?? $generalConfig->devMode;

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
require_once $libPath . DIRECTORY_SEPARATOR . 'yii2' . DIRECTORY_SEPARATOR . 'Yii.php';
require_once $srcPath . DIRECTORY_SEPARATOR . 'Craft.php';

// Set aliases
Craft::setAlias('@craftcms', $cmsPath);
Craft::setAlias('@root', $rootPath);
Craft::setAlias('@lib', $libPath);
Craft::setAlias('@craft', $srcPath); // same as @app, but needed for the `help` command
Craft::setAlias('@dotenv', app()->environmentFilePath());
Craft::setAlias('@config', $configPath);
Craft::setAlias('@contentMigrations', $contentMigrationsPath);
Craft::setAlias('@storage', $storagePath);
Craft::setAlias('@templates', $templatesPath);
Craft::setAlias('@translations', $translationsPath);
Craft::setAlias('@tests', $testsPath);

// Set any custom aliases
foreach ($generalConfig->aliases as $name => $value) {
    if (is_string($value)) {
        Craft::setAlias($name, $value);
    }
}

$webUrl = Env::get('CRAFT_WEB_URL');
if ($webUrl) {
    Craft::setAlias('@web', $webUrl);
}

$webRoot = Env::get('CRAFT_WEB_ROOT');
if ($webRoot) {
    Craft::setAlias('@webroot', $webRoot);
}

// Load the config
$components = [
    'config' => $configService,
];

$config = Arr::merge(
    [
        'vendorPath' => $vendorPath,
        'env' => $environment,
        'components' => $components,
    ],
    require $srcPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php',
    require $srcPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . "app.{$appType}.php"
);

$localConfig = Arr::merge(
    \Illuminate\Support\Facades\Config::get('craft.app', []),
    \Illuminate\Support\Facades\Config::get("app.{$appType}", []),
);

$safeMode = Env::get('CRAFT_SAFE_MODE') ?? $generalConfig->safeMode;

if ($safeMode) {
    Arr::forget($localConfig, 'bootstrap');
    Arr::forget($localConfig, 'components');
    Arr::forget($localConfig, 'extensions');
    Arr::forget($localConfig, 'container');
}

$config = Arr::merge($config, $localConfig);

if (function_exists('craft_modify_app_config')) {
    craft_modify_app_config($config, $appType);
}

Craft::$container = new \CraftCms\Yii2Adapter\Container();
Craft::setLogger(new \CraftCms\Yii2Adapter\Log\Logger());

// Initialize the application
/** @var \craft\web\Application|craft\console\Application $app */
$app = Craft::createObject($config);

// If there was a max_input_vars error, kill the request before we start processing it with incomplete data
if ($lastError && strpos($lastError['message'], 'max_input_vars') !== false) {
    throw new ErrorException($lastError['message']);
}

return $app;
