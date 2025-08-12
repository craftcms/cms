<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Closure;
use Craft;
use craft\attributes\EnvName;
use craft\behaviors\SessionBehavior;
use craft\cache\FileCache;
use craft\config\DbConfig;
use craft\db\Command;
use craft\db\Connection;
use craft\db\mysql\Schema as MysqlSchema;
use craft\db\pgsql\Schema as PgsqlSchema;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\enums\LicenseKeyStatus;
use craft\errors\InvalidPluginException;
use craft\errors\MissingComponentException;
use craft\helpers\Session as SessionHelper;
use craft\i18n\Locale;
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
use HTMLPurifier_Encoder;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionProperty;
use Symfony\Component\Process\PhpExecutableFinder;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\helpers\Inflector;
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
     * @internal
     */
    public const CACHE_KEY_LICENSE_INFO = 'licenseInfo';
    /**
     * @internal
     */
    public const CACHE_KEY_LICENSE_INFO_HOST = 'licenseInfoHost';

    /**
     * @var bool
     */
    private static bool $_iconv;

    /**
     * @var string[]
     * @see isPathAllowed()
     */
    private static array $_basePaths;

    /**
     * @var string[]
     */
    private static array $_secrets;

    /**
     * Returns whether Dev Mode is enabled.
     *
     * @return bool
     * @since 4.0.0
     */
    public static function devMode(): bool
    {
        return YII_DEBUG;
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
     * @return mixed The value, or `null` if not found.
     * @throws Exception
     * @since 3.4.18
     */
    public static function env(string $name): mixed
    {
        if (!isset(self::$_secrets)) {
            // set it to an empty array initially, so the nested env() call doesn’t cause infinite recursion
            self::$_secrets = [];
            $secretsPath = static::env('CRAFT_SECRETS_PATH');
            if ($secretsPath && is_file($secretsPath)) {
                self::$_secrets = require $secretsPath;
            }
        }

        if (isset(self::$_secrets[$name])) {
            return static::normalizeValue(self::$_secrets[$name]);
        }

        if (isset($_SERVER[$name])) {
            return static::normalizeValue($_SERVER[$name]);
        }

        if (($env = getenv($name)) !== false) {
            return static::normalizeValue($env);
        }

        if (defined($name)) {
            return static::normalizeValue(constant($name));
        }

        return null;
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
        $envPrefix = $envPrefix !== null ? StringHelper::ensureRight($envPrefix, '_') : '';
        $properties = (new ReflectionClass($class))->getProperties(ReflectionProperty::IS_PUBLIC);
        $envConfig = [];

        foreach ($properties as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $envName = null;

            foreach ($prop->getAttributes(EnvName::class) as $attribute) {
                /** @var EnvName $envName */
                $envName = $attribute->newInstance();
                $envName = $envName->name;
                break;
            }

            if (!$envName) {
                $envName = strtoupper(StringHelper::toSnakeCase($prop->getName()));
            }

            $envValue = static::env(sprintf('%s%s', $envPrefix, $envName));

            if ($envValue !== null) {
                $envConfig[$prop->getName()] = $envValue;
            }
        }

        return $envConfig;
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
     */
    public static function parseEnv(?string $value): bool|string|null
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('/^\$(\w+)(\/.*)?/', $value, $matches)) {
            $env = static::env($matches[1]);

            if ($env === null) {
                // No env var or constant is defined here by that name
                return null;
            }

            $value = $env . ($matches[2] ?? '');
        }

        if (str_starts_with($value, '@')) {
            $value = Craft::getAlias($value, false) ?: $value;
        }

        return $value;
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
    public static function parseBooleanEnv(mixed $value): ?bool
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

        $value = static::parseEnv($value);
        if ($value === null) {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
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

            return static::normalizeValue($value);
        }

        return null;
    }

    /**
     * Returns an array of all known Craft editions’ IDs.
     *
     * @return int[] All the known Craft editions’ IDs.
     * @deprecated in 5.0.0. [[CmsEdition::cases()]] should be used instead.
     */
    public static function editions(): array
    {
        return array_map(fn(CmsEdition $edition) => $edition->value, CmsEdition::cases());
    }

    /**
     * Returns the handle of the given Craft edition.
     *
     * @param int $edition An edition’s ID.
     * @return string The edition’s handle.
     * @throws InvalidArgumentException if $edition is invalid
     * @since 3.1.0
     * @deprecated in 5.0.0. [[CmsEdition::handle()]] should be used instead.
     */
    public static function editionHandle(int $edition): string
    {
        $handle = CmsEdition::tryFrom($edition)?->handle();
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
     * @deprecated in 5.0.0. [[CmsEdition::name]] should be used instead.
     */
    public static function editionName(int $edition): string
    {
        $name = CmsEdition::tryFrom($edition)?->name;
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
     * @throws InvalidArgumentException if $handle is invalid
     * @since 3.1.0
     * @deprecated in 5.0.0. [[CmsEdition::fromHandle()]] should be used instead.
     */
    public static function editionIdByHandle(string $handle): int
    {
        return CmsEdition::fromHandle($handle)->value;
    }

    /**
     * Returns whether an edition is valid.
     *
     * @param mixed $edition An edition’s ID (or is it?)
     * @return bool Whether $edition is a valid edition ID.
     * @deprecated in 5.0.0. [[CmsEdition::tryFrom()]] should be used instead.
     */
    public static function isValidEdition(mixed $edition): bool
    {
        return (
            is_numeric($edition) &&
            CmsEdition::tryFrom((int)$edition) !== null
        );
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
     * Normalizes an environment variable/constant name/CLI command option.
     *
     * It converts the following:
     *
     * - `'true'` → `true`
     * - `'false'` → `false`
     * - Numeric string → integer or float
     *
     * @param mixed $value
     * @return mixed
     * @since 4.0.0
     */
    public static function normalizeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            switch (strtolower($value)) {
                case 'true':
                    return true;
                case 'false':
                    return false;
                case 'null':
                    return null;
            }

            if (Number::isIntOrFloat($value)) {
                $intOrFloat = Number::toIntOrFloat($value);
                // make sure we didn't lose any precision
                if ((string)$intOrFloat === $value) {
                    return $intOrFloat;
                }
            }
        }

        return $value;
    }

    /**
     * Removes distribution info from a version string, and returns the highest version number found in the remainder.
     *
     * @param string $version
     * @return string
     */
    public static function normalizeVersion(string $version): string
    {
        // Strip out the distribution info
        $versionPattern = '\d[\d.]*(-(dev|alpha|beta|rc)(\.?\d[\d.]*)?)?';
        if (!preg_match("/^((v|version\s*)?$versionPattern-?)+/i", $version, $match)) {
            return '';
        }
        $version = $match[0];

        // Return the highest version
        preg_match_all("/$versionPattern/i", $version, $matches, PREG_SET_ORDER);
        $versions = array_map(fn(array $match) => $match[0], $matches);
        usort($versions, fn($a, $b) => match (true) {
            version_compare($a, $b, '<') => 1,
            version_compare($a, $b, '>') => -1,
            default => 0,
        });
        return reset($versions) ?: '';
    }

    /**
     * Retrieves a bool PHP config setting and normalizes it to an actual bool.
     *
     * @param string $var The PHP config setting to retrieve.
     * @return bool Whether it is set to the php.ini equivalent of `true`.
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
    public static function phpConfigValueInBytes(string $var): float|int
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
    public static function phpSizeToBytes(string $value): float|int
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
            } catch (InvalidValueException) {
                // References an env var that doesn’t exist
                continue;
            }

            // '.' => working dir
            if ($path === '.' || str_starts_with($path, './') || str_starts_with($path, '.\\')) {
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
            if (str_starts_with($path, $basePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the path to a PHP executable which should be used by sub processes.
     *
     * @return string|null The PHP executable path, or `null` if it can’t be determined.
     * @since 4.5.6
     */
    public static function phpExecutable(): ?string
    {
        // If PHP_BINARY was set to $_SERVER, update the environment variable to match
        if (isset($_SERVER['PHP_BINARY']) && $_SERVER['PHP_BINARY'] !== getenv('PHP_BINARY')) {
            putenv(sprintf('PHP_BINARY=%s', $_SERVER['PHP_BINARY']));
        }

        if (
            getenv('PHP_BINARY') === false &&
            /** @phpstan-ignore-next-line */
            PHP_BINARY &&
            PHP_SAPI === 'cgi-fcgi' &&
            str_ends_with(PHP_BINARY, 'php-cgi')
        ) {
            // See if a `php` file exists alongside `php-cgi`, and if so, use that
            $file = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'php';
            if (@is_executable($file) && !@is_dir($file)) {
                return $file;
            }
        }

        return (new PhpExecutableFinder())->find() ?: null;
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
        /** @phpstan-ignore-next-line */
        set_error_handler(function() {
        });
        $result = ini_set('memory_limit', $testValue);
        $newValue = ini_get('memory_limit');
        ini_set('memory_limit', $oldValue);
        restore_error_handler();

        // ini_set can return false or an empty string depending on your php version / FastCGI.
        // If ini_set has been disabled in php.ini, the value will be null because of our muted error handler
        return (
            /** @phpstan-ignore-next-line */
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
        // Check if iconv is installed. Note we can't just use HTMLPurifier_Encoder::iconvAvailable() because they
        // don't consider iconv "installed" if it's there but "unusable".
        return self::$_iconv ?? (self::$_iconv = (function_exists('iconv') && HTMLPurifier_Encoder::testIconvTruncateBug() === HTMLPurifier_Encoder::ICONV_OK));
    }

    /**
     * Returns whether the server supports IDNA ASCII strings.
     *
     * @return bool
     * @since 3.7.9
     */
    public static function supportsIdn(): bool
    {
        return defined('INTL_IDNA_VARIANT_UTS46');
    }

    /**
     * Returns a humanized class name.
     *
     * @param class-string $class
     * @return string
     */
    public static function humanizeClass(string $class): string
    {
        $classParts = explode('\\', $class);

        return strtolower(Inflector::camel2words(array_pop($classParts)));
    }

    /**
     * Sets PHP’s memory limit to the maximum specified by the
     * <config5:phpMaxMemoryLimit> config setting, and gives the script an
     * unlimited amount of time to execute.
     */
    public static function maxPowerCaptain(): void
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
     * Calls the given closure with all error reporting silenced, and returns its response.
     *
     * @param Closure|string $callable
     * @param int|null $mask Error levels to suppress, default value NULL indicates all warnings and below.
     * @return mixed
     * @since 5.0.0
     */
    public static function silence(Closure|string $callable, ?int $mask = null): mixed
    {
        // loosely based on Composer\Util\Silencer
        if (!isset($mask)) {
            $mask = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED | E_STRICT;
        }

        $old = error_reporting();
        error_reporting($old & ~$mask);

        try {
            $returnType = (new ReflectionFunction($callable))->getReturnType();
            if ($returnType instanceof ReflectionNamedType && $returnType->getName() === 'void') {
                $callable();
                return null;
            } else {
                return $callable();
            }
        } finally {
            error_reporting($old);
        }
    }

    /**
     * @return string|null
     */
    public static function licenseKey(): ?string
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
                (isset($frame['file']) ? sprintf('%s%s: ', $frame['file'], isset($frame['line']) ? "({$frame['line']})" : '') : '') .
                ($frame['class'] ?? '') .
                ($frame['type'] ?? '') .
                /** @phpstan-ignore-next-line */
                (isset($frame['function']) ? "{$frame['function']}()" : '');
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
        return self::parseBooleanEnv('$CRAFT_EPHEMERAL') === true;
    }

    /**
     * Returns whether Craft is running on a Windows environment
     *
     * @since 5.0.0
     */
    public static function isWindows(): bool
    {
        return defined('PHP_WINDOWS_VERSION_BUILD');
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
        if (in_array(strtoupper(self::env('MSYSTEM') ?: ''), ['MINGW32', 'MINGW64'], true)) {
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
            'charset' => $dbConfig->getCharset(),
            'tablePrefix' => $dbConfig->tablePrefix ?? '',
            'enableLogging' => static::devMode(),
            'enableProfiling' => static::devMode(),
            'schemaMap' => [
                $driver => $schemaConfig,
            ],
            'commandMap' => [
                $driver => Command::class,
            ],
            'attributes' => $dbConfig->attributes,
            'enableSchemaCache' => !static::devMode(),
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
     * @return array
     * @phpstan-return array{class:class-string<Mailer>}
     * @since 3.0.18
     */
    public static function mailerConfig(?MailSettings $settings = null): array
    {
        if ($settings === null) {
            $settings = static::mailSettings();
        }

        try {
            $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);
        } catch (MissingComponentException) {
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
            'siteOverrides' => $settings->siteOverrides,
            'transport' => $adapter->defineTransport(),
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
                'keyPrefix' => Craft::$app->getEnvId(),
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
        $generalConfig = Craft::$app->getConfig()->getGeneral();

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
        $stateKeyPrefix = md5('Craft.' . Session::class . '.' . Craft::$app->getEnvId());

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

        $stateKeyPrefix = md5('Craft.' . WebUser::class . '.' . Craft::$app->getEnvId());

        return [
            'class' => WebUser::class,
            'identityClass' => User::class,
            'enableAutoLogin' => true,
            'autoRenewCookie' => true,
            'loginUrl' => $loginUrl,
            'authTimeout' => $generalConfig->userSessionDuration ?: null,
            'identityCookie' => Craft::cookieConfig(['name' => $stateKeyPrefix . '_identity']),
            'usernameCookie' => Craft::cookieConfig(['name' => $stateKeyPrefix . '_username']),
            'absoluteAuthTimeoutParam' => $stateKeyPrefix . '__absoluteExpire',
            'authTimeoutParam' => $stateKeyPrefix . '__expire',
            'idParam' => $stateKeyPrefix . '__id',
            'impersonatorIdParam' => $stateKeyPrefix . '__impersonator_id',
            'returnUrlParam' => $stateKeyPrefix . '__returnUrl',
            'tokenParam' => $stateKeyPrefix . '__token',
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
        if (!$request->getIsConsoleRequest()) {
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
            'isCpRequest' => static::parseBooleanEnv('$CRAFT_CP'),
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
                if (($locale = $usersService->getUserPreference($id, 'locale')) !== null) {
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

    /**
     * Returns all known licensing issues.
     *
     * @param bool $withUnresolvables
     * @param bool $fetch
     * @return array{0:string,1:string,2:array|null}[]
     * @internal
     */
    public static function licensingIssues(bool $withUnresolvables = true, bool $fetch = false): array
    {
        $user = Craft::$app->getUser()->getIdentity();
        if (!$user) {
            return [];
        }

        $updatesService = Craft::$app->getUpdates();
        $cache = Craft::$app->getCache();
        $isInfoCached = $cache->exists(App::CACHE_KEY_LICENSE_INFO) && $updatesService->getIsUpdateInfoCached();

        if (!$isInfoCached) {
            if (!$fetch) {
                return [];
            }

            $updatesService->getUpdates(true);
        }

        $issues = [];

        $allLicenseInfo = $cache->get(App::CACHE_KEY_LICENSE_INFO) ?: [];
        $licenseInfoHost = $cache->get(App::CACHE_KEY_LICENSE_INFO_HOST);
        $pluginsService = Craft::$app->getPlugins();
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $consoleUrl = rtrim(Craft::$app->getPluginStore()->craftIdEndpoint, '/');

        foreach ($allLicenseInfo as $handle => $licenseInfo) {
            $isCraft = $handle === 'craft';
            if ($isCraft) {
                $name = 'Craft';
                $editions = array_map(fn(CmsEdition $edition) => $edition->handle(), CmsEdition::cases());
                $currentEdition = Craft::$app->edition->handle();
                $currentEditionName = Craft::$app->edition->name;
                $licensedEdition = isset($licenseInfo['edition']) ? CmsEdition::fromHandle($licenseInfo['edition']) : CmsEdition::Solo;
                $licenseEditionName = $licensedEdition->name;
                $version = Craft::$app->getVersion();
            } else {
                if (!str_starts_with($handle, 'plugin-')) {
                    continue;
                }
                $handle = StringHelper::removeLeft($handle, 'plugin-');

                try {
                    $pluginInfo = $pluginsService->getPluginInfo($handle);
                } catch (InvalidPluginException) {
                    continue;
                }

                $plugin = $pluginsService->getPlugin($handle);
                if (!$plugin) {
                    continue;
                }

                $name = $plugin->name;
                $editions = $plugin::editions();
                $currentEdition = $pluginInfo['edition'];
                $currentEditionName = ucfirst($currentEdition);
                $licenseEditionName = ucfirst($licenseInfo['edition'] ?? 'standard');
                $version = $pluginInfo['version'];
            }

            $isMultiEdition = count($editions) > 1;

            if ($licenseInfo['status'] === LicenseKeyStatus::Invalid->value) {
                // invalid license
                if ($withUnresolvables) {
                    $issues[] = [
                        $name,
                        Craft::t('app', 'The {name} license is invalid.', ['name' => $name]),
                        null,
                    ];
                }
            } elseif ($licenseInfo['status'] === LicenseKeyStatus::Trial->value) {
                // trial license
                $issues[] = [
                    $isMultiEdition ? sprintf('%s %s', $name, $currentEditionName) : $name,
                    Craft::t('app', '{name} requires purchase.', ['name' => $name]),
                    array_filter([
                        'type' => $isCraft ? 'cms-edition' : 'plugin-edition',
                        'plugin' => !$isCraft ? $handle : null,
                        'licenseId' => $licenseInfo['id'],
                        'edition' => $currentEdition,
                    ]),
                ];
            } elseif ($licenseInfo['status'] === LicenseKeyStatus::Mismatched->value) {
                if ($withUnresolvables) {
                    if ($isCraft) {
                        // wrong domain. ignore if the cache wasn't saved from the same host name we're currently on
                        $request = Craft::$app->getRequest();
                        if ($licenseInfoHost && $request->getIsWebRequest() && $request->getHostName() === $licenseInfoHost) {
                            $licensedDomain = $cache->get('licensedDomain');
                            $domainLink = Html::a($licensedDomain, "http://$licensedDomain", [
                                'rel' => 'noopener',
                                'target' => '_blank',
                            ]);

                            if (defined('CRAFT_LICENSE_KEY')) {
                                $message = Craft::t('app', 'The Craft CMS license key in use belongs to {domain}', [
                                    'domain' => $domainLink,
                                ]);
                            } else {
                                $keyPath = Craft::$app->getPath()->getLicenseKeyPath();

                                // If the license key path starts with the root project path, trim the project path off
                                $rootPath = Craft::getAlias('@root');
                                if (str_starts_with($keyPath, $rootPath . '/')) {
                                    $keyPath = substr($keyPath, strlen($rootPath) + 1);
                                }

                                $message = Craft::t('app', 'The Craft CMS license located at {file} belongs to {domain}.', [
                                    'file' => $keyPath,
                                    'domain' => $domainLink,
                                ]);
                            }

                            $learnMoreLink = Html::a(Craft::t('app', 'Learn more'), 'https://craftcms.com/support/resolving-mismatched-licenses', [
                                'class' => 'go',
                            ]);
                            $issues[] = [$name, "$message $learnMoreLink", null];
                        }
                    } else {
                        // wrong Craft install
                        $issues[] = [
                            $name,
                            Craft::t('app', 'The {name} license is attached to a different Craft CMS license. You can <a class="go" href="{detachUrl}">detach it in Craft Console</a> or <a class="go" href="{buyUrl}">buy a new license</a>.', [
                                'name' => $name,
                                'detachUrl' => "$consoleUrl/licenses/plugins/{$licenseInfo['id']}",
                                'buyUrl' => $user->admin && $generalConfig->allowAdminChanges
                                    ? UrlHelper::cpUrl("plugin-store/buy/$handle/$currentEdition")
                                    : "https://plugins.craftcms.com/$handle",
                            ]),
                            null,
                        ];
                    }
                }
            } elseif ($licenseInfo['edition'] !== $currentEdition) {
                // wrong edition
                $message = Craft::t('app', '{name} is licensed for the {licenseEdition} edition, but the {currentEdition} edition is installed.', [
                    'name' => $name,
                    'licenseEdition' => $licenseEditionName,
                    'currentEdition' => $currentEditionName,
                ]);
                $currentEditionIdx = array_search($currentEdition, $editions);
                $licenseEditionIdx = array_search($licenseInfo['edition'], $editions);
                if ($currentEditionIdx !== false && $licenseEditionIdx !== false && $currentEditionIdx > $licenseEditionIdx) {
                    $issues[] = [
                        $isMultiEdition ? sprintf('%s %s', $name, $currentEditionName) : $name,
                        $message,
                        [
                            'type' => $isCraft ? 'cms-edition' : 'plugin-edition',
                            'edition' => $currentEdition,
                            'licenseId' => $licenseInfo['id'],
                        ],
                    ];
                }
            } elseif ($licenseInfo['status'] === LicenseKeyStatus::Astray->value) {
                // updated too far
                $issues[] = [
                    sprintf('%s %s', $name, $version),
                    Craft::t('app', '{name} isn’t licensed to run version {version}.', [
                        'name' => $name,
                        'version' => $version,
                    ]),
                    array_filter([
                        'type' => $isCraft ? 'cms-renewal' : 'plugin-renewal',
                        'plugin' => !$isCraft ? $handle : null,
                        'licenseId' => $licenseInfo['id'],
                    ]),
                ];
            }
        }

        return $issues;
    }

    /**
     * Returns the license_shun cookie name.
     *
     * @return string
     * @internal
     */
    public static function licenseShunCookieName(): string
    {
        return sprintf('%s_license_shun', md5('Craft.' . WebUser::class . '.' . Craft::$app->id));
    }

    /**
     * Returns a hash of the given licensing issues.
     *
     * @param array $issues
     * @return string
     * @internal
     */
    public static function licensingIssuesHash(array $issues): string
    {
        $resolveItems = array_map(fn($issue) => Json::encode($issue[2]), $issues);
        sort($resolveItems);
        return md5(implode('', $resolveItems));
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
