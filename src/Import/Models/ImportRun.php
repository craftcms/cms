<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

class ImportRun extends BaseModel
{
    use HasUid;
    use SoftDeletes;

    #[Override]
    protected $table = Table::IMPORT_RUNS;

    #[Override]
    protected function casts(): array
    {
        return [
            'steps' => 'json',
        ];
    }
}
