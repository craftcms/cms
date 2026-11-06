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
     * @param  list<string>  $stepLabels
     */
    public function __construct(
        public ?string $uid,
        public string $name,
        public string $handle,
        public ?string $description,
        public int $stepCount,
        public array $stepLabels,
        public bool $editable,
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
            'stepLabels' => $this->stepLabels,
            'editable' => $this->editable,
        ];
    }
}
