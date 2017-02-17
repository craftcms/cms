<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use yii\helpers\Inflector;

/**
 * App helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class App
{
    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    private static $_isComposerInstall;

    /**
     * @var bool
     */
    private static $_isPhpDevServer;

    /**
     * @var bool
     */
    private static $_iconv;

    // Public Methods
    // =========================================================================

    /**
     * Returns whether Craft was installed via Composer.
     *
     * @return bool
     */
    public static function isComposerInstall(): bool
    {
        if (self::$_isComposerInstall !== null) {
            return self::$_isComposerInstall;
        }

        // If this was installed via a craftcms.com zip, there will be an index.php file
        // at the root of the vendor directory.
        return self::$_isComposerInstall = !is_file(Craft::$app->getVendorPath().DIRECTORY_SEPARATOR.'index.php');
    }

    /**
     * Returns whether Craft is running on the dev server bundled with PHP 5.4+.
     *
     * @return bool Whether Craft is running on the PHP Dev Server.
     */
    public static function isPhpDevServer(): bool
    {
        if (self::$_isPhpDevServer !== null) {
            return self::$_isPhpDevServer;
        }

        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            return self::$_isPhpDevServer = (strpos($_SERVER['SERVER_SOFTWARE'], 'PHP') === 0);
        }

        return self::$_isPhpDevServer = false;
    }

    /**
     * Returns an array of all known Craft editions’ IDs.
     *
     * @return array All the known Craft editions’ IDs.
     */
    public static function editions(): array
    {
        return [Craft::Personal, Craft::Client, Craft::Pro];
    }

    /**
     * Returns the name of the given Craft edition.
     *
     * @param int $edition An edition’s ID.
     *
     * @return string The edition’s name.
     */
    public static function editionName(int $edition): string
    {
        switch ($edition) {
            case Craft::Client:
                return 'Client';
            case Craft::Pro:
                return 'Pro';
            default:
                return 'Personal';
        }
    }

    /**
     * Returns whether an edition is valid.
     *
     * @param mixed $edition An edition’s ID (or is it?)
     *
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
     * Retrieves a bool PHP config setting and normalizes it to an actual bool.
     *
     * @param string $var The PHP config setting to retrieve.
     *
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
     * Retrieves a PHP config setting that represents a filesize and normalizes it to bytes.
     *
     * @param string $var The PHP config setting to retrieve.
     *
     * @return int The size in bytes.
     */
    public static function phpConfigValueInBytes(string $var): int
    {
        $value = ini_get($var);

        // See if we can recognize that.
        if (!preg_match('/(\d+)(K|M|G|T)/i', $value, $matches)) {
            return (int)$value;
        }

        $value = (int)$matches[1];

        // Multiply! Falling through here is intentional.
        switch (strtolower($matches[2])) {
            case 't':
                $value *= 1024;
            // no break
            case 'g':
                $value *= 1024;
            // no break
            case 'm':
                $value *= 1024;
            // no break
            case 'k':
                $value *= 1024;
            // no break
        }

        return $value;
    }

    /**
     * Normalizes a version number based on the same logic as PHP’s [version_compare](http://php.net/manual/en/function.version-compare.php) uses internally.
     *
     * @param string $version The version number
     *
     * @return string The normalized version number
     */
    public static function normalizeVersionNumber(string $version): string
    {
        // Periods before/after non-numeric sequences
        $version = preg_replace('/\D+/', '.$0.', $version);

        // Convert sequences of ./-/+'s into single periods
        $version = preg_replace('/[\._\-\+]+/', '.', $version);

        // Remove any leading/trailing periods
        $version = trim($version, '.');

        return $version;
    }

    /**
     * Returns the major version from a given version number.
     *
     * @param string $version The full version number
     *
     * @return string|null The major version
     */
    public static function majorVersion(string $version)
    {
        $version = static::normalizeVersionNumber($version);
        $parts = explode('.', $version, 2);

        if (!empty($parts[0])) {
            return $parts[0];
        }

        return null;
    }

    /**
     * Returns the major and minor (X.Y) versions from a given version number.
     *
     * @param string $version The full version number
     *
     * @return string|null The X.Y parts of the version number
     */
    public static function majorMinorVersion(string $version)
    {
        preg_match('/^\d+\.\d+/', $version, $matches);

        if (isset($matches[0])) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Returns the Craft download URL for a given version.
     *
     * @param string $version The Craft version
     *
     * @return string The download URL
     */
    public static function craftDownloadUrl(string $version): string
    {
        $xy = self::majorMinorVersion($version);

        return "https://download.craftcdn.com/craft/{$xy}/{$version}/Craft-{$version}.zip";
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
     *
     * @return string
     */
    public static function humanizeClass(string $class): string
    {
        $classParts = explode('\\', $class);

        return StringHelper::toLowerCase(Inflector::camel2words(array_pop($classParts)));
    }
}
