<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\User\Elements\User as UserElement;

/**
 * Class User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.7
 * @deprecated 6.0.0
 */
class User
{
    /**
     * @param UserElement $user
     *
     * @return string|null
     * @deprecated 6.0.0 use {@see AuthMethods::getAuthError()} instead.
     */
    public static function getAuthStatus(UserElement $user): ?string
    {
        return app(AuthMethods::class)->getAuthError($user)?->value;
    }

    /**
     * @param UserElement|null $user
     * @return string|null
     */
    public static function getAuthFailureMessage(?UserElement $user): ?string
    {
        return app(AuthMethods::class)->getLoginFailureInfo(null, $user)[1] ?? null;
    }

    /**
     * @param string|null $authError
     * @param UserElement|null $user
     *
     * @return array{0:string,1:string}
     * @since 5.8.10
     * @deprecated 6.0.0 use {@see AuthMethods::getLoginFailureInfo()} instead.
     */
    public static function getLoginFailureInfo(?string $authError, ?UserElement $user): array
    {
        $info = app(AuthMethods::class)->getLoginFailureInfo(AuthError::from($authError), $user);
        $info[0] = $info[0]?->value;

        return $info;
    }

    /**
     * @param string|null $authError
     * @param UserElement|null $user
     * @return string
     * @deprecated in 5.8.10
     */
    public static function getLoginFailureMessage(?string $authError, ?UserElement $user): string
    {
        return app(AuthMethods::class)->getLoginFailureInfo(AuthError::tryFrom($authError),  $user)[1] ?? '';
    }
}
