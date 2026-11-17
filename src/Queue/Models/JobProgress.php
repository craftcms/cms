<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Query;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Override;
use RuntimeException;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class JobProgress extends BaseModel
{
    use HasUid;
    use Prunable;

    #[Override]
    protected $primaryKey = 'uid';

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $table = Table::JOBPROGRESS;

    #[Override]
    protected $casts = [
        'status' => JobStatus::class,
        'dateCompleted' => 'datetime',
        'dateFailed' => 'datetime',
    ];

    protected function getLabelAttribute(): ?string
    {
        return $this->progressLabel ? t($this->progressLabel) : null;
    }

    #[Override]
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        $attributes['description'] = t($this->description);

        if ($this->progressLabel) {
            $attributes['progressLabel'] = t($this->progressLabel);
        }

        if ($this->status instanceof JobStatus) {
            $attributes['status'] = $this->status->jsonSerialize();
        }

        $formatter = I18N::getFormatter();

        foreach (['dateCreated', 'dateUpdated', 'dateCompleted', 'dateFailed'] as $attribute) {
            $attributes[$attribute] = $this->$attribute ? $formatter->asDateTime($this->$attribute, withTimeZone: true) : null;
        }

        return $attributes;
    }

    /**
     * Limits the query to jobs whose delay had passed by the given time, so a worker could pick them up.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function availableBy(Builder $query, DateTimeInterface $time): Builder
    {
        $driver = $query->getModel()->getConnection()->getDriverName();
        $availableAt = match ($driver) {
            'mysql', 'mariadb' => 'UNIX_TIMESTAMP(dateCreated) + COALESCE(delay, 0)',
            'pgsql' => 'EXTRACT(EPOCH FROM "dateCreated") + COALESCE(delay, 0)',
            'sqlite' => 'CAST(strftime(\'%s\', dateCreated) AS INTEGER) + COALESCE(delay, 0)',
            default => throw new RuntimeException("Unsupported database driver: $driver"),
        };

        return $query->whereRaw("$availableAt <= ?", [$time->getTimestamp()]);
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        $cutoff = now()->subDays(7);

        return static::query()
            ->where('dateCreated', '<', Query::prepareDateForDb($cutoff))
            // Cancellation markers stop delayed jobs that a worker hasn't picked up yet,
            // so keep them until a week after the job's delay ran out.
            ->where(fn (Builder $query) => $query
                ->where('status', '!=', JobStatus::Cancelled->value)
                ->orWhere(fn (Builder $query) => $query->availableBy($cutoff)));
    }
}
