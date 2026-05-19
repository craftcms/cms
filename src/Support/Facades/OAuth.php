<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static bool isAvailable()
 * @method static \Illuminate\Support\Collection getProviderDefinitions()
 * @method static \CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition|null getProviderDefinition(string $handle)
 * @method static \Illuminate\Support\HtmlString[] getLoginButtons(bool|null $isCpRequest = null)
 * @method static \Laravel\Socialite\Contracts\Provider buildProvider(\CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition $provider, bool|null $isCpRequest = null)
 * @method static string redirectPath(\CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition $provider, bool $isCpRequest = false)
 * @method static string callbackPath(\CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition $provider, bool $isCpRequest = false)
 * @method static string resolveIdentity(\CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition $provider, \Laravel\Socialite\Contracts\User $socialiteUser)
 * @method static \CraftCms\Cms\User\Elements\User|null resolveUser(\CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition $provider, \Laravel\Socialite\Contracts\User $socialiteUser, string $identity)
 * @method static \CraftCms\Cms\User\Elements\User populateUser(\CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition $provider, \Laravel\Socialite\Contracts\User $socialiteUser, \CraftCms\Cms\User\Elements\User $user, string $identity, bool $isNew)
 * @method static int[] resolveGroupIds(\CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition $provider, \Laravel\Socialite\Contracts\User $socialiteUser, \CraftCms\Cms\User\Elements\User $user, string $identity)
 * @method static \Illuminate\Support\HtmlString renderButton(\CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition $provider, bool $isCpRequest)
 * @method static \CraftCms\Cms\User\Elements\User|null findLinkedUser(\CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition $provider, string $identity)
 * @method static bool hasIdentity(int $userId)
 * @method static bool canCreateUsers(\CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition $provider)
 * @method static bool publicRegistrationIsAllowed()
 * @method static void linkIdentity(\CraftCms\Cms\User\Elements\User $user, \CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition $provider, string $identity)
 *
 * @see \CraftCms\Cms\Auth\OAuth\OAuth
 */
class OAuth extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Auth\OAuth\OAuth::class;
    }
}
