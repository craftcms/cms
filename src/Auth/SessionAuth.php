<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Cms;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

final class SessionAuth
{
    private const string AUTH_LOCK_NAME = 'authAccess';

    /**
     * @var string|null The session variable name used to store the authorization keys for the current session.
     *
     * @see authorize()
     * @see deauthorize()
     * @see checkAuthorization()
     */
    public static ?string $authAccessParam = '__auth_access';

    /**
     * @var string The session variable name used to store the value of the expiration timestamp of the elevated session state.
     */
    public static string $elevatedSessionTimeoutParam = '__elevated_timeout';

    /**
     * Authorizes the user to perform an action for the duration of the session.
     */
    public static function authorize(string $action): void
    {
        Cache::lock(self::AUTH_LOCK_NAME)->block(5, function () use ($action) {
            $access = Session::get(self::authAccessParam(), []);

            if (in_array($action, $access, true)) {
                return;
            }

            $access[] = $action;

            Session::put(self::authAccessParam(), $access);
        });
    }

    /**
     * Deauthorizes the user from performing an action.
     */
    public static function deauthorize(string $action): void
    {
        Cache::lock(self::AUTH_LOCK_NAME)->block(5, function () use ($action) {
            $access = Session::get(self::authAccessParam(), []);
            $index = array_search($action, $access, true);

            if ($index === false) {
                return;
            }

            array_splice($access, $index, 1);

            Session::put(self::authAccessParam(), $access);
        });
    }

    /**
     * Returns whether the user is authorized to perform an action.
     */
    public static function checkAuthorization(string $action): bool
    {
        $access = Session::get(self::authAccessParam(), []);

        return in_array($action, $access, true);
    }

    private static function authAccessParam(): string
    {
        $prefix = md5('CraftSession'.Cms::envId());

        return $prefix.self::$authAccessParam;
    }

    public static function hasElevatedSession(): bool
    {
        // If it's been disabled, just return true
        if (Cms::config()->elevatedSessionDuration === 0) {
            return true;
        }

        return self::elevatedSessionTimeout() !== 0;
    }

    /**
     * Returns how many seconds are left in the current elevated user session.
     *
     * @return int|false The number of seconds left in the current elevated user session
     *                   or false if it has been disabled.
     */
    public static function elevatedSessionTimeout(): int|false
    {
        // Are they logged in?
        if (Auth::check()) {
            $expires = Session::get(self::$elevatedSessionTimeoutParam);

            if ($expires !== null) {
                $currentTime = now()->getTimestamp();

                if ($expires > $currentTime) {
                    return $expires - $currentTime;
                }
            }
        }

        // If it has been disabled, return false.
        if (Cms::config()->elevatedSessionDuration === 0) {
            return false;
        }

        return 0;
    }
}
