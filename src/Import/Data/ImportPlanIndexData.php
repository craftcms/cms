<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * One row of the import plans index.
 *
 * @since 6.0.0
 */
readonly class ImportPlanIndexData implements JsonSerializable
{
    /**
     * @param  list<array{label: string, source: string|null, enabled: bool, url: string|null}>  $steps
     */
    public function __construct(
        public ?string $uid,
        public string $name,
        public string $handle,
        public ?string $description,
        public int $stepCount,
        /** Each step, as the index lists it under the plan, with its own page if the plan is editable. */
        #[LiteralTypeScriptType('{label: string, source: string | null, enabled: boolean, url: string | null}[]')]
        public array $steps,
        public bool $editable,
        /** Whether the plan is running right now. */
        public bool $running = false,
        /** Where its run can be followed in the queue manager, while it’s running. */
        public ?string $runningJobUrl = null,
        /** Whether its latest finished run had failures. */
        public bool $lastRunFailed = false,
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
            'runningJobUrl' => $this->runningJobUrl,
            'lastRunFailed' => $this->lastRunFailed,
        ];
    }
}
