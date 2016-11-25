<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;

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
     * @var boolean
     */
    private static $_isPhpDevServer = null;

    /**
     * @var boolean
     */
    private static $_iconv;

    // Public Methods
    // =========================================================================

    /**
     * Returns whether Craft is running on the dev server bundled with PHP 5.4+.
     *
     * @return boolean Whether Craft is running on the PHP Dev Server.
     */
    public static function isPhpDevServer()
    {
        if (!isset(static::$_isPhpDevServer)) {
            if (isset($_SERVER['SERVER_SOFTWARE'])) {
                static::$_isPhpDevServer = (strncmp($_SERVER['SERVER_SOFTWARE'],
                        'PHP', 3) == 0);
            } else {
                static::$_isPhpDevServer = false;
            }
        }

        return static::$_isPhpDevServer;
    }

    /**
     * Returns an array of all known Craft editions’ IDs.
     *
     * @return array All the known Craft editions’ IDs.
     */
    public static function editions()
    {
        return [Craft::Personal, Craft::Client, Craft::Pro];
    }

    /**
     * Returns the name of the given Craft edition.
     *
     * @param integer $edition An edition’s ID.
     *
     * @return string The edition’s name.
     */
    public static function editionName($edition)
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
     * @return boolean Whether $edition is a valid edition ID.
     */
    public static function isValidEdition($edition)
    {
        return (is_numeric($edition) && in_array($edition,
                static::editions()));
    }

    /**
     * Retrieves a boolean PHP config setting and normalizes it to an actual bool.
     *
     * @param string $var The PHP config setting to retrieve.
     *
     * @return boolean Whether it is set to the php.ini equivelant of `true`.
     */
    public static function phpConfigValueAsBool($var)
    {
        $value = ini_get($var);

        // Supposedly “On” values will always be normalized to '1' but who can trust PHP...
        return ($value == '1' || strtolower($value) == 'on');
    }

    /**
     * Retrieves a PHP config setting that represents a filesize and normalizes it to bytes.
     *
     * @param string $var The PHP config setting to retrieve.
     *
     * @return integer The size in bytes.
     */
    public static function phpConfigValueInBytes($var)
    {
        $value = ini_get($var);

        // See if we can recognize that.
        if (!preg_match('/[0-9]+(K|M|G|T)/i', $value, $matches)) {
            return (int)$value;
        }

        // Multiply! Falling through here is intentional.
        switch (strtolower($matches[1])) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 't':
                $value *= 1024;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'g':
                $value *= 1024;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
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
    public static function normalizeVersionNumber($version)
    {
        // Periods before/after non-numeric sequences
        $version = preg_replace('/[^0-9]+/', '.$0.', $version);

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
    public static function majorVersion($version)
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
     * @return string The X.Y parts of the version number
     */
    public static function majorMinorVersion($version)
    {
        preg_match('/^\d+\.\d+/', $version, $matches);

        return $matches[0];
    }

    /**
     * Returns the Craft download URL for a given version.
     *
     * @param string $version The Craft version
     *
     * @return string The download URL
     */
    public static function craftDownloadUrl($version)
    {
        $xy = self::majorMinorVersion($version);

        return "https://download.craftcdn.com/craft/{$xy}/{$version}/Craft-{$version}.zip";
    }

    /**
     * Returns whether the server has a valid version of the iconv extension installed.
     *
     * @return boolean
     */
    public static function checkForValidIconv()
    {
        if (!isset(static::$_iconv)) {
            // Check if iconv is installed. Note we can't just use HTMLPurifier_Encoder::iconvAvailable() because they
            // don't consider iconv "installed" if it's there but "unusable".
            if (function_exists('iconv') && \HTMLPurifier_Encoder::testIconvTruncateBug() === \HTMLPurifier_Encoder::ICONV_OK) {
                static::$_iconv = true;
            } else {
                static::$_iconv = false;
            }
        }

        return static::$_iconv;
    }
}
