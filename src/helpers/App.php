<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use yii\helpers\Inflector;

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
        return PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
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
        return preg_replace('/^([^~+-]+).*$/', '$1', $version);
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

        return StringHelper::toLowerCase(Inflector::camel2words(array_pop($classParts)));
    }

    /**
     * Sets PHP’s memory limit to the maximum specified by the
     * [phpMaxMemoryLimit](http://craftcms.com/docs/config-settings#phpMaxMemoryLimit) config setting, and gives
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
        $path = Craft::$app->getPath()->getLicenseKeyPath();

        // Check to see if the key exists and it's not a temp one.
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if (empty($contents) || $contents === 'temp') {
            return null;
        }

        $licenseKey = trim(preg_replace('/[\r\n]+/', '', $contents));

        if (strlen($licenseKey) !== 250) {
            return null;
        }

        return $licenseKey;
    }
}
