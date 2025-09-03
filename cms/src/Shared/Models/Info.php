<?php

namespace CraftCms\Cms\Shared\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

final class Info extends BaseModel
{
    use HasUid;

    protected $table = Table::INFO;

    protected function casts(): array
    {
        return [
            'maintenance' => 'bool',
        ];
    }
}
