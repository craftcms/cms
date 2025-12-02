<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use Composer\Semver\Semver;
use craft\helpers\FileHelper;
use HTMLPurifier_Encoder;
use InvalidArgumentException;
use Symfony\Component\Process\PhpExecutableFinder;

use function CraftCms\Cms\normalizeVersion;
use function CraftCms\Cms\t;

final class PHP
{
    private static array $basePaths = [];

    private static ?bool $iconv = null;

    /**
     * Returns the PHP version, without the distribution info.
     */
    public static function version(): string
    {
        return PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
    }

    /**
     * Returns a PHP extension version, without the distribution info.
     */
    public static function extensionVersion(string $name): string
    {
        $version = phpversion($name);

        return normalizeVersion($version);
    }

    /**
     * Retrieves a bool PHP config setting and normalizes it to an actual bool.
     *
     * @param  string  $var  The PHP config setting to retrieve.
     * @return bool Whether it is set to the php.ini equivalent of `true`.
     */
    public static function configValueAsBool(string $var): bool
    {
        $value = trim(ini_get($var) ?: '');

        // Supposedly “On” values will always be normalized to '1' but who can trust PHP...
        return $value === '1' || strtolower($value) === 'on';
    }

    /**
     * Retrieves a disk size PHP config setting and normalizes it into bytes.
     *
     * @param  string  $var  The PHP config setting to retrieve.
     * @return int The value normalized into bytes.
     */
    public static function configValueInBytes(string $var): int
    {
        $value = trim(ini_get($var));

        return self::sizeToBytes($value);
    }

    /**
     * Normalizes a PHP file size into bytes.
     *
     * @param  string|int  $value  The file size expressed in PHP config value notation
     * @return int The value normalized into bytes.
     */
    public static function sizeToBytes(string|int $value): int
    {
        // See if we can recognize that.
        if (is_numeric($value) || ! preg_match('/(\d+)(K|M|G)/i', $value, $matches)) {
            return (int) $value;
        }

        $value = (int) $matches[1];

        // Multiply!
        switch (strtolower($matches[2])) {
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
     * @param  string  $var  The PHP config setting to retrieve
     * @return string[] The normalized paths
     */
    public static function configValueAsPaths(string $var): array
    {
        return self::normalizePaths(ini_get($var));
    }

    /**
     * Normalizes a PHP path setting to an array of paths
     *
     * @param  string  $value  The PHP path setting value
     * @return string[] The normalized paths
     */
    public static function normalizePaths(string $value): array
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
                $path = preg_replace_callback('/\$\{(.*?)\}/', function ($match) {
                    $env = Env::get($match[1]);
                    if ($env === false) {
                        throw new InvalidArgumentException;
                    }

                    return $env;
                }, $path);
            } catch (InvalidArgumentException) {
                // References an env var that doesn’t exist
                continue;
            }

            // '.' => working dir
            if ($path === '.' || str_starts_with((string) $path, './') || str_starts_with((string) $path, '.\\')) {
                $path = getcwd().substr((string) $path, 1);
            }

            // Normalize
            $paths[] = FileHelper::normalizePath($path);
        }

