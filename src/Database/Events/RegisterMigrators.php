<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Events;

final class RegisterMigrators
{
    public function __construct(
        /** @var \CraftCms\Cms\Database\Migrator[] */
        public array $migrators,
    ) {}
}
