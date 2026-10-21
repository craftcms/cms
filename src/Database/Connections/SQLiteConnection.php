<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Connections;

use Illuminate\Database\SQLiteConnection as BaseConnection;

/**
 * @since 6.0.0
 */
class SQLiteConnection extends BaseConnection
{
    use PreparesDatesInUtc;
}
