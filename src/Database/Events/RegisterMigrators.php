<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Events;

use CraftCms\Cms\Database\Migrator;

class RegisterMigrators
{
    public function __construct(
        /** @var Migrator[] */
        public array $migrators = [],
    ) {}
}
