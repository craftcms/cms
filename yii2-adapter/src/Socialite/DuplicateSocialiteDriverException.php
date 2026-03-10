<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Socialite;

use RuntimeException;

final class DuplicateSocialiteDriverException extends RuntimeException
{
    public function __construct(
        public readonly string $handle,
        public readonly string $registeredBy,
        public readonly string $attemptedBy = 'yii2-adapter legacy SSO',
    ) {
        parent::__construct(
            "Socialite driver [$handle] is already registered by {$this->registeredBy}. {$this->attemptedBy} cannot use the same handle.",
        );
    }
}
