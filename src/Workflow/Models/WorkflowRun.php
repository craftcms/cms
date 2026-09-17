<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Models;

use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property ActivityEvent $activityRootEvent
 * @property int $currentStage
 * @property string|null $currentStageResult
 * @property WorkflowStatus $status
 * @property int $activityRootEventId
 * @property array<string, array<string, mixed>>|null $payload
 */
class WorkflowRun extends BaseModel
{
    #[\Override]
    protected $table = Table::WORKFLOWRUNS;

    /** @return BelongsTo<ActivityEvent, $this> */
    public function activityRootEvent(): BelongsTo
    {
        return $this->belongsTo(ActivityEvent::class, 'activityRootEventId');
    }

    /** @return BelongsTo<Workflow, $this> */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflowId');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorId');
    }

    public function isPending(): bool
    {
        return $this->status === WorkflowStatus::Pending;
    }

    /** @return Collection<int, WorkflowStageData> */
    public function stages(): Collection
    {
        return collect((array) $this->activityRootEvent->data['workflow']['stages'])
            ->filter(fn (mixed $snapshot): bool => is_array($snapshot))
            ->map(fn (array $snapshot): WorkflowStageData => WorkflowStageData::fromArray($snapshot))
            ->values();
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'activityRootEventId' => 'integer',
            'status' => WorkflowStatus::class,
            'currentStage' => 'integer',
            'payload' => 'array',
        ];
    }
}
