<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use JsonSerializable;

/**
 * One row of the import plans index.
 *
 * @since 6.0.0
 */
readonly class ImportPlanIndexData implements JsonSerializable
{
    /**
     * @param  list<array{uid: string|null, label: string, enabled: bool, source: string|null, settings: list<array{key: string, label: string, value: string}>, mappedFields: int, matchMode: string|null}>  $steps
     * @param  array<string, mixed>|null  $run  The plan's latest run, without its failures.
     */
    public function __construct(
        public ?string $uid,
        public string $name,
        public string $handle,
        public ?string $description,
        public int $stepCount,
        public array $steps,
        public bool $editable,
        public bool $running,
        public ?int $progress,
        public ?array $run,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'uid' => $this->uid,
            'name' => $this->name,
            'handle' => $this->handle,
            'description' => $this->description,
            'stepCount' => $this->stepCount,
            'steps' => $this->steps,
            'editable' => $this->editable,
            'running' => $this->running,
            'progress' => $this->progress,
            'run' => $this->run,
        ];
    }
}
