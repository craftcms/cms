<?php

namespace craft\db;

use Closure;

class BackupCommand
{
    public ?array $ignoreTables = null;
    public bool $archiveFormat = false;
    public ?Closure $callback = null;
}
