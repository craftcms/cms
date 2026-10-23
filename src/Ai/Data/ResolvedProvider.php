<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Data;

/**
 * The provider and model a task resolved to.
 *
 * Pass them to any `laravel/ai` call that accepts `provider:` and `model:` arguments.
 *
 * @since 6.0.0
 */
readonly class ResolvedProvider
{
    public function __construct(
        public string $provider,
        public ?string $model = null,
    ) {}
}
