<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/** @property Collection<int, WorkflowStageData> $stages */
class Workflow extends BaseModel
{
    use HasUid;

    #[\Override]
    protected $table = Table::WORKFLOWS;

    #[\Override]
    protected $attributes = [
        'stages' => '[]',
    ];

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'workflowId');
    }

    /** @return array{name: string, stages: list<array{uid: string, name: string, type: string, settings: array<string, mixed>}>} */
    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'stages' => $this->stages->map(fn (WorkflowStageData $stage): array => $stage->getConfig())->all(),
        ];
    }

    /** @return array{stages: list<array{uid: string, type: string, settings: array<string, mixed>}>} */
    public function getExecutionConfig(): array
    {
        return [
            'stages' => $this->stages->map(fn (WorkflowStageData $stage): array => [
                'uid' => $stage->uid,
                'type' => $stage->type,
                'settings' => $stage->settings,
            ])->all(),
        ];
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'stages' => AsCollection::of([WorkflowStageData::class, 'fromArray']),
        ];
    }
}
