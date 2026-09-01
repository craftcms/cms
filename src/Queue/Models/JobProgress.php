<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Override;

use function CraftCms\Cms\t;

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

        $attributes['dateCreated'] = $this->dateCreated ? $formatter->asDateTime($this->dateCreated, withTimeZone: true) : null;
        $attributes['dateUpdated'] = $this->dateUpdated ? $formatter->asDateTime($this->dateUpdated, withTimeZone: true) : null;

        return $attributes;
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->where('dateCreated', '<', now()->subDays(7));
    }
}
