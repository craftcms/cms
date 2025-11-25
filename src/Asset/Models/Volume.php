<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Volume extends BaseModel
{
    use HasFactory;
    use HasUid;
    use SoftDeletes;

    protected $table = Table::VOLUMES;

    protected function casts(): array
    {
        return [
            'sortOrder' => 'int',
        ];
    }

    /**
     * @return BelongsTo<FieldLayout, $this>
     */
    public function fieldLayout(): BelongsTo
    {
        return $this->belongsTo(FieldLayout::class, 'fieldLayoutId');
    }
}
