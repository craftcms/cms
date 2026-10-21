<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Connections;

use Illuminate\Database\PostgresConnection as BaseConnection;

/**
 * @since 6.0.0
 */
class PostgresConnection extends BaseConnection
{
    use PreparesDatesInUtc;
}
