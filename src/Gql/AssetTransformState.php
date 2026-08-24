<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

readonly class AssetTransformState
{
    /** @param array<string, mixed>|string $definition */
    public function __construct(
        public array|string $definition,
        public ?bool $immediately,
    ) {}
}
