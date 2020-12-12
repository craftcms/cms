<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\behaviors\SessionBehavior;
use craft\cache\FileCache;
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
use craft\services\ProjectConfig as ProjectConfigService;
use craft\web\AssetManager;
use craft\web\Request;
use craft\web\Request as WebRequest;
use craft\web\Response as WebResponse;
use craft\web\Session;
use craft\web\User as WebUser;
use craft\web\View;
use yii\base\InvalidArgumentException;
use yii\helpers\Inflector;
use yii\i18n\PhpMessageSource;
use yii\log\Dispatcher;
use yii\log\Logger;
use yii\mutex\FileMutex;
use yii\mutex\MysqlMutex;
use yii\mutex\PgsqlMutex;
use yii\web\JsonParser;

/**
 * App helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class App
{
    /**
     * @var bool
     */
    private static $_iconv;

    /**
     * Returns an environment variable, checking for it in `$_SERVER` and calling `getenv()` as a fallback.
     *
     * @param string $name The environment variable name
     * @return string|array|false The environment variable value
     * @since 3.4.18
     */
    public static function env(string $name)
    {
        return $_SERVER[$name] ?? getenv($name);
    }

    /**
     * Returns whether Craft is running within [Nitro](https://getnitro.sh).
     *
     * @return bool
     * @since 3.4.19
     */
    public static function isNitro(): bool
    {
        return static::env('CRAFT_NITRO') === '1';
    }

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
     * Returns the handle of the given Craft edition.
     *
     * @param int $edition An edition’s ID.
     * @return string The edition’s name.
     * @since 3.1.0
     */
    public static function editionHandle(int $edition): string
    {
        switch ($edition) {
            case Craft::Solo:
                return 'solo';
            case Craft::Pro:
                return 'pro';
            default:
                throw new InvalidArgumentException('Invalid Craft edition ID: ' . $edition);
        }
    }

    /**
     * Returns the name of the given Craft edition.
     *
     * @param int $edition An edition’s ID.
     * @return string The edition’s name.
     */
    public static function editionName(int $edition): string
    {
        switch ($edition) {
            case Craft::Solo:
                return 'Solo';
            case Craft::Pro:
                return 'Pro';
            default:
                throw new InvalidArgumentException('Invalid Craft edition ID: ' . $edition);
        }
    }

    /**
     * Returns the ID of a Craft edition by its handle.
     *
     * @param string $handle An edition’s handle
     * @return int The edition’s ID
     * @throws InvalidArgumentException if $handle is invalid
     * @since 3.1.0
     */
    public static function editionIdByHandle(string $handle): int
    {
        switch ($handle) {
            case 'solo':
                return Craft::Solo;
            case 'pro':
                return Craft::Pro;
            default:
                throw new InvalidArgumentException('Invalid Craft edition handle: ' . $handle);
        }
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
        $value = trim(ini_get($var));

        // Supposedly “On” values will always be normalized to '1' but who can trust PHP...
        return ($value === '1' || strtolower($value) === 'on');
    }

    /**
     * Retrieves a disk size PHP config setting and normalizes it into bytes.
     *
     * @param string $var The PHP config setting to retrieve.
     * @return int|float The value normalized into bytes.
     * @since 3.0.38
     */
    public static function phpConfigValueInBytes(string $var)
    {
        $value = trim(ini_get($var));
        $unit = strtolower(substr($value, -1, 1));
        $value = (int)$value;

        switch ($unit) {
            case 'g':
                $value *= 1024;
            // no break (cumulative multiplier)
            case 'm':
                $value *= 1024;
            // no break (cumulative multiplier)
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Tests whether ini_set() works.
     *
     * @return bool
     * @since 3.0.40
     */
    public static function testIniSet(): bool
    {
        $oldValue = ini_get('memory_limit');
        $oldBytes = static::phpConfigValueInBytes('memory_limit');

        // When the old value is not equal to '-1', add 1MB to the limit set at the moment
        if ($oldBytes === -1) {
            $testBytes = 1024 * 1024 * 442;
        } else {
            $testBytes = $oldBytes + 1024 * 1024;
        }

        $testValue = sprintf('%sM', ceil($testBytes / (1024 * 1024)));
        set_error_handler(function() {
        });
        $result = ini_set('memory_limit', $testValue);
        $newValue = ini_get('memory_limit');
        ini_set('memory_limit', $oldValue);
        restore_error_handler();

        // ini_set can return false or an empty string depending on your php version / FastCGI.
        // If ini_set has been disabled in php.ini, the value will be null because of our muted error handler
        return (
            $result !== null &&
            $result !== false &&
            $result !== '' &&
            $result !== $newValue
        );
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
     * <config3:phpMaxMemoryLimit> config setting, and gives the script an
     * unlimited amount of time to execute.
     */
    public static function maxPowerCaptain()
    {
        // Don't mess with the memory_limit, even at the config's request, if it's already set to -1 or >= 1.5GB
        $memoryLimit = static::phpConfigValueInBytes('memory_limit');
        if ($memoryLimit !== -1 && $memoryLimit < 1024 * 1024 * 1536) {
            $maxMemoryLimit = Craft::$app->getConfig()->getGeneral()->phpMaxMemoryLimit;
            @ini_set('memory_limit', $maxMemoryLimit ?: '1536M');
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
     * @return string
     * @since 3.0.13
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

    /**
     * Returns whether Craft is running on an environment with ephemeral storage.
     *
     * @return bool
     * @since 3.4.0
     */
    public static function isEphemeral(): bool
    {
        return defined('CRAFT_EPHEMERAL') && CRAFT_EPHEMERAL === true;
    }

    // App component configs
    // -------------------------------------------------------------------------

    /**
     * Returns the `assetManager` component config for web requests.
     *
     * @return array
     * @since 3.0.18
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
     * @since 3.0.18
     */
    public static function cacheConfig(): array
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        return [
            'class' => FileCache::class,
            'keyPrefix' => Craft::$app->id,
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
     * @since 3.0.18
     */
    public static function dbConfig(DbConfig $dbConfig = null): array
    {
        if ($dbConfig === null) {
            $dbConfig = Craft::$app->getConfig()->getDb();
        }

        $driver = $dbConfig->dsn ? Db::parseDsn($dbConfig->dsn, 'driver') : Connection::DRIVER_MYSQL;

        if ($driver === Connection::DRIVER_MYSQL) {
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
            'driverName' => $driver,
            'dsn' => $dbConfig->dsn,
            'username' => $dbConfig->user,
            'password' => $dbConfig->password,
            'charset' => $dbConfig->charset,
            'tablePrefix' => $dbConfig->tablePrefix,
            'schemaMap' => [
                $driver => $schemaConfig,
            ],
            'commandMap' => [
                $driver => Command::class,
            ],
            'attributes' => $dbConfig->attributes,
            'enableSchemaCache' => !YII_DEBUG,
        ];
    }

    /**
     * Returns the system email settings.
     *
     * @return MailSettings
     * @since 3.1.0
     */
    public static function mailSettings(): MailSettings
    {
        $settings = Craft::$app->getProjectConfig()->get('email') ?? [];
        return new MailSettings($settings);
    }

    /**
     * Returns the `mailer` component config.
     *
     * @param MailSettings|null $settings The system mail settings
     * @return array
     * @since 3.0.18
     */
    public static function mailerConfig(MailSettings $settings = null): array
    {
        if ($settings === null) {
            $settings = static::mailSettings();
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
            'from' => [
                Craft::parseEnv($settings->fromEmail) => Craft::parseEnv($settings->fromName)
            ],
            'replyTo' => Craft::parseEnv($settings->replyToEmail),
            'template' => Craft::parseEnv($settings->template),
            'transport' => $adapter->defineTransport(),
        ];
    }

    /**
     * Returns a file-based `mutex` component config.
     *
     * ::: tip
     * If you were calling this to override the [[\yii\mutex\FileMutex::$isWindows]] property, note that you
     * can safely remove your custom `mutex` component config for Craft 3.5.0 and later. Craft now uses a
     * database-based mutex component by default (see [[dbMutexConfig()]]), which doesn’t care which type of
     * file system is used.
     * :::
     *
     * @return array
     * @since 3.0.18
     * @deprecated in 3.5.0.
     *
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
     * Returns the `mutex` component config.
     *
     * @return array
     * @since 3.5.18
     */
    public static function dbMutexConfig(): array
    {
        if (!Craft::$app->getIsInstalled()) {
            return App::mutexConfig();
        }

        $db = Craft::$app->getDb();

        return [
            'class' => $db->getIsMysql() ? MysqlMutex::class : PgsqlMutex::class,
            'db' => $db,
        ];
    }

    /**
     * Returns the `log` component config.
     *
     * @return array|null
     * @since 3.0.18
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
            'includeUserIp' => $generalConfig->storeUserIps,
            'except' => [
                PhpMessageSource::class . ':*',
            ],
        ];

        if ($isConsoleRequest) {
            $target['logFile'] = '@storage/logs/console.log';
        } else {
            $target['logFile'] = '@storage/logs/web.log';
        }

        // Only log errors and warnings, unless Craft is running in Dev Mode or it's being installed/updated
        // (Explicitly check GeneralConfig::$devMode here, because YII_DEBUG is always `1` for console requests.)
        if (!$generalConfig->devMode && Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
            $target['levels'] = Logger::LEVEL_ERROR | Logger::LEVEL_WARNING;
        }

        return [
            'class' => Dispatcher::class,
            'targets' => [
                $target,
            ]
        ];
    }

    /**
     * Returns the `projectConfig` component config.
     */
    public static function projectConfigConfig(): array
    {
        return [
            'class' => ProjectConfigService::class,
            'readOnly' => Craft::$app->getIsInstalled() && !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
        ];
    }

    /**
     * Returns the `session` component config for web requests.
     *
     * @return array
     * @since 3.0.18
     */
    public static function sessionConfig(): array
    {
        $stateKeyPrefix = md5('Craft.' . Session::class . '.' . Craft::$app->id);

        return [
            'class' => Session::class,
            'as session' => SessionBehavior::class,
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
     * @since 3.0.18
     */
    public static function userConfig(): array
    {
        $configService = Craft::$app->getConfig();
        $generalConfig = $configService->getGeneral();
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest() || $request->getIsSiteRequest()) {
            $loginUrl = UrlHelper::siteUrl($generalConfig->getLoginPath());
        } else {
            $loginUrl = UrlHelper::cpUrl(Request::CP_PATH_LOGIN);
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
            'tokenParam' => $stateKeyPrefix . '__token',
            'authTimeoutParam' => $stateKeyPrefix . '__expire',
            'absoluteAuthTimeoutParam' => $stateKeyPrefix . '__absoluteExpire',
            'returnUrlParam' => $stateKeyPrefix . '__returnUrl',
        ];
    }

    /**
     * Returns the `view` component config.
     *
     * @return array
     * @since 3.0.18
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
     * @since 3.0.18
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
            'parsers' => [
                'application/json' => JsonParser::class,
            ],
            'isCpRequest' => defined('CRAFT_CP') ? (bool)CRAFT_CP : null,
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

    /**
     * Returns the `response` component config for web requests.
     *
     * @return array
     * @since 3.3.0
     */
    public static function webResponseConfig(): array
    {
        $config = [
            'class' => WebResponse::class,
        ];

        // Default to JSON responses if running in headless mode
        if (
            Craft::$app->has('request', true) &&
            Craft::$app->getRequest()->getIsSiteRequest() &&
            Craft::$app->getConfig()->getGeneral()->headlessMode
        ) {
            $config['format'] = WebResponse::FORMAT_JSON;
        }

        return $config;
    }
}
