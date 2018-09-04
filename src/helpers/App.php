<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\config\DbConfig;
use craft\db\Command;
use craft\db\Connection;
use craft\db\mysql\Schema as MysqlSchema;
use craft\db\pgsql\Schema as PgsqlSchema;
use craft\elements\User;
use craft\errors\MissingComponentException;
use craft\log\FileTarget;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\mail\transportadapters\Sendmail;
use craft\models\MailSettings;
use craft\mutex\FileMutex;
use craft\web\AssetManager;
use craft\web\Request as WebRequest;
use craft\web\Session;
use craft\web\User as WebUser;
use craft\web\View;
use yii\caching\FileCache;
use yii\helpers\Inflector;
use yii\log\Dispatcher;
use yii\log\Logger;

/**
 * App helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class App
{
    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    private static $_iconv;

    // Public Methods
    // =========================================================================

    /**
     * Returns an array of all known Craft editions’ IDs.
     *
     * @return array All the known Craft editions’ IDs.
     */
    public static function editions(): array
    {
        return [Craft::Solo, Craft::Pro];
    }

    /**
     * Returns the name of the given Craft edition.
     *
     * @param int $edition An edition’s ID.
     * @return string The edition’s name.
     */
    public static function editionName(int $edition): string
    {
        return ($edition == Craft::Pro) ? 'Pro' : 'Solo';
    }

    /**
     * Returns whether an edition is valid.
     *
     * @param mixed $edition An edition’s ID (or is it?)
     * @return bool Whether $edition is a valid edition ID.
     */
    public static function isValidEdition($edition): bool
    {
        if ($edition === false || $edition === null) {
            return false;
        }

        return (is_numeric((int)$edition) && in_array((int)$edition, static::editions(), true));
    }

    /**
     * Returns the PHP version, without the distribution info.
     *
     * @return string
     */
    public static function phpVersion(): string
    {
        return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
    }

    /**
     * Returns a PHP extension version, without the distribution info.
     *
     * @param string $name The extension name
     * @return string
     */
    public static function extensionVersion(string $name): string
    {
        $version = phpversion($name);
        return static::normalizeVersion($version);
    }

    /**
     * Removes distribution info from a version
     *
     * @param string $version
     * @return string
     */
    public static function normalizeVersion(string $version): string
    {
        return preg_replace('/^([^\s~+-]+).*$/', '$1', $version);
    }

    /**
     * Retrieves a bool PHP config setting and normalizes it to an actual bool.
     *
     * @param string $var The PHP config setting to retrieve.
     * @return bool Whether it is set to the php.ini equivelant of `true`.
     */
    public static function phpConfigValueAsBool(string $var): bool
    {
        $value = ini_get($var);

        // Supposedly “On” values will always be normalized to '1' but who can trust PHP...

        /** @noinspection TypeUnsafeComparisonInspection */
        return ($value == 1 || strtolower($value) === 'on');
    }

    /**
     * Returns whether the server has a valid version of the iconv extension installed.
     *
     * @return bool
     */
    public static function checkForValidIconv(): bool
    {
        if (self::$_iconv !== null) {
            return self::$_iconv;
        }

        // Check if iconv is installed. Note we can't just use HTMLPurifier_Encoder::iconvAvailable() because they
        // don't consider iconv "installed" if it's there but "unusable".
        return self::$_iconv = (function_exists('iconv') && \HTMLPurifier_Encoder::testIconvTruncateBug() === \HTMLPurifier_Encoder::ICONV_OK);
    }

    /**
     * Returns a humanized class name.
     *
     * @param string $class
     * @return string
     */
    public static function humanizeClass(string $class): string
    {
        $classParts = explode('\\', $class);

        return strtolower(Inflector::camel2words(array_pop($classParts)));
    }

    /**
     * Sets PHP’s memory limit to the maximum specified by the
     * [[\craft\config\GeneralConfig::phpMaxMemoryLimit|phpMaxMemoryLimit]] config setting, and gives
     * the script an unlimited amount of time to execute.
     */
    public static function maxPowerCaptain()
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->phpMaxMemoryLimit !== '') {
            @ini_set('memory_limit', $generalConfig->phpMaxMemoryLimit);
        } else {
            // Grab. It. All.
            @ini_set('memory_limit', -1);
        }

        // Try to disable the max execution time
        @set_time_limit(0);
    }

    /**
     * @return string|null
     */
    public static function licenseKey()
    {
        if (defined('CRAFT_LICENSE_KEY')) {
            $licenseKey = CRAFT_LICENSE_KEY;
        } else {
            $path = Craft::$app->getPath()->getLicenseKeyPath();

            // Check to see if the key exists
            if (!is_file($path)) {
                return null;
            }

            $licenseKey = file_get_contents($path);
        }

        $licenseKey = trim(preg_replace('/[\r\n]+/', '', $licenseKey));

        if (strlen($licenseKey) !== 250) {
            return null;
        }

        return $licenseKey;
    }

    /**
     * Returns the backtrace as a string (omitting the final frame where this method was called).
     *
     * @param int $limit The max number of stack frames to be included (0 means no limit)
     */
    public static function backtrace(int $limit = 0): string
    {
        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit ? $limit + 1 : 0);
        array_shift($frames);
        $trace = '';

        foreach ($frames as $i => $frame) {
            $trace .= ($i !== 0 ? "\n" : '') .
                '#' . $i . ' ' .
                ($frame['class'] ?? '') .
                ($frame['type'] ?? '') .
                ($frame['function'] ?? '') . '()' .
                (isset($frame['file']) ? ' called at [' . ($frame['file'] ?? '') . ':' . ($frame['line'] ?? '') . ']' : '');
        }

        return $trace;
    }

    // App component configs
    // -------------------------------------------------------------------------

    /**
     * Returns the `assetManager` component config for web requests.
     *
     * @return array
     */
    public static function assetManagerConfig(): array
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        return [
            'class' => AssetManager::class,
            'basePath' => $generalConfig->resourceBasePath,
            'baseUrl' => $generalConfig->resourceBaseUrl,
            'fileMode' => $generalConfig->defaultFileMode,
            'dirMode' => $generalConfig->defaultDirMode,
            'appendTimestamp' => true,
        ];
    }

    /**
     * Returns the `cache` component config.
     *
     * @return array
     */
    public static function cacheConfig(): array
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        return [
            'class' => FileCache::class,
            'cachePath' => Craft::$app->getPath()->getCachePath(),
            'fileMode' => $generalConfig->defaultFileMode,
            'dirMode' => $generalConfig->defaultDirMode,
            'defaultDuration' => $generalConfig->cacheDuration,
        ];
    }

    /**
     * Returns the `db` component config.
     *
     * @param DbConfig|null $dbConfig The database config settings
     * @return array
     */
    public static function dbConfig(DbConfig $dbConfig = null): array
    {
        if ($dbConfig === null) {
            $dbConfig = Craft::$app->getConfig()->getDb();
        }

        if ($dbConfig->driver === DbConfig::DRIVER_MYSQL) {
            $schemaConfig = [
                'class' => MysqlSchema::class,
            ];
        } else {
            $schemaConfig = [
                'class' => PgsqlSchema::class,
                'defaultSchema' => $dbConfig->schema,
            ];
        }

        return [
            'class' => Connection::class,
            'driverName' => $dbConfig->driver,
            'dsn' => $dbConfig->dsn,
            'username' => $dbConfig->user,
            'password' => $dbConfig->password,
            'charset' => $dbConfig->charset,
            'tablePrefix' => $dbConfig->tablePrefix,
            'schemaMap' => [
                $dbConfig->driver => $schemaConfig,
            ],
            'commandMap' => [
                $dbConfig->driver => Command::class,
            ],
            'attributes' => $dbConfig->attributes,
            'enableSchemaCache' => !YII_DEBUG,
        ];
    }

    /**
     * Returns the `mailer` component config.
     *
     * @param MailSettings|null $settings The system mail settings
     * @return array
     */
    public static function mailerConfig(MailSettings $settings = null): array
    {
        if ($settings === null) {
            $settings = Craft::$app->getSystemSettings()->getEmailSettings();
        }

        try {
            $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);
        } catch (MissingComponentException $e) {
            // Fallback to the PHP mailer
            $adapter = new Sendmail();
        }

        return [
            'class' => Mailer::class,
            'messageClass' => Message::class,
            'from' => [$settings->fromEmail => $settings->fromName],
            'template' => $settings->template,
            'transport' => $adapter->defineTransport(),
        ];
    }

    /**
     * Returns the `mutex` component config.
     *
     * @return array
     */
    public static function mutexConfig(): array
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        return [
            'class' => FileMutex::class,
            'fileMode' => $generalConfig->defaultFileMode,
            'dirMode' => $generalConfig->defaultDirMode,
        ];
    }

    /**
     * Returns the `log` component config.
     *
     * @return array|null
     */
    public static function logConfig()
    {
        // Only log console requests and web requests that aren't getAuthTimeout requests
        $isConsoleRequest = Craft::$app->getRequest()->getIsConsoleRequest();
        if (!$isConsoleRequest && !Craft::$app->getUser()->enableSession) {
            return null;
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $target = [
            'class' => FileTarget::class,
            'fileMode' => $generalConfig->defaultFileMode,
            'dirMode' => $generalConfig->defaultDirMode,
        ];

        if ($isConsoleRequest) {
            $target['logFile'] = '@storage/logs/console.log';
        } else {
            $target['logFile'] = '@storage/logs/web.log';

            // Only log errors and warnings, unless Craft is running in Dev Mode or it's being installed/updated
            if (!YII_DEBUG && Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
                $target['levels'] = Logger::LEVEL_ERROR | Logger::LEVEL_WARNING;
            }
        }

        return [
            'class' => Dispatcher::class,
            'targets' => [
                $target,
            ]
        ];
    }

    /**
     * Returns the `session` component config for web requests.
     *
     * @return array
     */
    public static function sessionConfig(): array
    {
        $stateKeyPrefix = md5('Craft.' . Session::class . '.' . Craft::$app->id);

        return [
            'class' => Session::class,
            'flashParam' => $stateKeyPrefix . '__flash',
            'authAccessParam' => $stateKeyPrefix . '__auth_access',
            'name' => Craft::$app->getConfig()->getGeneral()->phpSessionName,
            'cookieParams' => Craft::cookieConfig(),
        ];
    }

    /**
     * Returns the `user` component config for web requests.
     *
     * @return array
     */
    public static function userConfig(): array
    {
        $configService = Craft::$app->getConfig();
        $generalConfig = $configService->getGeneral();
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest() || $request->getIsSiteRequest()) {
            $loginUrl = UrlHelper::siteUrl($generalConfig->getLoginPath());
        } else {
            $loginUrl = UrlHelper::cpUrl('login');
        }

        $stateKeyPrefix = md5('Craft.' . WebUser::class . '.' . Craft::$app->id);

        return [
            'class' => WebUser::class,
            'identityClass' => User::class,
            'enableAutoLogin' => true,
            'autoRenewCookie' => true,
            'loginUrl' => $loginUrl,
            'authTimeout' => $generalConfig->userSessionDuration ?: null,
            'identityCookie' => Craft::cookieConfig(['name' => $stateKeyPrefix . '_identity']),
            'usernameCookie' => Craft::cookieConfig(['name' => $stateKeyPrefix . '_username']),
            'idParam' => $stateKeyPrefix . '__id',
            'authTimeoutParam' => $stateKeyPrefix . '__expire',
            'absoluteAuthTimeoutParam' => $stateKeyPrefix . '__absoluteExpire',
            'returnUrlParam' => $stateKeyPrefix . '__returnUrl',
        ];
    }

    /**
     * Returns the `view` component config.
     *
     * @return array
     */
    public static function viewConfig(): array
    {
        $config = [
            'class' => View::class,
        ];

        $request = Craft::$app->getRequest();

        if ($request->getIsCpRequest()) {
            $headers = $request->getHeaders();
            $config['registeredAssetBundles'] = explode(',', $headers->get('X-Registered-Asset-Bundles', ''));
            $config['registeredJsFiles'] = explode(',', $headers->get('X-Registered-Js-Files', ''));
        }

        return $config;
    }

    /**
     * Returns the `request` component config for web requests.
     *
     * @return array
     */
    public static function webRequestConfig(): array
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $config = [
            'class' => WebRequest::class,
            'enableCookieValidation' => true,
            'cookieValidationKey' => $generalConfig->securityKey,
            'enableCsrfValidation' => $generalConfig->enableCsrfProtection,
            'enableCsrfCookie' => $generalConfig->enableCsrfCookie,
            'csrfParam' => $generalConfig->csrfTokenName,
        ];

        if ($generalConfig->trustedHosts !== null) {
            $config['trustedHosts'] = $generalConfig->trustedHosts;
        }

        if ($generalConfig->secureHeaders !== null) {
            $config['secureHeaders'] = $generalConfig->secureHeaders;
        }

        if ($generalConfig->ipHeaders !== null) {
            $config['ipHeaders'] = $generalConfig->ipHeaders;
        }

        if ($generalConfig->secureProtocolHeaders !== null) {
            $config['secureProtocolHeaders'] = $generalConfig->secureProtocolHeaders;
        }

        return $config;
    }
}
