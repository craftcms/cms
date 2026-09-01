<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Events;

use CraftCms\Cms\Auth\Enums\AuthError;
use SensitiveParameter;

class UserAuthenticating
{
    /** @param array{password?: string|null} $credentials */
    public function __construct(
        #[SensitiveParameter]
        public array $credentials = [],
        public ?AuthError $authError = null,

        /**
         * @var bool Whether authentication should continue. If not, and the user doesn’t authenticate, set `$event->authError` to something,
         *           to prevent Craft from considering the user to be authenticated.
         */
        public bool $performAuthentication = true,
    ) {}
}
