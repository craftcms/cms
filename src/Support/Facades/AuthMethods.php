<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Auth\Methods\AuthMethodInterface> getAllMethods(\CraftCms\Cms\User\Elements\User|null $user = null)
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Auth\Methods\AuthMethodInterface> getAvailableMethods(\CraftCms\Cms\User\Elements\User|null $user = null)
 * @method static bool hasActiveMethod(\CraftCms\Cms\User\Elements\User|null $user = null)
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Auth\Methods\AuthMethodInterface> getActiveMethods(\CraftCms\Cms\User\Elements\User|null $user = null)
 * @method static \CraftCms\Cms\Auth\Methods\AuthMethodInterface getMethod(class-string<\CraftCms\Cms\Auth\Methods\AuthMethodInterface> $class, \CraftCms\Cms\User\Elements\User|null $user = null)
 * @method static \CraftCms\Cms\User\Elements\User|null getUser()
 * @method static void setUser(\CraftCms\Cms\User\Elements\User|null $user)
 * @method static bool is2faRequired(\CraftCms\Cms\User\Elements\User $user)
 * @method static bool authenticate(\CraftCms\Cms\User\Elements\User $user, array $credentials)
 * @method static bool authenticateWithPasskey(\CraftCms\Cms\User\Elements\User $user, string $requestOptions, string $response)
 * @method static bool verifyMethod(string $methodClass, mixed ...$args)
 * @method static \CraftCms\Cms\Auth\Enums\AuthError|null getAuthError(\CraftCms\Cms\User\Elements\User $user)
 * @method static string getAuthMethodErrorMessage(string|null $defaultMessage = null)
 * @method static array getLoginFailureInfo(\CraftCms\Cms\Auth\Enums\AuthError|null $authError, \CraftCms\Cms\User\Elements\User|null $user)
 * @method static void handleInvalidLogin(\CraftCms\Cms\User\Elements\User $user)
 * @method static string rememberedUsernameCookie()
 * @method static string|null getRememberedUsername()
 * @method static void setRememberedUsername(\CraftCms\Cms\User\Elements\User $user)
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
