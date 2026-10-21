<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Connections;

use Illuminate\Database\MySqlConnection as BaseConnection;

/**
 * @since 6.0.0
 */
class MySqlConnection extends BaseConnection
{
    use PreparesDatesInUtc;
}
