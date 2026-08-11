<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Models;

use CraftCms\Cms\Database\Factories\PluginFactory;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plugin extends BaseModel
{
    /** @use HasFactory<PluginFactory> */
    use HasFactory;

    #[\Override]
    protected $table = Table::PLUGINS;

    #[\Override]
    protected function casts(): array
    {
        return [
            'installDate' => 'datetime',
        ];
    }
}
