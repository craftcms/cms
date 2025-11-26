<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AssetIndexData extends BaseModel
{
    use HasUid;

    protected $table = Table::ASSETINDEXDATA;

    protected function casts(): array
    {
        return [
            'size' => 'int',
            'timestamp' => 'datetime',
            'isDir' => 'bool',
            'recordId' => 'int',
            'isSkipped' => 'bool',
            'inProgress' => 'bool',
            'completed' => 'bool',

        ];
    }

    /**
     * @return BelongsTo<AssetIndexingSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AssetIndexingSession::class, 'sessionId');
    }

    /**
     * @return BelongsTo<Volume, $this>
     */
    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class, 'volumeId');
    }
}
