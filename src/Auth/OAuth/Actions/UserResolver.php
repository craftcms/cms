<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Actions;

use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUser;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\Events\ResolvingOAuthUserLink;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class UserResolver implements ResolvesOAuthUser
{
    public function __construct(
        protected readonly OAuth $oauthManager,
        protected readonly Users $users,
    ) {}

    public function handle(ProviderDefinition $provider, SocialiteUser $socialiteUser, string $identity): ?User
    {
        if ($user = $this->oauthManager->findLinkedUser($provider, $identity)) {
            return $user;
        }

        event($event = new ResolvingOAuthUserLink($provider, $socialiteUser, $identity));

        if ($event->user) {
            return $event->user;
        }

        $email = $socialiteUser->getEmail();

        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        return $this->users->getUserByUsernameOrEmail(trim($email));
    }
}
