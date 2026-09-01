<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Models;

use CraftCms\Cms\Database\Factories\VolumeFolderFactory;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolumeFolder extends BaseModel
{
    /** @use HasFactory<VolumeFolderFactory> */
    use HasFactory;

    use HasUid;

    #[\Override]
    protected $table = Table::VOLUMEFOLDERS;

    /**
     * @return BelongsTo<VolumeFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parentId');
    }

    /**
     * @return HasMany<VolumeFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parentId');
    }

    /**
     * @return BelongsTo<Volume, $this>
     */
    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class, 'volumeId');
    }
}
