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
    private static YiiSession|false|null $_session = null;

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

    /**
     * Returns a flash message.
     *
     * @param string $key the key identifying the flash message
     * @param mixed $defaultValue value to be returned if the flash message does not exist
     * @param bool $delete whether to delete this flash message right after this method is called
     * @return mixed the flash message or an array of messages if addFlash was used
     * @see YiiSession::getFlash()
     * @since 5.5.0
     */
    public static function getFlash(string $key, mixed $defaultValue = null, bool $delete = false): mixed
    {
        if (!static::exists()) {
            return $defaultValue;
        }

        return self::session()->getFlash($key, $defaultValue, $delete);
    }

    /**
     * Returns all flash messages.
     *
     * @param bool $delete whether to delete the flash messages right after this method is called
     * @return array flash messages (key => message or key => [message1, message2])
     * @see YiiSession::getAllFlashes()
     * @since 5.5.0
     */
    public static function getAllFlashes(bool $delete): array
    {
        if (!static::exists()) {
            return [];
        }
        return self::session()->getAllFlashes($delete);
    }

    /**
     * Returns a value indicating whether there are flash messages associated with the specified key.
     *
     * @param string $key key identifying the flash message type
     * @return bool whether any flash messages exist under specified key
     * @see YiiSession::hasFlash()
     * @since 5.5.0
     */
    public static function hasFlash(string $key): bool
    {
        if (!static::exists()) {
            return false;
        }
        return self::session()->hasFlash($key);
    }

    /**
     * Adds a flash message.
     *
     * @param string $key the key identifying the flash message
     * @param mixed $value flash message
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if it is accessed
     * @see YiiSession::addFlash()
     * @since 5.5.0
     */
    public static function addFlash($key, $value = true, $removeAfterAccess = true): void
    {
        self::session()?->addFlash($key, $value, $removeAfterAccess);
    }

    /**
     * Removes a flash message.
     *
     * @param string $key the key identifying the flash message
     * @return mixed the removed flash message or `null` if the flash message does not exist
     * @see YiiSession::removeFlash()
     * @since 5.5.0
     */
    public static function removeFlash(string $key): mixed
    {
        if (!static::exists()) {
            return false;
        }

        return self::session()->removeFlash($key);
    }

    /**
     * Removes all flash messages.
     *
     * @see YiiSession::removeAllFlashes()
     * @since 5.5.0
     */
    public static function removeAllFlashes(): void
    {
        if (!static::exists()) {
            return;
        }

        self::session()->removeAllFlashes();
    }
}
