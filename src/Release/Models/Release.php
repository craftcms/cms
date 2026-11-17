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
use LogicException;
use RuntimeException;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $targetDate
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

    /**
     * Moves the release to a new status, and saves any other given attributes with it.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws LogicException if the release can't move from its current status to the given one
     * @throws RuntimeException if another request changed the release's status first
     */
    public function transitionTo(ReleaseStatus $status, array $attributes = []): void
    {
        $currentStatus = $this->status;

        if (! $currentStatus->canTransitionTo($status)) {
            throw new LogicException("Release {$this->getKey()} cannot transition from {$currentStatus->value} to {$status->value}.");
        }

        $this->forceFill([...$attributes, 'status' => $status]);

        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('status', $currentStatus)
            ->update($this->getDirty());

        if ($updated !== 1) {
            throw new RuntimeException("Release {$this->getKey()} was changed concurrently.");
        }

        $this->refresh();
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'targetDate' => 'datetime',
            'publishedAt' => 'datetime',
            'status' => ReleaseStatus::class,
            'publishAttempts' => 'integer',
        ];
    }
}
