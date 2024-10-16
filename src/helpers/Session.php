<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\errors\MissingComponentException;
use yii\web\Session as YiiSession;

/**
 * Class Session
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.9
 */
class Session
{
    /**
     * @see session()
     */
    private static YiiSession|false|null $_session;

    /**
     * @see exists()
     */
    private static bool $_exists = false;

    /**
     * Returns the session variable value with the session variable name.
     *
     * @param string $key the session variable name
     * @return mixed the session variable value, or `null` if it doesn’t exist
     */
    public static function get(string $key): mixed
    {
        if (!static::exists()) {
            return null;
        }
        return self::session()->get($key);
    }

    /**
     * Adds a session variable.
     *
     * If the specified name already exists, the old value will be overwritten.
     *
     * @param string $key the session variable name
     * @param mixed $value the session variable value
     */
    public static function set(string $key, mixed $value): void
    {
        self::session()?->set($key, $value);
    }

    /**
     * Removes a session variable.
     *
     * @param string $key the session variable name
     * @return mixed the old value, or `null` if it didn’t exist
     */
    public static function remove(string $key): mixed
    {
        if (!static::exists()) {
            return null;
        }
        return self::session()->remove($key);
    }

    /**
     * Removes all session variables.
     */
    public static function removeAll(): void
    {
        if (!static::exists()) {
            return;
        }
        self::session()->removeAll();
    }

    /**
     * Returns whether a session variable exists.
     *
     * @param string $key the session variable name
     * @return bool whether there is the named session variable
     */
    public static function has(string $key): bool
    {
        if (!static::exists()) {
            return false;
        }
        return self::session()->has($key);
    }

    /**
     * Closes the session, if open.
     *
     * @since 5.4.0
     */
    public static function close(): void
    {
        self::session()?->close();
    }

    private static function session(): ?YiiSession
    {
        if (!isset(self::$_session)) {
            try {
                self::$_session = Craft::$app->getSession();
            } catch (MissingComponentException) {
                self::$_session = false;
            }
        }

        return self::$_session ?: null;
    }

    /**
     * Returns whether a PHP session exists (regardless of whether it’s currently active).
     *
     * @return bool
     */
    public static function exists(): bool
    {
        if (self::$_exists) {
            return true;
        }

        // Keep re-checking until it does
        return self::$_exists = self::session()?->getIsActive() || self::session()?->getHasSessionId();
    }

    /**
     * Resets the memoized database connection.
     *
     * @since 3.5.12.1
     */
    public static function reset(): void
    {
        self::$_session = null;
        self::$_exists = false;
    }
}
