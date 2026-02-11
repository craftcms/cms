<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

use function CraftCms\Cms\t;

final class JobProgress extends BaseModel
{
    use HasUid;

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $table = Table::JOBPROGRESS;

    protected $casts = [
        'status' => JobStatus::class,
    ];

    protected function getLabelAttribute(): ?string
    {
        return $this->progressLabel ? t($this->progressLabel) : null;
    }
}
