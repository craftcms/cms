<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Actions;

use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUser;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\Events\OAuthUserLinkResolving;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\User\Elements\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class UserResolver implements ResolvesOAuthUser
{
    public function __construct(
        protected readonly OAuth $oauthManager,
    ) {}

    public function handle(ProviderDefinition $provider, SocialiteUser $socialiteUser, string $identity): ?User
    {
        if ($user = $this->oauthManager->findLinkedUser($provider, $identity)) {
            return $user;
        }

        event($event = new OAuthUserLinkResolving($provider, $socialiteUser, $identity));

        if ($event->user) {
            return $event->user;
        }

        if (! $provider->trustsEmail) {
            return null;
        }

        $email = $socialiteUser->getEmail();

        if (! is_string($email)) {
            return null;
        }

        return $this->oauthManager->findUserByEmail($email);
    }
}
