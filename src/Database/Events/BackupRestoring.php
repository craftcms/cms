<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Events;

use Illuminate\Database\Connection;

/**
 * @since 6.0.0
 */
class BackupRestoring
{
    public function __construct(
        public Connection $connection,
        public string $file,
    ) {}
}
