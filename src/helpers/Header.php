<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

/**
 * Class Header
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Header
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    private static $_mimeType;

    // Public Methods
    // =========================================================================

    /**
     * Returns the MIME type that is going to be included in the response via
     * the Content-Type header, whether that has been set explicitly in the PHP
     * code or if it's going to be based on the default_mimetype setting in php.ini.
     *
     * @return string
     */
    public static function getMimeType(): string
    {
        if (self::$_mimeType !== null) {
            return self::$_mimeType;
        }

        // Has it been explicitly set?
        if ((self::$_mimeType = static::getHeader('Content-Type')) !== null) {
            // Drop the charset, if it's there
            if (($pos = strpos(self::$_mimeType, ';')) !== false) {
                self::$_mimeType = rtrim(substr(self::$_mimeType, 0, $pos));
            }
        } else {
            // Then it's whatever's in php.ini
            self::$_mimeType = ini_get('default_mimetype');
        }

        return self::$_mimeType;
    }

    /**
     * Removes a header by key.
     *
     * @param string $key
     *
     * @return void
     */
    public static function removeHeader(string $key)
    {
        header_remove($key);
    }

    /**
     * Checks whether a header is currently set or not.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function isHeaderSet(string $name): bool
    {
        return (static::getHeader($name) !== null);
    }

    /**
     * Returns the value of a given header, if it has been set.
     *
     * @param string $name The name of the header.
     *
     * @return string|null The value of the header, or `null` if it hasn’t been set.
     */
    public static function getHeader(string $name)
    {
        // Normalize to lowercase
        $name = strtolower($name);

        // Loop through each of the headers
        foreach (headers_list() as $header) {
            // Split it into its trimmed key/value
            $parts = array_map('trim', explode(':', $header, 2));

            // Is this the header we're looking for?
            if (isset($parts[1]) && $name == strtolower($parts[0])) {
                return $parts[1];
            }
        }

        return null;
    }
}
