<?php

declare(strict_types=1);

namespace CraftCms\Cms\Deprecator\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

final class DeprecationError extends BaseModel
{
    use HasUid;

    protected $table = Table::DEPRECATIONERRORS;

    #[\Override]
    protected function casts(): array
    {
        return [
            'line' => 'int',
            'lastOccurrence' => 'datetime',
            'traces' => 'json',
        ];
    }

    /**
     * Laravel tries to determine this automatically by checking the database connection.
     * However, we don't always have a connection yet when logging deprecation errors.
     */
    #[\Override]
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
