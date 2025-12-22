<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
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
        return Cookie::craftPrefix().self::$authAccessParam;
    }
}
