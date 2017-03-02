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
