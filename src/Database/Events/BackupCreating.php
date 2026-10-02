<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Events;

use Illuminate\Database\Connection;

/**
 * @since 6.0.0
 */
class BackupCreating
{
    /** @param string[]|null $ignoreTables */
    public function __construct(
        public Connection $connection,
        public string $file,
        public ?array $ignoreTables = null,
    ) {}
}
