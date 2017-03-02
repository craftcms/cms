<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;

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
     * Tells the browser that the following content is private
     *
     * @return void
     */
    public static function setPrivate()
    {
        static::setHeader(
            [
                'Pragma' => 'private',
                'Cache-control' => 'private, must-revalidate',
            ]
        );
    }


    /**
     * Tells the browser that the following content is public
     *
     * @return void
     */
    public static function setPublic()
    {
        static::setHeader(
            [
                'Pragma' => 'public',
            ]
        );
    }

    /**
     * Tells the browser the length of the following content. This mostly makes sense when using the download function
     * so the browser can calculate how many bytes are left during the process.
     *
     * @param int $sizeInBytes The content size in bytes
     *
     * @return void
     */
    public static function setLength(int $sizeInBytes)
    {
        static::setHeader(['Content-Length' => (int)$sizeInBytes]);
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

    /**
     * Called to output a header.
     *
     * @param array|string $header Use key => value
     *
     * @return bool
     */
    public static function setHeader($header): bool
    {
        // Don't try to set headers when it's already too late
        if (headers_sent()) {
            return false;
        }

        // Clear out our stored MIME type in case its about to be overridden
        self::$_mimeType = null;

        if (is_string($header)) {
            $header = [$header];
        }

        foreach ($header as $key => $value) {
            if (is_numeric($key)) {
                header($value);
            } else {
                header("$key: $value");
            }
        }

        return true;
    }
}
