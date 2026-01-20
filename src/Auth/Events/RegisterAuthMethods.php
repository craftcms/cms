<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Events;

use CraftCms\Cms\Auth\Methods\AuthMethodInterface;
use Illuminate\Support\Collection;

final class RegisterAuthMethods
{
    public function __construct(
        /**
         * @var Collection<class-string<AuthMethodInterface>>
         */
        public Collection $methods,
    ) {}
}
