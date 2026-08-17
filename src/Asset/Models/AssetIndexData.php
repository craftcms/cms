<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Models;

use CraftCms\Cms\Asset\Enums\AssetIndexStatus;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use RuntimeException;
use Stringable;

/**
 * @property AssetIndexStatus $status
 * @property DateTimeInterface|null $timestamp
 */
class AssetIndexData extends BaseModel implements Stringable
{
    use HasUid;

    #[\Override]
    protected $attributes = [
        'status' => 'pending',
    ];

    #[\Override]
    protected $table = Table::ASSETINDEXDATA;

    #[\Override]
    protected function casts(): array
    {
        return [
            'size' => 'int',
            'timestamp' => 'datetime',
            'isDir' => 'bool',
            'recordId' => 'int',
            'status' => AssetIndexStatus::class,
        ];
    }

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->uri;
    }

    public function transitionTo(AssetIndexStatus $status, ?int $recordId = null): void
    {
        $currentStatus = $this->status;

        if (! $currentStatus->canTransitionTo($status)) {
            throw new LogicException("Asset index entry {$this->getKey()} cannot transition from {$currentStatus->value} to {$status->value}.");
        }

        if ($status === AssetIndexStatus::Indexed && $recordId === null) {
            throw new LogicException('Indexed asset index entries require a record ID.');
        }

        if ($status !== AssetIndexStatus::Indexed && $recordId !== null) {
            throw new LogicException("Asset index entries with a {$status->value} status cannot have a record ID.");
        }

        $attributes = [
            'status' => $status,
            'dateUpdated' => now(),
        ];

        if ($status === AssetIndexStatus::Indexed) {
            $attributes['recordId'] = $recordId;
        } elseif ($status === AssetIndexStatus::Pending) {
            $attributes['recordId'] = null;
        }

        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('status', $currentStatus)
            ->update($attributes);

        if ($updated !== 1) {
            throw new RuntimeException("Asset index entry {$this->getKey()} was changed concurrently.");
        }

        $this->forceFill($attributes)->syncOriginal();
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
