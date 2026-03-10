<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Exceptions;

use RuntimeException;

final class SocialiteProviderNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $handle,
    ) {
        parent::__construct("Unknown Socialite provider [$handle].");
    }
}
