<?php
/**
 * Craft bootstrap file.
 *
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\helpers\App;
use craft\services\Config;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Yii2Adapter\Container;
use CraftCms\Yii2Adapter\Log\Logger;
use Illuminate\Foundation\Application;
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

// Get the Laravel application instance
$app = Application::getInstance();

// Load the general config
// -----------------------------------------------------------------------------

$configService = new Config();
$configService->appType = $appType;
$configService->env = $app->environment();
$configService->configDir = $app->configPath();
$configService->appDefaultsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'defaults';
$generalConfig = app(GeneralConfig::class);

if (is_null($generalConfig)) {
    $generalConfig = GeneralConfig::create();
    app()->instance(GeneralConfig::class, $generalConfig);
}

// Log errors to storage/logs/phperrors.log or php://stderr
if (Env::parseBoolean('$CRAFT_LOG_PHP_ERRORS') !== false) {
    ini_set('log_errors', '1');

    if (App::isStreamLog()) {
        ini_set('error_log', 'php://stderr');
    } else {
        ini_set('error_log', $app->storagePath('logs/phperrors.log'));
    }
}

$errorLevel = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED;
error_reporting($errorLevel);

// Determine if Craft is running in Dev Mode
// -----------------------------------------------------------------------------
if ($app->hasDebugModeEnabled()) {
    ini_set('display_errors', '1');
    defined('YII_DEBUG') || define('YII_DEBUG', true);
    defined('YII_ENV') || define('YII_ENV', 'dev');
} else {
    ini_set('display_errors', '0');
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
$cmsPath = dirname(__DIR__);
$libPath = $cmsPath . DIRECTORY_SEPARATOR . 'lib';
$srcPath = $cmsPath . DIRECTORY_SEPARATOR . 'legacy';
require_once $libPath . DIRECTORY_SEPARATOR . 'yii2' . DIRECTORY_SEPARATOR . 'Yii.php';
require_once $srcPath . DIRECTORY_SEPARATOR . 'Craft.php';

// Set aliases
Craft::setAlias('@lib', $libPath);
Craft::setAlias('@craft', $srcPath); // same as @app, but needed for the `help` command
Craft::setAlias('@dotenv', $app->environmentFilePath());
Craft::setAlias('@config', $app->configPath());
Craft::setAlias('@contentMigrations', Env::get('CRAFT_CONTENT_MIGRATIONS_PATH', $app->basePath('migrations')));
Craft::setAlias('@storage', defined('CRAFT_STORAGE_PATH') ? CRAFT_STORAGE_PATH : $app->storagePath());
Craft::setAlias('@templates', CRAFT_TEMPLATES_PATH); // Defined in Yii2ServiceProvider
Craft::setAlias('@translations', Env::get('CRAFT_TRANSLATIONS_PATH', $app->langPath()));
Craft::setAlias('@tests', Env::get('CRAFT_TESTS_PATH', $app->basePath('tests')));
Craft::setAlias('@assetBundles', Arr::join([
    dirname(__DIR__, 2),
    'resources',
    'build',
    'legacy',
], DIRECTORY_SEPARATOR));

// Load the config
$config = Arr::merge(
    [
        'vendorPath' => $app->basePath('vendor'),
        'env' => $app->environment(),
        'components' => [
            'config' => $configService,
        ],
    ],
    require $srcPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php',
    require $srcPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . "app.{$appType}.php"
);

$localConfig = Arr::merge(
    $app->get('config')->get('craft.app', []),
    $app->get('config')->get("app.{$appType}", []),
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

Craft::$container = new Container();
Craft::setLogger(new Logger());

// Initialize the application
/** @var \craft\web\Application|craft\console\Application $app */
$app = Craft::createObject($config);

// If there was a max_input_vars error, kill the request before we start processing it with incomplete data
if ($lastError && strpos($lastError['message'], 'max_input_vars') !== false) {
    throw new ErrorException($lastError['message']);
}

return $app;
