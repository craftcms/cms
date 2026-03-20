<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Closure;
use Craft;
use craft\behaviors\SessionBehavior;
use craft\config\DbConfig;
use craft\db\Command;
use craft\db\Connection;
use craft\db\mysql\Schema as MysqlSchema;
use craft\db\pgsql\Schema as PgsqlSchema;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\models\MailSettings;
use craft\services\Config;
use craft\web\AssetManager;
use craft\web\Request;
use craft\web\Request as WebRequest;
use craft\web\Response as WebResponse;
use craft\web\User as WebUser;
use craft\web\View;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\License;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigService;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Cache;
use InvalidArgumentException;
use yii\base\Event;
use yii\base\Exception;
use yii\db\sqlite\Schema as SqliteSchema;
use yii\mutex\FileMutex;
use yii\mutex\MysqlMutex;
use yii\mutex\PgsqlMutex;
use yii\web\JsonParser;
use function CraftCms\Cms\backTraceAsString;
use function CraftCms\Cms\maxPowerCaptain;
use function CraftCms\Cms\normalizeValue;
use function CraftCms\Cms\normalizeVersion;
use function CraftCms\Cms\silence;

/**
 * App helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0
 */
class App
{
    /**
     * Returns whether Dev Mode is enabled.
     *
     * @return bool
     * @since 4.0.0
     * @deprecated 6.0.0 use `app()->hasDebugModeEnabled()` instead.
     */
    public static function devMode(): bool
    {
        return app()->hasDebugModeEnabled();
    }

    /**
     * Returns an environment-specific value.
     *
     * Values will be looked for in the following places:
     *
     * 1. “Secret” values returned by a PHP file identified by a `CRAFT_SECRETS_PATH` environment variable
     * 2. Environment variables stored in `$_SERVER`
     * 3. Environment variables returned by `getenv()`
     * 4. PHP constants
     *
     * If the value cannot be found, `null` will be returned.
     *
     * @param string $name The name to search for.
     *
     * @return mixed The value, or `null` if not found.
     * @throws Exception
     * @since 3.4.18
     * @deprecated in 6.0.0. Use {@see Env::get()} instead.
     */
    public static function env(string $name): mixed
    {
        return Env::get($name);
    }

    /**
     * Returns a config array for a given class, based on any environment variables or PHP constants named based on its
     * public properties.
     *
     * Environment variable/PHP constant names must be capitalized, SNAKE_CASED versions of the object’s property names,
     * possibly with a given prefix.
     *
     * For example, if an object has a `fooBar` property, and `X`/`X_` is passed as the prefix, the resulting array
     * may contain a `fooBar` key set to an `X_FOO_BAR` environment variable value, if it exists.
     *
     * @param class-string $class The class name
     * @param string|null $envPrefix The environment variable name prefix
     * @return array
     * @phpstan-return array<string, mixed>
     * @since 4.0.0
     */
    public static function envConfig(string $class, ?string $envPrefix = null): array
    {
        return Env::config($class, $envPrefix);
    }

