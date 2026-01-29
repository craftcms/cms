<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Factory;

final readonly class FactoryFieldConfig
{
    public function __construct(
        public string $handle,
        public string $type,
        public array $settings = [],
        public bool $required = false,
        public mixed $value = null,
    ) {}
}
