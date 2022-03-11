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
use craft\helpers\Session as SessionHelper;
use craft\i18n\Locale;
use craft\log\Dispatcher;
use craft\log\FileTarget;
use craft\log\StreamLogTarget;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\mail\transportadapters\Sendmail;
use craft\models\MailSettings;
use craft\mutex\MysqlMutex;
use craft\mutex\PgsqlMutex;
use craft\services\ProjectConfig as ProjectConfigService;
use craft\web\AssetManager;
use craft\web\Request;
use craft\web\Request as WebRequest;
use craft\web\Response as WebResponse;
use craft\web\Session;
use craft\web\User as WebUser;
use craft\web\View;
use yii\base\Event;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\helpers\Inflector;
use yii\i18n\PhpMessageSource;
use yii\log\Dispatcher as YiiDispatcher;
use yii\log\Logger;
use yii\log\Target;
use yii\mutex\FileMutex;
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
     * @var string[]
     * @see isPathAllowed()
     */
    private static $_basePaths;

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
     * Checks if a string references an environment variable (`$VARIABLE_NAME`)
     * and/or an alias (`@aliasName`), and returns the referenced value.
     *
     * If the string references an environment variable with a value of `true`
     * or `false`, a boolean value will be returned.
     *
     * ---
     *
     * ```php
     * $value1 = App::parseEnv('$SMTP_PASSWORD');
     * $value2 = App::parseEnv('@webroot');
     * ```
     *
     * @param string|null $str
     * @return string|bool|null The parsed value, or the original value if it didn’t
     * reference an environment variable and/or alias.
     * @since 3.7.29
     */
    public static function parseEnv(string $str = null)
    {
        if ($str === null) {
            return null;
        }

        if (preg_match('/^\$(\w+)$/', $str, $matches)) {
            $value = App::env($matches[1]);
            if ($value !== false) {
                switch (strtolower($value)) {
                    case 'true':
                        return true;
                    case 'false':
                        return false;
                }
                $str = $value;
            }
        }

        if (StringHelper::startsWith($str, '@')) {
            $str = Craft::getAlias($str, false) ?: $str;
        }

        return $str;
    }

    /**
     * Checks if a string references an environment variable (`$VARIABLE_NAME`) and returns the referenced
     * boolean value, or `null` if a boolean value can’t be determined.
     *
     * ---
     *
     * ```php
     * $status = App::parseBooleanEnv('$SYSTEM_STATUS') ?? false;
     * ```
     *
     * @param mixed $value
     * @return bool|null
     * @since 3.7.29
     */
    public static function parseBooleanEnv($value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 0 || $value === 1) {
            return (bool)$value;
        }

        if (!is_string($value)) {
            return null;
        }

        return filter_var(static::parseEnv($value), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    /**
     * Returns whether Craft is running within [Nitro](https://getnitro.sh) v1.
     *
     * @return bool
     * @since 3.4.19
     * @deprecated in 3.7.9.
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
        return static::phpSizeToBytes($value);
    }

    /**
     * Normalizes a PHP file size into bytes.
     *
     * @param string $value The file size expressed in PHP config value notation
     * @return int|float The value normalized into bytes.
     * @since 3.6.0
     */
    public static function phpSizeToBytes(string $value)
    {
        $unit = strtolower(substr($value, -1, 1));
        $value = (int)$value;

        switch ($unit) {
            case 'g':
                $value *= 1024;
            // no break
            case 'm':
                $value *= 1024;
            // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Retrieves a file path PHP config setting and normalizes it to an array of paths.
     *
     * @param string $var The PHP config setting to retrieve
     * @return string[] The normalized paths
     * @since 3.7.34
     */
    public static function phpConfigValueAsPaths(string $var): array
    {
        return static::normalizePhpPaths(ini_get($var));
    }

    /**
     * Normalizes a PHP path setting to an array of paths
     *
     * @param string $value The PHP path setting value
     * @return string[] The normalized paths
     * @since 3.7.34
     */
    public static function normalizePhpPaths(string $value): array
    {
        // semicolons are used to separate paths on Windows; everything else uses colons
        $value = str_replace(';', ':', trim($value));

        if ($value === '') {
            return [];
        }

        $paths = [];

        foreach (explode(':', $value) as $path) {
            $path = trim($path);

            // Parse ${ENV_VAR}s
            try {
                $path = preg_replace_callback('/\$\{(.*?)\}/', function($match) {
                    $env = App::env($match[1]);
                    if ($env === false) {
                        throw new InvalidValueException();
                    }
                    return $env;
                }, $path);
            } catch (InvalidValueException $e) {
                // References an env var that doesn’t exist
                continue;
            }

            // '.' => working dir
            if ($path === '.' || StringHelper::startsWith($path, './') || StringHelper::startsWith($path, '.\\')) {
                $path = getcwd() . substr($path, 1);
            }

            // Normalize
            $paths[] = FileHelper::normalizePath($path);
        }

        return $paths;
    }

    /**
     * Returns whether the given path is within PHP’s `open_basedir` setting.
     *
     * @param string $path
     * @return bool
     * @since 3.7.34
     */
    public static function isPathAllowed(string $path): bool
    {
        if (!isset(self::$_basePaths)) {
            self::$_basePaths = static::phpConfigValueAsPaths('open_basedir');
        }

        if (!self::$_basePaths) {
            return true;
        }

        $path = FileHelper::normalizePath($path);

        foreach (self::$_basePaths as $basePath) {
            if (strpos($path, $basePath) === 0) {
                return true;
            }
        }

        return false;
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
     * Returns whether the server supports IDNA ASCII strings.
     *
     * @return bool
     * @since 3.7.9
     */
    public static function supportsIdn(): bool
    {
        return function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46');
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

        // Try to reset time limit
        if (!function_exists('set_time_limit') || !@set_time_limit(0)) {
            Craft::warning('set_time_limit() is not available', __METHOD__);
        }
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
    public static function dbConfig(?DbConfig $dbConfig = null): array
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

        $config = [
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

        if ($driver === Connection::DRIVER_PGSQL && $dbConfig->setSchemaOnConnect && $dbConfig->schema) {
            $config['on afterOpen'] = function(Event $event) use ($dbConfig) {
                /** @var Connection $db */
                $db = $event->sender;
                $db->createCommand("SET search_path TO $dbConfig->schema;")->execute();
            };
        }

        return $config;
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
     * @return array{class: class-string<Mailer>}
     * @since 3.0.18
     */
    public static function mailerConfig(?MailSettings $settings = null): array
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
                App::parseEnv($settings->fromEmail) => App::parseEnv($settings->fromName),
            ],
            'replyTo' => App::parseEnv($settings->replyToEmail),
            'template' => App::parseEnv($settings->template),
            'transport' => $adapter->defineTransport(),
        ];
    }

    /**
     * Returns a file-based mutex driver config.
     *
     * ::: tip
     * If you were calling this to override the [[\yii\mutex\FileMutex::$isWindows]] property, note that
     * overriding the `mutex` component may no longer be necessary, as Craft no longer uses a mutex
     * when Dev Mode is enabled.
     * :::
     *
     * @return array
     * @since 3.0.18
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
     * Returns a database-based mutex driver config.
     *
     * @return array
     * @since 3.5.18
     * @deprecated in 3.7.30. Database-based mutex locking is no longer recommended.
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
            'namePrefix' => Craft::$app->id,
        ];
    }

    /**
     * Returns the `log` component config.
     *
     * @return array|null
     * @since 3.0.18
     * @deprecated in 3.6.0. Override `components.log.targets` instead
     */
    public static function logConfig()
    {
        // Using Yii's Dispatcher class here is intentional
        return [
            'class' => YiiDispatcher::class,
            'targets' => array_values(static::defaultLogTargets()),
        ];
    }

    /**
     * Returns the default log targets.
     *
     * @return Target[]
     * @since 3.6.14
     */
    public static function defaultLogTargets(): array
    {
        // Warning - Don't do anything that could cause something to get logged from here!
        // If the dispatcher is configured with flushInterval => 1, it could cause a PHP error if any log
        // targets haven’t been instantiated yet.

        $targets = [];

        $isConsoleRequest = Craft::$app->getRequest()->getIsConsoleRequest();

        if ($isConsoleRequest || Craft::$app->getUser()->enableSession) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            $fileTargetConfig = [
                'class' => FileTarget::class,
                'fileMode' => $generalConfig->defaultFileMode,
                'dirMode' => $generalConfig->defaultDirMode,
                'includeUserIp' => $generalConfig->storeUserIps,
                'except' => [
                    PhpMessageSource::class . ':*',
                ],
            ];

            if ($isConsoleRequest) {
                $fileTargetConfig['logFile'] = '@storage/logs/console.log';
            } else {
                $fileTargetConfig['logFile'] = '@storage/logs/web.log';
            }

            if (!YII_DEBUG) {
                $fileTargetConfig['levels'] = Logger::LEVEL_ERROR | Logger::LEVEL_WARNING;
            }

            $targets[Dispatcher::TARGET_FILE] = Craft::createObject($fileTargetConfig);

            if (!$isConsoleRequest && defined('CRAFT_STREAM_LOG') && CRAFT_STREAM_LOG === true) {
                $targets[Dispatcher::TARGET_STDERR] = Craft::createObject([
                    'class' => StreamLogTarget::class,
                    'url' => 'php://stderr',
                    'levels' => Logger::LEVEL_ERROR | Logger::LEVEL_WARNING,
                    'includeUserIp' => $generalConfig->storeUserIps,
                ]);

                if (YII_DEBUG) {
                    $targets[Dispatcher::TARGET_STDOUT] = Craft::createObject([
                        'class' => StreamLogTarget::class,
                        'url' => 'php://stdout',
                        'levels' => ~Logger::LEVEL_ERROR & ~Logger::LEVEL_WARNING,
                        'includeUserIp' => $generalConfig->storeUserIps,
                    ]);
                }
            }
        }

        return $targets;
    }

    /**
     * Returns the `projectConfig` component config.
     */
    public static function projectConfigConfig(): array
    {
        return [
            'class' => ProjectConfigService::class,
            'readOnly' => Craft::$app->getIsInstalled() && !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            'writeYamlAutomatically' => !self::isEphemeral(),
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

    /**
     * Creates a locale object that should be used for date and number formatting.
     *
     * @return Locale
     * @since 3.6.0
     */
    public static function createFormattingLocale(): Locale
    {
        $i18n = Craft::$app->getI18n();

        if (Craft::$app->getRequest()->getIsCpRequest() && !Craft::$app->getResponse()->isSent) {
            // Is someone logged in?
            if (
                Craft::$app->getIsInstalled() &&
                ($id = SessionHelper::get(Craft::$app->getUser()->idParam))
            ) {
                // If they have a preferred locale, use it
                $usersService = Craft::$app->getUsers();
                if (
                    ($locale = $usersService->getUserPreference($id, 'locale')) !== null &&
                    $i18n->validateAppLocaleId($locale)
                ) {
                    return $i18n->getLocaleById($locale);
                }

                // Otherwise see if they have a preferred language
                if (
                    ($language = $usersService->getUserPreference($id, 'language')) !== null &&
                    $i18n->validateAppLocaleId($language)
                ) {
                    return $i18n->getLocaleById($language);
                }
            }

            // If the defaultCpLocale setting is set, go with that
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            if ($generalConfig->defaultCpLocale) {
                return $i18n->getLocaleById($generalConfig->defaultCpLocale);
            }
        }

        // Default to the application locale
        return Craft::$app->getLocale();
    }
}
