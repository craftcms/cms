<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Data;

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;

class ReadOnlyProjectConfigData
{
    /** @param array<string|int, mixed> $data */
    public function __construct(
        public array $data,
        public ProjectConfig $projectConfig,
    ) {}

    /**
     * Get a stored data value for a path.
     *
     * @param  string|string[]  $path
     */
    public function get(array|string $path): mixed
    {
        return ProjectConfigHelper::traverseDataArray($this->data, $path);
    }

    /**
     * Export the data to an array.
     */
    /** @return array<string|int, mixed> */
    public function export(): array
    {
        return $this->data;
    }
}
