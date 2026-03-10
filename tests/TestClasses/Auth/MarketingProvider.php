<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Auth;

use CraftCms\Cms\Auth\OAuth\Provider;

final class MarketingProvider extends Provider
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
