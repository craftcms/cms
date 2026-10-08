<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $plannedAt
 * @property Carbon|null $publishedAt
 * @property ReleaseStatus $status
 * @property int|null $createdById
 * @property int|null $scheduledById
 * @property string|null $lastError
 * @property int $publishAttempts
 * @property string $uid
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 * @property int|null $items_count
 *
 * @since 6.0.0
 */
class Release extends BaseModel
{
    #[\Override]
    protected $table = Table::RELEASES;

    /** @return HasMany<ReleaseItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ReleaseItem::class, 'releaseId')->orderBy('id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createdById');
    }

    /** @return BelongsTo<User, $this> */
    public function schedulingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduledById');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'plannedAt' => 'datetime',
            'publishedAt' => 'datetime',
            'status' => ReleaseStatus::class,
            'publishAttempts' => 'integer',
        ];
    }
}
