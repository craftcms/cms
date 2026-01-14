<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Events;

use Illuminate\Support\Collection;

final class RegisterAuthMethods
{
    public function __construct(
        /**
         * @var Collection<class-string<\craft\auth\methods\AuthMethodInterface>>
         */
        public Collection $methods,
    ) {}
}
