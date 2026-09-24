<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Data;

readonly class WorkflowTimelineItemData
{
    /**
     * @param  array{label: string, url: string|null, deleted: bool}  $actor
     * @param  array{label: string, url: string|null, deleted: bool}|null  $impersonator
     * @param  array{date: string, dateLabel: string, time: string, full: string}  $formattedOccurredAt
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $icon,
        public string $description,
        public array $actor,
        public ?array $impersonator,
        public ?string $decision,
        public ?string $noteHtml,
        public string $occurredAt,
        public array $formattedOccurredAt,
        private int $runId,
        private ?int $stageNumber,
    ) {}

    public function belongsToRun(int $runId): bool
    {
        return $this->runId === $runId;
    }

    public function belongsToStage(int $stageNumber): bool
    {
        return $this->stageNumber === $stageNumber;
    }
}
