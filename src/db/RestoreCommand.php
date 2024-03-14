<?php

namespace craft\db;

use Closure;

class RestoreCommand
{
    public bool $archiveFormat = false;
    public ?Closure $callback = null;
}
