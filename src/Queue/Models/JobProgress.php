<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Query;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Override;

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
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            // Cancelled jobs may still be delayed or reserved on an external queue.
            ->where('status', '!=', JobStatus::Cancelled->value)
            ->where('dateCreated', '<', Query::prepareDateForDb(now()->subDays(7)));
    }
}
