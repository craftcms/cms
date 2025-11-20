<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class VolumeFolder extends BaseModel
{
    use HasFactory;
    use HasUid;

    protected $table = Table::VOLUMEFOLDERS;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\CraftCms\Cms\Asset\Models\VolumeFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parentId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\CraftCms\Cms\Asset\Models\VolumeFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parentId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\CraftCms\Cms\Asset\Models\Volume, $this>
     */
    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class, 'volumeId');
    }
}
