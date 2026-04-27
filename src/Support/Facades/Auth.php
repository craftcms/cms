<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static string rememberedUsernameCookie()
 * @method static string|null getRememberedUsername()
 * @method static void setRememberedUsername(\CraftCms\Cms\User\Elements\User $user)
 *
 * @see \CraftCms\Cms\Auth\Auth
 */
class Auth extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Auth\Auth::class;
    }
}
