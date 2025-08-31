<?php

namespace CraftCms\Cms\Deprecator\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;

/**
 * @since 6.0.0
 */
final class DeprecationError extends BaseModel
{
    protected $table = Table::DEPRECATIONERRORS;

    protected function casts(): array
    {
        return [
            'line' => 'int',
            'lastOccurrence' => 'datetime',
            'traces' => 'json',
        ];
    }
}
