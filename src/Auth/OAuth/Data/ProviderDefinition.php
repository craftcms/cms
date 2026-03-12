<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Data;

use CraftCms\Cms\Auth\OAuth\Actions\ButtonRenderer;
use CraftCms\Cms\Auth\OAuth\Actions\IdentityResolver;
use CraftCms\Cms\Auth\OAuth\Actions\UserGroupResolver;
use CraftCms\Cms\Auth\OAuth\Actions\UserPopulator;
use CraftCms\Cms\Auth\OAuth\Actions\UserResolver;
use CraftCms\Cms\Auth\OAuth\Contracts\PopulatesOAuthUser;
use CraftCms\Cms\Auth\OAuth\Contracts\RendersOAuthButton;
use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthIdentity;
use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUser;
use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUserGroups;
use Laravel\Socialite\Two\AbstractProvider;

readonly class ProviderDefinition
{
    public function __construct(
        public string $handle,
        public string $driver,
        /** @var class-string<AbstractProvider>|null */
        public ?string $providerClass,
        public string $name,
        public string $label,
        public ?string $clientId,
        public ?string $clientSecret,
        public bool $stateless = false,
        public ?bool $createsUsers = null,
        public bool $activatesUsers = false,
        /** @var string[] */
        public array $scopes = [],
        /** @var array<string, mixed> */
        public array $with = [],
        /** @var int[] */
        public array $groupIds = [],
        /** @var class-string<ResolvesOAuthIdentity> */
        public string $identityResolver = IdentityResolver::class,
        /** @var class-string<ResolvesOAuthUser> */
        public string $userResolver = UserResolver::class,
        /** @var class-string<PopulatesOAuthUser> */
        public string $userPopulator = UserPopulator::class,
        /** @var class-string<ResolvesOAuthUserGroups> */
        public string $groupResolver = UserGroupResolver::class,
        /** @var class-string<RendersOAuthButton> */
        public string $buttonRenderer = ButtonRenderer::class,
    ) {}
}
