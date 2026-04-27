<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static string rememberedUsernameCookie()
 * @method static string|null getRememberedUsername()
 * @method static void setRememberedUsername(User $user)
 *
 * @see \CraftCms\Cms\Auth\AuthMethods
 */
class AuthMethods extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Auth\AuthMethods::class;
    }
}
