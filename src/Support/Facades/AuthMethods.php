<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection getAllMethods(\CraftCms\Cms\User\Contracts\CraftUser|null $user = null)
 * @method static void register(string ...$types)
 * @method static void remove(string ...$types)
 * @method static \Illuminate\Support\Collection types()
 * @method static \Illuminate\Support\Collection getAvailableMethods(\CraftCms\Cms\User\Contracts\CraftUser|null $user = null)
 * @method static bool hasActiveMethod(\CraftCms\Cms\User\Contracts\CraftUser|null $user = null)
 * @method static \Illuminate\Support\Collection getActiveMethods(\CraftCms\Cms\User\Contracts\CraftUser|null $user = null)
 * @method static \CraftCms\Cms\Auth\Methods\AuthMethodInterface getMethod(string $class, \CraftCms\Cms\User\Contracts\CraftUser|null $user = null)
 * @method static \CraftCms\Cms\User\Elements\User|null getUser()
 * @method static void setUser(\CraftCms\Cms\User\Contracts\CraftUser|null $user, bool $remember = false, \CraftCms\Cms\User\Contracts\CraftUser|null $loginUser = null)
 * @method static bool is2faRequired(\CraftCms\Cms\User\Contracts\CraftUser $user)
 * @method static bool authenticate(\CraftCms\Cms\User\Contracts\CraftUser $user, array $credentials)
 * @method static bool authenticateWithPasskey(\CraftCms\Cms\User\Contracts\CraftUser $user, string $requestOptions, string $response)
 * @method static bool verifyMethod(string $methodClass, mixed ...$args)
 * @method static \CraftCms\Cms\Auth\Enums\AuthError|null getAuthError(\CraftCms\Cms\User\Contracts\CraftUser $user)
 * @method static \CraftCms\Cms\Auth\Enums\AuthError|null getMaintenanceAuthError(\CraftCms\Cms\User\Contracts\CraftUser $user, bool $isCpRequest)
 * @method static string getAuthMethodErrorMessage(string|null $defaultMessage = null)
 * @method static array getLoginFailureInfo(\CraftCms\Cms\Auth\Enums\AuthError|null $authError, \CraftCms\Cms\User\Contracts\CraftUser|null $user)
 * @method static void handleInvalidLogin(\CraftCms\Cms\User\Contracts\CraftUser $user)
 * @method static string rememberedUsernameCookie()
 * @method static string|null getRememberedUsername()
 * @method static void setRememberedUsername(\CraftCms\Cms\User\Contracts\CraftUser $user)
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
