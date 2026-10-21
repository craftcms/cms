<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Connections;

use Illuminate\Database\MariaDbConnection as BaseConnection;

/**
 * @since 6.0.0
 */
class MariaDbConnection extends BaseConnection
{
    use PreparesDatesInUtc;
}
