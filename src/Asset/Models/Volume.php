<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Models;

use CraftCms\Cms\Database\Factories\VolumeFactory;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Volume extends BaseModel
{
    /** @use HasFactory<VolumeFactory> */
    use HasFactory;

    use HasUid;
    use SoftDeletes;

    #[\Override]
    protected $table = Table::VOLUMES;

    #[\Override]
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

    /**
     * @return HasMany<AssetIndexData, $this>
     */
    public function assetIndexDataRows(): HasMany
    {
        return $this->hasMany(AssetIndexData::class, 'volumeId');
    }
}
