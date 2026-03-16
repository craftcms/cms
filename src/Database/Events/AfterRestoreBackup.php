<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Events;

use Illuminate\Database\Connection;

class AfterRestoreBackup
{
    public function __construct(
        public Connection $connection,
        public string $file,
    ) {}
}