        return $paths;
    }

    /**
     * Returns whether the given path is within PHP’s `open_basedir` setting.
     */
    public static function isPathAllowed(string $path): bool
    {
        if (! isset(self::$basePaths)) {
            self::$basePaths = self::configValueAsPaths('open_basedir');
        }

        if (! self::$basePaths) {
            return true;
        }

        $path = FileHelper::normalizePath($path);

        return array_any(self::$basePaths, fn ($basePath) => str_starts_with($path, (string) $basePath));
    }

    /**
     * Returns the path to a PHP executable which should be used by sub processes.
     *
     * @return string|null The PHP executable path, or `null` if it can’t be determined.
     */
    public static function executable(): ?string
    {
        // If PHP_BINARY was set to $_SERVER, update the environment variable to match
        if (isset($_SERVER['PHP_BINARY']) && \Illuminate\Support\Facades\Request::server('PHP_BINARY') !== getenv('PHP_BINARY')) {
            putenv(sprintf('PHP_BINARY=%s', \Illuminate\Support\Facades\Request::server('PHP_BINARY')));
        }

        if (
            getenv('PHP_BINARY') === false &&
            /** @phpstan-ignore-next-line */
            PHP_BINARY &&
            PHP_SAPI === 'cgi-fcgi' &&
            str_ends_with(PHP_BINARY, 'php-cgi')
        ) {
            // See if a `php` file exists alongside `php-cgi`, and if so, use that
            $file = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'php';
            if (@is_executable($file) && ! @is_dir($file)) {
                return $file;
            }
        }

        return new PhpExecutableFinder()->find() ?: null;
    }

    /**
     * Tests whether ini_set() works.
     */
    public static function testIniSet(): bool
    {
        $oldValue = ini_get('memory_limit');
        $oldBytes = self::configValueInBytes('memory_limit');

        // When the old value is not equal to '-1', add 1MB to the limit set at the moment
        if ($oldBytes === -1) {
            $testBytes = 1024 * 1024 * 442;
        } else {
            $testBytes = $oldBytes + 1024 * 1024;
        }

        $testValue = sprintf('%sM', ceil($testBytes / (1024 * 1024)));

        /** @phpstan-ignore-next-line */
        set_error_handler(function () {});

        $result = ini_set('memory_limit', $testValue);
        $newValue = ini_get('memory_limit');
        ini_set('memory_limit', $oldValue);
        restore_error_handler();

        // ini_set can return false or an empty string depending on your php version / FastCGI.
        // If ini_set has been disabled in php.ini, the value will be null because of our muted error handler
        return
            /** @phpstan-ignore-next-line */
            $result !== null &&
            $result !== false &&
            $result !== '' &&
            $result !== $newValue;
    }

    /**
     * Returns whether the server has a valid version of the iconv extension installed.
     */
    public static function checkForValidIconv(): bool
    {
        // Check if iconv is installed. Note we can't just use HTMLPurifier_Encoder::iconvAvailable() because they
        // don't consider iconv "installed" if it's there but "unusable".
        return self::$iconv ?? (self::$iconv = (function_exists('iconv') && HTMLPurifier_Encoder::testIconvTruncateBug() === HTMLPurifier_Encoder::ICONV_OK));
    }

    /**
     * Returns whether the server supports IDNA ASCII strings.
     */
    public static function supportsIdn(): bool
    {
        return defined('INTL_IDNA_VARIANT_UTS46');
    }

    /**
     * Compares the given PHP version constraint with the environment, and returns any issues with it.
     *
     * @param  string  $constraint  The PHP version constraint
     * @param  bool  $withLink  Whether the error message should include a “Learn more” link
     * @return ?string The error if the environment doesn't pass or null when it does.
     */
    public static function checkConstraint(string $constraint, bool $withLink = false): ?string
    {
        $installedVersion = self::version();

        if (! Semver::satisfies($installedVersion, $constraint)) {
            return t('This update requires PHP {v1}, but your environment is currently running PHP {v2}.', [
                'v1' => $constraint,
                'v2' => $installedVersion,
            ]);
        }

        $composerVersion = resolve(Composer::class)->getConfig()['config']['platform']['php'] ?? null;

        if (! $composerVersion) {
            return null;
        }

        if (Semver::satisfies($composerVersion, $constraint)) {
            return null;
        }

        $error = t('This update requires PHP {v1}, but your composer.json file is currently set to PHP {v2}.', [
            'v1' => $constraint,
            'v2' => $composerVersion,
        ]);

        if ($withLink) {
            $error .= ' '.Html::a(t('Learn more'), 'https://craftcms.com/knowledge-base/resolving-php-requirement-conflicts', [
                'class' => 'go',
            ]);
        }

        return $error;
    }
}
