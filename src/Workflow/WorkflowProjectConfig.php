<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow;

use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use CraftCms\Cms\Workflow\Models\Workflow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowProjectConfig
{
    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly Workflows $workflows,
    ) {}

    /** @return Collection<int, Workflow> */
    public function getAllWorkflows(): Collection
    {
        return Workflow::query()->get();
    }

    /** @param list<WorkflowStageData> $stages */
    public function save(Workflow $workflow, array $stages): void
    {
        $workflow->uid ??= Str::uuid7()->toString();

        $this->projectConfig->set(
            ProjectConfig::PATH_WORKFLOWS.'.'.$workflow->uid,
            [
                'name' => trim($workflow->name),
                'stages' => array_map(fn (WorkflowStageData $stage): array => $stage->getConfig(), $stages),
            ],
            "Save workflow “{$workflow->name}”",
        );

        $workflow->id = Workflow::findByUid($workflow->uid)?->id;
    }

    public function delete(Workflow $workflow): void
    {
        $this->projectConfig->remove(
            ProjectConfig::PATH_WORKFLOWS.'.'.$workflow->uid,
            "Delete workflow “{$workflow->name}”",
        );
    }

    public function handleChanged(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        /** @var array{name: string, stages: list<array{uid: string, name: string, type: string, settings?: array<string, mixed>}>} $data */
        $data = $event->newValue;

        DB::transaction(function () use ($uid, $data): void {
            $workflow = Workflow::findByUid($uid);
            $oldExecutableConfig = $workflow === null ? [] : $this->executableConfig($workflow);
            $workflow ??= new Workflow(['uid' => $uid]);
            $workflow->fill([
                'name' => $data['name'],
                'stages' => array_map(
                    fn (array $stage): array => WorkflowStageData::fromArray($stage)->getConfig(),
                    $data['stages'],
                ),
            ])->save();

            if ($oldExecutableConfig !== [] && $oldExecutableConfig !== $this->executableConfig($workflow)) {
                $this->workflows->invalidateRuns($workflow, 'The workflow configuration changed.');
            }
        });
    }

    public function handleDeleted(ConfigEvent $event): void
    {
        $workflow = Workflow::findByUid($event->tokenMatches[0]);
        if ($workflow === null) {
            return;
        }

        $this->workflows->invalidateRuns($workflow, 'The workflow was deleted.');
        $workflow->delete();
    }

    /** @return list<array{uid: string, type: string, settings: array<string, mixed>}> */
    private function executableConfig(Workflow $workflow): array
    {
        return $workflow->stages->map(fn (WorkflowStageData $stage): array => [
            'uid' => $stage->uid,
            'type' => $stage->type,
            'settings' => $stage->settings,
        ])->all();
    }
}
