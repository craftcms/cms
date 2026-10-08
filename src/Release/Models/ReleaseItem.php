<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $releaseId
 * @property int|null $draftId
 * @property int $canonicalId
 * @property string $canonicalStateHash SHA-256 hash of the persisted live element tree at attachment time
 * @property int|null $publishedElementId
 * @property Carbon|null $publishedAt
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 *
 * @since 6.0.0
 */
class ReleaseItem extends BaseModel
{
    #[\Override]
    protected $table = Table::RELEASE_ITEMS;

    /** @return BelongsTo<Release, $this> */
    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class, 'releaseId');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'releaseId' => 'integer',
            'draftId' => 'integer',
            'canonicalId' => 'integer',
            'publishedElementId' => 'integer',
            'publishedAt' => 'datetime',
        ];
    }
}