    /**
     * Checks if a string references an environment variable (`$VARIABLE_NAME`)
     * and/or an alias (`@aliasName`), and returns the referenced value.
     *
     * If the string references an environment variable with a value of `true`
     * or `false`, a boolean value will be returned.
     *
     * If the string references an environment variable that’s not defined,
     * `null` will be returned.
     *
     * ---
     *
     * ```php
     * $value1 = App::parseEnv('$SMTP_PASSWORD');
     * $value2 = App::parseEnv('@webroot');
     * ```
     *
     * @param string|null $value
     * @return string|bool|null The parsed value, or the original value if it didn’t
     * reference an environment variable and/or alias.
     * @since 3.7.29
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Env::parse()} instead.
     */
    public static function parseEnv(?string $value): bool|string|null
    {
        return Env::parse($value);
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
     * @deprecated 6.0.0 use {@see Env::parseBoolean()} instead.
     */
    public static function parseBooleanEnv(mixed $value): ?bool
    {
        return Env::parseBoolean($value);
    }

    /**
     * Returns a CLI command option from `argv`, or `null` if it wasn’t passed.
     *
     * Supported option syntaxes are:
     *
     * - `name=value`
     * - `name value`
     * - `name` (implies `true`)
     *
     * `name` must begin with `--` or `-`. Other values will be rejected.
     *
     * If the value is numeric, a float or int will be returned.
     *
     * If the value is `true` or `false`, a boolean will be returned.
     *
     * If the option has no value (either because the following item begins with `-` or it’s the last item),
     * `true` will be returned.
     *
     * @param string $name The option name, beginning with `--` or `-`
     * @param bool $unset Whether the option should be removed from `argv` if found
     * @return string|float|int|bool|null
     * @since 4.0.0
     * @deprecated 6.0.0
     */
    public static function cliOption(string $name, bool $unset = false): string|float|int|bool|null
    {
        if (!preg_match('/^--?[\w-]+$/', $name)) {
            throw new InvalidArgumentException("Invalid CLI option name: $name");
        }

        if (empty($_SERVER['argv'])) {
            return null;
        }

        // We shouldn’t count on array being perfectly indexed
        $keys = array_keys($_SERVER['argv']);
        $nameLen = strlen($name);

        foreach ($keys as $i => $key) {
            $item = $_SERVER['argv'][$key];
            $nextKey = $keys[$i + 1] ?? null;

            if ($item === $name) {
                $nextItem = $nextKey !== null ? ($_SERVER['argv'][$nextKey] ?? null) : null;
                if ($nextItem !== null && $nextItem[0] !== '-') {
                    $value = $nextItem;
                    $unsetNext = true;
                } else {
                    $value = true;
                }
            } elseif (str_starts_with($item, "$name=")) {
                $value = substr($item, $nameLen + 1);
            } else {
                continue;
            }

            if ($unset) {
                unset($_SERVER['argv'][$key]);
                if (isset($unsetNext)) {
                    unset($_SERVER['argv'][$nextKey]);
                }
                $_SERVER['argv'] = array_values($_SERVER['argv']);
            }

            return normalizeValue($value);
        }

        return null;
    }

    /**
     * Returns an array of all known Craft editions’ IDs.
     *
     * @return int[] All the known Craft editions’ IDs.
     * @deprecated in 5.0.0. [[Edition::cases()]] should be used instead.
     */
    public static function editions(): array
    {
        return array_map(fn(Edition $edition) => $edition->value, Edition::cases());
    }

    /**
     * Returns the handle of the given Craft edition.
     *
     * @param int $edition An edition’s ID.
     * @return string The edition’s handle.
     * @throws InvalidArgumentException if $edition is invalid
     * @since 3.1.0
     * @deprecated in 5.0.0. [[Edition::handle()]] should be used instead.
     */
    public static function editionHandle(int $edition): string
    {
        $handle = Edition::tryFrom($edition)?->handle();
        if ($handle === null) {
            throw new InvalidArgumentException("Invalid edition ID: $edition");
        }
        return $handle;
    }

    /**
     * Returns the name of the given Craft edition.
     *
     * @param int $edition An edition’s ID.
     * @return string The edition’s name.
     * @throws InvalidArgumentException if $edition is invalid
     * @deprecated in 5.0.0. [[Edition::name]] should be used instead.
     */
    public static function editionName(int $edition): string
    {
        $name = Edition::tryFrom($edition)?->name;
        if ($name === null) {
            throw new InvalidArgumentException("Invalid edition ID: $edition");
        }
        return $name;
    }

    /**
     * Returns the ID of a Craft edition by its handle.
     *
     * @param string $handle An edition’s handle
     * @return int The edition’s ID
     * @throws \InvalidArgumentException if $handle is invalid
     * @since 3.1.0
     * @deprecated in 5.0.0. [[Edition::fromHandle()]] should be used instead.
     */
    public static function editionIdByHandle(string $handle): int
    {
        return Edition::fromHandle($handle)->value;
    }

    /**
     * Returns whether an edition is valid.
     *
     * @param mixed $edition An edition’s ID (or is it?)
     * @return bool Whether $edition is a valid edition ID.
     * @deprecated in 5.0.0. [[Edition::tryFrom()]] should be used instead.
     */
    public static function isValidEdition(mixed $edition): bool
    {
        return (
            is_numeric($edition) &&
            Edition::tryFrom((int)$edition) !== null
        );
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::version}
     * @deprecated 6.0.0 use {@see PHP::version()} instead.
     */
    public static function phpVersion(): string
    {
        return PHP::version();
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::extensionVersion}
     * @deprecated 6.0.0 use {@see PHP::extensionVersion($name)} instead.
    */
    public static function extensionVersion(string $name): string
    {
        return PHP::extensionVersion($name);
    }

    /**
     * {@see \CraftCms\Cms\normalizeValue()}
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\normalizeValue()} instead.
     */
    public static function normalizeValue(mixed $value): mixed
    {
        return normalizeValue($value);
    }

    /**
     * Removes distribution info from a version string, and returns the highest version number found in the remainder.
     *
     * @param string $version
     * @return string
     * @deprecated 6.0.0 use `CraftCms\Cms\normalizeVersion($version)` instead.
     */
    public static function normalizeVersion(string $version): string
    {
        return normalizeVersion($version);
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::configValueAsBool}
     * @deprecated 6.0.0 use {@see PHP::configValueAsBool($var)} instead.
     */
    public static function phpConfigValueAsBool(string $var): bool
    {
        return PHP::configValueAsBool($var);
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::phpConfigValueInBytes}
     * @deprecated 6.0.0 use {@see PHP::phpConfigValueInBytes($var)} instead.
     * @since 3.0.38
     */
    public static function phpConfigValueInBytes(string $var): float|int
    {
        return PHP::configValueInBytes($var);
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::sizeToBytes()}
     * @deprecated 6.0.0 use {@see PHP::sizeToBytes($value)} instead.
     * @since 3.6.0
     */
    public static function phpSizeToBytes(string $value): float|int
    {
        return PHP::sizeToBytes($value);
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::configValueAsPaths()}
     * @deprecated 6.0.0 use {@see PHP::configValueAsPaths($var)} instead.
     * @since 3.7.34
     */
    public static function phpConfigValueAsPaths(string $var): array
    {
        return PHP::configValueAsPaths($var);
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::normalizePaths()}
     * @deprecated 6.0.0 use {@see PHP::normalizePaths($value)} instead.
     * @since 3.7.34
     */
    public static function normalizePhpPaths(string $value): array
    {
        return PHP::normalizePaths($value);
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::isPathAllowed()}
     * @deprecated 6.0.0 use {@see PHP::isPathAllowed($path)} instead.
     * @since 3.7.34
     */
    public static function isPathAllowed(string $path): bool
    {
        return PHP::isPathAllowed($path);
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::executable()}
     * @deprecated 6.0.0 use {@see PHP::executable()} instead.
     * @since 4.5.6
     */
    public static function phpExecutable(): ?string
    {
        return PHP::executable();
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::testIniSet()}
     * @deprecated 6.0.0 use {@see PHP::testIniSet()} instead.
     * @since 3.0.40
     */
    public static function testIniSet(): bool
    {
        return PHP::testIniSet();
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::checkForValidIconv()}
     * @deprecated 6.0.0 use {@see PHP::checkForValidIconv()} instead.
    */
    public static function checkForValidIconv(): bool
    {
        return PHP::checkForValidIconv();
    }

    /**
     * {@see \CraftCms\Cms\Support\PHP::supportsIdn()}
     * @deprecated 6.0.0 use {@see PHP::supportsIdn()} instead.
     * @since 3.7.9
     */
    public static function supportsIdn(): bool
    {
        return PHP::supportsIdn();
    }

    /**
     * Returns a humanized class name.
     *
     * @param class-string $class
     * @return string
     * @deprecated 6.0.0
     */
    public static function humanizeClass(string $class): string
    {
        $classParts = explode('\\', $class);

        return strtolower(Str::headline(array_pop($classParts)));
    }

    /**
     * Sets PHP’s memory limit to the maximum specified by the
     * <config5:phpMaxMemoryLimit> config setting, and gives the script an
     * unlimited amount of time to execute.
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\maxPowerCaptain()} instead.
     */
    public static function maxPowerCaptain(): void
    {
        maxPowerCaptain();
    }

    /**
     * Calls the given closure with all error reporting silenced, and returns its response.
     *
     * @param Closure|string $callable
     * @param int|null $mask Error levels to suppress, default value NULL indicates all warnings and below.
     * @return mixed
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\silence()} instead.
     */
    public static function silence(Closure|string $callable, ?int $mask = null): mixed
    {
        return silence($callable, $mask);
    }

    /**
     * @deprecated 6.0.0 use {@see License::key()} instead.
     */
    public static function licenseKey(): ?string
    {
        return app(License::class)->key();
    }

    /**
     * Returns the backtrace as a string (omitting the final frame where this method was called).
     *
     * @param int $limit The max number of stack frames to be included (0 means no limit)
     * @return string
     * @since 3.0.13
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\backTraceAsString()} instead.
     */
    public static function backtrace(int $limit = 0): string
    {
        return backTraceAsString($limit);
    }

    /**
     * Returns whether Craft is running on an environment with ephemeral storage.
     *
     * @return bool
     * @since 3.4.0
     */
    public static function isEphemeral(): bool
    {
        return self::parseBooleanEnv('$CRAFT_EPHEMERAL') === true;
    }

    /**
     * Returns whether Craft is running on a Windows environment
     *
     * @since 5.0.0
     * @deprecated 6.0.0 use `windows_os()` instead.
     */
    public static function isWindows(): bool
    {
        return windows_os();
    }

    /**
     * Returns whether Craft is logging to stdout/stderr.
     *
     * @return bool
     * @since 4.0.0
     */
    public static function isStreamLog(): bool
    {
        return self::parseBooleanEnv('$CRAFT_STREAM_LOG') === true;
    }

    /**
     * Returns whether Craft is being run from a TTY terminal.
     *
     * This is copied verbatim from `Composer\Util\Platform::isTty()`. Full credit to Nils Adermann and Jordi Boggiano.
     *
     * @param resource|null $fd Open file descriptor or `null`. Defaults to `STDOUT`.
     * @since 5.4.8
     */
    public static function isTty($fd = null): bool
    {
        if ($fd === null) {
            $fd = defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w');
            if ($fd === false) {
                return false;
            }
        }

        // detect msysgit/mingw and assume this is a tty because detection
        // does not work correctly, see https://github.com/composer/composer/issues/9690
        if (in_array(strtoupper(Env::get('MSYSTEM') ?: ''), ['MINGW32', 'MINGW64'], true)) {
            return true;
        }

        // modern cross-platform function, includes the fstat
        // fallback so if it is present we trust it
        if (function_exists('stream_isatty')) {
            return stream_isatty($fd);
        }

        // only trusting this if it is positive, otherwise prefer fstat fallback
        if (function_exists('posix_isatty') && posix_isatty($fd)) {
            return true;
        }

        $stat = @fstat($fd);
        // Check if formatted mode is S_IFCHR
        return $stat ? 0020000 === ($stat['mode'] & 0170000) : false;
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
        $generalConfig = Cms::config();

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
        $generalConfig = Cms::config();

        return [
            'class' => Cache::class,
            'keyPrefix' => Craft::$app->id,
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

        $driver = $dbConfig->dsn ? Db::parseDsn($dbConfig->dsn, 'driver') : config('database.default');

        if ($driver === Connection::DRIVER_MYSQL) {
            $schemaConfig = [
                'class' => MysqlSchema::class,
            ];
        } elseif ($driver === Connection::DRIVER_PGSQL) {
            $schemaConfig = [
                'class' => PgsqlSchema::class,
                'defaultSchema' => $dbConfig->schema,
            ];
        } else {
            // SQLite or other: use Yii2's built-in schema support
            $schemaConfig = [
                'class' => SqliteSchema::class,
            ];
        }

        $config = [
            'class' => Connection::class,
            'driverName' => $driver,
            'dsn' => $dbConfig->dsn,
            'server' => $dbConfig->server,
            'port' => $dbConfig->port,
            'database' => $dbConfig->database,
            'username' => $dbConfig->user,
            'password' => $dbConfig->password,
            'charset' => $dbConfig->getCharset(),
            'tablePrefix' => $dbConfig->tablePrefix ?? '',
            'enableLogging' => app()->hasDebugModeEnabled(),
            'enableProfiling' => app()->hasDebugModeEnabled(),
            'schemaMap' => [
                $driver => $schemaConfig,
            ],
            'commandMap' => [
                $driver => Command::class,
            ],
            'attributes' => $dbConfig->attributes,
            'enableSchemaCache' => !app()->hasDebugModeEnabled(),
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
        $settings = app(ProjectConfigService::class)->get('email') ?? [];
        return new MailSettings($settings);
    }

    /**
     * Returns the `mailer` component config.
     *
     * @param MailSettings|null $settings The system mail settings
     * @return array
     * @phpstan-return array{class:class-string<Mailer>}
     * @since 3.0.18
     */
    public static function mailerConfig(?MailSettings $settings = null): array
    {
        if ($settings?->template) {
            Deprecator::log(
                'craft\\models\\MailSettings::$template',
                '`craft\\models\\MailSettings::$template` is deprecated. Set the template via the email settings in Settings → Email instead.',
            );
        }

        if ($settings && !empty($settings->siteOverrides)) {
            Deprecator::log(
                'craft\\models\\MailSettings::$siteOverrides',
                '`craft\\models\\MailSettings::$siteOverrides` is deprecated and no longer has any effect. Use the email settings in Settings → Email instead.',
            );
        }

        $fromEmail = data_get(config('mail'), 'from.address');
        $fromName = data_get(config('mail'), 'from.name');
        $replyTo = data_get(config('mail'), 'reply_to.address');

        if (!is_string($fromEmail) || $fromEmail === '') {
            $fromEmail = Env::get('FROM_EMAIL_ADDRESS');
        }

        if (!is_string($fromName) || $fromName === '') {
            $fromName = Env::get('FROM_EMAIL_NAME');
        }

        if (!is_string($replyTo) || $replyTo === '') {
            $replyTo = null;
        }

        return [
            'class' => Mailer::class,
            'messageClass' => Message::class,
            'from' => ($fromEmail && is_string($fromEmail)) ? [
                $fromEmail => is_string($fromName) ? $fromName : null,
            ] : [],
            'replyTo' => $replyTo,
            'template' => null,
            'siteOverrides' => [],
            'transport' => app('mail.manager')->mailer()->getSymfonyTransport(),
        ];
    }

    /**
     * Returns a database-based mutex driver config.
     *
     * @return array
     * @since 4.6.0
     */
    public static function dbMutexConfig(): array
    {
        if (Craft::$app->getDb()->getIsMysql()) {
            return [
                'class' => MysqlMutex::class,
                'db' => 'db2',
                //'keyPrefix' => Craft::$app->getEnvId(),
            ];
        }

        return [
            'class' => PgsqlMutex::class,
            'db' => 'db2',
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
     * @deprecated in 4.6.0
     */
    public static function mutexConfig(): array
    {
        $generalConfig = Cms::config();

        return [
            'class' => FileMutex::class,
            'fileMode' => $generalConfig->defaultFileMode,
            'dirMode' => $generalConfig->defaultDirMode,
        ];
    }

    /**
     * Returns the `projectConfig` component config.
     */
    public static function projectConfigConfig(): array
    {
        return [
            'class' => \craft\services\ProjectConfig::class,
            'readOnly' => Cms::isInstalled() && !Cms::config()->allowAdminChanges,
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
        $stateKeyPrefix = md5('Craft.' . \craft\web\Session::class . '.' . Craft::$app->getEnvId());

        return [
            'class' => \craft\web\Session::class,
            'as session' => SessionBehavior::class,
            'flashParam' => $stateKeyPrefix . '__flash',
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
        /** @var \craft\config\GeneralConfig $generalConfig */
        $generalConfig = Craft::$app->getConfig()->getConfigSettings(Config::CATEGORY_GENERAL);

        if (app()->runningInConsole() || request()->isSiteRequest()) {
            $loginUrl = UrlHelper::siteUrl($generalConfig->getLoginPath());
        } else {
            $loginUrl = UrlHelper::cpUrl(Request::CP_PATH_LOGIN);
        }

        return [
            'class' => WebUser::class,
            'identityClass' => User::class,
            'enableAutoLogin' => true,
            'autoRenewCookie' => true,
            'loginUrl' => $loginUrl,
            'authTimeout' => $generalConfig->userSessionDuration ?: null,
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
        if (!app()->runningInConsole()) {
            // Check these headers for site requests too, in case we're rendering a system fallback template
            $headers = $request->getHeaders();
            $config['registeredAssetBundles'] = array_filter(explode(',', $headers->get('X-Registered-Asset-Bundles', '')));
            $config['registeredJsFiles'] = array_filter(explode(',', $headers->get('X-Registered-Js-Files', '')));
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
        $generalConfig = Cms::config();

        $config = [
            'class' => WebRequest::class,
            'enableCookieValidation' => true,
            'cookieValidationKey' => $generalConfig->securityKey,
            'enableCsrfValidation' => $generalConfig->enableCsrfProtection,
            'enableCsrfCookie' => $generalConfig->enableCsrfCookie,
            'csrfParam' => $generalConfig->csrfTokenName,
            'trustedHosts' => $generalConfig->trustedHosts,
            'parsers' => [
                'application/json' => JsonParser::class,
            ],
            'isCpRequest' => static::parseBooleanEnv('$CRAFT_CP'),
        ];

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
            Cms::config()->headlessMode
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
     * @deprecated 6.0.0 use {I18N::getFormattingLocale()} instead.
     */
    public static function createFormattingLocale(): Locale
    {
        return I18N::getFormattingLocale();
    }

    /**
     * Returns all known licensing issues.
     *
     * @param bool $withUnresolvables
     * @param bool $fetch
     *
     * @return array{0:string,1:string,2:array|null}[]
     * @internal
     * @deprecated 6.0.0 use {@see License::issues()} instead.
     */
    public static function licensingIssues(bool $withUnresolvables = true, bool $fetch = false): array
    {
        return app(License::class)->issues($withUnresolvables, $fetch);
    }

    /**
     * Returns the license_shun cookie name.
     *
     * @return string
     * @internal
     * @deprecated 6.0.0 use {@see License::shunCookieName()} instead.
     */
    public static function licenseShunCookieName(): string
    {
        return app(License::class)->shunCookieName();
    }

    /**
     * Returns a hash of the given licensing issues.
     *
     * @param array $issues
     *
     * @return string
     * @internal
     * @deprecated 6.0.0 use {@see License::issuesHash()} instead.
     */
    public static function licensingIssuesHash(array $issues): string
    {
        return app(License::class)->issuesHash($issues);
    }

    /**
     * Configures an object with property values.
     *
     * This is identical to [[\BaseYii::configure()]], except this class is safe to be called during application
     * bootstrap, whereas `\BaseYii` is not.
     *
     * @param object $object the object to be configured
     * @param array $properties the property initial values given in terms of name-value pairs.
     * @since 5.3.0
     */
    public static function configure(object $object, array $properties): void
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }
    }
}
