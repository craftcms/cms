<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Auth;

use CraftCms\Cms\Auth\OAuth\ProviderDefinition;

final class MarketingProviderDefinition extends ProviderDefinition
{
    public function __construct(string $handle = 'marketing')
    {
        parent::__construct(
            handle: $handle,
            config: [
                'driver' => 'fake-socialite',
                'name' => 'Marketing SSO',
                'scopes' => ['openid', 'email'],
                'with' => ['prompt' => 'login'],
                'stateless' => true,
            ]
        );
    }
}
