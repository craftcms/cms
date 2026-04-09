<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportConfig extends BaseModel
{
    use HasUid;
    use SoftDeletes;

    #[\Override]
    protected $table = Table::IMPORT_CONFIGS;

    #[\Override]
    protected function casts(): array
    {
        return [
            'settings' => 'json',
            'elementImport' => 'boolean',
        ];
    }
}
