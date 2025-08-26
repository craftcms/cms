<?php

namespace CraftCms\Cms\Plugin\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** @since 6.0.0 */
class Plugin extends BaseModel
{
    use HasFactory;

    protected $table = Table::PLUGINS;

    protected function casts(): array
    {
        return [
            'installDate' => 'datetime',
        ];
    }
}
